<?php
/**
 * File containing the ezcMailTransportSmtp class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * This class implements the Simple Mail Transfer Protocol (SMTP)
 * with authentication support.
 *
 * The ezcMailTransportSmtp class has the following properties:
 * - string <B>serverHost</B>,  the SMTP server host to connect to.
 * - int <B>serverPort</B>,  the port of the SMTP server. Defaults to 25.
 * - string <B>username</B>,  the username used for authentication. The default
 *                   is blank which means no authentication.
 * - string <B>password</B>,  the password used for authentication.
 * - int <B>timeout</B>, the timeout value of the connection in seconds.
 *                  The default is five seconds.
 * - string <B>senderHost</B>, the hostname of the computer that sends the mail.
 *                     the default is 'localhost'.
 *
 * See for further information the RFC's:
 * - {@link http://www.faqs.org/rfcs/rfc821.html}
 * - {@link http://www.faqs.org/rfcs/rfc2554.html}
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailSmtpTransport implements ezcMailTransport
{
    /**
     * The line-break characters to use.
     */
    const CRLF = "\r\n";

    /**
     * We are not connected to a server.
     * @access private
     */
    const STATUS_NOT_CONNECTED = 1;

    /**
     * We are connected to the server, but not authenticated.
     * @access private
     */
    const STATUS_CONNECTED = 2;

    /**
     * We are connected to the server and authenticated.
     * @access private
     */
    const STATUS_AUTHENTICATED = 3;

    /**
     * The connection to the SMTP server.
     *
     * @var resource
     */
    private $connection;

    /**
     * Holds the connection status.
     *
     * $var int {@link STATUS_NOT_CONNECTED}, {@link STATUS_CONNECTED} or {@link STATUS_AUTHENTICATED}.
     */
    private $status;

    /**
     * True if authentication should be performed; otherwise false.
     *
     * This variable is set to true if a username is provided for login.
     *
     * @var bool
     */
    private $doAuthenticate;

    /**
     * Holds if the connection should be kept open after sending a mail.
     *
     * @var bool
     */
    private $keepConnection = false;

    /**
     * Holds the properties of this class.
     *
     * @var array(string=>mixed)
     */
    private $properties = array();

    /**
     * Constructs a new ezcMailTransportSmtp.
     *
     * The constructor expects, at least, the hostname $host of the SMTP server.
     *
     * The username $user will be used for authentication if provided.
     * If it is left blank no authentication will be performed.
     *
     * The password $password will be used for authentication
     * if provided. Use this parameter always in combination with the $user
     * parameter.
     *
     * The portnumber $port, default the SMTP standard port, to which will
     * be connected.
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param int $port
     * @return void
     */
    public function __construct( $host, $user = '', $password = '', $port = 25  )
    {
        $this->serverHost = $host;
        $this->user = $user;
        $this->password = $password;
        $this->serverPort = $port;
        $this->doAuthenticate = $user != '' ? true : false;

        $this->status = self::STATUS_NOT_CONNECTED;
        $this->timeout = 5;
        $this->senderHost = 'localhost';
    }

    /**
     * Destructs this object.
     *
     * Closes the connection if it is still open.
     * @return void
     */
    public function __destruct()
    {
        if ( $this->status != self::STATUS_NOT_CONNECTED )
        {
            $this->sendData( 'QUIT' );
            fclose( $this->connection );
        }
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'user':
                $this->properties['user'] = $value;
                break;
            case 'password':
                $this->properties['password'] = $value;
                break;
            case 'senderHost':
                $this->properties['senderHost'] = $value;
                break;
            case 'serverHost':
                $this->properties['serverHost'] = $value;
                break;
            case 'serverPort':
                $this->properties['serverPort'] = $value;
                break;
            case 'timeout':
                $this->properties['timeout'] = $value;
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }

    /**
     * Returns the property $name.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @return mixed
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'user':
                return $this->properties['user'];
                break;
            case 'password':
                return $this->properties['password'];
                break;
            case 'senderHost':
                return $this->properties['senderHost'];
                break;
            case 'serverHost':
                return $this->properties['serverHost'];
                break;
            case 'serverPort':
                return $this->properties['serverPort'];
                break;
            case 'timeout':
                return $this->properties['timeout'];
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }

    /**
     * Sets if the connection should be kept open after sending an email.
     *
     * This method should be called prior to the first call to send().
     *
     * Keeping the connection open is useful if you are sending a lot of mail.
     * It removes the overhead of opening the connection after each mail is sent.
     *
     * Use disconnect() to close the connection if you have requested to keep it open.
     *
     * @return void
     */
    public function keepConnection()
    {
        $this->keepConnection = true;
    }

    /**
     * Sends the ezcMail $mail using the SMTP protocol.
     *
     * If you want to send several email use keepConnection() to leave the connection
     * to the server open between each mail.
     *
     * @throws ezcMailTransportException if the mail could not be sent.
     * @param ezcMail $mail
     * @return void
     */
    public function send( ezcMail $mail )
    {
        // sanity check the e-mail
        // need at least one recepient
        if ( count( $mail->to ) < 1 )
        {
            throw new ezcMailTransportException( "Can not send e-mail with no 'to' recipients." );
        }

        try
        {
            // open connection unless we are connected already.
            if ( $this->status != self::STATUS_AUTHENTICATED )
            {
                $this->connect();
            }

            $this->cmdMail( $mail->from->email );
            // each recepient must be listed here.
            // this controls where the mail is actually sent as SMTP does not
            // read the headers itself
            foreach ( $mail->to as $address )
            {
                $this->cmdRcpt( $address->email );
            }
            foreach ( $mail->cc as $address )
            {
                $this->cmdRcpt( $address->email );
            }
            foreach ( $mail->bcc as $address )
            {
                $this->cmdRcpt( $address->email );
            }
            // done with the from and recipients, lets send the mail itself
            $this->cmdData();

            // A '.' on a line ends the mail. Make sure this does not happen in
            // the data we want to send.  also called transparancy in the RFC,
            // section 4.5.2
            $data = $mail->generate();
            $data = str_replace( self::CRLF . '.', self::CRLF . '..', $data );
            if ( $data[0] == '.' )
            {
                $data = '.' . $data;
            }

            $this->sendData( $data );
            $this->sendData( '.' );

            if ( $this->getReplyCode( $error ) !== '250' )
            {
                throw new ezcMailTransportSmtpException( "Error: {$error}" );
            }
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            throw new ezcMailTransportException( $e->getMessage() );
            // TODO: reset connection here.pin
        }

        // close connection unless we should keep it
        if( $this->keepConnection === false )
        {
            try
            {
                $this->disconnect();
            }
            catch ( Exception $e )
            {
                // Eat! We don't care anyway since we are aborting the connection
            }
        }
    }

    /**
     * Creates a connection to the SMTP server and initiates the login
     * procedure.
     *
     * @throws ezcMailTransportSmtpException if no connection could be made
     *         or if the login failed.
     * @return void
     */
    private function connect( )
    {
        // FIXME: The @ should be removed when PHP doesn't throw warnings for connect problems
        $this->connection = @stream_socket_client( "tcp://{$this->serverHost}:{$this->serverPort}",
                                                   $errno, $errstr, $this->timeout );

        if ( is_resource( $this->connection ) )
        {
            stream_set_timeout( $this->connection, $this->timeout );
            $this->status = self::STATUS_CONNECTED;
            $greeting = $this->getData();
            $this->login();
        }
        else
        {
            throw new ezcMailTransportSmtpException( "Failed to connect to the smtp server: {$this->serverHost}:{$this->serverPort}." );
        }
    }

    /**
     * Performs the initial handshake with the SMTP server and
     * authenticates the user, if login data is provided to the
     * constructor.
     *
     * @throws ezcMailTransportSmtpException if the HELO/EHLO command or authentication fails.
     * @return void
     */
    private function login()
    {
        if ( $this->doAuthenticate )
        {
            $this->sendData( 'EHLO ' . $this->senderHost );
        }
        else
        {
            $this->sendData( 'HELO ' . $this->senderHost );
        }
        if ( $this->getReplyCode( $error ) !== '250' )
        {
                throw new ezcMailTransportSmtpException( "HELO/EHLO failed with error: $error." );
        }

        // do authentication
        if ( $this->doAuthenticate )
        {
            $this->sendData( 'AUTH LOGIN' );
            if ( $this->getReplyCode( $error ) !== '334' )
            {
                throw new ezcMailTransportSmtpException( 'SMTP server does not accept AUTH LOGIN.' );
            }

            $this->sendData( base64_encode( $this->user ) );
            if ( $this->getReplyCode( $error ) !== '334' )
            {
                throw new ezcMailTransportSmtpException( "SMTP server does not accept login: {$this->user}." );
            }

            $this->sendData( base64_encode( $this->password ) );
            if ( $this->getReplyCode( $error ) !== '235' )
            {
                throw new ezcMailTransportSmtpException( 'SMTP server does not accept the password.' );
            }
        }
        $this->status = self::STATUS_AUTHENTICATED;
    }

    /**
     * Sends the QUIT command to the server and breaks the connection.
     *
     * @throws ezcMailTransportException if the QUIT command failed.
     * @return void
     */
    public function disconnect()
    {
        if ( $this->status != self::STATUS_NOT_CONNECTED )
        {
            $this->sendData( 'QUIT' );
            $replyCode = $this->getReplyCode( $error ) !== '221';
            fclose( $this->connection );
            $this->status = self::STATUS_NOT_CONNECTED;
            if ( $replyCode )
            {
                throw new ezcMailTransportSmtpException( "QUIT failed with error: $error." );
            }
        }
    }

    /**
     * Returns the $email enclosed within '< >'.
     *
     * If $email is already enclosed within '< >' it is returned unmodified.
     *
     * @param string $email
     * $return string
     */
    private function composeSmtpMailAddress( $email )
    {
        if ( !preg_match( "/<.+>/", $email ) )
        {
            $email = "<{$email}>";
        }
        return $email;
    }

    /**
     * Sends the MAIL FROM command, with the sender's mail address $from, to the server.
     *
     * This method must be called once to tell the server the sender address.
     *
     * The senders mail address $from may be enclosed in angle brackets.
     *
     * @throws ezcMailTransportException if there is no valid connection or if the MAIL FROM command failed.
     * @param string $from
     * @return void
     */
    private function cmdMail( $from )
    {
        if ( self::STATUS_AUTHENTICATED )
        {
            $this->sendData( 'MAIL FROM:' . $this->composeSmtpMailAddress( $from ) . '' );
            if ( $this->getReplyCode( $error ) !== '250' )
            {
                throw new ezcMailTransportSmtpException( "MAIL FROM failed with error: $error." );
            }
        }
    }

    /**
     * Sends the 'RCTP TO' to the server with the address $email.
     *
     * This method must be called once for each recipient of the mail
     * including cc and bcc recipients. The RCPT TO commands control
     * where the mail is actually sent. It does not affect the headers
     * of the email.
     *
     * The recipient mail address $email may be enclosed in angle brackets.
     *
     * @throws ezcMailTransportException if there is no valid connection or if the RCPT TO command failed.
     * @param string $to
     * @return void
     */
    protected function cmdRcpt( $email )
    {
        if ( self::STATUS_AUTHENTICATED )
        {
            $this->sendData( 'RCPT TO:' . $this->composeSmtpMailAddress( $email ) );
            if ( $this->getReplyCode( $error ) !== '250' )
            {
                throw new ezcMailTransportSmtpException( "RCPT TO failed with error: $error." );
            }
        }
    }

    /**
     * Send the DATA command to the SMTP server.
     *
     * @throws ezcMailTransportException if there is no valid connection or if the DATA command failed.
     * @return void
     */
    private function cmdData()
    {
        if ( self::STATUS_AUTHENTICATED )
        {
            $this->sendData( 'DATA' );
            if ( $this->getReplyCode( $error ) !== '354' )
            {
                throw new ezcMailTransportSmtpException( "DATA failed with error: $error." );
            }
        }
    }

    /**
     * Send $data to the SMTP server through the connection.
     *
     * This method appends one line-break at the end of $data.
     *
     * @throws ezcMailTransportSmtpException if there is no valid connection.
     * @param string $data
     * @return void
     */
    private function sendData( $data )
    {
        if ( is_resource( $this->connection ) )
        {
            if ( fwrite( $this->connection, $data . self::CRLF,
                        strlen( $data ) + strlen( self::CRLF  ) ) === false )
            {
                throw new ezcMailTransportSmtpException( 'Could not write to SMTP stream. It was probably terminated by the host.' );
            }
        }
    }

    /**
     * Returns data received from the connection stream.
     *
     * @throws ezcMailTransportSmtpConnection if there is no valid connection.
     * @return string
     */
    private function getData()
    {
        $data = '';
        $line   = '';
        $loops  = 0;

        if ( is_resource( $this->connection ) )
        {
            while ( ( strpos( $data, self::CRLF ) === false || (string) substr( $line, 3, 1 ) !== ' ' ) && $loops < 100 )
            {
                $line = fgets( $this->connection, 512 );
                $data .= $line;
                $loops++;
            }
            return $data;
        }
        throw new ezcMailTransportSmtpException( 'Could not read from SMTP stream. It was probably terminated by the host.' );
    }

    /**
     * Returns the reply code of the last message from the server.
     *
     * $line contains the complete data retrieved from the stream. This can be used to retrieve
     * the error message in case of an error.
     *
     * @throws ezcMailTransportSmtpException if it could not fetch data from the stream.
     * @param string &$line
     * @return string
     */
    private function getReplyCode( &$line )
    {
        return substr( trim( $line = $this->getData() ), 0, 3 );
    }
}


/**
 * This class is deprecated. Use ezcMailSmtpTransport instead.
 * @package Mail
 */
class ezcMailTransportSmtp extends ezcMailSmtpTransport
{
}
?>
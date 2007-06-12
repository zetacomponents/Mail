<?php
/**
 * File containing the ezcMailSmtpTransport class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * This class implements the Simple Mail Transfer Protocol (SMTP)
 * with authentication support.
 *
 * See for further information the RFCs:
 * - {@link http://www.faqs.org/rfcs/rfc821.html}
 * - {@link http://www.faqs.org/rfcs/rfc2554.html}
 *
 * @property string $serverHost
 *           The SMTP server host to connect to.
 * @property int $serverPort
 *           The port of the SMTP server. Defaults to 25.
 * @property string $username
 *           The username used for authentication. The default is blank which
 *           means no authentication.
 * @property string $password
 *           The password used for authentication.
 * @property int $timeout
 *           The timeout value of the connection in seconds. The default is
 *           5 seconds. When setting/getting this option, the timeout option
 *           from $this->options will be set instead {@link ezcMailTransportOptions}.
 * @property string $senderHost
 *           The hostname of the computer that sends the mail. The default is
 *           'localhost'.
 * @property ezcMailSmtpTransportOptions $options
 *           Holds the options you can set to the SMTP transport.
 *
 * @package Mail
 * @version //autogen//
 * @mainclass
 */
class ezcMailSmtpTransport implements ezcMailTransport
{
    /**
     * Plain connection.
     */
    const CONNECTION_PLAIN = 'tcp';

    /**
     * SSL connection.
     */
    const CONNECTION_SSL = 'ssl';

    /**
     * SSLv2 connection.
     */
    const CONNECTION_SSLV2 = 'sslv2';

    /**
     * SSLv3 connection.
     */
    const CONNECTION_SSLV3 = 'sslv3';

    /**
     * TLS connection.
     */
    const CONNECTION_TLS = 'tls';

    /**
     * The line-break characters to use.
     *
     * @access private
     */
    const CRLF = "\r\n";

    /**
     * We are not connected to a server.
     *
     * @access private
     */
    const STATUS_NOT_CONNECTED = 1;

    /**
     * We are connected to the server, but not authenticated.
     *
     * @access private
     */
    const STATUS_CONNECTED = 2;

    /**
     * We are connected to the server and authenticated.
     *
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
     * $var int {@link STATUS_NOT_CONNECTED},
     *          {@link STATUS_CONNECTED} or
     *          {@link STATUS_AUTHENTICATED}.
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
     * Holds the options of this class.
     *
     * @var ezcMailSmtpTransportOptions
     */
    private $options;

    /**
     * Constructs a new ezcMailSmtpTransport.
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
     * The value $port specifies on which port to connect to $host. By default
     * it is 25 for plain connections and 465 for TLS/SSL/SSLv2/SSLv3.
     *
     * Note: The ssl option from ezcMailTransportOptions doesn't apply to SMTP.
     * If you want to connect to SMTP using TLS/SSL/SSLv2/SSLv3 use the connectionType
     * option in ezcMailSmtpTransportOptions.
     *
     * For options you can specify for SMTP see: {@link ezcMailSmtpTransportOptions}
     *
     * @throws ezcBasePropertyNotFoundException
     *         if $options contains a property not defined
     * @throws ezcBaseValueException
     *         if $options contains a property with a value not allowed
     * @param string $host
     * @param string $user
     * @param string $password
     * @param int $port
     * @param array(string=>mixed) $options
     */
    public function __construct( $host, $user = '', $password = '', $port = null, array $options = array() )
    {
        $this->options = new ezcMailSmtpTransportOptions( $options );
        $this->serverHost = $host;
        if ( $port === null )
        {
            $port = ( $this->options->connectionType === self::CONNECTION_PLAIN ) ? 25 : 465;
        }
        $this->serverPort = $port;
        $this->user = $user;
        $this->password = $password;
        $this->doAuthenticate = $user != '' ? true : false;

        $this->status = self::STATUS_NOT_CONNECTED;
        $this->senderHost = 'localhost';
    }

    /**
     * Destructs this object.
     *
     * Closes the connection if it is still open.
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
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name does not exist
     * @throws ezcBaseValueException
     *         if $value is not accepted for the property $name
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'user':
            case 'password':
            case 'senderHost':
            case 'serverHost':
            case 'serverPort':
                $this->properties[$name] = $value;
                break;

            case 'timeout':
                // the timeout option from $this->options is used instead of
                // the timeout option of this class
                $this->options->timeout = $value;
                break;

            case 'options':
                if ( !( $value instanceof ezcMailSmtpTransportOptions ) )
                {
                    throw new ezcBaseValueException( 'options', $value, 'instanceof ezcMailSmtpTransportOptions' );
                }
                $this->options = $value;
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }

    /**
     * Returns the value of the property $name.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name does not exist
     * @param string $name
     * @return mixed
     * @ignore
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'user':
            case 'password':
            case 'senderHost':
            case 'serverHost':
            case 'serverPort':
                return $this->properties[$name];

            case 'timeout':
                return $this->options->timeout;

            case 'options':
                return $this->options;

            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }

    /**
     * Returns true if the property $name is set, otherwise false.
     *
     * @param string $name
     * @return bool
     * @ignore
     */
    public function __isset( $name )
    {
        switch ( $name )
        {
            case 'user':
            case 'password':
            case 'senderHost':
            case 'serverHost':
            case 'serverPort':
                return isset( $this->properties[$name] );

            case 'timeout':
            case 'options':
                return true;

            default:
                return false;
        }
    }

    /**
     * Sets if the connection should be kept open after sending an email.
     *
     * This method should be called prior to the first call to send().
     *
     * Keeping the connection open is useful if you are sending a lot of mail.
     * It removes the overhead of opening the connection after each mail is
     * sent.
     *
     * Use disconnect() to close the connection if you have requested to keep
     * it open.
     */
    public function keepConnection()
    {
        $this->keepConnection = true;
    }

    /**
     * Sends the ezcMail $mail using the SMTP protocol.
     *
     * If you want to send several emails use keepConnection() to leave the
     * connection to the server open between each mail.
     *
     * @throws ezcMailTransportException
     *         if the mail could not be sent
     * @throws ezcBaseFeatureNotFoundException
     *         if trying to use SSL and the openssl extension is not installed
     * @param ezcMail $mail
     */
    public function send( ezcMail $mail )
    {
        // sanity check the e-mail
        // need at least one recepient
        if ( ( count( $mail->to ) + count( $mail->cc ) + count( $mail->bcc ) ) < 1 )
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

            if ( isset( $mail->returnPath ) )
            {
                $this->cmdMail( $mail->returnPath->email );
            }
            else
            {
                $this->cmdMail( $mail->from->email );
            }

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
        if ( $this->keepConnection === false )
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
     * @todo The @ should be removed when PHP doesn't throw warnings for connect problems
     *
     * @throws ezcMailTransportSmtpException
     *         if no connection could be made
     *         or if the login failed
     * @throws ezcBaseExtensionNotFoundException
     *         if trying to use SSL and the openssl extension is not installed
     */
    private function connect()
    {
        $errno = null;
        $errstr = null;
        if ( $this->options->connectionType !== self::CONNECTION_PLAIN &&
             !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            throw new ezcBaseExtensionNotFoundException( 'openssl', null, "PHP not configured --with-openssl." );
        }
        if ( count( $this->options->connectionOptions ) > 0 )
        {
            $context = stream_context_create( $this->options->connectionOptions );
            $this->connection = @stream_socket_client( "{$this->options->connectionType}://{$this->serverHost}:{$this->serverPort}",
                                                       $errno, $errstr, $this->options->timeout, STREAM_CLIENT_CONNECT, $context );
        }
        else
        {
            $this->connection = @stream_socket_client( "{$this->options->connectionType}://{$this->serverHost}:{$this->serverPort}",
                                                       $errno, $errstr, $this->options->timeout );
        }

        if ( is_resource( $this->connection ) )
        {
            stream_set_timeout( $this->connection, $this->options->timeout );
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
     * @throws ezcMailTransportSmtpException
     *         if the HELO/EHLO command or authentication fails
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
     * @throws ezcMailTransportSmtpException
     *         if the QUIT command failed
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
     * Sends the MAIL FROM command, with the sender's mail address $from.
     *
     * This method must be called once to tell the server the sender address.
     *
     * The sender's mail address $from may be enclosed in angle brackets.
     *
     * @throws ezcMailTransportSmtpException
     *         if there is no valid connection
     *         or if the MAIL FROM command failed
     * @param string $from
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
     * @throws ezcMailTransportSmtpException
     *         if there is no valid connection
     *         or if the RCPT TO command failed
     * @param string $email
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
     * @throws ezcMailTransportSmtpException
     *         if there is no valid connection
     *         or if the DATA command failed
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
     * @throws ezcMailTransportSmtpException
     *         if there is no valid connection
     * @param string $data
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
     * @throws ezcMailTransportSmtpException
     *         if there is no valid connection
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
     * @throws ezcMailTransportSmtpException
     *         if it could not fetch data from the stream
     * @param string &$line
     * @return string
     */
    private function getReplyCode( &$line )
    {
        return substr( trim( $line = $this->getData() ), 0, 3 );
    }
}
?>

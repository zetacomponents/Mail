<?php
/**
 * File containing the ezcMailPop3Transport class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * ezcMailPop3Transport implements POP3 for mail retrieval.
 *
 * The implementation supports most of the basic commands specified in
 * http://www.faqs.org/rfcs/rfc1939.html
 *
 * The implementation also supports the following authentication
 * methods:
 * http://www.faqs.org/rfc/rfc1734.txt - auth
 *
 * @todo ignore messages of a certain size?
 * @todo // support for signing?
 *
 * @property ezcMailPop3TransportOptions $options
 *           Holds the options you can set to the POP3 transport.
 *
 * @package Mail
 * @version //autogen//
 * @mainclass
 */
class ezcMailPop3Transport
{
    /**
     * Internal state set when the POP3 transport is not connected to a server.
     *
     * @access private
     */
    const STATE_NOT_CONNECTED = 1;

    /**
     * Internal state set when the POP3 transport is connected to the server
     * but no successful authentication has been performed.
     *
     * @access private
     */
    const STATE_AUTHORIZATION = 2;

    /**
     * Internal state set when the POP3 transport is connected to the server
     * and authenticated.
     *
     * @access private
     */
    const STATE_TRANSACTION = 3;

    /**
     * Internal state set when the QUIT command has been issued to the POP3 server
     * but before the disconnect has taken place.
     *
     * @access private
     */
    const STATE_UPDATE = 4;

    /**
     * Plain text authorization.
     */
    const AUTH_PLAIN_TEXT = 1;

    /**
     * APOP authorization.
     */
    const AUTH_APOP = 2;

    /**
     * Holds the connection state.
     *
     * $var int {@link STATE_NOT_CONNECTED},
     *          {@link STATE_AUTHORIZATION},
     *          {@link STATE_TRANSACTION} or
     *          {@link STATE_UPDATE}.
     */
    private $state = self::STATE_NOT_CONNECTED;

    /**
     * The connection to the POP3 server.
     *
     * @var ezcMailTransportConnection
     */
    private $connection = null;

    /**
     * Holds the initial greeting from the POP3 server when connecting.
     *
     * @var string
     */
    private $greeting = null;

    /**
     * Options for a POP3 transport connection.
     * 
     * @var ezcMailPop3TransportOptions
     */
    private $options;

    /**
     * Creates a new POP3 transport and connects to the $server at $port.
     *
     * You can specify the $port if the POP3 server is not on the default
     * port 995 (for SSL connections) or 110 (for plain connections). Use the
     * $options parameter to specify an SSL connection.
     *
     * @see ezcMailPop3TransportOptions for options you can specify for POP3.
     *
     * @throws ezcMailTransportException
     *         if it was not possible to connect to the server
     * @throws ezcBaseFeatureNotFoundException
     *         if trying to use SSL and the extension openssl is not installed
     * @throws ezcBasePropertyNotFoundException
     *         if $options contains a property not defined
     * @throws ezcBaseValueException
     *         if $options contains a property with a value not allowed
     * @param string $server
     * @param int $port
     * @param array(string=>mixed) $options
     */
    public function __construct( $server, $port = null, array $options = array() )
    {
        $this->options = new ezcMailPop3TransportOptions( $options );
        if ( $port === null )
        {
            $port = ( $this->options->ssl === true ) ? 995 : 110;
        }
        $this->connection = new ezcMailTransportConnection( $server, $port, $this->options );
        $this->greeting = $this->connection->getLine();
        if ( !$this->isPositiveResponse( $this->greeting ) )
        {
            throw new ezcMailTransportException( "The connection to the POP3 server is ok, but a negative response from server was received: '{$this->greeting}'. Try again later." );
        }
        $this->state = self::STATE_AUTHORIZATION;
    }

    /**
     * Destructs the POP3 transport object.
     *
     * If there is an open connection to the POP3 server it is closed.
     */
    public function __destruct()
    {
        if ( $this->state != self::STATE_NOT_CONNECTED )
        {
            $this->connection->sendData( 'QUIT' );
            $this->connection->getLine(); // discard
            $this->connection->close();
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
            case 'options':
                if ( !( $value instanceof ezcMailPop3TransportOptions ) )
                {
                    throw new ezcBaseValueException( 'options', $value, 'instanceof ezcMailPop3TransportOptions' );
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
     * @ignore
     */
    public function __get( $name )
    {
        switch ( $name )
        {
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
            case 'options':
                return true;

            default:
                return false;
        }
    }

    /**
     * Disconnects the transport from the POP3 server.
     */
    public function disconnect()
    {
        if ( $this->state != self::STATE_NOT_CONNECTED )
        {
            $this->connection->sendData( 'QUIT' );
            $this->connection->getLine(); // discard
            $this->state = self::STATE_UPDATE;

            $this->connection->close();
            $this->connection = null;
            $this->state = self::STATE_NOT_CONNECTED;
        }
    }

    /**
     * Authenticates the user to the POP3 server with $user and $password.
     *
     * You can choose the authentication method with the $method parameter.
     * The default is to use plaintext username and password (specified in the
     * ezcMailPop3TransportOptions class).
     *
     * This method should be called directly after the construction of this
     * object.
     *
     * @throws ezcMailTransportException
     *         if there is no connection to the server
     *         or if already authenticated
     *         or if the authentication method is not accepted by the server
     *         or if the provided username/password combination did not work
     * @param string $user
     * @param string $password
     * @param int method
     */
    public function authenticate( $user, $password, $method = null )
    {
        if ( $this->state != self::STATE_AUTHORIZATION )
        {
            throw new ezcMailTransportException( "Tried to authenticate when there was no connection or when already authenticated." );
        }

        if ( is_null( $method ) )
        {
            $method = $this->options->authenticationMethod;
        }

        if ( $method == self::AUTH_PLAIN_TEXT ) // normal plain text login
        {
            // authenticate ourselves
            $this->connection->sendData( "USER {$user}" );
            $response = $this->connection->getLine();
            if ( !$this->isPositiveResponse( $response ) )
            {
                throw new ezcMailTransportException( "The POP3 server did not accept the username: {$response}." );
            }
            $this->connection->sendData( "PASS {$password}" );
            $response = $this->connection->getLine();
            if ( !$this->isPositiveResponse( $response ) )
            {
                throw new ezcMailTransportException( "The POP3 server did not accept the password: {$response}." );
            }
        }
        else if ( $method == self::AUTH_APOP ) // APOP login
        {
            // fetch the timestamp from the greeting
            $timestamp = '';
            preg_match( '/.*(<.*>).*/',
                        $this->greeting,
                        $timestamp );
            // check if there was a greeting. If not, apop is not supported
            if ( count( $timestamp ) < 2 )
            {
                throw new ezcMailTransportException( "The POP3 server did not accept the APOP login: No greeting." );
            }

            $hash = md5( $timestamp[1] . $password );
            $this->connection->sendData( "APOP {$user} {$hash}" );
            $response = $this->connection->getLine();
            if ( !$this->isPositiveResponse( $response ) )
            {
                throw new ezcMailTransportException( "The POP3 server did not accept the APOP login: {$response}." );
            }
        }
        else
        {
            throw new ezcMailTransportException( "Invalid authentication method provided." );
        }
        $this->state = self::STATE_TRANSACTION;
    }

    /**
     * Returns a list of the messages on the server and the size of the
     * messages in bytes.
     *
     * The format of the returned array is array(message_id=>message_size)
     *
     * @throws ezcMailTransportException
     *         if there was no connection to the server
     *         or if the server sent a negative response
     * @return array(int=>int)
     */
    public function listMessages()
    {
        if ( $this->state != self::STATE_TRANSACTION )
        {
            throw new ezcMailTransportException( "Can't call listMessages() on the POP3 transport when not successfully logged in." );
        }

        // send the command
        $this->connection->sendData( "LIST" );
        $response = $this->connection->getLine();
        if ( !$this->isPositiveResponse( $response ) )
        {
            throw new ezcMailTransportException( "The POP3 server sent a negative response to the LIST command: {$response}." );
        }

        // fetch the data from the server and prepare it to be returned.
        $messages = array();
        while ( ( $response = $this->connection->getLine( true ) ) !== "." )
        {
            list( $num, $size ) = split( ' ', $response );
            $messages[$num] = $size;
        }
        return $messages;
    }

    /**
     * Returns the unique identifiers for messages on the POP3 server.
     *
     * You can fetch the unique identifier for a specific message by providing
     * the $msgNum parameter.
     *
     * The unique identifier can be used to recognize mail from servers
     * between requests. In contrast to the message numbers the unique numbers
     * assigned to an email never changes.
     *
     * The format of the returned array is array(message_num=>unique_identifier).
     *
     * Note: POP3 servers are not required to support this command and it may fail.
     *
     * @throws ezcMailTransportException
     *         if there was no connection to the server
     *         or if the server sent a negative response
     * @param int $msgNum
     * @return array(int=>string)
     */
    public function listUniqueIdentifiers( $msgNum = null )
    {
        if ( $this->state != self::STATE_TRANSACTION )
        {
            throw new ezcMailTransportException( "Can't call ListUniqueIdentifiers() on the POP3 transport when not successfully logged in." );
        }

        // send the command
        $result = array();
        if ( $msgNum !== null )
        {
            $this->connection->sendData( "UIDL {$msgNum}" );
            $response = $this->connection->getLine( true );
            if ( $this->isPositiveResponse( $response ) )
            {
                // get the single response line from the server
                list( $dummy, $num, $id ) = explode( ' ', $response );
                $result[(int)$num] = $id;
            }
            else
            {
                throw new ezcMailTransportException( "The POP3 server sent a negative response to the UIDL command: {$response}." );
            }
        }
        else
        {
            $this->connection->sendData( "UIDL" );
            $response = $this->connection->getLine();
            if ( $this->isPositiveResponse( $response ) )
            {
                // fetch each of the result lines and add it to the result
                while ( ( $response = $this->connection->getLine( true ) ) !== "." )
                {
                    list( $num, $id ) = explode( ' ', $response );
                    $result[(int)$num] = $id;
                }
            }
            else
            {
                throw new ezcMailTransportException( "The POP3 server sent a negative response to the UIDL command: {$response}." );
            }
        }
        return $result;
    }

    /**
     * Returns the number of messages on the server and the combined
     * size of the messages through the input variables $numMessages and
     * $sizeMessages.
     *
     * @throws ezcMailTransportException
     *         if there was no connection to the server
     *         or if the server sent a negative response
     * @param int &$numMessages
     * @param int &$sizeMessages
     */
    public function status( &$numMessages, &$sizeMessages )
    {
        if ( $this->state != self::STATE_TRANSACTION )
        {
            throw new ezcMailTransportException( "Can't call status() on the POP3 transport when not successfully logged in." );
        }

        $this->connection->sendData( "STAT" );
        $response = $this->connection->getLine();
        if ( $this->isPositiveResponse( $response ) )
        {
            // get the single response line from the server
            list( $dummy, $numMessages, $sizeMessages ) = explode( ' ', $response );
            $numMessages = (int)$numMessages;
            $sizeMessages = (int)$sizeMessages;
        }
        else
        {
            throw new ezcMailTransportException( "The POP3 server did not respond with a status message: {$response}." );
        }
    }

    /**
     * Deletes the message with the message number $msgNum from the server.
     *
     * The message number must be a valid identifier fetched with e.g
     * listMessages().
     * Any future reference to the message-number associated with the message
     * in a command generates an error.
     *
     * @throws ezcMailTransportException
     *         if there was no connection to the server
     *         or if the server sent a negative response
     */
    public function delete( $msgNum )
    {
        if ( $this->state != self::STATE_TRANSACTION )
        {
            throw new ezcMailTransportException( "Can't call delete() on the POP3 transport when not successfully logged in." );
        }

        $this->connection->sendData( "DELE {$msgNum}" );
        $response = $this->connection->getLine();

        if ( !$this->isPositiveResponse( $response ) )
        {
            throw new ezcMailTransportException( "The POP3 server could not delete the message: {$response}." );
        }
    }

    /**
     * Returns the headers and the $numLines first lines of the body of the mail with
     * the message number $msgNum.
     *
     * If the command failed or if it was not supported by the server an empty string is
     * returned.
     *
     * Note: POP3 servers are not required to support this command and it may fail.
     *
     * @throws ezcMailTransportException
     *         if there was no connection to the server
     *         or if the server sent a negative response
     * @param int $msgNum
     * @param int $numLines
     * @return string
     */
    public function top( $msgNum, $numLines = 0 )
    {
        if ( $this->state != self::STATE_TRANSACTION )
        {
            throw new ezcMailTransportException( "Can't call top() on the POP3 transport when not successfully logged in." );
        }

        // send the command
        $this->connection->sendData( "TOP {$msgNum} {$numLines}" );
        $response = $this->connection->getLine();
        if ( !$this->isPositiveResponse( $response ) )
        {
            throw new ezcMailTransportException( "The POP3 server sent a negative response to the TOP command: {$response}." );
        }

        // fetch the data from the server and prepare it to be returned.
        $message = "";
        while ( ( $response = $this->connection->getLine( true ) ) !== "." )
        {
            $message .= $response . "\n";
        }
        return $message;
    }

    /**
     * Returns a parserset with all the messages on the server.
     *
     * If $deleteFromServer is set to true the mail will be removed from the
     * server after retrieval. If not it will be left.
     *
     * @throws ezcMailTransportException
     *         if there was no connection to the server
     *         or if the server sent a negative response
     * @param bool $deleteFromServer
     * @return ezcMailParserSet
     */
    public function fetchAll( $deleteFromServer = false )
    {
        $messages = $this->listMessages();
        return new ezcMailPop3Set( $this->connection, array_keys( $messages ), $deleteFromServer );
    }
    /**
     * Returns an ezcMailPop3Set containing only the $number -th message in
     * the mailbox.
     *
     * If $deleteFromServer is set to true the mail will be removed from the
     * server after retrieval. If not it will be left.
     * Note: for POP3 the first message is 1 (so for $number = 0 the exception
     * will be thrown).
     * 
     * @throws ezcMailTransportException
     *         if there was no connection to the server
     *         or if the server sent a negative response
     * @throws ezcMailNoSuchMessageException
     *         if the message $number is out of range
     * @param int $number
     * @param bool $deleteFromServer
     * @return ezcMailPop3Set
     */
    public function fetchByMessageNr( $number, $deleteFromServer = false )
    {
        $messages = $this->listMessages();
        if ( !isset( $messages[$number] ) )
        {
            throw new ezcMailNoSuchMessageException( $number );
        }
        return new ezcMailPop3Set( $this->connection, array( $number ), $deleteFromServer );
    }

    /**
     * Returns an ezcMailPop3Set with $count messages starting from $offset.
     *
     * Fetches $count messages starting from the $offset and returns them as a
     * ezcMailPop3Set. If $count is not specified or if it is 0, it fetches
     * all messages starting from the $offset.
     * 
     * @throws ezcMailTransportException
     *         if there was no connection to the server
     *         or if the server sent a negative response
     * @throws ezcMailInvalidLimitException
     *         if $count is negative
     * @throws ezcMailOffsetOutOfRangeException
     *         if $offset is outside of the existing range of messages
     * @param int $offset
     * @param int $count
     * @param bool $deleteFromServer
     * @return ezcMailPop3Set
     */
    public function fetchFromOffset( $offset, $count = 0, $deleteFromServer = false )
    {
        if ( $count < 0 )
        {
            throw new ezcMailInvalidLimitException( $offset, $count );
        }
        $messages = array_keys( $this->listMessages() );
        if ( $count == 0 )
        {
            $range = array_slice( $messages, $offset - 1, count( $messages ), true );
        }
        else
        {
            $range = array_slice( $messages, $offset - 1, $count, true );
        }
        if ( !isset( $range[$offset - 1] ) )
        {
            throw new ezcMailOffsetOutOfRangeException( $offset, $count );
        }
        return new ezcMailPop3Set( $this->connection, $range, $deleteFromServer );
    }

    /**
     * Sends a NOOP command to the server, use it to keep the connection alive.
     *
     * @throws ezcMailTransportException
     *         if there was no connection to the server
     *         or if the server sent a negative response
     */
    public function noop()
    {
        if ( $this->state != self::STATE_TRANSACTION )
        {
            throw new ezcMailTransportException( "Can't call noop() on the POP3 transport when not successfully logged in." );
        }

        // send the command
        $this->connection->sendData( "NOOP" );
        $response = $this->connection->getLine();
        if ( !$this->isPositiveResponse( $response ) )
        {
            throw new ezcMailTransportException( "The POP3 server sent a negative response to the NOOP command: {$response}." );
        }
    }

    /**
     * Returns true if the response from the server is a positive one.
     *
     * @param string $line
     * @return bool
     */
    private function isPositiveResponse( $line )
    {
        if ( strpos( $line, "+OK" ) === 0 )
        {
            return true;
        }
        return false;
    }
}
?>

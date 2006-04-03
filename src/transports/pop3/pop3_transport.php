<?php
/**
 * File containing the ezcMailPop3Transport class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
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
 * @todo // add support for SSL?
 * @todo // support for signing?
 * @package Mail
 * @version //autogen//
 */
class ezcMailPop3Transport
{
    /**
     * Internal state set when the pop3 transport is not connected to a server.
     */
    const STATE_NOT_CONNECTED = 1;

    /**
     * Internal state set when the the pop3 transport is connected to the server
     * but no successfull authentication has been performed.
     */
    const STATE_AUTHORIZATION = 2;

    /**
     * Internal state set when the pop3 transport is connected to the server
     * and authenticated.
     */
    const STATE_TRANSACTION = 3;

    /**
     * Internal state set when the QUIT command has been issued to the pop3 server
     * but before the disconnect has taken place.
     */
    const STATE_UPDATE = 4;

    /**
     * Plain text authorization.
     */
    const AUTH_PLAIN_TEXT = 1;

    /**
     * APOP authorization
     */
    const AUTH_APOP = 2;

    /**
     * Holds the connection state.
     *
     * $var int {@link STATE_NOT_CONNECTED}, {@link STATE_AUTHORIZATION}, {@link STATE_TRANSACTION} or {@link STATE_UPDATE}.
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
     */
    private $greeting = null;

    /**
     * Creates a new pop3 transport and connects to the $server.
     *
     * You can specify the $port if the pop3 server is not on the default port
     * 110.
     *
     * @throws ezcMailTransportException if it was not possible to connect to the server.
     * @param string $server
     * @param int $port
     */
    public function __construct( $server, $port = 110 )
    {
        // open the connection
        $this->connection = new ezcMailTransportConnection( $server, $port );
        $this->greeting = $this->connection->getLine();
        if ( !$this->isPositiveResponse( $this->greeting ) )
        {
            throw new ezcMailTransportException( "The connection to the POP3 server is ok, but a negative response from server was received. Try again later." );
        }
        $this->state = self::STATE_AUTHORIZATION;
    }

    /**
     * Destructs the pop3 transport.
     *
     * If there is an open connection to the pop3 server it is closed.
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
     * Authenticates the user to the POP3 server with the user $user and the password $password.
     *
     * You can choose the authentication method with the $method parameter. The default is to use
     * plaintext username and password.
     *
     * This method should be called directly after the construction of this object.
     *
     * @throws ezcMailTransportException if the provided username/password combination did not work.
     * @param string $user
     * @param string $password
     * @param int method
     */
    public function authenticate( $user, $password, $method = self::AUTH_PLAIN_TEXT )
    {
        if ( $this->state != self::STATE_AUTHORIZATION )
        {
            throw new ezcMailTransportException( "Tried to authenticate when there was no connection or when already authenticated." );
        }
        if ( $method == self::AUTH_PLAIN_TEXT ) // normal plain text login
        {
            // authenticate ourselves
            $this->connection->sendData( "USER {$user}" );
            $response = $this->connection->getLine();
            if ( !$this->isPositiveResponse( $response ) )
            {
                throw new ezcMailTransportException( "The POP3 server did not accept the username." );
            }
            $this->connection->sendData( "PASS {$password}" );
            $response = $this->connection->getLine();
            if ( !$this->isPositiveResponse( $response ) )
            {
                throw new ezcMailTransportException( "The POP3 server did not accept the password." );
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
                throw new ezcMailTransportException( "The POP3 server did not accept the APOP login." );
            }

            $hash = md5( $timestamp[1] . $password );
            $this->connection->sendData( "APOP {$user} {$hash}" );
            $response = $this->connection->getLine();
            if ( !$this->isPositiveResponse( $response ) )
            {
                throw new ezcMailTransportException( "The POP3 server did not accept the APOP login." );
            }
        }
        else
        {
            throw new ezcMailTransportException( "Invalid authentication method provided." );
        }
        $this->state = self::STATE_TRANSACTION;
    }

    /**
     * Returns a list of the messages on the server and the size of the messages
     * in bytes.
     *
     * The format of the returned array is array(message_id => message_size)
     *
     * @throws Exception if there was no connection to the server or if the server
     *         sent a negative response.
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
            throw new ezcMailTransportException( "The POP3 server responded negatative to the LIST command: {$response}" );
        }

        // fetch the data from the server and prepare it to be returned.
        $messages = array();
        while ( ( $response = $this->connection->getLine() ) !== "." )
        {
            list( $num, $size ) = split( ' ', $response );
            $messages[$num] = $size;
        }
        return $messages;
    }

    /**
     * Returns the unique identifiers for each message on the POP3 server or for the
     * specified message $msgNum if provided.
     *
     * The unique identifier can be used to recognize mail from servers between requests.
     * In contrast to the message numbers the unique numbers assigned to an email never
     * changes.
     *
     * The format of the returned array is array(message_num => unique_identifier)
     *
     * Note: POP3 servers are not required to support this command and it may fail.
     *
     * @throws Exception if there was no connection to the server.
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
            $response = $this->connection->getLine();
            if ( $this->isPositiveResponse( $response ) )
            {
                // get the single response line from the server
                list( $dummy, $num, $id ) = explode( ' ', $response );
                $result[(int)$num] = $id;
            }
        }
        else
        {
            $this->connection->sendData( "UIDL" );
            $response = $this->connection->getLine();
            if ( $this->isPositiveResponse( $response ) )
            {
                // fetch each of the result lines and add it to the result
                while ( ( $response = $this->connection->getLine() ) !== "." )
                {
                    list( $num, $id ) = explode( ' ', $response );
                    $result[(int)$num] = $id;
                }
            }
        }
        return $result;
    }

    /**
     * Returns the number of messages on the server and the combined
     * size of the messages through the input variables $numMessages and
     * $sizeMessages.
     *
     * @param int &$numMessages
     * @param int &$sizeMessages
     * @return void
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
            throw new ezcMailTransportException( "The POP3 server did not respond with a status message: {$response}" );
        }
    }

    /**
     * Deletes the message with the message number $msgNum from the server.
     *
     * The message number must be a valid identifier fetched with e.g listMessages().
     * Any future reference to the message-number associated with the message
     * in a command generates an error.
     *
     * @throws ezcMailTransportException if the mail could not be deleted or if there is no connection to the server.
     * @return void
     */
    public function delete( $msgNum )
    {
        if ( $this->state != self::STATE_TRANSACTION )
        {
            throw new ezcMailTransportException( "Can't call listMessages() on the POP3 transport when not successfully logged in." );
        }
        $this->connection->sendData( "DELE {$msgNum}" );
        $response = $this->connection->getLine(); // ignore response

        if ( !$this->isPositiveResponse( $response ) )
        {
            throw new ezcMailTransportException( "The POP3 server could not delete the message: {$response}" );
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
     * @throws Exception if there was no connection to the server or if the server
     *         sent a negative response.
     * @param int $msgNum
     * @param int $numLines
     * @return string
     */
    public function top( $msgNum, $numLines )
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
            throw new ezcMailTransportException( "The POP3 server responded negatative to the TOP command: {$response}" );
        }

        // fetch the data from the server and prepare it to be returned.
        $message = "";
        while ( ( $response = $this->connection->getLine() ) !== "." )
        {
            $message .= $response . "\n";
        }
        return $message;
    }

    /**
     * Returns a parserset with all the messages on the server.
     *
     * If $leaveOnServer is set to true the mail will be left on server after
     * retrieval. If not it will be removed.
     *
     * @throws ezcMailTransportException if the mail could not be retrieved.
     * @param bool $leaveOnServer
     * @return ezcMailParserSet
     */
    public function fetchAll( $leaveOnServer = false )
    {
        $messages = $this->listMessages();
        return new ezcMailPop3Set( $this->connection, array_keys( $messages ), $leaveOnServer );
    }

    /**
     * Disconnects the transport from the pop3 server.
     *
     * @return void
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

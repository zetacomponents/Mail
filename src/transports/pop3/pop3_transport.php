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
     * Connects to the $server and tries to log in with $user and $password.
     *
     * You can specify the $port if the pop3 server is not on the default port
     * 110.
     *
     * @throws ezcMailTransportException if it was not possible to connect to the server or if the provided username/password
     *         combination did not work.
     */
    public function __construct( $server, $user, $password, $port = 110 )
    {
        // open the connection
        $this->connection = new ezcMailTransportConnection( $server, $port );
        $response = $this->connection->getData();
        if( !$this->isPositiveResponse( $response ) )
        {
            throw new ezcMailTransportException( "The connection to the POP3 server is ok, but a negative response from server was received. Try again later." );
        }
        $this->state = self::STATE_AUTHORIZATION;

        // authenticate ourselves
        $this->connection->sendData( "USER {$user}" );
        $response = $this->connection->getData();
        if( !$this->isPositiveResponse( $response ) )
        {
            throw new ezcMailTransportException( "The POP3 server did not accept the username." );
        }
        $this->connection->sendData( "PASS {$password}" );
        $response = $this->connection->getData();
        if( !$this->isPositiveResponse( $response ) )
        {
            throw new ezcMailTransportException( "The POP3 server did not accept the password." );
        }
        $this->state = self::STATE_TRANSACTION;
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
            $this->connection->getData(); // discard
            $this->connection->close();
        }
    }

    public function setAuthenticationMethod( $method  )
    {
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
        if( $this->state != self::STATE_TRANSACTION )
        {
            throw new ezcMailTransportException( "Can't call listMessages() on the POP3 transport when not successfully logged in." );
        }
        $this->connection->sendData( "LIST" );
        $response = $this->connection->getData();
        if( !$this->isPositiveResponse( $response ) )
        {
            throw new ezcMailTransportException( "The POP3 server responded negatative to the LIST command." );
        }
        $messages = array();
        while( ( $response = $this->connection->getData() ) !== "." )
        {
            list( $num, $size ) = split( ' ', $response );
            $messages[$num] = $size;
        }
        return $messages;
    }

    // returns a ezcMailParserSet
    /**
     * Returns a parserset with all the messages on the server.
     *
     * If $leaveOnServer is set to true the mail will be left on server after
     * retrieval. If not it will be removed.
     *
     * @param bool $leaveOnServer
     * @return ezcMailParserSet
     */
    public function fetchAll( $leaveOnServer = false )
    {
        $messages = $this->listMessages();
        return new ezcMailPop3Set( $this->connection, array_keys( $messages ) );
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
            $this->connection->getData(); // discard
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
        if( strpos( $line, "+OK" ) === 0 )
        {
            return true;
        }
        return false;
    }
}

?>

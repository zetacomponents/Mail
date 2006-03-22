<?php
// http://www.faqs.org/rfcs/rfc1939.html
// http://www.faqs.org/rfc/rfc1734.txt - auth

// ignore messages of a certain size?
// add support for SSL?
// support for signing?
class ezcMailPop3Transport
{
    const STATE_NOT_CONNECTED = 1;
    const STATE_AUTHORIZATION = 2;
    const STATE_TRANSACTION = 3;
    const STATE_UPDATE = 4;

    private $state = self::STATE_NOT_CONNECTED;
    private $connection = null;

    // throws if it could not connect
    public function __construct( $server, $user, $password, $port = 110 )
    {
        $this->connection = new ezcMailTransportConnection( $server, $port );
        $response = $this->connection->getData();
        if( !$this->isPositiveResponse( $response ) )
        {
            throw new Exception( "Connection ok, but negative response from server before login." );
        }
        $this->state = self::STATE_AUTHORIZATION;

        // authenticate
        $this->connection->sendData( "USER {$user}" );
        $response = $this->connection->getData();
        if( !$this->isPositiveResponse( $response ) )
        {
            throw new Exception( "Server did not accept the username." );
        }
        $this->connection->sendData( "PASS {$password}" );
        $response = $this->connection->getData();
        if( !$this->isPositiveResponse( $response ) )
        {
            throw new Exception( "Server did not accept the username." );
        }
        $this->state = self::STATE_TRANSACTION;
    }

    // close connection if needed
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

    public function listMessages()
    {
        if( $this->state != self::STATE_TRANSACTION )
        {
            throw new Exception( "Can't call list when not in the transaction state" );
        }
        $this->connection->sendData( "LIST" );
        $response = $this->connection->getData();
        if( !$this->isPositiveResponse( $response ) )
        {
            throw new Exception( "Server did want to send a list." );
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
    public function fetchAll( $leaveOnServer = false )
    {
        $messages = $this->listMessages();
        return new ezcMailPop3Set( $this->connection, array_keys( $messages ) );
    }

    public function disconnect()
    {
        if ( $this->state != self::STATE_NOT_CONNECTED )
        {
            $this->connection->sendData( 'QUIT' );
            $this->connection->getData(); // discard
            $this->state = self::STATE_UPDATE;

            fclose( $this->connection );
            $this->connection = null;
            $this->state = self::STATE_NOT_CONNECTED;
        }
    }

    private function isPositiveResponse( $line )
    {
        if( strpos( $line, "+OK" ) === 0 )
        {
            return true;
        }
        return false;
    }
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////


// private class
class ezcMailPop3Set implements ezcMailParserSet
{
    private $messages;
    // false if beyond the last one
    private $currentMessage = null;
    private $hasMoreMailData = false;
    /**
     * Constructs a new pop3 parser set that will fetch the messages with the
     * id's in $messages.
     *
     * $connection must hold a valid connection to a pop3 server that is ready to retrieve
     * the messages.
     */
    public function __construct( ezcMailTransportConnection $connection, array $messages )
    {
        $this->connection = $connection;
        $this->messages = $messages;
        $this->nextMail();
    }

    /**
     * Returns true if all the data has been fetched from this set.
     */
    public function isFinished()
    {
        return $this->currentMessage === false ? true : false;
    }

    /**
     * Returns one line of data from the current mail in the set
     * excluding the ending linebreak.
     *
     * Null is returned if there is no current mail in the set or
     * the end of the mail is reached,
     *
     * @return string
     */
    public function getNextLine()
    {
        if( $this->hasMoreMailData )
        {
            $data = $this->connection->getData();
            if( $data === "." )
            {
                $this->hasMoreMailData = false;
                return null;
            }
            return $data;
        }
        return null;
    }

    /**
     * Moves the set to the next mail and returns true upon success.
     *
     * False is returned if there are no more mail in the set.
     *
     * @return bool
     */
    public function nextMail()
    {
        if( $this->currentMessage === null )
        {
            $this->currentMessage = reset( $this->messages );
        }
        else
        {
            $this->currentMessage = next( $this->messages );
        }

        if( is_integer( $this->currentMessage ) )
        {
            $this->connection->sendData( "RETR {$this->currentMessage}" );
            $response = $this->connection->getData();
            if( strpos( $response, "+OK" ) === 0 )
            {
                $this->hasMoreMailData = true;
                return true;
            }
        }
        return false;
    }

}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ezcMailTransportConnection
{
    private $connection = null;

    /**
     * The line-break characters to use.
     */
    const CRLF = "\r\n";

    public function __construct( $server, $port, $timeout = 5 )
    {
        $errno = null;
        $errstr = null;

        // FIXME: The @ should be removed when PHP doesn't throw warnings for connect problems
        $this->connection = @stream_socket_client( "tcp://{$server}:{$port}",
                                                   $errno, $errstr, $timeout );

        if ( is_resource( $this->connection ) )
        {
            stream_set_timeout( $this->connection, $timeout );
        }
        else
        {
            throw new ezcMailTransportSmtpException( "Failed to connect to the server: {$server}:{$port}." );
        }
    }

    /**
     * Send $data to the server through the connection.
     *
     * This method appends one line-break at the end of $data.
     *
     * @throws ezcMailTransportSmtpException if there is no valid connection.
     * @param string $data
     * @return void
     */
    public function sendData( $data )
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
    public function getData()
    {
        $data = '';
        $line   = '';
        $loops  = 0;

        if ( is_resource( $this->connection ) )
        {
            while ( ( strpos( $line, self::CRLF ) === false ) && $loops < 100 )
            {
                $line = fgets( $this->connection, 512 );
                $data .= $line;
                $loops++;
            }
            return trim( $data );
        }
        throw new ezcMailTransportSmtpException( 'Could not read from SMTP stream. It was probably terminated by the host.' );
    }

    public function close()
    {
        if( is_resource( $this->connection ) )
        {
            fclose( $this->connection );
            $this->connection = null;
        }
    }
}

?>

<?php
/**
 * File containing the ezcMailTransportConnection class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * ezcMailTransportConnection is an internal class used to connect to
 * a server and have line based communication with.
 *
 * @package Mail
 * @version //autogen//
 * @access private
 */
class ezcMailTransportConnection
{
    /**
     * The connection to the server or null if there is none.
     *
     * @var resource
     */
    private $connection = null;

    /**
     * The line-break characters to send to the server.
     */
    const CRLF = "\r\n";

    /**
     * Constructs a new connection to the $server using the port $port.
     *
     * $timeout controls the amount of seconds before the connection times out.
     *
     * @throws Exception if a connection to the server could not be made.
     */
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
            throw new ezcMailTransportException( "Failed to connect to the server: {$server}:{$port}." );
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
                throw new ezcMailTransportException( 'Could not write to the stream. It was probably terminated by the host.' );
            }
        }
    }

    /**
     * Returns one line of data from the stream.
     *
     * The returned lined will have linebreaks removed if the $trim option is set.
     *
     * @param bool $trim
     * @throws ezcMailTransportSmtpConnection if there is no valid connection.
     * @return string
     */
    public function getLine( $trim = false )
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

            if( $trim == false )
            {
                return $data;
            }
            else
            {
                return rtrim( $data, "\r\n" );
            }
        }
        throw new ezcMailTransportSmtpException( 'Could not read from the stream. It was probably terminated by the host.' );
    }

    /**
     * Closes the connection to the server if it is open.
     *
     * @return void
     */
    public function close()
    {
        if ( is_resource( $this->connection ) )
        {
            fclose( $this->connection );
            $this->connection = null;
        }
    }
}

?>

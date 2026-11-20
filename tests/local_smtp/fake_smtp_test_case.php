<?php
/**
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version //autogentag//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

/**
 * Runs fake_smtp_server.php in a separate process.
 *
 * @package Mail
 * @subpackage Tests
 * @access private
 */
class ezcMailFakeSmtpServer
{
    public $port;

    private $process;

    private $pipes;

    public function __construct( array $config = array() )
    {
        $command = escapeshellarg( PHP_BINARY ) . ' ' .
                   escapeshellarg( __DIR__ . '/fake_smtp_server.php' ) . ' ' .
                   escapeshellarg( base64_encode( json_encode( $config ) ) );
        $this->process = proc_open( $command, array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $this->pipes );
        $line = fgets( $this->pipes[1] );
        if ( !preg_match( '/^PORT (\d+)$/', trim( (string) $line ), $matches ) )
        {
            throw new RuntimeException( 'Fake SMTP server did not start: ' . stream_get_contents( $this->pipes[2] ) );
        }
        $this->port = (int) $matches[1];
    }

    /**
     * Waits for the connection to end and returns the received lines as an
     * array of array( type, line ), where type is 'command', 'data' or
     * 'end-of-data'.
     */
    public function received()
    {
        $log = json_decode( (string) stream_get_contents( $this->pipes[1] ), true );
        $this->stop();
        return is_array( $log ) ? $log : array();
    }

    /**
     * Returns the received commands, without line endings.
     */
    public function commands()
    {
        $commands = array();
        foreach ( $this->received() as $entry )
        {
            if ( $entry[0] === 'command' )
            {
                $commands[] = rtrim( $entry[1], "\r\n" );
            }
        }
        return $commands;
    }

    public function stop()
    {
        if ( is_resource( $this->process ) )
        {
            proc_terminate( $this->process );
            proc_close( $this->process );
        }
    }
}

/**
 * Base class for the SMTP transport tests which run against a local fake
 * server instead of a real mail server.
 *
 * @package Mail
 * @subpackage Tests
 * @access private
 */
abstract class ezcMailTransportSmtpLocalTestCase extends ezcTestCase
{
    protected $server;

    protected function tearDown(): void
    {
        ezcMailTools::setLineBreak( "\r\n" );
        if ( $this->server !== null )
        {
            $this->server->stop();
        }
    }

    protected function startServer( array $config = array() )
    {
        $this->server = new ezcMailFakeSmtpServer( $config );
        return $this->server;
    }

    protected static function createMail()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'from@example.com' );
        $mail->addTo( new ezcMailAddress( 'to@example.com' ) );
        $mail->subject = 'Subject';
        $mail->body = new ezcMailText( 'Body' );
        return $mail;
    }

    protected static function createTransport( ezcMailFakeSmtpServer $server, $user = '', $password = '', array $options = array() )
    {
        return new ezcMailSmtpTransport( '127.0.0.1', $user, $password, $server->port, $options + array( 'timeout' => 5 ) );
    }
}
?>

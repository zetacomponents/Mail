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

/*
 * A minimal SMTP server for the tests in this directory. It is started as a
 * separate process by ezcMailFakeSmtpServer, listens on a random local port,
 * accepts exactly one connection and records everything it receives.
 *
 * Usage: php fake_smtp_server.php <base64 encoded JSON configuration>
 *
 * Configuration keys:
 *  - ehlo:     reply to EHLO (default: "250 localhost")
 *  - helo:     reply to HELO (default: "250 localhost")
 *  - sequence: replies used, in order, for AUTH commands and for every line
 *              which is not a known SMTP command (e.g. AUTH continuation
 *              lines)
 *
 * Like many real servers, the end of the DATA section is detected on a line
 * consisting of a single dot, whatever line ending follows it.
 *
 * The first line written to STDOUT is "PORT <port>". When the connection is
 * closed, a JSON array with the received commands and data lines follows.
 */

$config = json_decode( base64_decode( $argv[1] ), true );
$config += array( 'ehlo' => '250 localhost', 'helo' => '250 localhost', 'sequence' => array() );

$server = stream_socket_server( 'tcp://127.0.0.1:0', $errno, $errstr );
if ( $server === false )
{
    fwrite( STDERR, "Could not start server: {$errstr}\n" );
    exit( 1 );
}
$name = stream_socket_get_name( $server, false );
fwrite( STDOUT, 'PORT ' . substr( $name, strrpos( $name, ':' ) + 1 ) . "\n" );
fflush( STDOUT );

$connection = @stream_socket_accept( $server, 10 );
if ( $connection === false )
{
    exit( 1 );
}
stream_set_timeout( $connection, 10 );

$log = array();
$reply = function ( $text ) use ( $connection )
{
    fwrite( $connection, str_replace( "\n", "\r\n", $text ) . "\r\n" );
};

$reply( '220 localhost ESMTP fake' );
$inData = false;
while ( ( $line = fgets( $connection ) ) !== false )
{
    if ( $inData )
    {
        if ( rtrim( $line, "\r\n" ) === '.' )
        {
            $inData = false;
            $log[] = array( 'end-of-data', $line );
            $reply( '250 OK queued' );
        }
        else
        {
            $log[] = array( 'data', $line );
        }
        continue;
    }

    $log[] = array( 'command', $line );
    $command = strtoupper( (string) strtok( rtrim( $line, "\r\n" ), ' :' ) );
    switch ( $command )
    {
        case 'EHLO':
            $reply( $config['ehlo'] );
            break;

        case 'HELO':
            $reply( $config['helo'] );
            break;

        case 'MAIL':
        case 'RCPT':
        case 'RSET':
        case 'NOOP':
            $reply( '250 OK' );
            break;

        case 'DATA':
            $inData = true;
            $reply( '354 Go ahead' );
            break;

        case 'QUIT':
            $reply( '221 Bye' );
            break 2;

        default:
            $reply( count( $config['sequence'] ) ? array_shift( $config['sequence'] ) : '502 Unexpected' );
            break;
    }
}
fclose( $connection );

fwrite( STDOUT, json_encode( $log ) . "\n" );
?>

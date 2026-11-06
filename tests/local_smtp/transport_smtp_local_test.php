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
 * SMTP transport tests which run against a local fake server instead of a
 * real mail server.
 *
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportSmtpLocalTest extends ezcTestCase
{
    private $server;

    protected function tearDown(): void
    {
        ezcMailTools::setLineBreak( "\r\n" );
        if ( $this->server !== null )
        {
            $this->server->stop();
        }
    }

    private function startServer( array $config = array() )
    {
        $this->server = new ezcMailFakeSmtpServer( $config );
        return $this->server;
    }

    private static function createMail()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'from@example.com' );
        $mail->addTo( new ezcMailAddress( 'to@example.com' ) );
        $mail->subject = 'Subject';
        $mail->body = new ezcMailText( 'Body' );
        return $mail;
    }

    private static function createTransport( ezcMailFakeSmtpServer $server, $user = '', $password = '', array $options = array() )
    {
        return new ezcMailSmtpTransport( '127.0.0.1', $user, $password, $server->port, $options + array( 'timeout' => 5 ) );
    }

    public function testSend()
    {
        $server = $this->startServer();
        self::createTransport( $server )->send( self::createMail() );

        $this->assertSame( array( 'EHLO localhost', 'MAIL FROM:<from@example.com>', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT' ),
                           $server->commands() );
    }

    public function testEhloIsSentWithoutAuthentication()
    {
        $server = $this->startServer();
        self::createTransport( $server )->send( self::createMail() );

        $this->assertSame( 'EHLO localhost', $server->commands()[0] );
    }

    public function testHeloIsSentIfEhloIsNotSupported()
    {
        $server = $this->startServer( array( 'ehlo' => '502 Command not implemented' ) );
        self::createTransport( $server )->send( self::createMail() );

        $commands = $server->commands();
        $this->assertSame( array( 'EHLO localhost', 'HELO localhost', 'MAIL FROM:<from@example.com>' ),
                           array_slice( $commands, 0, 3 ) );
    }

    public function testStartTlsIsRequiredWithoutAuthentication()
    {
        // The server does not offer STARTTLS, so a 'tls' connection without
        // user name must not continue unencrypted.
        $server = $this->startServer();
        $transport = self::createTransport( $server, '', '', array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
        try
        {
            $transport->send( self::createMail() );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertStringContainsString( 'STARTTLS', $e->getMessage() );
        }
        // the destructor closes the connection
        unset( $transport );
        $this->assertNotContains( 'MAIL FROM:<from@example.com>', $server->commands() );
    }

    public static function invalidEnvelopeAddresses()
    {
        return array(
            array( "from@example.com>\r\nRCPT TO:<evil@example.com", "to@example.com" ),
            array( "from@example.com\nRCPT TO:<evil@example.com>", "to@example.com" ),
            array( "from@example.com\rRCPT TO:<evil@example.com>", "to@example.com" ),
            array( "from@example.com", "to@example.com>\r\nRCPT TO:<evil@example.com" ),
            array( "from@example.com", "Name <evil@example.com>" ),
            array( "from@example.com", "to@example.com> NOTIFY=NEVER <x" ),
            array( "from@example.com", "to@example.com\0" ),
            array( "from@example.com", "<to@example.com NOTIFY=NEVER>" ),
            array( "from@example.com", "to@example.com NOTIFY=NEVER" ),
            array( "from@example.com SIZE=1", "to@example.com" ),
            array( "from@example.com", "to@example.com\tNOTIFY=NEVER" ),
            array( "from@example.com", "" ),
            array( "from@example.com", "<>" ),
        );
    }

    /**
     * @dataProvider invalidEnvelopeAddresses
     */
    public function testInvalidEnvelopeAddress( $from, $to )
    {
        $server = $this->startServer();
        $transport = self::createTransport( $server );
        $transport->keepConnection();
        $transport->send( self::createMail() );

        // the addresses are set directly, the header generation is not part
        // of this test
        $mail = self::createMail();
        $mail->to = array();
        $mail->bcc = array( new ezcMailAddress( $to ) );
        $mail->returnPath = new ezcMailAddress( 'from@example.com' );
        $mail->returnPath->email = $from;

        try
        {
            $transport->send( $mail );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
        $transport->disconnect();

        // nothing of the second mail reached the server
        $this->assertSame( array( 'EHLO localhost', 'MAIL FROM:<from@example.com>', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT' ),
                           $server->commands() );
    }

    public function testEnvelopeAddressInAngleBracketsIsAccepted()
    {
        $server = $this->startServer();
        $mail = self::createMail();
        $mail->to = array( new ezcMailAddress( '<to@example.com>' ) );
        self::createTransport( $server )->send( $mail );

        $this->assertContains( 'RCPT TO:<to@example.com>', $server->commands() );
    }

    public static function nullReversePaths()
    {
        return array( array( '' ), array( '<>' ) );
    }

    /**
     * @dataProvider nullReversePaths
     */
    public function testNullReversePathIsAccepted( $from )
    {
        $server = $this->startServer();
        $mail = self::createMail();
        $mail->from = new ezcMailAddress( $from );
        self::createTransport( $server )->send( $mail );

        $commands = $server->commands();
        $this->assertSame( 'MAIL FROM:<>', $commands[1] );
        $this->assertSame( 'RCPT TO:<to@example.com>', $commands[2] );
    }

    public function testDotStuffingWithUnixLineBreaks()
    {
        ezcMailTools::setLineBreak( "\n" );
        $server = $this->startServer();
        $mail = self::createMail();
        $mail->body = new ezcMailText( "first\n.\nRCPT TO:<evil@example.com>\n.leading dot" );
        self::createTransport( $server )->send( $mail );

        $received = $server->received();
        $endOfData = array_filter( $received, function ( $entry ) { return $entry[0] === 'end-of-data'; } );
        $this->assertCount( 1, $endOfData );

        $commands = array();
        $data = '';
        foreach ( $received as $entry )
        {
            if ( $entry[0] === 'command' )
            {
                $commands[] = rtrim( $entry[1], "\r\n" );
            }
            else if ( $entry[0] === 'data' )
            {
                $data .= $entry[1];
            }
        }
        $this->assertSame( array( 'EHLO localhost', 'MAIL FROM:<from@example.com>', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT' ), $commands );
        $this->assertStringContainsString( "\r\nfirst\r\n..\r\nRCPT TO:<evil@example.com>\r\n..leading dot\r\n", $data );
        $this->assertStringNotContainsString( "\n.\n", str_replace( "\r", '', $data ) );
        // every line ends with CRLF
        $this->assertSame( 0, preg_match( "/(?<!\r)\n|\r(?!\n)/", $data ) );
    }

    public function testDotStuffingWithCrLfLineBreaks()
    {
        $server = $this->startServer();
        $mail = self::createMail();
        $mail->body = new ezcMailText( "first\r\n.\r\nsecond" );
        self::createTransport( $server )->send( $mail );

        $data = '';
        foreach ( $server->received() as $entry )
        {
            if ( $entry[0] === 'data' )
            {
                $data .= $entry[1];
            }
        }
        $this->assertStringContainsString( "\r\nfirst\r\n..\r\nsecond\r\n", $data );
    }

    public function testAuthFallsBackToNextMethod()
    {
        $server = $this->startServer( array(
            'ehlo' => "250-localhost\n250 AUTH CRAM-MD5 LOGIN",
            'sequence' => array(
                '334 ' . base64_encode( '<1234@localhost>' ),
                '534 Authentication mechanism is too weak',
                '334 VXNlcm5hbWU6',
                '334 UGFzc3dvcmQ6',
                '235 Authentication succeeded',
            ),
        ) );
        self::createTransport( $server, 'user', 'secret' )->send( self::createMail() );

        $commands = $server->commands();
        $this->assertSame( 'AUTH CRAM-MD5', $commands[1] );
        $this->assertSame( 'AUTH LOGIN', $commands[3] );
        $this->assertSame( base64_encode( 'user' ), $commands[4] );
        $this->assertSame( base64_encode( 'secret' ), $commands[5] );
        $this->assertContains( 'MAIL FROM:<from@example.com>', $commands );
    }

    public static function authMethodRejections()
    {
        return array(
            array( '504 Unrecognized authentication type' ),
            array( '534 Authentication mechanism is too weak' ),
            array( '538 Encryption required for requested authentication mechanism' ),
        );
    }

    /**
     * @dataProvider authMethodRejections
     */
    public function testAuthFallsBackIfMethodIsRejected( $rejection )
    {
        $server = $this->startServer( array(
            'ehlo' => "250-localhost\n250 AUTH CRAM-MD5 PLAIN",
            'sequence' => array(
                $rejection,
                '235 Authentication succeeded',
            ),
        ) );
        self::createTransport( $server, 'user', 'secret' )->send( self::createMail() );

        $commands = $server->commands();
        $this->assertSame( array( 'EHLO localhost', 'AUTH CRAM-MD5', 'AUTH PLAIN ' . base64_encode( "\0user\0secret" ), 'MAIL FROM:<from@example.com>' ),
                           array_slice( $commands, 0, 4 ) );
    }

    public static function authFailuresWithoutFallback()
    {
        return array(
            // invalid credentials: trying them with other methods would only
            // add failed logins
            array( '334 ' . base64_encode( '<1234@localhost>' ), '535 Authentication credentials invalid' ),
            // temporary failure
            array( '334 ' . base64_encode( '<1234@localhost>' ), '454 Temporary authentication failure' ),
            array( '454 Temporary authentication failure', '235 Authentication succeeded' ),
        );
    }

    /**
     * @dataProvider authFailuresWithoutFallback
     */
    public function testAuthDoesNotFallBackOnCredentialOrTemporaryFailure( $first, $second )
    {
        $server = $this->startServer( array(
            'ehlo' => "250-localhost\n250 AUTH CRAM-MD5 LOGIN PLAIN",
            'sequence' => array(
                $first,
                $second,
                '235 Authentication succeeded',
                '235 Authentication succeeded',
            ),
        ) );
        try
        {
            self::createTransport( $server, 'user', 'secret' )->send( self::createMail() );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        $commands = $server->commands();
        $auth = array_filter( $commands, function ( $command ) { return strncmp( $command, 'AUTH ', 5 ) === 0; } );
        $this->assertSame( array( 'AUTH CRAM-MD5' ), array_values( $auth ) );
        $this->assertNotContains( 'MAIL FROM:<from@example.com>', $commands );
    }

    public function testAuthDigestMd5InvalidChallengeIsCancelled()
    {
        $server = $this->startServer( array(
            'ehlo' => "250-localhost\n250 AUTH DIGEST-MD5 PLAIN",
            'sequence' => array(
                '334 ' . base64_encode( 'realm="localhost"' ),
                '501 Authentication cancelled',
                '235 Authentication succeeded',
            ),
        ) );
        self::createTransport( $server, 'user', 'secret' )->send( self::createMail() );

        $this->assertSame( array( 'EHLO localhost', 'AUTH DIGEST-MD5', '*', 'AUTH PLAIN ' . base64_encode( "\0user\0secret" ) ),
                           array_slice( $server->commands(), 0, 4 ) );
    }

    public function testAuthFailsIfAllMethodsFail()
    {
        $server = $this->startServer( array(
            'ehlo' => "250-localhost\n250 AUTH LOGIN PLAIN",
            'sequence' => array(
                '504 Unrecognized authentication type',
                '534 Authentication mechanism is too weak',
            ),
        ) );
        try
        {
            self::createTransport( $server, 'user', 'secret' )->send( self::createMail() );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertStringContainsString( 'LOGIN, PLAIN', $e->getMessage() );
        }
        $this->assertSame( array( 'EHLO localhost', 'AUTH LOGIN', 'AUTH PLAIN ' . base64_encode( "\0user\0secret" ) ),
                           array_slice( $server->commands(), 0, 3 ) );
    }

    public function testAuthXOAuth2ErrorChallengeIsAnswered()
    {
        $server = $this->startServer( array(
            'ehlo' => "250-localhost\n250 AUTH XOAUTH2",
            'sequence' => array(
                '334 ' . base64_encode( '{"status":"401"}' ),
                '535 Authentication failed',
            ),
        ) );
        try
        {
            self::createTransport( $server, 'user', 'token' )->send( self::createMail() );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
        $this->assertSame( array( 'EHLO localhost', 'AUTH XOAUTH2 ' . base64_encode( "user=user\1auth=Bearer token\1\1" ), '' ),
                           array_slice( $server->commands(), 0, 3 ) );
    }

    public function testAuthSkipsNtlmWithoutMcrypt()
    {
        if ( ezcBaseFeatures::hasExtensionSupport( 'mcrypt' ) )
        {
            $this->markTestSkipped( 'Only applies if ext/mcrypt is not available.' );
        }

        $server = $this->startServer( array(
            'ehlo' => "250-localhost\n250 AUTH NTLM PLAIN",
            'sequence' => array( '235 Authentication succeeded' ),
        ) );
        self::createTransport( $server, 'user', 'secret' )->send( self::createMail() );

        $commands = $server->commands();
        $this->assertSame( 'AUTH PLAIN ' . base64_encode( "\0user\0secret" ), $commands[1] );
    }

    public static function suite()
    {
        return new PHPUnit\Framework\TestSuite( __CLASS__ );
    }
}

?>

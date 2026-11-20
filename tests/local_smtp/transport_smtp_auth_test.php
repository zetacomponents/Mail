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

require_once( dirname( __FILE__ ) . '/fake_smtp_test_case.php' );

/**
 * Tests the authentication of the SMTP transport.
 *
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportSmtpAuthTest extends ezcMailTransportSmtpLocalTestCase
{
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

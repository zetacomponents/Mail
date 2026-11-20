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
 * Tests the greeting (EHLO or HELO) sent by the SMTP transport.
 *
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportSmtpEhloTest extends ezcMailTransportSmtpLocalTestCase
{
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

    public static function suite()
    {
        return new PHPUnit\Framework\TestSuite( __CLASS__ );
    }
}
?>

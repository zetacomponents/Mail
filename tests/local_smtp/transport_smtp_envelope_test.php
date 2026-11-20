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
 * Tests the envelope addresses sent by the SMTP transport.
 *
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportSmtpEnvelopeTest extends ezcMailTransportSmtpLocalTestCase
{
    public function testSend()
    {
        $server = $this->startServer();
        self::createTransport( $server )->send( self::createMail() );

        // the first command is the greeting (EHLO or HELO)
        $this->assertSame( array( 'MAIL FROM:<from@example.com>', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT' ),
                           array_slice( $server->commands(), 1 ) );
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
        $this->assertSame( array( 'MAIL FROM:<from@example.com>', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT' ),
                           array_slice( $server->commands(), 1 ) );
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

    public static function suite()
    {
        return new PHPUnit\Framework\TestSuite( __CLASS__ );
    }
}
?>

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
 * Tests the line endings and dot-stuffing of the DATA sent by the SMTP transport.
 *
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportSmtpDotStuffingTest extends ezcMailTransportSmtpLocalTestCase
{
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
        // the first command is the greeting (EHLO or HELO)
        $this->assertSame( array( 'MAIL FROM:<from@example.com>', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT' ), array_slice( $commands, 1 ) );
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

    public static function suite()
    {
        return new PHPUnit\Framework\TestSuite( __CLASS__ );
    }
}
?>

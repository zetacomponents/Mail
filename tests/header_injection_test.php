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
 * Tests that header values, header names and addresses cannot be used to
 * inject additional headers into a generated mail.
 *
 * @package Mail
 * @subpackage Tests
 */
class ezcMailHeaderInjectionTest extends ezcTestCase
{
    protected function tearDown(): void
    {
        ezcMailTools::setLineBreak( "\r\n" );
    }

    /**
     * Returns the names of all headers in the generated header block $headers.
     */
    private static function headerNames( $headers )
    {
        $names = array();
        foreach ( preg_split( '/\r\n|\r|\n/', $headers ) as $line )
        {
            if ( preg_match( '/^([^\s:]+):/', $line, $matches ) )
            {
                $names[] = strtolower( $matches[1] );
            }
        }
        return $names;
    }

    private static function createMail()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'from@example.com', 'From' );
        $mail->addTo( new ezcMailAddress( 'to@example.com', 'To' ) );
        $mail->subject = 'Subject';
        $mail->body = new ezcMailText( 'Body' );
        return $mail;
    }

    public function testFromNameWithLineBreak()
    {
        $mail = self::createMail();
        $mail->from = new ezcMailAddress( 'from@example.com', "Eve\r\nBcc: victim@example.com" );
        $headers = $mail->generateHeaders();

        $this->assertNotContains( 'bcc', self::headerNames( $headers ) );
        $this->assertStringContainsString( 'From: "Eve Bcc: victim@example.com" <from@example.com>', $headers );
    }

    public function testToNameWithLineBreakUtf8()
    {
        $mail = self::createMail();
        $mail->to = array( new ezcMailAddress( 'to@example.com', "Eve\nBcc: victim@example.com", 'utf-8' ) );
        $headers = $mail->generateHeaders();

        $this->assertNotContains( 'bcc', self::headerNames( $headers ) );
    }

    public static function addressesWithLineBreak()
    {
        return array(
            array( "to@example.com\r\nX-Injected: 1" ),
            array( "to@example.com\nX-Injected: 1" ),
            array( "to@example.com\rX-Injected: 1" ),
            array( "to@example.com\0" ),
        );
    }

    /**
     * @dataProvider addressesWithLineBreak
     */
    public function testToAddressWithLineBreak( $email )
    {
        $mail = self::createMail();
        $mail->to = array( new ezcMailAddress( $email ) );

        $this->expectException( 'ezcMailException' );
        $mail->generateHeaders();
    }

    public function testComposeEmailAddressWithLineBreakInEmail()
    {
        $this->expectException( 'ezcMailException' );
        ezcMailTools::composeEmailAddress( new ezcMailAddress( "to@example.com\r\nX-Injected: 1", 'Name' ) );
    }

    public static function addressesWithSeparators()
    {
        return array(
            array( 'to@example.com>, <evil@example.com' ),
            array( 'to@example.com, evil@example.com' ),
            array( 'to@example.com; evil@example.com' ),
            array( 'to@example.com <evil@example.com>' ),
            array( '"to@example.com" evil@example.com' ),
            array( "to@example.com\tevil@example.com" ),
            array( 'to@example.com evil@example.com' ),
        );
    }

    /**
     * @dataProvider addressesWithSeparators
     */
    public function testToAddressWithSeparators( $email )
    {
        $mail = self::createMail();
        $mail->to = array( new ezcMailAddress( $email, 'Name' ) );

        $this->expectException( 'ezcMailException' );
        $mail->generateHeaders();
    }

    /**
     * @dataProvider addressesWithSeparators
     */
    public function testComposeEmailAddressWithSeparatorsWithoutName( $email )
    {
        $this->expectException( 'ezcMailException' );
        ezcMailTools::composeEmailAddress( new ezcMailAddress( $email ) );
    }

    public function testComposeEmailAddressAcceptsCommonAddresses()
    {
        $this->assertSame( "john.o'doe+tag@sub.example.com", ezcMailTools::composeEmailAddress( new ezcMailAddress( "john.o'doe+tag@sub.example.com" ) ) );
        $this->assertSame( 'Name <a_b-c@[127.0.0.1]>', ezcMailTools::composeEmailAddress( new ezcMailAddress( 'a_b-c@[127.0.0.1]', 'Name' ) ) );
    }

    public function testComposeEmailAddressAcceptsEnclosingAngleBrackets()
    {
        $this->assertSame( '<to@example.com>', ezcMailTools::composeEmailAddress( new ezcMailAddress( '<to@example.com>' ) ) );
        $this->assertSame( 'Name <to@example.com>', ezcMailTools::composeEmailAddress( new ezcMailAddress( '<to@example.com>', 'Name' ) ) );
        $this->assertSame( '<>', ezcMailTools::composeEmailAddress( new ezcMailAddress( '<>' ) ) );

        $this->expectException( 'ezcMailException' );
        ezcMailTools::composeEmailAddress( new ezcMailAddress( '<to@example.com>, <evil@example.com>' ) );
    }

    public function testSubjectWithLineBreak()
    {
        $mail = self::createMail();
        $mail->subject = "Hello\r\nX-Injected: 1";
        $headers = $mail->generateHeaders();

        $this->assertNotContains( 'x-injected', self::headerNames( $headers ) );
        $this->assertStringContainsString( "Subject: Hello X-Injected: 1\r\n", $headers );
        // transports like ezcMailMtaTransport read the header after generation
        $this->assertSame( 'Hello X-Injected: 1', $mail->getHeader( 'Subject' ) );
    }

    public function testSubjectWithLineBreakUtf8()
    {
        $mail = self::createMail();
        $mail->subject = "H\xc3\xa4llo\r\nX-Injected: 1";
        $mail->subjectCharset = 'utf-8';
        $headers = $mail->generateHeaders();

        $this->assertNotContains( 'x-injected', self::headerNames( $headers ) );
    }

    public function testSubjectWithLineBreakAndCustomLineBreak()
    {
        ezcMailTools::setLineBreak( "\n" );
        $mail = self::createMail();
        $mail->subject = "Hello\r\nX-Injected: 1";
        $headers = $mail->generateHeaders();

        $this->assertNotContains( 'x-injected', self::headerNames( $headers ) );
    }

    public function testCustomHeaderWithLineBreak()
    {
        $mail = self::createMail();
        $mail->setHeader( 'X-Custom', "value\r\nX-Injected: 1" );
        $headers = $mail->generateHeaders();

        $this->assertNotContains( 'x-injected', self::headerNames( $headers ) );
        $this->assertStringContainsString( "X-Custom: value X-Injected: 1\r\n", $headers );
    }

    public function testCustomHeaderWithEmptyLine()
    {
        $mail = self::createMail();
        $mail->setHeader( 'X-Custom', "value\r\n\r\nbody" );
        $headers = $mail->generateHeaders();

        $this->assertStringNotContainsString( "\r\n\r\n", $headers );
    }

    public function testCustomHeaderWithFoldingIsKept()
    {
        $mail = self::createMail();
        $mail->setHeader( 'X-Custom', "first line\r\n second line" );
        $headers = $mail->generateHeaders();

        $this->assertStringContainsString( "X-Custom: first line\r\n second line\r\n", $headers );
    }

    public function testCustomHeaderWithFoldingIsNormalised()
    {
        $mail = self::createMail();
        $mail->setHeader( 'X-Custom', "first line\n\tsecond line\nX-Injected: 1" );
        $headers = $mail->generateHeaders();

        $this->assertNotContains( 'x-injected', self::headerNames( $headers ) );
        $this->assertStringContainsString( "X-Custom: first line\r\n\tsecond line X-Injected: 1\r\n", $headers );
    }

    public static function whitespaceOnlyContinuations()
    {
        return array(
            array( "a\r\n \r\n Bcc: x", "X-Custom: a \r\n Bcc: x\r\n" ),
            array( "a\n\t\n\tb", "X-Custom: a \r\n\tb\r\n" ),
            array( "a\r\n \r\n\t\r\n b", "X-Custom: a \r\n b\r\n" ),
            array( "a\r\n ", "X-Custom: a\r\n" ),
        );
    }

    /**
     * @dataProvider whitespaceOnlyContinuations
     */
    public function testCustomHeaderWithWhitespaceOnlyContinuation( $value, $expected )
    {
        $mail = self::createMail();
        $mail->setHeader( 'X-Custom', $value );
        $headers = $mail->generateHeaders();

        $this->assertStringContainsString( $expected, $headers );
        foreach ( preg_split( '/\r\n/', $headers ) as $line )
        {
            if ( $line !== '' )
            {
                $this->assertNotSame( '', trim( $line ), 'whitespace-only header line' );
            }
        }
    }

    public function testFoldedAddressListIsKept()
    {
        $addresses = array();
        for ( $i = 0; $i < 10; $i++ )
        {
            $addresses[] = new ezcMailAddress( "recipient{$i}@example.com", "Recipient {$i}" );
        }
        $folded = ezcMailTools::composeEmailAddresses( $addresses, 76 );

        $mail = self::createMail();
        $mail->setHeader( 'X-Recipients', $folded );
        $headers = $mail->generateHeaders();

        $this->assertStringContainsString( "X-Recipients: {$folded}\r\n", $headers );
    }

    public static function invalidHeaderNames()
    {
        return array(
            array( "X-Custom\r\nX-Injected" ),
            array( "X-Custom\n" ),
            array( "X-Custom: 1\r\nX-Injected" ),
            array( "X Custom" ),
            array( "X-Custom\0" ),
            array( "" ),
        );
    }

    /**
     * @dataProvider invalidHeaderNames
     */
    public function testInvalidHeaderName( $name )
    {
        $mail = self::createMail();
        $mail->setHeader( $name, 'value' );

        $this->expectException( 'ezcMailException' );
        $mail->generateHeaders();
    }

    public function testContentDispositionFileNameIsEscaped()
    {
        $part = new ezcMailText( 'text' );
        $part->contentDisposition = new ezcMailContentDispositionHeader( 'attachment', 'a"; size=1; x="\\.txt' );
        $headers = $part->generateHeaders();

        $this->assertStringContainsString( 'Content-Disposition: attachment; filename="a\"; size=1; x=\"\\\\.txt"', $headers );
    }

    public function testContentDispositionParametersAreEscaped()
    {
        $part = new ezcMailText( 'text' );
        $part->contentDisposition = new ezcMailContentDispositionHeader( 'attachment', null, 'a"b', null, null, null, array( 'x-param' => 'c"d' ) );
        $headers = $part->generateHeaders();

        $this->assertStringContainsString( 'creation-date="a\"b"', $headers );
        $this->assertStringContainsString( 'x-param="c\"d"', $headers );
    }

    public function testContentDispositionFileNameWithLineBreak()
    {
        $part = new ezcMailText( 'text' );
        $part->contentDisposition = new ezcMailContentDispositionHeader( 'attachment', "a.txt\r\nX-Injected: 1" );
        $headers = $part->generateHeaders();

        $this->assertNotContains( 'x-injected', self::headerNames( $headers ) );
    }

    public function testFilePartContentTypeNameIsEscaped()
    {
        $part = new ezcMailVirtualFile( 'a"b.txt', 'content', 'text', 'plain' );
        $part->generate();

        $this->assertSame( 'text/plain; name="a\"b.txt"', $part->getHeader( 'Content-Type' ) );
    }

    public static function suite()
    {
        return new PHPUnit\Framework\TestSuite( __CLASS__ );
    }
}

?>

<?php
declare(encoding="latin1");
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

class ezcMailExtended extends ezcMail
{

}

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailToolsTest extends ezcTestCase
{
    /**
     * Tests if ezcMailTools::composeEmailAddress works as it should
     * @todo test if no 'email' is given.
     */
    public function testComposeEmailAddress()
    {
        $address = new ezcMailAddress( 'john@example.com', 'John Doe' );
        $this->assertEquals( 'John Doe <john@example.com>', ezcMailTools::composeEmailAddress( $address ) );

        $address = new ezcMailAddress( 'john@example.com' );
        $this->assertEquals( 'john@example.com', ezcMailTools::composeEmailAddress( $address ) );
    }

    /**
     * Tests if ezcMailTools::composeEmailAddresses works as it should
     * @todo test if no 'email' is given.
     */
    public function testComposeEmailAddresses()
    {
        $addresses = array( new ezcMailAddress( 'john@example.com', 'John Doe' ),
                            new ezcMailAddress( 'debra@example.com' ) );

        $this->assertEquals( 'John Doe <john@example.com>, debra@example.com',
                             ezcMailTools::composeEmailAddresses( $addresses ) );
    }

    public function testComposeEmailAddressUsAscii()
    {
        $address = new ezcMailAddress( 'john-ascii@example.com', 'John Ascii' );
        $this->assertEquals( 'John Ascii <john-ascii@example.com>', ezcMailTools::composeEmailAddress( $address ) );

        // The Ä does not in US ASCII, but we pass it along anyway
        $address = new ezcMailAddress( 'john-ascii@example.com', 'John Äscii' );
        $this->assertEquals( 'John Äscii <john-ascii@example.com>', ezcMailTools::composeEmailAddress( $address ) );
    }

    public function testComposeEmailAddressLatin1()
    {
        // no 8-bit-chars
        $address = new ezcMailAddress( 'john-ascii@example.com', 'John Ascii', 'iso-8859-1' );
        $this->assertEquals( 'John Ascii <john-ascii@example.com>', ezcMailTools::composeEmailAddress( $address ) );

        // with 8-bit chars
        $address = new ezcMailAddress( 'john-ascii@example.com', 'John Äscii', 'iso-8859-1' );
        $this->assertEquals( '=?iso-8859-1?Q?John=20=C4scii?= <john-ascii@example.com>', ezcMailTools::composeEmailAddress( $address ) );
    }

    public function testComposeEmailAddressOtherLatin()
    {
        foreach ( array( 'iso-8859-2', 'iso-8859-6', 'iso-8859-7', 'iso-8859-9', 'iso-8859-15' ) as $charset )
        {
            // no 8-bit-chars
            $address = new ezcMailAddress( 'john-ascii@example.com', 'John Ascii', $charset );
            $this->assertEquals( 'John Ascii <john-ascii@example.com>', ezcMailTools::composeEmailAddress( $address ) );

            // with 8-bit chars
            $address = new ezcMailAddress( 'john-ascii@example.com', 'John Äscii', $charset );
            $this->assertEquals( "=?{$charset}?Q?John=20=C4scii?= <john-ascii@example.com>", ezcMailTools::composeEmailAddress( $address ) );
        }
    }

    public function testComposeEmailAddressUTF8()
    {
        // no 8-bit-chars
        $address = new ezcMailAddress( 'john-ascii@example.com', 'John Ascii', 'UTF-8' );
        $this->assertEquals( 'John Ascii <john-ascii@example.com>', ezcMailTools::composeEmailAddress( $address ) );

        // with 8-bit chars
        $address = new ezcMailAddress( 'john-ascii@example.com', 'John Ã„scii','UTF-8' );
        $this->assertEquals( "=?UTF-8?Q?John=20=C3=84scii?= <john-ascii@example.com>", ezcMailTools::composeEmailAddress( $address ) );
    }

    public function testComposeEmailAddressesSingleFolding()
    {
        $reference = "John Doe <john@example.com>, Harry Doe <harry@example.com>," .
            ezcMailTools::lineBreak() .
            " Gordon Doe <gordon@example.com>, debra@example.com";
        $addresses = array( new ezcMailAddress( 'john@example.com', 'John Doe' ),
                            new ezcMailAddress( 'harry@example.com', 'Harry Doe' ),
                            new ezcMailAddress( 'gordon@example.com', 'Gordon Doe' ),
                            new ezcMailAddress( 'debra@example.com' ) );
        $result = ezcMailTools::composeEmailAddresses( $addresses, 76 );
        $this->assertEquals( $reference, $result );
    }

    public function testComposeEmailAddressesMultiFolding()
    {
        $reference = "John Doe <john@example.com>, Harry Doe <harry@example.com>," .
            ezcMailTools::lineBreak() .
            " Nancy Doe <nancy@example.com>, Faith Doe <faith@example.com>," .
            ezcMailTools::lineBreak() .
            " Gordon Doe <gordon@example.com>, debra@example.com";
        $addresses = array( new ezcMailAddress( 'john@example.com', 'John Doe' ),
                            new ezcMailAddress( 'harry@example.com', 'Harry Doe' ),
                            new ezcMailAddress( 'nancy@example.com', 'Nancy Doe' ),
                            new ezcMailAddress( 'faith@example.com', 'Faith Doe' ),
                            new ezcMailAddress( 'gordon@example.com', 'Gordon Doe' ),
                            new ezcMailAddress( 'debra@example.com' ) );
        $result = ezcMailTools::composeEmailAddresses( $addresses, 76 );
        $this->assertEquals( $reference, $result );
    }

    public function testComposeEmailAddressNameNotQuoted()
    {
        $addressesNotQuoted = array(
            array( "Doe John <john@example.com>", new ezcMailAddress( 'john@example.com', 'Doe John' ) ),
            array( "\"Doe, John\" <john@example.com>", new ezcMailAddress( 'john@example.com', '"Doe, John"' ) ), // already quoted
            array( "\"<Doe John>\" <john@example.com>", new ezcMailAddress( 'john@example.com', '"<Doe John>"' ) ), // already quoted
            array( "\"Doe@John.example.com\" <john@example.com>", new ezcMailAddress( 'john@example.com', '"Doe@John.example.com"' ) ), // already quoted
            array( "\"John, Doe@John.example.com\" <john@example.com>", new ezcMailAddress( 'john@example.com', '"John, Doe@John.example.com"' ) ), // already quoted
            array( "\":sysmail\" <john@example.com>", new ezcMailAddress( 'john@example.com', '":sysmail"' ) ), // already quoted
            array( "\";sysmail\" <john@example.com>", new ezcMailAddress( 'john@example.com', '";sysmail"' ) ), // already quoted
            array( "sysmail <john@example.com>", new ezcMailAddress( 'john@example.com', 'sysmail' ) ),
            array( "\"John 'Doe'\" <john@example.com>", new ezcMailAddress( 'john@example.com', 'John \'Doe\'' ) ),
            );

        foreach ( $addressesNotQuoted as $address )
        {
            $reference = $address[0];
            $result = ezcMailTools::composeEmailAddress( $address[1] );

            $this->assertEquals( $reference, $result );
        }
    }
        
    public function testComposeEmailAddressNameQuoted()
    {
        $addressesQuoted = array(
            array( "\"Doe, John\" <john@example.com>", new ezcMailAddress( 'john@example.com', 'Doe, John' ) ),
            array( "\"<Doe John>\" <john@example.com>", new ezcMailAddress( 'john@example.com', '<Doe John>' ) ), // double bad character < and >
            array( "\"john.doe@example.com\" <john@example.com>", new ezcMailAddress( 'john@example.com', 'john.doe@example.com' ) ),
            array( "\"John, john.doe@example.com\" <john@example.com>", new ezcMailAddress( 'john@example.com', 'John, john.doe@example.com' ) ), // double bad character , and @
            array( "\"John \\\"Doe\\\"\" <john@example.com>", new ezcMailAddress( 'john@example.com', 'John "Doe"' ) ),
            array( "\":sysmail\" <john@example.com>", new ezcMailAddress( 'john@example.com', ':sysmail' ) ),
            array( "\";sysmail\" <john@example.com>", new ezcMailAddress( 'john@example.com', ';sysmail' ) ),
            array( "\"John \\\"Doe\" <john@example.com>", new ezcMailAddress( 'john@example.com', 'John "Doe' ) ),
            array( "\"John 'Doe\" <john@example.com>", new ezcMailAddress( 'john@example.com', 'John \'Doe' ) ),
            array( "\"John \\\\\\\"Doe\" <john@example.com>", new ezcMailAddress( 'john@example.com', 'John \"Doe' ) ), // already escaped quotes
            array( "\"John \\\"Doe\" <john@example.com>", new ezcMailAddress( 'john@example.com', '"John "Doe"' ) ),
            array( "\"\\\"Doe\\\" \\\"John\" <john@example.com>", new ezcMailAddress( 'john@example.com', '"Doe" "John' ) ),
            array( "\"Doe\\\" John\" <john@example.com>", new ezcMailAddress( 'john@example.com', '"Doe" John"' ) ),
            array( "\"'Doe' 'John\" <john@example.com>", new ezcMailAddress( 'john@example.com', "'Doe' 'John" ) ),
            );

        foreach ( $addressesQuoted as $key => $address )
        {
            $reference = $address[0];
            $result = ezcMailTools::composeEmailAddress( $address[1] );

            $this->assertEquals( $reference, $result );
        }
    }

    public function testParseEmailAddressMimeGood()
    {
        $add = ezcMailTools::parseEmailAddress( '"John Doe" <john@example.com>' );
        $this->assertEquals( 'John Doe', $add->name );
        $this->assertEquals( 'john@example.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( '"John Doe" <john.doe@example.com>' );
        $this->assertEquals( 'John Doe', $add->name );
        $this->assertEquals( 'john.doe@example.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( '"John Doe" <"john.doe"@example.com>' );
        $this->assertEquals( 'John Doe', $add->name );
        $this->assertEquals( 'john.doe@example.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( 'john@example.com' );
        $this->assertEquals( '', $add->name );
        $this->assertEquals( 'john@example.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( '<john@example.com>' );
        $this->assertEquals( '', $add->name );
        $this->assertEquals( 'john@example.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( '"!#%&/()" <jo-_!#%&+hn@example.com>' );
        $this->assertEquals( '!#%&/()', $add->name );
        $this->assertEquals( 'jo-_!#%&+hn@example.com', $add->email );
    }

    public function testParseEmailAddressMimeWrong()
    {
        $add = ezcMailTools::parseEmailAddress( "No address in this place @ here" );
        $this->assertEquals( null, $add );
    }

    public function testParseEmailMimeAddresses()
    {
        $add = ezcMailTools::parseEmailAddresses( '"John Doe" <john@example.com>, "my, name" <my@example.com>' );
        $this->assertEquals( 'John Doe', $add[0]->name );
        $this->assertEquals( 'john@example.com', $add[0]->email );
        $this->assertEquals( 'my, name', $add[1]->name );
        $this->assertEquals( 'my@example.com', $add[1]->email );

        $add = ezcMailTools::parseEmailAddresses( '<john@example.com>' );
        $this->assertEquals( '', $add[0]->name );
        $this->assertEquals( 'john@example.com', $add[0]->email );
    }

    public function testParseEmailAddressLocalEncoding()
    {
        $add = ezcMailTools::parseEmailAddress( 'Test äöää <foobar@example.com>', 'iso-8859-1' );
        $this->assertEquals( 'Test Ã¤Ã¶Ã¤Ã¤', $add->name );
        $this->assertEquals( 'foobar@example.com', $add->email );
    }

    public function testParseEmailAddressesLocalEncoding()
    {
        $add = ezcMailTools::parseEmailAddresses( 'Test äöää<foobar@example.com>, En Lømmel <test@example.com>',
                                                'iso-8859-1' );
        $this->assertEquals( 'Test Ã¤Ã¶Ã¤Ã¤', $add[0]->name );
        $this->assertEquals( 'foobar@example.com', $add[0]->email );
        $this->assertEquals( 'En LÃ¸mmel', $add[1]->name );
        $this->assertEquals( 'test@example.com', $add[1]->email );
    }

    public function testValidateEmailAddressCorrect()
    {
        $data = file_get_contents( dirname( __FILE__ ) . '/tools/data/addresses_correct.txt' );
        $addresses = split( "\n", $data );
        foreach ( $addresses as $address )
        {
            if ( strlen( trim( $address ) ) > 1 && $address{0} !== '#' )
            {
                $this->assertEquals( true, ezcMailTools::validateEmailAddress( $address ), "Failed asserting that {$address} is correct." );
            }
        }
    }

    public function testValidateEmailAddressCorrectMX()
    {
        $data = file_get_contents( dirname( __FILE__ ) . '/tools/data/addresses_correct_mx.txt' );
        $addresses = split( "\n", $data );
        foreach ( $addresses as $address )
        {
            if ( strlen( trim( $address ) ) > 1 && $address{0} !== '#' )
            {
                $this->assertEquals( true, ezcMailTools::validateEmailAddress( $address, true ), "Failed asserting that {$address} is correct with MX." );
            }
        }
    }

    public function testValidateEmailAddressIncorrect()
    {
        $data = file_get_contents( dirname( __FILE__ ) . '/tools/data/addresses_incorrect.txt' );
        $addresses = split( "\n", $data );
        foreach ( $addresses as $address )
        {
            if ( strlen( trim( $address ) ) > 1 && $address{0} !== '#' )
            {
                $this->assertEquals( false, ezcMailTools::validateEmailAddress( $address ), "Failed asserting that {$address} is incorrect." );
            }
        }
    }

    public function testValidateEmailAddressIncorrectMX()
    {
        $data = file_get_contents( dirname( __FILE__ ) . '/tools/data/addresses_incorrect_mx.txt' );
        $addresses = split( "\n", $data );
        foreach ( $addresses as $address )
        {
            if ( strlen( trim( $address ) ) > 1 && $address{0} !== '#' )
            {
                $this->assertEquals( false, ezcMailTools::validateEmailAddress( $address, true ), "Failed asserting that {$address} is incorrect with MX." );
            }
        }
    }

    /**
     * Tests if generateContentId works as it should.
     * Somewhat hard to test since it is supposed to return a unique string.
     * We simply test if two calls return different strings.
     */
    public function testGenerateContentId()
    {
        if ( ezcMailTools::generateContentID() === ezcMailTools::generateContentID() )
        {
            $this->fail( "testGenerateMessageID generated the same ID twice" );
        }
    }

    /**
     * Tests if generateMessageId works as it should.
     * Somewhat hard to test since it is supposed to return a unique string.
     * We simply test if two calls return different strings.
     */
    public function testGenerateMessageId()
    {
        if ( ezcMailTools::generateMessageID( "doe.com" ) === ezcMailTools::generateMessageID( "doe.com") )
        {
            $this->fail( "testGenerateMessageID generated the same ID twice" );
        }
    }

    /**
     *
     */
    public function testEndline()
    {
        // defaul is \n\r as specified in RFC2045
        $this->assertEquals( "\r\n", ezcMailTools::lineBreak() );

        // now let's set it and check that it works
        ezcMailTools::setLineBreak( "\n" );
        $this->assertEquals( "\n", ezcMailTools::lineBreak() );
    }

    public function testReplyTo()
    {
        $parser = new ezcMailParser();
        $set = new ezcMailFileSet( array( dirname( __FILE__ )
                                          . '/parser/data/kmail/simple_mail_with_text_subject_and_body.mail' ) );
        $mail = $parser->parseMail( $set );

        $reply = ezcMailTools::replyToMail( $mail[0],
                                            new ezcMailAddress( 'test@example.com', 'Reply Guy' ) );

        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ) ),
                             $reply->to );
        $this->assertEquals( new ezcMailAddress( 'test@example.com', 'Reply Guy' ), $reply->from );
        $this->assertEquals( 'Re: Simple mail with text subject and body', $reply->subject );
        $this->assertEquals( '<200602061533.27600.fh@ez.no>', $reply->getHeader( 'In-Reply-To' ) );
        $this->assertEquals( '<200602061533.27600.fh@ez.no>', $reply->getHeader( 'References' ) );
    }
    
    public function testReplyToExtended()
    {
        $parser = new ezcMailParser();
        $set = new ezcMailFileSet( array( dirname( __FILE__ )
                                          . '/parser/data/kmail/simple_mail_with_text_subject_and_body.mail' ) );
        $mail = $parser->parseMail( $set );

        $reply = ezcMailTools::replyToMail(
            $mail[0],
            new ezcMailAddress( 'test@example.com', 'Reply Guy' ),
            ezcMailTools::REPLY_SENDER,
            "Re: ",
            "ezcMailExtended"
        );

        $this->assertType(
            "ezcMailExtended",
            $reply,
            "replyToMail created incorrect class instance."
        );
    }

    public function testReplyToAll()
    {
        $parser = new ezcMailParser();
        $set = new ezcMailFileSet( array( dirname( __FILE__ )
                                          . '/parser/data/various/multiple_recipients' ) );
        $mail = $parser->parseMail( $set );

        $reply = ezcMailTools::replyToMail( $mail[0],
                                            new ezcMailAddress( 'test@example.com', 'Reply Guy' ),
                                            ezcMailTools::REPLY_ALL, 'Sv: ' );

        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ) ),
                             $reply->to );
        $this->assertEquals( new ezcMailAddress( 'test@example.com', 'Reply Guy' ), $reply->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ),
                                    new ezcMailAddress( 'user@example.com', '', 'utf-8' ) ), $reply->cc );
        $this->assertEquals( 'Sv: Simple mail with text subject and body', $reply->subject );
        $this->assertEquals( '<200602061533.27600.fh@ez.no>', $reply->getHeader( 'In-Reply-To' ) );
        $this->assertEquals( '<1234.567@example.com> <200602061533.27600.fh@ez.no>', $reply->getHeader( 'References' ) );
    }

    public function testReplyToReply()
    {
        $mail = new ezcMail();
        $mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Fræderik Hølljen', 'ISO-8859-1' ) );

        $address = new ezcMailAddress( 'test@example.com', 'Reply Går', 'ISO-8859-1' );
        $mail->setHeader( 'Reply-To', ezcMailTools::composeEmailAddress( $address ) );
        // $mail->setHeader( 'Reply-To', 'test@example.com' );

        $reply = ezcMailTools::replyToMail( $mail,
                                            new ezcMailAddress( 'test@example.com', 'Reply Går', 'ISO-8859-1' ) );
        $this->assertEquals( $reply->to, array( new ezcMailAddress( 'test@example.com', "Reply G\xC3\xA5r", 'utf-8' ) ) );
    }

    public function testGuessContentType()
    {
        $fileNames = array( '/home/1.jpg',
                            '2.jpe',
                            '3.jpeg',
                            '4.gif',
                            '5.tif',
                            '6.tiff',
                            '7.bmp',
                            '8.png',
                            '9.xxx',
                            '10'
                          );
        $types = array( 'image/jpeg',
                        'image/jpeg',
                        'image/jpeg',
                        'image/gif',
                        'image/tiff',
                        'image/tiff',
                        'image/bmp',
                        'image/png',
                        '/',
                        '/' );
        for ( $i = 0; $i < count( $fileNames ); $i++ )
        {
            $contentType = null;
            $mimeType = null;
            ezcMailTools::guessContentType( $fileNames[$i], $contentType, $mimeType );
            $this->assertEquals( $types[$i], $contentType . '/' . $mimeType );
        }
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailToolsTest" );
    }
}

?>

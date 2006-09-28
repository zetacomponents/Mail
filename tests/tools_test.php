<?php
/**
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
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
        $address = new ezcMailAddress( 'john@doe.com', 'John Doe' );
        $this->assertEquals( 'John Doe <john@doe.com>', ezcMailTools::composeEmailAddress( $address ) );

        $address = new ezcMailAddress( 'john@doe.com' );
        $this->assertEquals( 'john@doe.com', ezcMailTools::composeEmailAddress( $address ) );
    }

    /**
     * Tests if ezcMailTools::composeEmailAddresses works as it should
     * @todo test if no 'email' is given.
     */
    public function testComposeEmailAddresses()
    {
        $addresses = array( new ezcMailAddress( 'john@doe.com', 'John Doe' ),
                            new ezcMailAddress( 'debra@doe.com' ) );

        $this->assertEquals( 'John Doe <john@doe.com>, debra@doe.com',
                             ezcMailTools::composeEmailAddresses( $addresses ) );
    }

    public function testComposeEmailAddressesSingleFolding()
    {
        $reference = "John Doe <john@doe.com>, Harry Doe <harry@doe.com>," .
            ezcMailTools::lineBreak() .
            " Gordon Doe <gordon@doe.com>, debra@doe.com";
        $addresses = array( new ezcMailAddress( 'john@doe.com', 'John Doe' ),
                            new ezcMailAddress( 'harry@doe.com', 'Harry Doe' ),
                            new ezcMailAddress( 'gordon@doe.com', 'Gordon Doe' ),
                            new ezcMailAddress( 'debra@doe.com' ) );
        $result = ezcMailTools::composeEmailAddresses( $addresses, 76 );
        $this->assertEquals( $reference, $result );
    }

    public function testComposeEmailAddressesMultiFolding()
    {
        $reference = "John Doe <john@doe.com>, Harry Doe <harry@doe.com>," .
            ezcMailTools::lineBreak() .
            " Nancy Doe <nancy@doe.com>, Faith Doe <faith@doe.com>," .
            ezcMailTools::lineBreak() .
            " Gordon Doe <gordon@doe.com>, debra@doe.com";
        $addresses = array( new ezcMailAddress( 'john@doe.com', 'John Doe' ),
                            new ezcMailAddress( 'harry@doe.com', 'Harry Doe' ),
                            new ezcMailAddress( 'nancy@doe.com', 'Nancy Doe' ),
                            new ezcMailAddress( 'faith@doe.com', 'Faith Doe' ),
                            new ezcMailAddress( 'gordon@doe.com', 'Gordon Doe' ),
                            new ezcMailAddress( 'debra@doe.com' ) );
        $result = ezcMailTools::composeEmailAddresses( $addresses, 76 );
        $this->assertEquals( $reference, $result );
    }

    public function testParseEmailAddressMimeGood()
    {
        $add = ezcMailTools::parseEmailAddress( '"John Doe" <john@doe.com>' );
        $this->assertEquals( 'John Doe', $add->name );
        $this->assertEquals( 'john@doe.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( '"John Doe" <john.doe@doe.com>' );
        $this->assertEquals( 'John Doe', $add->name );
        $this->assertEquals( 'john.doe@doe.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( '"John Doe" <"john.doe"@doe.com>' );
        $this->assertEquals( 'John Doe', $add->name );
        $this->assertEquals( 'john.doe@doe.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( 'john@doe.com' );
        $this->assertEquals( '', $add->name );
        $this->assertEquals( 'john@doe.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( '<john@doe.com>' );
        $this->assertEquals( '', $add->name );
        $this->assertEquals( 'john@doe.com', $add->email );

        $add = ezcMailTools::parseEmailAddress( '"!#%&/()" <jo-_!#%&+hn@doe.com>' );
        $this->assertEquals( '!#%&/()', $add->name );
        $this->assertEquals( 'jo-_!#%&+hn@doe.com', $add->email );
    }

    public function testParseEmailAddressMimeWrong()
    {
        $add = ezcMailTools::parseEmailAddress( "No address in this place @ here" );
        $this->assertEquals( null, $add );
    }

    public function testParseEmailMimeAddresses()
    {
        $add = ezcMailTools::parseEmailAddresses( '"John Doe" <john@doe.com>, "my, name" <my@example.com>' );
        $this->assertEquals( 'John Doe', $add[0]->name );
        $this->assertEquals( 'john@doe.com', $add[0]->email );
        $this->assertEquals( 'my, name', $add[1]->name );
        $this->assertEquals( 'my@example.com', $add[1]->email );

        $add = ezcMailTools::parseEmailAddresses( '<john@doe.com>' );
        $this->assertEquals( '', $add[0]->name );
        $this->assertEquals( 'john@doe.com', $add[0]->email );
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
            "replyToMaol created incorrect class instance."
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
        //$mail->setHeader( 'Reply-To', 'test@example.com' );

        $reply = ezcMailTools::replyToMail( $mail,
                                            new ezcMailAddress( 'test@example.com', 'Reply Går', 'ISO-8859-1' ) );
        $this->assertEquals( $reply->to, array( new ezcMailAddress( 'test@example.com', "Reply G\xC3\xA5r", 'utf-8' ) ) );
    }

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailToolsTest" );
    }
}

?>

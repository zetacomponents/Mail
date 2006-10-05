<?php
/**
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */


/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTest extends ezcTestCase
{
    private $mail;

    protected function setUp()
    {
        $this->mail = new ezcMail();
    }

    public function testProperties()
    {
        $this->assertSetPropertyFails( $this->mail, "does_not_exist", array( 42 ) );
        $this->assertSetProperty( $this->mail, "to", array( array( 'email' => 'fh@ez.no' ) ) );

        try
        {
            $this->mail->timestamp = 0;
            $this->fail( 'Expected exception not thrown' );
        }
        catch ( ezcBasePropertyPermissionException $e )
        {
            $this->assertEquals( 'The property <timestamp> is read-only.', $e->getMessage() );
        }
    }

    public function testAddAddresses()
    {
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addTo( new ezcMailAddress( 'bh@ez.no' ) );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ),
                                    new ezcMailAddress( 'bh@ez.no' ) ), $this->mail->to );

        $this->mail->addCc( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addCc( new ezcMailAddress( 'bh@ez.no' ) );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ),
                                    new ezcMailAddress( 'bh@ez.no' ) ), $this->mail->cc );

        $this->mail->addBcc( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addBcc( new ezcMailAddress( 'bh@ez.no' ) );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ),
                                    new ezcMailAddress( 'bh@ez.no' ) ), $this->mail->bcc );


    }

    public function testAddAddresses2()
    {
        $this->mail->from = new ezcMailAddress( 'from@ez.no' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addTo( new ezcMailAddress( 'bh@ez.no' ) );
        $this->mail->addCc( new ezcMailAddress( 'dr@ez.no', 'Derick Rethans' ) );
        $this->mail->addBcc( new ezcMailAddress( 'amos@ez.no' ) );

        $expected = "From: from@ez.no" . ezcMailTools::lineBreak() .
            "To: Frederik Holljen <fh@ez.no>, bh@ez.no" . ezcMailTools::lineBreak() .
            "Cc: Derick Rethans <dr@ez.no>" . ezcMailTools::lineBreak() .
            "Bcc: amos@ez.no" . ezcMailTools::lineBreak() .
            "Subject: " . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 7 ) );
        $this->assertEquals( $expected, $return );
    }

    public function testSubjectWithCharset()
    {
        $this->mail->from = new ezcMailAddress( 'from@ez.no' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Døtte er en test";
        $this->mail->subjectCharset = 'ISO-8859-1';
        $expected = "From: from@ez.no" . ezcMailTools::lineBreak() .
            "To: Frederik Holljen <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: =?ISO-8859-1?B?RPh0dGUgZXIgZW4gdGVzdA==?=" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 5 ) );

        $this->assertEquals( $expected, $return );
    }

    public function testHeadersWithCharset()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Fræderik Hølljen', 'ISO-8859-1' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Fræderik Hølljen','ISO-8859-1' ) );
        $this->mail->addCc( new ezcMailAddress( 'fh@ez.no', 'Fræderik Hølljen','ISO-8859-1' ) );
        $this->mail->addBcc( new ezcMailAddress( 'fh@ez.no', 'Fræderik Hølljen','ISO-8859-1' ) );
        $this->mail->subject = "Døtte er en test";
        $this->mail->subjectCharset = 'ISO-8859-1';
        $expected = "From: =?ISO-8859-1?B?RnLmZGVyaWsgSPhsbGplbg==?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "To: =?ISO-8859-1?B?RnLmZGVyaWsgSPhsbGplbg==?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Cc: =?ISO-8859-1?B?RnLmZGVyaWsgSPhsbGplbg==?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Bcc: =?ISO-8859-1?B?RnLmZGVyaWsgSPhsbGplbg==?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: =?ISO-8859-1?B?RPh0dGUgZXIgZW4gdGVzdA==?=" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 7 ) );

        $this->assertEquals( $expected, $return );
    }
    
    public function testSubjectWithCharsetUtf8()
    {
        $this->mail->from = new ezcMailAddress( 'from@ez.no' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Døtte er en test";
        $this->mail->subjectCharset = 'ISO-8859-1';
        $expected = "From: from@ez.no" . ezcMailTools::lineBreak() .
            "To: Frederik Holljen <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: =?ISO-8859-1?B?RPh0dGUgZXIgZW4gdGVzdA==?=" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 5 ) );

        $this->assertEquals( $expected, $return );
    }

    public function testHeadersWithCharsetUtf8()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'FrÃ¦derik HÃ¸lljen', 'UTF-8' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'FrÃ¦derik HÃ¸lljen','UTF-8' ) );
        $this->mail->addCc( new ezcMailAddress( 'fh@ez.no', 'FrÃ¦derik HÃ¸lljen','UTF-8' ) );
        $this->mail->addBcc( new ezcMailAddress( 'fh@ez.no', 'FrÃ¦derik HÃ¸lljen','UTF-8' ) );
        $this->mail->subject = "DÃ¤tte er en test";
        $this->mail->subjectCharset = 'UTF-8';
        $expected = "From: =?UTF-8?B?RnLDpmRlcmlrIEjDuGxsamVu?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "To: =?UTF-8?B?RnLDpmRlcmlrIEjDuGxsamVu?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Cc: =?UTF-8?B?RnLDpmRlcmlrIEjDuGxsamVu?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Bcc: =?UTF-8?B?RnLDpmRlcmlrIEjDuGxsamVu?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: =?UTF-8?B?RMOkdHRlIGVyIGVuIHRlc3Q=?=" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 7 ) );

        $this->assertEquals( $expected, $return );
    }

    public function testFullMail()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "æøå";
        $this->mail->body = new ezcMailText( "Dette er body ßßæøååå" );
//        echo "\n---------------\n";
//        echo $this->mail->generate();
//        echo "---------------\n";
        // let's try to send the thing
        $transport = new ezcMailTransportMta();
//        $transport->send( $this->mail );
    }

    public function testFullMailMultipart()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Fræderik Hølljen', "iso-8859-1" );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Fræderik Hølljeñ', "iso-8859-1" ) );
        $this->mail->subject = "æøå";
        $this->mail->subjectCharset = 'iso-8859-1';
        $this->mail->body = new ezcMailMultipartAlternative( new ezcMailText( "Dette er body ßßæøååå", "iso-8859-1" ),
                                                             $html = new ezcMailText( "<html>Hello</html>" ) );
        $html->subType = "html";

//        echo "\n---------------\n";
//        echo $this->mail->generate();
//        echo "---------------\n";
        // let's try to send the thing
//        $transport = new ezcMailTransportSmtp( "smtp.ez.no" );
//        $transport->send( $this->mail );
    }

    public function testFullMailDigest()
    {
        $digest = new ezcMail();
        $digest->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $digest->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $digest->subject = "æøå";
        $digest->body = new ezcMailText( "Dette er body ßßæøååå" );

        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "æøå";
        $this->mail->subjectCharset = 'iso-8859-1';
        $this->mail->body = new ezcMailMultipartMixed( new ezcMailText( "Dette er body ßßæøååå", "iso-8859-1" ),
                                                       new ezcMailRfc822Digest( $digest ) );

//        $transport = new ezcMailTransportSmtp( "smtp.ez.no" );
//        $transport->send( $this->mail );
    }

    public function testFullMailDigest2()
    {
        $digest = new ezcMail();
        $digest->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $digest->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $digest->subject = "æøå";
        $digest->body = new ezcMailText( "Dette er body ßßæøååå" );

        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "æøå";
        $this->mail->subjectCharset = 'iso-8859-1';
        $this->mail->body = new ezcMailMultipartMixed( new ezcMailText( "Dette er body ßßæøååå", "iso-8859-1" ),
                                                       new ezcMailMultipartDigest( new ezcMailRfc822Digest( $digest ) ) );

//        $transport = new ezcMailTransportSmtp( "smtp.ez.no" );
//        $transport->send( $this->mail );
    }

    public function testFullMailDigestArray()
    {
        $digest = new ezcMail();
        $digest->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $digest->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $digest->subject = "æøå";
        $digest->body = new ezcMailText( "Dette er body ßßæøååå" );

        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "æøå";
        $this->mail->subjectCharset = 'iso-8859-1';
        $this->mail->body = new ezcMailMultipartMixed( new ezcMailText( "Dette er body ßßæøååå", "iso-8859-1" ),
                                                       new ezcMailMultipartDigest( array( new ezcMailRfc822Digest( $digest ) ) ) );

//        $transport = new ezcMailTransportSmtp( "smtp.ez.no" );
//        $transport->send( $this->mail );
    }

    public function testMessageID1()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "æøå";
        $this->mail->body = new ezcMailText( "Dette er body ßßæøååå" );

        $this->mail->generateHeaders();
        $expected = '<'. date( 'YmdGHjs' ) . '.' . getmypid() . '.5@ez.no>';
        $this->assertEquals( $expected, $this->mail->getHeader( 'Message-Id' ) );
    }

    public function testMessageID2()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "æøå";
        $this->mail->body = new ezcMailText( "Dette er body ßßæøååå" );

        $this->mail->messageID = "<test-ezc-message-id@ezc.ez.no>";
        $this->mail->generateHeaders();
        $this->assertEquals( '<test-ezc-message-id@ezc.ez.no>', $this->mail->getHeader( 'Message-Id' ) );
    }

    public function testMailAddressToString()
    {
        $addr = new ezcMailAddress( "test@example.com", "John Doe" );

        $this->assertEquals(
            "John Doe <test@example.com>",
            $addr->__toString(),
            "Address not correctly serialized."
        );
    }

    public function testGenerateEmpty()
    {
        $return = $this->mail->generate();
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailTest" );
    }
}
?>

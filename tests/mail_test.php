<?php
declare(encoding="latin1");
/**
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
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
        $this->assertSetProperty( $this->mail, "to", array( array( new ezcMailAddress( 'fh@ez.no' ) ) ) );

        try
        {
            $this->mail->timestamp = 0;
            $this->fail( 'Expected exception not thrown' );
        }
        catch ( ezcBasePropertyPermissionException $e )
        {
            $this->assertEquals( "The property 'timestamp' is read-only.", $e->getMessage() );
        }

        try
        {
            $this->mail->headers = null;
            $this->fail( 'Expected exception not thrown' );
        }
        catch ( ezcBasePropertyPermissionException $e )
        {
            $this->assertEquals( "The property 'headers' is read-only.", $e->getMessage() );
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
            "User-Agent: eZ Components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 7 ) );
        $this->assertEquals( $expected, $return );
    }

    public function testSubjectWithCharset7Bit()
    {
        $this->mail->from = new ezcMailAddress( 'from@ez.no' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Dette er en test";
        $this->mail->subjectCharset = 'ISO-8859-1';
        $expected = "From: from@ez.no" . ezcMailTools::lineBreak() .
            "To: Frederik Holljen <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: Dette er en test" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ Components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 5 ) );

        $this->assertEquals( $expected, $return );
    }

    public function testSubjectWithCharset8Bit()
    {
        $this->mail->from = new ezcMailAddress( 'from@ez.no' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Døtte er en test";
        $this->mail->subjectCharset = 'ISO-8859-1';
        $expected = "From: from@ez.no" . ezcMailTools::lineBreak() .
            "To: Frederik Holljen <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: =?ISO-8859-1?Q?D=F8tte=20er=20en=20test?=" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ Components";

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
        $expected = "From: =?ISO-8859-1?Q?Fr=E6derik=20H=F8lljen?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "To: =?ISO-8859-1?Q?Fr=E6derik=20H=F8lljen?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Cc: =?ISO-8859-1?Q?Fr=E6derik=20H=F8lljen?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Bcc: =?ISO-8859-1?Q?Fr=E6derik=20H=F8lljen?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: =?ISO-8859-1?Q?D=F8tte=20er=20en=20test?=" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ Components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 7 ) );

        $this->assertEquals( $expected, $return );
    }

    public function testSubjectWithCharset7BitUtf8()
    {
        $this->mail->from = new ezcMailAddress( 'from@ez.no' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Dette er en test";
        $this->mail->subjectCharset = 'UTF-8';
        $expected = "From: from@ez.no" . ezcMailTools::lineBreak() .
            "To: Frederik Holljen <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: Dette er en test" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ Components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 5 ) );

        $this->assertEquals( $expected, $return );
    }

    public function testSubjectWithCharset8BitUtf8()
    {
        $this->mail->from = new ezcMailAddress( 'from@ez.no' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "DÃ¸tte er en test";
        $this->mail->subjectCharset = 'UTF-8';
        $expected = "From: from@ez.no" . ezcMailTools::lineBreak() .
            "To: Frederik Holljen <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: =?UTF-8?Q?D=C3=B8tte=20er=20en=20test?=" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ Components";

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
        $expected = "From: =?UTF-8?Q?Fr=C3=A6derik=20H=C3=B8lljen?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "To: =?UTF-8?Q?Fr=C3=A6derik=20H=C3=B8lljen?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Cc: =?UTF-8?Q?Fr=C3=A6derik=20H=C3=B8lljen?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Bcc: =?UTF-8?Q?Fr=C3=A6derik=20H=C3=B8lljen?= <fh@ez.no>" . ezcMailTools::lineBreak() .
            "Subject: =?UTF-8?Q?D=C3=A4tte=20er=20en=20test?=" . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ Components";

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
        $transport = new ezcMailMtaTransport();
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
//        $transport = new ezcMailSmtpTransport( "smtp.ez.no" );
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

//        $transport = new ezcMailSmtpTransport( "smtp.ez.no" );
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

//        $transport = new ezcMailSmtpTransport( "smtp.ez.no" );
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

//        $transport = new ezcMailSmtpTransport( "smtp.ez.no" );
//        $transport->send( $this->mail );
    }

    public function testMultipartReport()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $mail->subject = "Report";
        $mail->subjectCharset = 'iso-8859-1';
        $delivery = new ezcMailDeliveryStatus();
        $delivery->message["Reporting-MTA"] = "dns; www.brssolutions.com";
        $lastRecipient = $delivery->createRecipient();
        $delivery->recipients[$lastRecipient]["Action"] = "failed";
        $mail->body = new ezcMailMultipartReport(
            new ezcMailText( "Dette er body ßßæøååå", "iso-8859-1" ),
            $delivery,
            new ezcMailText( "The content initially sent" )
            );
    }

    public function testMessageID1()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "æøå";
        $this->mail->body = new ezcMailText( "Dette er body ßßæøååå" );

        $this->mail->generateHeaders();
        $expected = '<'. date( 'YmdGHjs' ) . '.' . getmypid() . '.7@ez.no>';
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

    // test for issue #11174
    public function testMailHeaderFolding76Char()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'john@example.com', 'John Doe' );
        $mail->addTo( new ezcMailAddress( 'john@example.com', 'John Doe' ) );
        $mail->body = new ezcMailText( 'Text' );

        // test some subject sizes
        for ( $i = 1; $i < 300; $i++ )
        {
            $mail->subject = str_repeat( '1', $i );
            $source = $mail->generate();
            preg_match( '/Subject:\s[0-9]+/', $source, $matches );
            $this->assertEquals( 1, count( $matches ), "Subject is folded incorrectly for length {$i}." );
        }
    }

    // test for issue #12595: the Bcc line would have had an empty line underneath before the fix
    // similar for To and Cc headers
    public function testFoldingAddresses()
    {
        $this->mail->from = new ezcMailAddress( 'from@ez.no' );
        $addresses = array( 'nospam1@ez.no', 'nospam2@ez.no', 'nospam3@ez.no',
            'nospam4@ez.no', 'nospam5@ez.no', 'nospam6@ez.no', 'nospam7@ez.no' );

        foreach ( $addresses as $address )
        {
            $this->mail->addBcc( new ezcMailAddress( $address ) );
        }

        $expected = "From: from@ez.no" . ezcMailTools::lineBreak() .
            "To: " . ezcMailTools::lineBreak() .
            "Bcc: nospam1@ez.no, nospam2@ez.no, nospam3@ez.no, nospam4@ez.no, nospam5@ez.no," . ezcMailTools::lineBreak() .
            " nospam6@ez.no, nospam7@ez.no" . ezcMailTools::lineBreak() .
            "Subject: " . ezcMailTools::lineBreak() .
            "MIME-Version: 1.0" . ezcMailTools::lineBreak() .
            "User-Agent: eZ Components";

        $return = $this->mail->generate();
        // cut away the Date and Message-ID headers as there is no way to predict what they will be
        $return = join( ezcMailTools::lineBreak(), array_slice( explode( ezcMailTools::lineBreak(), $return ), 0, 7 ) );
        $this->assertEquals( $expected, $return );
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

    public function testContentDispositionHeaderSetState()
    {
        $header = ezcMailContentDispositionHeader::__set_state( array(
                        'disposition' => 'inline',
                        'fileName' => 'spacer.gif',
                        'creationDate' => 'Sun, 21 May 2006 16:00:50 +0400',
                        'modificationDate' => 'Sun, 21 May 2006 16:01:50 +0400',
                        'readDate' => 'Sun, 21 May 2006 16:02:50 +0400',
                        'size' => 51,
                        'additionalParameters' => array( 'foo' => 'bar' ),
                        'fileNameLanguage' => 'EN',
                        'fileNameCharSet' => 'ISO-8859-1',
                        // for issue #13038
                        'displayFileName' => null
                ) );
        $this->assertEquals( 'inline', $header->disposition );
        $this->assertEquals( 'spacer.gif', $header->fileName );
        $this->assertEquals( 'Sun, 21 May 2006 16:00:50 +0400', $header->creationDate );
        $this->assertEquals( 'Sun, 21 May 2006 16:01:50 +0400', $header->modificationDate );
        $this->assertEquals( 'Sun, 21 May 2006 16:02:50 +0400', $header->readDate );
        $this->assertEquals( 51, $header->size );
        $this->assertEquals( array( 'foo' => 'bar' ), $header->additionalParameters );
        $this->assertEquals( 'EN', $header->fileNameLanguage );
        $this->assertEquals( 'ISO-8859-1', $header->fileNameCharSet );
    }

    public function testMailAddressSetState()
    {
        $address = ezcMailAddress::__set_state( array(
                         'email' => 'nospam@example.com',
                         'name' => 'No Spam'
                ) );
        $this->assertEquals( 'nospam@example.com', $address->email );
        $this->assertEquals( 'No Spam', $address->name );
    }

    /**
     * Test for issue #16154: Bcc headers are not stripped when using SMTP
     */
    public function testKeepBccHeader()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( "nospam@ez.no", "No Spam 1" );
        $mail->addTo( new ezcMailAddress( "alex.stanoi@gmail.com", "No Spam 2" ) );
        $mail->addBcc( new ezcMailAddress( "as@ez.no", "No Spam 3" ) );
        $mail->subject = __FUNCTION__; 
 
        $source = $mail->generate();

        // Assert that the mail source contains the Bcc header
        $this->assertNotEquals( false, strpos( $source, "Bcc: " ) );
    }

    /**
     * Test for issue #16154: Bcc headers are not stripped when using SMTP
     */
    public function testStripBccHeader()
    {
        $options = new ezcMailOptions();
        $options->stripBccHeader = true;
        $mail = new ezcMail( $options );
        $mail->from = new ezcMailAddress( "nospam@ez.no", "No Spam 1" );
        $mail->addTo( new ezcMailAddress( "alex.stanoi@gmail.com", "No Spam 2" ) );
        $mail->addBcc( new ezcMailAddress( "as@ez.no", "No Spam 3" ) );
        $mail->subject = __FUNCTION__; 

        $source = $mail->generate();

        // Assert that the mail source doesn't contain the Bcc header
        $this->assertEquals( false, strpos( $source, "Bcc: " ) );
    }

    public function testIsSet()
    {
        $mail = new ezcMail();
        $mail->generateBody();
        $mail->generateHeaders();
        $this->assertEquals( true, isset( $mail->headers ) );
        $this->assertEquals( false, isset( $mail->contentDisposition ) );
        $this->assertEquals( true, isset( $mail->to ) );
        $this->assertEquals( true, isset( $mail->cc ) );
        $this->assertEquals( true, isset( $mail->bcc ) );
        $this->assertEquals( false, isset( $mail->from ) );
        $this->assertEquals( false, isset( $mail->subject ) );
        $this->assertEquals( true, isset( $mail->subjectCharset ) );
        $this->assertEquals( false, isset( $mail->body ) );
        $this->assertEquals( false, isset( $mail->messageId ) );
        $this->assertEquals( false, isset( $mail->messageID ) );
        $this->assertEquals( true, isset( $mail->timestamp ) );
        $this->assertEquals( false, isset( $mail->no_such_property ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailTest" );
    }
}
?>

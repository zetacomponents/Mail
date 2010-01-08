<?php
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
class TestMultipart extends ezcMailMultipart
{
    public function __construct()
    {
        parent::__construct( func_get_args() );
    }

    public function addPart( $part )
    {
        $this->parts[] = $part;
    }

    public function multipartType()
    {
        return 'Test';
    }
}

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailMultipartTest extends ezcTestCase
{
    private $multipart;

	protected function setUp()
	{
        $this->multipart = new TestMultipart();
	}

    /**
     * Tests if the properties work correctly
     */
    public function testProperties()
    {
        $this->assertSetPropertyFails( $this->multipart, "does_not_exist", array( 42 )  );
        $this->assertSetProperty( $this->multipart, "boundary", array( "testvalue" ) );
        try
        {
            $this->multipart->does_not_exist;
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    /**
     * Test if the ContentType header is correctly set
     */
    public function testContentType()
    {
        $this->multipart->boundary = "LeChuck";
        $expected = 'multipart/Test; boundary="LeChuck"';
        $this->assertEquals( $expected, $this->multipart->getHeader( 'Content-Type' ) );
    }

    /**
     * Tests if creating two Multiparts in rapid succession creates different boundaries
     */
    public function testMultipleBoundaries()
    {
        $part1 = new TestMultipart();
        $part2 = new TestMultipart();
        if ( $part1->boundary === $part2->boundary )
        {
            $this->fail( "Multiparts got same boundary" );
        }
    }

    /**
     * Tests generating a multipart
     */
    public function testGenerate()
    {
        $this->multipart->addPart( new ezcMailText( "Look behind you, a three headed monkey!." ) );
        $this->multipart->addPart( new ezcMailText( "Ask me about Loom(tm)" ) );
        $this->multipart->addPart( new ezcMailText( "Whew, a rubber tree" ) );

        $this->multipart->boundary = "pirate";

        $this->assertEquals( trim( file_get_contents( dirname( __FILE__) . "/data/ezcMailMultiPartTest_testGenerate.data" ) ),
                             $this->multipart->generate() );
    }

    /**
     * Tests generating a multipart with custom "client does not understand MIME" message.
     *
     * Test for issue #13538: Add Option to disable noMimeMessage.
     */
    public function testGenerateCustomNoMimeMessage()
    {
        $this->multipart->addPart( new ezcMailText( "Look behind you, a three headed monkey!." ) );
        $this->multipart->addPart( new ezcMailText( "Ask me about Loom(tm)" ) );
        $this->multipart->addPart( new ezcMailText( "Whew, a rubber tree" ) );

        $this->multipart->boundary = "pirate";
        $this->multipart->noMimeMessage = "Denne meldingen er kult, men e-postklienten din forstår ikke MIME, så du kan ikke se det.";

        $this->assertEquals( trim( file_get_contents( dirname( __FILE__) . "/data/ezcMailMultiPartTest_customNoMimeMessage.data" ) ),
                             $this->multipart->generate() );
    }

    /**
     * Tests that the constructor can handle both array arguments and non-array arguments
     */
    public function testConstructor()
    {
        $this->multipart = new TestMultipart( new ezcMailText( "Look behind you, a three headed monkey!." ),
                                              array( new ezcMailText( "Ask me about Loom(tm)" ),
                                                     new ezcMailText( "Whew, a rubber tree" ) ) );

        $this->multipart->boundary = "pirate";

        $this->assertEquals( trim( file_get_contents( dirname( __FILE__) . "/data/ezcMailMultiPartTest_testGenerate.data" ) ),
                             $this->multipart->generate() );
    }

    // move to multipart specific subclasses if more methods like this arise.
    public function testGetParts()
    {
        $part = new ezcMailMultipartMixed;
        $part->appendPart( new ezcMailText( 'a' ) );
        $this->assertEquals( 1, count( $part->getParts() ) );

        $part = new ezcMailMultipartAlternative;
        $part->appendPart( new ezcMailText( 'a' ) );
        $this->assertEquals( 1, count( $part->getParts() ) );

        $part = new ezcMailMultipartRelated;
        $part->setMainPart( $main = new ezcMailText( 'a' ) );
        $this->assertEquals( $main, $part->getMainPart() );
        $part->addRelatedPart( new ezcMailText( 'a' ) );
        $part->addRelatedPart( new ezcMailText( 'a' ) );
        $this->assertEquals( 2, count( $part->getRelatedParts() ) );
    }

    public function testGetMultipartRelatedPartsEmpty()
    {
        $part = new ezcMailMultipartRelated();
        $this->assertEquals( null, $part->getMainPart() );
        $this->assertEquals( 0, count( $part->getRelatedParts() ) );
        $this->assertEquals( false, $part->getRelatedPartByID( 'no such id' ) );
    }

    public function testInvalidGetMultipartRelatedByID()
    {
        $part = new ezcMailMultipartRelated;
        $part->setMainPart( $main = new ezcMailText( 'a' ) );
        $this->assertEquals( $main, $part->getMainPart() );
        $part->addRelatedPart( new ezcMailText( 'a' ) );
        $this->assertEquals( false, $part->getRelatedPartByID( 'no such id' ) );
    }

    public function testGetMultipartRelatedWithoutMain()
    {
        $part = new ezcMailMultipartRelated;
        $part->addRelatedPart( new ezcMailText( 'a' ) );
        $this->assertEquals( 1, count( $part->getRelatedParts() ) );
    }

    public function testMultipartReportFetchParts()
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
        $this->assertEquals( "delivery-status", $mail->body->reportType );
        $msg = $mail->generate();
        $set = new ezcMailVariableSet( $msg );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( null, true );
        $expected = array( 'ezcMailText',
                           'ezcMailDeliveryStatus',
                           'ezcMailText'
                         );
        $this->assertEquals( 3, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    public function testMultipartReportEmpty()
    {
        $report = new ezcMailMultipartReport();
        $this->assertEquals( null, $report->getReadablePart() );
        $this->assertEquals( null, $report->getMachinePart() );
        $this->assertEquals( null, $report->getOriginalPart() );
    }

    public function testIsSet()
    {
        $part = new ezcMailMultipartRelated();
        $this->assertEquals( true, isset( $part->boundary ) );
        $this->assertEquals( false, isset( $part->no_such_property ) );

        $part = new ezcMailMultipartReport();
        $this->assertEquals( true, isset( $part->reportType ) );
        $this->assertEquals( false, isset( $part->no_such_property ) );
    }

    public function testDeliveryStatusProperties()
    {
        $part = new ezcMailDeliveryStatus();
        $this->assertEquals( true, isset( $part->message ) );
        $this->assertEquals( true, isset( $part->recipients ) );
        $this->assertEquals( false, isset( $part->no_such_property ) );

        try
        {
            $part->no_such_property = "";
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailMultipartTest" );
    }
}
?>

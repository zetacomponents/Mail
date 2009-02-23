<?php
declare(encoding="latin1");
/**
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
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
class ezcMailTextTest extends ezcTestCase
{
    private $part;

	protected function setUp()
	{
        $this->part = new ezcMailText( "dummy" );
	}

    /**
     * Test that the constuctor eats parameters like it should
     */
    public function testConstructor()
    {
        $this->part = new ezcMailText( "TestText", "ISO-String", ezcMail::BASE64 );
        $this->assertEquals( "TestText", $this->part->text );
        $this->assertEquals( "ISO-String", $this->part->charset );
        $this->assertEquals( ezcMail::BASE64, $this->part->encoding );
    }

    /**
     * Tests if headers are generated as expected by the TextPart.
     * It should include both extra headers set manually and content type
     * and encoding headers
     */
    public function testGenerateHeaders()
    {
        $expectedResult = "X-Extra: Test" . ezcMailTools::lineBreak() .
                          "Content-Type: text/plain; charset=us-ascii" . ezcMailTools::lineBreak() .
                          "Content-Transfer-Encoding: 8bit" . ezcMailTools::lineBreak();

        $this->part->setHeader( "X-Extra", "Test" );
        $this->assertEquals( $expectedResult, $this->part->generateHeaders() );
    }

    /**
     * Tests for properties
     */
    public function testGetProperties()
    {
        $temp = new ezcMailText( 'dummy', 'utf-8', ezcMail::EIGHT_BIT, 'iso-8859-2' );
        $this->assertEquals( 'utf-8', $temp->charset );
        $this->assertEquals( 'iso-8859-2', $temp->originalCharset );
        $this->assertEquals( ezcMail::EIGHT_BIT, $temp->encoding );
        $this->assertEquals( 'plain', $temp->subType );
        $this->assertEquals( 'dummy', $temp->text );
        $this->assertEquals(
            new ezcMailHeadersHolder(),
            $temp->headers
        );
    }

    public function testSetProperties()
    {
        $temp = new ezcMailText( 'dummy', 'bogus', -1, 'iso-8859-2' );
        $temp->charset = 'utf-8';
        $temp->encoding = ezcMail::EIGHT_BIT;
        $temp->subType = 'html';
        $temp->text = 'new dummy';
        try
        {
            $temp->originalCharset = 'iso-8859-5';
            $this->fail( 'Expected exception not thrown' );
        }
        catch ( ezcBasePropertyPermissionException $e )
        {
            $this->assertEquals( "The property 'originalCharset' is read-only.", $e->getMessage() );
        }

        try
        {
            $temp->no_such_property = 'xxx';
            $this->fail( 'Expected exception was not thrown' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }

        $this->assertEquals( 'utf-8', $temp->charset );
        $this->assertEquals( 'iso-8859-2', $temp->originalCharset );
        $this->assertEquals( ezcMail::EIGHT_BIT, $temp->encoding );
        $this->assertEquals( 'html', $temp->subType );
        $this->assertEquals( 'new dummy', $temp->text );
    }

    public function testBase64Encode()
    {
        $reference = "Content-Type: text/plain; charset=us-ascii" . ezcMailTools::lineBreak() .
            "Content-Transfer-Encoding: base64" . ezcMailTools::lineBreak() .ezcMailTools::lineBreak() .
            "SGVyZSBpcyBzb21lIHRleHQ=" . ezcMailTools::lineBreak();
        $text = new ezcMailText( "Here is some text", "us-ascii", ezcMail::BASE64 );
        $this->assertEquals( $reference, $text->generate() );
    }

    public function testQuotedPrintableEncode()
    {
        $reference = "Content-Type: text/plain; charset=iso-8859-1" . ezcMailTools::lineBreak() .
            "Content-Transfer-Encoding: quoted-printable"  . ezcMailTools::lineBreak() . ezcMailTools::lineBreak() .
            "=E6=F8=E5=0A=F8=E6=E5";

        $text = new ezcMailText( "זרו\nרזו", "iso-8859-1", ezcMail::QUOTED_PRINTABLE );
        $this->assertEquals( $reference, $text->generate() );
    }

    public function testIsSet()
    {
        $this->assertEquals( true, isset( $this->part->charset ) );
        $this->assertEquals( true, isset( $this->part->originalCharset ) );
        $this->assertEquals( true, isset( $this->part->subType ) );
        $this->assertEquals( true, isset( $this->part->encoding ) );
        $this->assertEquals( true, isset( $this->part->text ) );
        $this->assertEquals( false, isset( $this->part->no_such_property ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailTextTest" );
    }
}
?>

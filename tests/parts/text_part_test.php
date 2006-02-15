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
class ezcMailTextTest extends ezcTestCase
{
    private $part;

	public function setUp()
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

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailTextTest" );
    }
}
?>

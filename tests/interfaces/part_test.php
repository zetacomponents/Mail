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
class MailPartTest extends ezcMailPart // Dummy implementation of class
{
    public function generateBody()
    {
        return "";
    }
}

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailPartTest extends ezcTestCase
{
    private $part;

	public function setUp()
	{
        $this->part = new MailPartTest();
	}

    /**
     * Tests the setHeader and getHeader methods
     */
    public function testSetAndGetHeader()
    {
        // check that it is empty before we start
        $this->assertEquals( "", $this->part->generateHeaders() );

        // set a header and check that we get the same back
        $this->part->setHeader( "To", "info@ez.no" );
        $this->assertEquals( "info@ez.no", $this->part->getHeader( "To" ) );

        // overwrite this one and check that is still set correctly
        $this->part->setHeader( "To", "fh@ez.no" );
        $this->assertEquals( "fh@ez.no", $this->part->getHeader( "To" ) );

        // set another one and check that it is correct as well
        $this->part->setHeader( "From", "pkej@ez.no" );
        $this->assertEquals( "pkej@ez.no", $this->part->getHeader( "From" ) );
    }

    /**
     * Tests that generateHeaders is generating headers according to
     * rfc822.
     */
    public function testGenerateHeaders()
    {
        $expectedResult = "To: info@ez.no" . ezcMailTools::lineBreak() .
                          "From: pkej@ez.no" . ezcMailTools::lineBreak() .
                          "Cc: ccer@ez.no" .ezcMailTools::lineBreak() .
                          "Bcc: bccer@ez.no" .ezcMailTools::lineBreak();
        $this->part->setHeader( "To", "info@ez.no" );
        $this->part->setHeader( "From", "pkej@ez.no" );
        $this->part->setHeader( "Cc", "ccer@ez.no" );
        $this->part->setHeader( "Bcc", "bccer@ez.no" );
        $this->assertEquals( $expectedResult, $this->part->generateHeaders() );
    }

    /**
     * Check that it is possible to exlucde headers with appendExcludeHeaders
     */
    public function testGenerateHeadersWithExclude()
    {
        $expectedResult = "From: pkej@ez.no" . ezcMailTools::lineBreak();
        $this->part->setHeader( "To", "info@ez.no" );
        $this->part->setHeader( "From", "pkej@ez.no" );
        $this->part->appendExcludeHeaders( array( "To" ) );
        $this->assertEquals( $expectedResult, $this->part->generateHeaders() );
    }

    /**
     * Check that it is possible to exlucde headers with appendExcludeHeaders
     */
    public function testGenerateHeadersWithExcludeCaseDifference()
    {
        $expectedResult = "From: pkej@ez.no" . ezcMailTools::lineBreak();
        $this->part->setHeader( "To", "info@ez.no" );
        $this->part->setHeader( "From", "pkej@ez.no" );
        $this->part->appendExcludeHeaders( array( "to" ) );
        $this->assertEquals( $expectedResult, $this->part->generateHeaders() );
    }

    /**
     * Test generate method
     */
    public function testGenerate()
    {
        // same as testGenerateHeaders but with an additional linebreak
        $expectedResult = "To: info@ez.no" . ezcMailTools::lineBreak() .
                         "From: pkej@ez.no" . ezcMailTools::lineBreak() .
                         "Cc: ccer@ez.no" . ezcMailTools::lineBreak() .
                         "Bcc: bccer@ez.no" . ezcMailTools::lineBreak() .
                         ezcMailTools::lineBreak();
        $this->part->setHeader( "To", "info@ez.no" );
        $this->part->setHeader( "From", "pkej@ez.no" );
        $this->part->setHeader( "Cc", "ccer@ez.no" );
        $this->part->setHeader( "Bcc", "bccer@ez.no" );
        $this->assertEquals( $expectedResult, $this->part->generate() );
    }

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailPartTest" );
    }
}
?>

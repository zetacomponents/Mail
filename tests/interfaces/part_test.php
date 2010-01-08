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

	protected function setUp()
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

    public function testGetUnknownHeader()
    {
        $this->assertSame( '', $this->part->getHeader( "FromFrom" ) );
    }
    
    public function testSetHeaders()
    {
        // check that it is empty before we start
        $this->assertEquals( "", $this->part->generateHeaders() );
        $this->part->setHeader( "To", "info@ez.no" );

        $this->part->setHeaders( array( "To" => "test@example.com",
                                        "Cc" => "test@example.com" ) );
        $expectedResult = "To: test@example.com" . ezcMailTools::lineBreak() .
                          "Cc: test@example.com". ezcMailTools::lineBreak();
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

    public function testGenerateHeadersContentDispositionNoAddParams()
    {
        $expectedResult = "Content-Disposition: inline; filename=\"paragliding_rocks.txt\";".
            " creation-date=\"Sun, 21 May 2006 16:00:50 +0400\";" .
            " modification-date=\"Sun, 21 May 2006 16:00:51 +0400\";" .
            " read-date=\"Sun, 21 May 2006 16:00:52 +0400\";" .
            " size=1024".
            ezcMailTools::lineBreak();

        $cd = new ezcMailContentDispositionHeader( 'inline',
                                                   'paragliding_rocks.txt',
                                                   'Sun, 21 May 2006 16:00:50 +0400',
                                                   'Sun, 21 May 2006 16:00:51 +0400',
                                                   'Sun, 21 May 2006 16:00:52 +0400',
                                                   '1024'
                                                   );
        $this->part->contentDisposition = $cd;
        // var_dump( str_replace( "\r\n", "", $this->part->generateHeaders() ) );
        $this->assertEquals( trim( $expectedResult ), str_replace( ezcMailTools::lineBreak(), "", $this->part->generateHeaders() ) );

    }

    public function testGenerateHeadersContentDispositionAddParams()
    {
        $expectedResult = "Content-Disposition: inline; filename=\"paragliding_rocks.txt\";".
            " creation-date=\"Sun, 21 May 2006 16:00:50 +0400\";" .
            " modification-date=\"Sun, 21 May 2006 16:00:51 +0400\";" .
            " read-date=\"Sun, 21 May 2006 16:00:52 +0400\";" .
            " size=1024;".
            " x-glider=\"sport2\";".
            " x-speed=\"52\"".
            ezcMailTools::lineBreak();

        $cd = new ezcMailContentDispositionHeader( 'inline',
                                                   'paragliding_rocks.txt',
                                                   'Sun, 21 May 2006 16:00:50 +0400',
                                                   'Sun, 21 May 2006 16:00:51 +0400',
                                                   'Sun, 21 May 2006 16:00:52 +0400',
                                                   '1024',
                                                   array( 'x-glider' => 'sport2',
                                                          'x-speed' => '52' )
                                                   );
        $this->part->contentDisposition = $cd;
        $this->assertEquals( trim( $expectedResult ), str_replace( ezcMailTools::lineBreak(), "", $this->part->generateHeaders() ) );
    }

    public function testSetHeaderWithEncoding()
    {
        $this->part->setHeader( "X-Related-Movie", 'James Bond - Шпион, который меня любил', 'iso-8859-5' );
        $this->assertEquals( 'James Bond - Шпион, который меня любил', $this->part->getHeader( 'X-Related-Movie' ) );

        $expected = "X-Related-Movie: =?iso-8859-5?Q?James=20Bond?==?iso-8859-5?Q?=20?=" . ezcMailTools::lineBreak() .
                    " =?iso-8859-5?Q?-=20=D0=A8=D0=BF=D0=B8=D0=BE=D0=BD,=20=D0=BA=D0=BE=D1?=" . ezcMailTools::lineBreak() .
                    " =?iso-8859-5?Q?=82=D0=BE=D1=80=D1=8B=D0=B9=20=D0=BC=D0=B5=D0?=" . ezcMailTools::lineBreak() .
                    " =?iso-8859-5?Q?=BD=D1=8F=20=D0=BB=D1=8E=D0=B1=D0=B8=D0=BB?=" . ezcMailTools::lineBreak();

        $this->assertEquals( $expected, $this->part->generateHeaders() );
    }

    public function testSetHeadersWithEncoding()
    {
        $this->part->setHeaders( array( "X-Related-City" => array( "Moscow" ), "X-Related-Movie" => array( 'James Bond - Из России с любовью', 'iso-8859-5' ) ) );
        $this->assertEquals( 'Moscow', $this->part->getHeader( 'X-Related-City' ) );
        $this->assertEquals( 'James Bond - Из России с любовью', $this->part->getHeader( 'X-Related-Movie' ) );

        $expected = "X-Related-City: Moscow" . ezcMailTools::lineBreak() .
                    "X-Related-Movie: =?iso-8859-5?Q?James=20Bond=20-=20=D0=98=D0=B7=20?=" . ezcMailTools::lineBreak() .
                    " =?iso-8859-5?Q?=D0=A0=D0=BE=D1=81=D1=81=D0=B8=D0=B8=20=D1=81?=" . ezcMailTools::lineBreak() .
                    " =?iso-8859-5?Q?=20=D0=BB=D1=8E=D0=B1=D0=BE=D0=B2=D1=8C=D1=8E?=" . ezcMailTools::lineBreak();

        $this->assertEquals( $expected, $this->part->generateHeaders() );
    }

    public function testMockSetHeaderWithEncodingNoCharsetReturnDefault()
    {
        $part = $this->getMock( 'ezcMailPart', array( 'setHeaderCharset', 'generateBody' ), array() );

        $part->expects( $this->any() )
             ->method( 'setHeaderCharset' )
             ->will( $this->returnValue( false ) );

        $part->expects( $this->any() )
             ->method( 'generateBody' )
             ->will( $this->returnValue( false ) );

        $part->setHeader( "X-Related-Movie", 'James Bond - Шпион, который меня любил', 'iso-8859-5' );
        $this->assertEquals( 'James Bond - Шпион, который меня любил', $part->getHeader( 'X-Related-Movie' ) );

        $expected = "X-Related-Movie: James Bond - Шпион, который меня любил" . ezcMailTools::lineBreak();

        $this->assertEquals( $expected, $part->generateHeaders() );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailPartTest" );
    }
}
?>

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

	public function setUp()
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

        $this->assertEquals( file_get_contents( dirname( __FILE__) . "/data/ezcMailMultiPartTest_testGenerate.data" ),
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

        $this->assertEquals( file_get_contents( dirname( __FILE__) . "/data/ezcMailMultiPartTest_testGenerate.data" ),
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

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailMultipartTest" );
    }
}
?>

<?php
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
class ezcMailStreamFileTest extends ezcTestCase
{
    /**
     * Tests generating a complete ezcMailStreamFile
     */
    public function testGenerateBase64()
    {
        $filePart = new ezcMailStreamFile( "fly.jpg", fopen( dirname( __FILE__) . "/data/fly.jpg", "r" ) );
        $filePart->contentType = ezcMailFile::CONTENT_TYPE_IMAGE;
        $filePart->mimeType = "jpeg";
        // file_put_contents( dirname( __FILE__ ) . "/data/ezcMailFileTest_testGenerateBase64.data" );
        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . "/data/ezcMailFilePartTest_testGenerateBase64.data" ),
                             $filePart->generate() );
    }

    public function testIsSet()
    {
        $filePart = new ezcMailStreamFile( "fly.jpg", fopen( dirname( __FILE__) . "/data/fly.jpg", "r" ) );
        $this->assertEquals( true, isset( $filePart->stream ) );
        $this->assertEquals( false, isset( $filePart->no_such_property ) );
    } 

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailStreamFileTest" );
    }
}
?>

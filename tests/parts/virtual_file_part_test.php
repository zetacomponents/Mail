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
class ezcMailVirtualFileTest extends ezcTestCase
{
    /**
     * Tests generating a complete ezcMailVirtualFile
     */
    public function testGenerateBase64()
    {
        $filePart = new ezcMailVirtualFile( "fly.jpg", file_get_contents( dirname( __FILE__) . "/data/fly.jpg" ) );
        $filePart->contentType = ezcMailFile::CONTENT_TYPE_IMAGE;
        $filePart->mimeType = "jpeg";
        // file_put_contents( dirname( __FILE__ ) . "/data/ezcMailFileTest_testGenerateBase64.data" );
        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . "/data/ezcMailFilePartTest_testGenerateBase64.data" ),
                             $filePart->generate() );
    }

    public function testIsSet()
    {
        $filePart = new ezcMailVirtualFile( "fly.jpg", file_get_contents( dirname( __FILE__) . "/data/fly.jpg" ) );
        $this->assertEquals( true, isset( $filePart->contents ) );
        $this->assertEquals( false, isset( $filePart->no_such_property ) );
    } 

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailVirtualFileTest" );
    }
}
?>

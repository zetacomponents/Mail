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

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailVirtualFileTest" );
    }
}
?>

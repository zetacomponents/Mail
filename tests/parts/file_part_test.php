<?php
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */


/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailFileTest extends ezcTestCase
{
    /**
     * Tests generating a complete ezcMailFile
     */
    public function testGenerateBase64()
    {
        $filePart = new ezcMailFile( dirname( __FILE__) . "/data/fly.jpg" );
        $filePart->contentType = ezcMailFile::CONTENT_TYPE_IMAGE;
        $filePart->mimeType = "jpeg";
        // file_put_contents( dirname( __FILE__ ) . "/data/ezcMailFileTest_testGenerateBase64.data" );
        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . "/data/ezcMailFilePartTest_testGenerateBase64.data" ),
                             $filePart->generate() );
    }

    /**
     * Tries to load a ezcMailFile with an a non existant file.
     */
    public function testNoSuchFile()
    {
        try
        {
            $filePart = new ezcMailFile( dirname( __FILE__) . "/data/fly_not_exit.jpg" );
        }
        catch ( ezcBaseFileNotFoundException $e )
        {
            return;
        }
        $this->fail( "Invalid file failed or wrong exception thrown" );
    }

    public function testIsSet()
    {
        $filePart = new ezcMailFile( dirname( __FILE__) . "/data/fly.jpg" );
        $this->assertEquals( true, isset( $filePart->fileName ) );
        $this->assertEquals( true, isset( $filePart->mimeType ) );
        $this->assertEquals( true, isset( $filePart->contentType ) );
        $this->assertEquals( false, isset( $filePart->dispositionType ) );
        $this->assertEquals( false, isset( $filePart->contentId ) );
        $this->assertEquals( false, isset( $filePart->no_such_property ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailFileTest" );
    }
}
?>

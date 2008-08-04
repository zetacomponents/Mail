<?php
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

// special mail class which generates to "headers" and "body" only.
class DigestTestMail extends ezcMail
{
    public function generateHeaders()
    {
        return 'headers';
    }

    public function generateBody()
    {
        return 'body';
    }
}

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailRfc822DigestTest extends ezcTestCase
{
    public function testDefault()
    {
        $digest = new ezcMailRfc822Digest( new DigestTestMail() );
//        file_put_contents( dirname( __FILE__ ) . "/data/ezcMailRfc822DigestTest_testDefault.data", $digest->generate() );
        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . "/data/ezcMailRfc822DigestTest_testDefault.data" ),
                             $digest->generate() );
    }

    public function testProperties()
    {
        $digest = new ezcMailRfc822Digest( new DigestTestMail() );
        try
        {
            $digest->no_such_property = 'xxx';
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    public function testIsSet()
    {
        $digest = new ezcMailRfc822Digest( new DigestTestMail() );
        $this->assertEquals( true, isset( $digest->mail ) );
        $this->assertEquals( false, isset( $digest->no_such_property ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailRfc822DigestTest" );
    }
}
?>

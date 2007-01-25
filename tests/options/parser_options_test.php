<?php
/**
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogen//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailParserOptionsTest extends ezcTestCase
{
    public function testParserOptionsDefault()
    {
        $options = new ezcMailParserOptions();
        $this->assertEquals( 'ezcMail', $options->mailClass );
    }

    public function testParserOptionsSet()
    {
        $options = new ezcMailParserOptions();
        $options->mailClass = 'MyMailClass';
        $this->assertEquals( 'MyMailClass', $options->mailClass );
    }

/*  // wait until the mail parser has options to test
    public function testParserOptionsSetInvalid()
    {
        $options = new ezcMailParserOptions();
        try
        {
            // ...
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }
*/
    public function testParserOptionsSetNotExistent()
    {
        $options = new ezcMailParserOptions();
        try
        {
            $options->no_such_option = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }
    
    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailParserOptionsTest" );
    }
}
?>

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
class ezcMailTransportOptionsTest extends ezcTestCase
{
    public function testTransportOptionsDefault()
    {
        $options = new ezcMailTransportOptions();
        $this->assertEquals( 5, $options->timeout );
    }

    public function testTransportOptionsSet()
    {
        $options = new ezcMailTransportOptions();
        $options->timeout = 10;
        $this->assertEquals( 10, $options->timeout );
    }

    public function testTransportOptionsSetInvalid()
    {
        $options = new ezcMailTransportOptions();
        try
        {
            $options->timeout = 0;
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $options->timeout = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public function testTransportOptionsSetNotExistent()
    {
        $options = new ezcMailTransportOptions();
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
         return new PHPUnit_Framework_TestSuite( "ezcMailTransportOptionsTest" );
    }
}
?>

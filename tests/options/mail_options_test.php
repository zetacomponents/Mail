<?php
/**
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
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
class ezcMailOptionsTest extends ezcTestCase
{
    public function testMailOptionsDefault()
    {
        $options = new ezcMailOptions();
        $this->assertEquals( false, $options->stripBccHeader );
    }

    public function testMailOptionsSet()
    {
        $options = new ezcMailOptions();
        $options->stripBccHeader = true;
        $this->assertEquals( true, $options->stripBccHeader );
    }

    public function testMailOptionsSetInvalid()
    {
        $options = new ezcMailOptions();
        try
        {
            $options->stripBccHeader = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public function testMailOptionsSetNotExistent()
    {
        $options = new ezcMailOptions();
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
         return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }
}
?>
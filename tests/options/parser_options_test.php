<?php
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

require dirname( __FILE__ ) . '/classes/custom_classes.php';

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

    public function testParserOptionsSetDefault()
    {
        $options = new ezcMailParserOptions();
        $options->mailClass = 'ezcMail';
        $this->assertEquals( 'ezcMail', $options->mailClass );
    }

    public function testParserOptionsSet()
    {
        $options = new ezcMailParserOptions();
        $options->mailClass = 'myCustomMail';
        $this->assertEquals( 'myCustomMail', $options->mailClass );
    }

    public function testWrongCustomClassArgument()
    {
        try
        {
            $options = new ezcMailParserOptions();
            $options->mailClass = 1;
            self::fail( "Expected exception not thrown." );
        }
        catch ( ezcBaseValueException $e )
        {
            self::assertEquals( "The value '1' that you were trying to assign to setting 'mailClass' is invalid. Allowed values are: string that contains a class name.", $e->getMessage() );
        }
    }

    public function testWrongCustomClasses()
    {
        try
        {
            $options = new ezcMailParserOptions();
            $options->mailClass = 'myFaultyCustomMail';
            self::fail( "Expected exception not thrown." );
        }
        catch ( ezcBaseInvalidParentClassException $e )
        {
            self::assertEquals( "Class 'myFaultyCustomMail' does not exist, or does not inherit from the 'ezcMail' class.", $e->getMessage() );
        }
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

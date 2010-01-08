<?php
/**
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogen//
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
    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testParserOptionsMailClassDefault()
    {
        $options = new ezcMailParserOptions();
        $this->assertEquals( 'ezcMail', $options->mailClass );
    }

    public function testParserOptionsSetMailClassDefault()
    {
        $options = new ezcMailParserOptions();
        $options->mailClass = 'ezcMail';
        $this->assertEquals( 'ezcMail', $options->mailClass );
    }

    public function testParserOptionsSetMailClass()
    {
        $options = new ezcMailParserOptions();
        $options->mailClass = 'myCustomMail';
        $this->assertEquals( 'myCustomMail', $options->mailClass );
    }

    public function testWrongCustomClassArgumentMailClass()
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

    public function testWrongCustomClassesMailClass()
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

    public function testParserOptionsFileClassDefault()
    {
        $options = new ezcMailParserOptions();
        $this->assertEquals( 'ezcMailFile', $options->fileClass );
    }

    public function testParserOptionsSetFileClassDefault()
    {
        $options = new ezcMailParserOptions();
        $options->fileClass = 'ezcMailFile';
        $this->assertEquals( 'ezcMailFile', $options->fileClass );
    }

    public function testParserOptionsSetFileClass()
    {
        $options = new ezcMailParserOptions();
        $options->fileClass = 'myCustomFileClass';
        $this->assertEquals( 'myCustomFileClass', $options->fileClass );
    }

    public function testWrongCustomClassArgumentFileClass()
    {
        try
        {
            $options = new ezcMailParserOptions();
            $options->fileClass = 1;
            self::fail( "Expected exception not thrown." );
        }
        catch ( ezcBaseValueException $e )
        {
            self::assertEquals( "The value '1' that you were trying to assign to setting 'fileClass' is invalid. Allowed values are: string that contains a class name.", $e->getMessage() );
        }
    }

    public function testWrongCustomClassesFileClass()
    {
        try
        {
            $options = new ezcMailParserOptions();
            $options->fileClass = 'myFaultyCustomMail';
            self::fail( "Expected exception not thrown." );
        }
        catch ( ezcBaseInvalidParentClassException $e )
        {
            self::assertEquals( "Class 'myFaultyCustomMail' does not exist, or does not inherit from the 'ezcMailFile' class.", $e->getMessage() );
        }
    }

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
}
?>

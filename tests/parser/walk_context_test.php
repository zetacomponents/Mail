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
class ezcMailPartWalkContextTest extends ezcTestCase
{
    public function testProperties()
    {
        $context = new ezcMailPartWalkContext( array( 'WalkContextTestApp', 'saveMailPart' ) );
        $context->includeDigests = true;
        $context->level = 3;
        $context->filter = array( 'ezcMailFile' );
        $this->assertEquals( true, $context->includeDigests );
        $this->assertEquals( 3, $context->level );
        $this->assertEquals( array( 'ezcMailFile' ), $context->filter );
    }

    public function testPropertiesInvalid()
    {
        $context = new ezcMailPartWalkContext( array( 'WalkContextTestApp', 'saveMailPart' ) );
        try
        {
            $context->no_such_property = true;
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }

        try
        {
            $context->level = -1;
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $context->includeDigests = "yes";
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $test = $context->no_such_property;
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    public function testIsSet()
    {
        $context = new ezcMailPartWalkContext( array( 'WalkContextTestApp', 'saveMailPart' ) );
        $this->assertEquals( true, isset( $context->includeDigests ) );
        $this->assertEquals( true, isset( $context->filter ) );
        $this->assertEquals( true, isset( $context->level ) );
        $this->assertEquals( true, isset( $context->callbackFunction ) );
        $this->assertEquals( false, isset( $context->no_such_property ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailPartWalkContextTest" );
    }
}

/**
 * Test class.
 */
class WalkContextTestApp
{
    public static function saveMailPart()
    {
    }
}
?>

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
class ezcMailPop3TransportOptionsTest extends ezcTestCase
{
    public function testTransportOptionsDefault()
    {
        $options = new ezcMailPop3TransportOptions();
        $this->assertEquals( ezcMailPop3Transport::AUTH_PLAIN_TEXT, $options->authenticationMethod );
    }

    public function testTransportOptionsSet()
    {
        $options = new ezcMailPop3TransportOptions();
        $options->authenticationMethod = ezcMailPop3Transport::AUTH_APOP;
        $this->assertEquals( ezcMailPop3Transport::AUTH_APOP, $options->authenticationMethod );
    }

    public function testTransportOptionsSetInvalid()
    {
        $options = new ezcMailPop3TransportOptions();
        try
        {
            $options->authenticationMethod = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public function testTransportOptionsSetNotExistent()
    {
        $options = new ezcMailPop3TransportOptions();
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
         return new PHPUnit_Framework_TestSuite( "ezcMailPop3TransportOptionsTest" );
    }
}
?>

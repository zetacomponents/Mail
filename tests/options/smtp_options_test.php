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
class ezcMailSmtpTransportOptionsTest extends ezcTestCase
{
    public function testTransportOptionsDefault()
    {
        $options = new ezcMailSmtpTransportOptions();
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_PLAIN, $options->connectionType );
        $this->assertEquals( array(), $options->connectionOptions );
    }

    public function testTransportOptionsSet()
    {
        $options = new ezcMailSmtpTransportOptions();
        $options->connectionType = ezcMailSmtpTransport::CONNECTION_TLS;
        $options->connectionOptions = array( 'wrapper' => array( 'option' => 'value' ) );
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_TLS, $options->connectionType );
        $this->assertEquals( array( 'wrapper' => array( 'option' => 'value' ) ), $options->connectionOptions );
        $options->ssl = true;
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_SSL, $options->connectionType );
        $options->ssl = false;
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_PLAIN, $options->connectionType );
    }

    public function testTransportOptionsSetInvalid()
    {
        $options = new ezcMailSmtpTransportOptions();
        try
        {
            $options->connectionOptions = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $options->ssl = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public function testTransportOptionsSetNotExistent()
    {
        $options = new ezcMailSmtpTransportOptions();
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
         return new PHPUnit_Framework_TestSuite( "ezcMailSmtpTransportOptionsTest" );
    }
}
?>

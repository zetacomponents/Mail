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
/*  // wait until SMTP has options to test
    public function testTransportOptionsDefault()
    {
        $options = new ezcMailSmtpTransportOptions();
        // ...
    }

    public function testTransportOptionsSet()
    {
        $options = new ezcMailSmtpTransportOptions();
        // ...
    }
    public function testTransportOptionsSetInvalid()
    {
        $options = new ezcMailSmtpTransportOptions();
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

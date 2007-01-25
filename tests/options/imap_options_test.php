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
class ezcMailImapTransportOptionsTest extends ezcTestCase
{
/*  // wait until IMAP has options to test
    public function testTransportOptionsDefault()
    {
        $options = new ezcMailImapTransportOptions();
        // ...
    }

    public function testTransportOptionsSet()
    {
        $options = new ezcMailImapTransportOptions();
        // ...
    }
    public function testTransportOptionsSetInvalid()
    {
        $options = new ezcMailImapTransportOptions();
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
        $options = new ezcMailImapTransportOptions();
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
         return new PHPUnit_Framework_TestSuite( "ezcMailImapTransportOptionsTest" );
    }
}
?>

<?php
/**
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportPop3Test extends ezcTestCase
{

	public function setUp()
	{
	}

    public function testTest()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no", "ezcomponents", "ezcomponents" );
        $set = $pop3->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
    }

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailTransportPop3Test" );
    }
}
?>

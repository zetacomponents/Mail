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
class ezcMailTransportVariableTest extends ezcTestCase
{
    public static function suite()
    {
         return new ezcTestSuite( "ezcMailTransportVariableTest" );
    }

    public function testOneLine()
    {
        $reference = "Line1";
        $set = new ezcMailVariableSet( $reference );
        $result = '';

        $line = $set->getNextLine();
        while( $line !== null )
        {
            $result .= $line;
            $line = $set->getNextLine();
        }
        $this->assertEquals( $reference, $result );
        $this->assertEquals( false, $set->nextMail() );
    }

    public function testMultiLineCRLF()
    {
        $reference = "Line1\r\nLine2";
        $set = new ezcMailVariableSet( $reference );
        $result = '';

        $line = $set->getNextLine();
        while( $line !== null )
        {
            $result .= $line;
            $line = $set->getNextLine();
        }
        $this->assertEquals( $reference, $result );
        $this->assertEquals( false, $set->nextMail() );
    }

    public function testMultiLineLF()
    {
        $reference = "Line1\nLine2";
        $set = new ezcMailVariableSet( $reference );
        $result = '';

        $line = $set->getNextLine();
        while( $line !== null )
        {
            $result .= $line;
            $line = $set->getNextLine();
        }
        $this->assertEquals( $reference, $result );
        $this->assertEquals( false, $set->nextMail() );
    }
}
?>

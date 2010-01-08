<?php
/**
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
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
         return new PHPUnit_Framework_TestSuite( "ezcMailTransportVariableTest" );
    }

    public function testOneLine()
    {
        $reference = "Line1\n";
        $input = "Line1";
        $set = new ezcMailVariableSet( $input );
        $result = '';

        $line = $set->getNextLine();
        while ( $line !== null )
        {
            $result .= $line;
            $line = $set->getNextLine();
        }
        $this->assertEquals( $reference, $result );
        $this->assertEquals( false, $set->nextMail() );
    }

    public function testMultiLineCRLF()
    {
        $input = "Line1\r\nLine2";
        $reference = "Line1\nLine2\n";
        $set = new ezcMailVariableSet( $input );
        $result = '';

        $line = $set->getNextLine();
        while ( $line !== null )
        {
            $result .= $line;
            $line = $set->getNextLine();
        }
        $this->assertEquals( $reference, $result );
        $this->assertEquals( false, $set->nextMail() );
    }

    public function testMultiLineLF()
    {
        $reference = "Line1\nLine2\n";
        $input = "Line1\nLine2";
        $set = new ezcMailVariableSet( $input );
        $result = '';

        $line = $set->getNextLine();
        while ( $line !== null )
        {
            $result .= $line;
            $line = $set->getNextLine();
        }
        $this->assertEquals( $reference, $result );
        $this->assertEquals( false, $set->nextMail() );
    }

    public function testFromProcMail()
    {
        $mail_msg = file_get_contents( dirname( __FILE__ ) . '/data/test-variable' );
        $set = new ezcMailVariableSet( $mail_msg );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        // check that we have no extra linebreaks
        $this->assertEquals( "notdisclosed@mydomain.com", $mail[0]->from->email );
    }
}
?>

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
 * TODO: Test with illogical ordering!
 * @package Mail
 * @subpackage Tests
 */
class ezcMailRfc2231ImplementationTest extends ezcTestCase
{
    public function testParseHeaderDefault()
    {
        $reference = array( 'message/external-body', array( 'access-type' => array( 'value' => 'URL' ) ) );
        $input = "message/external-body; access-type=URL";
        $result = ezcMailRfc2231Implementation::parseHeader( $input );
        $this->assertEquals( $reference, $result );
    }

    public function testParseHeaderMultiParamSingleFold()
    {
        $reference = array( 'message/external-body',
                            array( 'access-type' => array( 'value' => 'URL' ),
                                   'URL' => array( 'value' => 'ftp://cs.utk.edu/pub/moore/bulk-mailer/bulk-mailer.tar' ) ) );
        $input = "message/external-body; access-type=URL; URL*0=\"ftp://\"; URL*1=\"cs.utk.edu/pub/moore/bulk-mailer/bulk-mailer.tar\"";
        $result = ezcMailRfc2231Implementation::parseHeader( $input );
        $this->assertEquals( $reference, $result );
    }

    public function testParseHeaderMultiParamMultiFold()
    {
        $reference = array( 'message/external-body',
                            array( 'access-type' => array( 'value' => 'URL' ),
                                   'URL' => array( 'value' => 'ftp://cs.utk.edu/pub/moore/bulk-mailer/bulk-mailer.tar' ) ) );
        $input = "message/external-body; access-type=URL; URL*0=\"ftp://\"; URL*1=\"cs.utk.edu/pub/moore/bulk-\"; URL*2=\"mailer/bulk-mailer.tar\" ";
        $result = ezcMailRfc2231Implementation::parseHeader( $input );
        $this->assertEquals( $reference, $result );
    }

    public function testParseHeaderLangOnly()
    {
        $reference = array( "application/x-stuff", array( 'title' => array( 'value' => 'This text is in english!',
                                                                            'language' => 'en-us' ) ) );
        $input = "application/x-stuff; title*='en-us'This text is in english!";
        $result = ezcMailRfc2231Implementation::parseHeader( $input );
        $this->assertEquals( $reference, $result );
    }

    public function testParseHeaderCharsetOnly()
    {
        $reference = array( "application/x-stuff", array( 'title' => array( 'value' => 'This text is in english!',
                                                                            'charset' => 'us-ascii' ) ) );
        $input = "application/x-stuff; title*=us-ascii''This text is in english!";
        $result = ezcMailRfc2231Implementation::parseHeader( $input );
        $this->assertEquals( $reference, $result );
    }

    public function testParseHeaderLangAndCharSet()
    {
        $input = "Content-Type: application/x-stuff; title*=us-ascii'en-us'This%20is%20%2A%2A%2Afun%2A%2A%2A";
        $input = "Content-Type: application/x-stuff; title*='en-us'This%20is%20%2A%2A%2Afun%2A%2A%2A";
//        $result = ezcMailRfc2231Implementation::parseHeader( $input );
    }

    public function testParseHeaderLangAndCharSetAndFold()
    {
//        $input = "message/external-body; access-type=URL; URL*0*=\"ftp://\"; URL*1=\"cs.utk.edu/pub/moore/bulk-mailer/bulk-mailer.tar\"";
//        $result = ezcMailRfc2231Implementation::parseHeader( $input );
    }

    public static function suite()
    {
        return new ezcTestSuite( "ezcMailRfc2231ImplementationTest" );
    }
}

?>

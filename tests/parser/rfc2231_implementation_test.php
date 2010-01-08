<?php
declare(encoding="latin1");
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

    public function testParseHeaderUnorderedParameterOrder()
    {
        $reference = array( 'message/external-body',
                            array( 'access-type' => array( 'value' => 'URL' ),
                                   'URL' => array( 'value' => 'ftp://cs.utk.edu/pub/moore/bulk-mailer/bulk-mailer.tar' ) ) );
        $input = "message/external-body; access-type=URL; URL*1=\"cs.utk.edu/pub/moore/bulk-\"; URL*0=\"ftp://\"; URL*2=\"mailer/bulk-mailer.tar\" ";
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
        $reference = array( "application/x-stuff", array( 'title' => array( 'value' => 'This text is in english!',
                                                                            'charset' => 'us-ascii',
                                                                            'language' => 'en-us' ) ) );
        $input = "application/x-stuff; title*=us-ascii'en-us'This text is in english!";
        $result = ezcMailRfc2231Implementation::parseHeader( $input );
        $this->assertEquals( $reference, $result );
    }

    public function testParseHeaderLangAndCharSetAndFold()
    {
        $reference = array( "application/x-stuff", array( 'title' => array( 'value' => 'This text is in english and has a fold',
                                                                            'charset' => 'us-ascii',
                                                                            'language' => 'en-us' ) ) );
        $input = "application/x-stuff; title*0*=us-ascii'en-us'This text is in english; title*1=\" and has a fold\"";
        $result = ezcMailRfc2231Implementation::parseHeader( $input );
        $this->assertEquals( $reference, $result );
    }

    public function testParseHeaderIso88691Encoding()
    {
        $reference = array( "attachment", array( 'filename' => array( 'value' => 'bølle.txt',
                                                                            'charset' => 'iso-8859-1' ) ) );
        $input = "attachment; filename*=\"iso-8859-1''b%F8lle%2Etxt\"";
        $result = ezcMailRfc2231Implementation::parseHeader( $input );
        $this->assertEquals( $reference, $result );
    }

   public function testParseHeaderIso88691EncodingFold()
    {
        $reference = array( "attachment", array( 'filename' => array( 'value' => 'bølle.txt.tar.gz',
                                                                            'charset' => 'iso-8859-1' ) ) );
        $input = "attachment; filename*0*=\"iso-8859-1''b%F8lle%2Etxt\"; filename*1*=\"%2Etar%2Egz\"";
        $result = ezcMailRfc2231Implementation::parseHeader( $input );
        $this->assertEquals( $reference, $result );
    }


    public function testParseContentDispositionNew()
    {
        $input = "attachment; filename=\"test.gif\"; creation-date=\"Wed, 12 Feb 1997 16:29:51 -0500\"; modification-date=\"Wed, 12 Feb 1997 16:29:51 -0500\"; read-date=\"Wed, 12 Feb 1997 16:29:51 -0500\"; size=5423; hendrix=l33t";

        $cd = ezcMailRfc2231Implementation::parseContentDisposition( $input );
        $this->assertEquals( "attachment", $cd->disposition );
        $this->assertEquals( "test.gif", $cd->fileName );
        $this->assertEquals( "Wed, 12 Feb 1997 16:29:51 -0500", $cd->creationDate );
        $this->assertEquals( "Wed, 12 Feb 1997 16:29:51 -0500", $cd->modificationDate );
        $this->assertEquals( "Wed, 12 Feb 1997 16:29:51 -0500", $cd->readDate );
        $this->assertEquals( "5423", $cd->size );
    }

    public function testParseContentDispositionFileNameLangCharset()
    {
        $input = "attachment; filename*0*=\"iso-8859-1'no'b%F8lle%2Etxt\"; filename*1*=\"%2Etar%2Egz\"";
        $result = ezcMailRfc2231Implementation::parseContentDisposition( $input );
        $this->assertEquals( 'bølle.txt.tar.gz', $result->fileName, "File name failed" );
        $this->assertEquals( 'iso-8859-1', $result->fileNameCharSet, "Character set failed" );
        $this->assertEquals( 'no', $result->fileNameLanguage, "Language failed" );
    }

    public function testParseContentDispositionAdditionalParametersLangCharSet()
    {
        $input = "attachment; filename*0*=\"iso-8859-1'no'b%F8lle%2Etxt\"; filename*1*=\"%2Etar%2Egz\"; murka*=\"iso-8859-1'no'b%F8lle%2Etxt\"";
        $result = ezcMailRfc2231Implementation::parseContentDisposition( $input );
        $this->assertEquals( 'bølle.txt.tar.gz', $result->fileName );
        $this->assertEquals( 'iso-8859-1', $result->fileNameCharSet );
        $this->assertEquals( 'no', $result->fileNameLanguage );

        $this->assertEquals( 'bølle.txt', $result->additionalParameters['murka'] );
        $this->assertEquals( 'iso-8859-1', $result->additionalParametersMetaData['murka']['charSet'] );
        $this->assertEquals( 'no', $result->additionalParametersMetaData['murka']['language'] );
    }


    public function testParseContentDispositionReuse()
    {
        $cd = new ezcMailContentDispositionHeader();
        $cd->fileName = "obsession.txt";
        $input = "attachment; creation-date=\"Wed, 12 Feb 1997 16:29:51 -0500\"; modification-date=\"Wed, 12 Feb 1997 16:29:51 -0500\"; read-date=\"Wed, 12 Feb 1997 16:29:51 -0500\"; size=5423; hendrix=l33t";
        $cd = ezcMailRfc2231Implementation::parseContentDisposition( $input, $cd );
        $this->assertEquals( "obsession.txt", $cd->fileName );
    }


    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( "ezcMailRfc2231ImplementationTest" );
    }
}

?>

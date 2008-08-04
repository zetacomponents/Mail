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
class ezcMailTransportMboxTest extends ezcTestCase
{
    public function testFetchMailEmptyMbox()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/empty.mbox" );
        $set = $mbox->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mail ) );
    }

    public function testFetchMailEmptyMboxNoHeader()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/empty-no-header.mbox" );
        $set = $mbox->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mail ) );
    }

    public function testFetchMailMboxHeader()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/one-mail.mbox" );
        $set = $mbox->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( "[xdebug-general] Re: Vim foldexpr for text profile output", $mail[0]->subject );
    }

    public function testFetchMailMboxNoHeader()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/one-mail-no-header.mbox" );
        $set = $mbox->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( "[xdebug-general] Re: Vim foldexpr for text profile output", $mail[0]->subject );
    }

    public function testFetchMailFromUnreadableMbox()
    {
        $tempDir = $this->createTempDir( 'ezcMailTransportMboxTest' );
        $fileName = $tempDir . "/test-unreadable-mbox.mbox";
        $fileHandle = fopen( $fileName, "wb" );
        fwrite( $fileHandle, "some contents" );
        fclose( $fileHandle );
        chmod( $fileName, 0 );
        try
        {
            $mbox = new ezcMailMboxTransport( realpath( $fileName ) );
            $this->removeTempDir();
            $this->fail( "Didn't get exception when expected." );
        }
        catch ( ezcBaseFilePermissionException $e )
        {
            $this->removeTempDir();
        }
    }

    public function testFetchMail()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/test-mbox" );
        $set = $mbox->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 2, count( $mail ) );
        $this->assertEquals( "[PHP-CVS] cvs: php-src /ext/ftp ftp.c  /ext/mhash mhash.c  /ext/soap php_encoding.c  /ext/standard basic_functions.c streamsfuncs.c string.c", $mail[0]->subject );
        $this->assertEquals( "[PHP-DEV] PHP 5.1.3RC2 Released", $mail[1]->subject );
    }

    public function testFetchMail2()
    {
        $set = new ezcMailMboxSet( fopen( dirname( __FILE__ ) . "/data/test-mbox", 'rt' ), array( 0 => 12053 ) );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( "[PHP-DEV] PHP 5.1.3RC2 Released", $mail[0]->subject );
    }

    public function testFetchMail3()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/test-mbox" );
        $set = $mbox->fetchByMessageNr( 1 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( "[PHP-DEV] PHP 5.1.3RC2 Released", $mail[0]->subject );
    }

    public function testFetchMail4()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/test-mbox" );
        try
        {
            $set = $mbox->fetchByMessageNr( -1 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailNoSuchMessageException $e )
        {
            $this->assertEquals( "The message with ID '-1' could not be found.", $e->getMessage() );
        }

        try
        {
            $set = $mbox->fetchByMessageNr( 3 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailNoSuchMessageException $e )
        {
            $this->assertEquals( "The message with ID '3' could not be found.", $e->getMessage() );
        }
    }

    public function testFetchMail5()
    {
        $dirname = dirname( __FILE__ );
        try
        {
            $mbox = new ezcMailMboxTransport( $dirname . "/data/not-here-at-all" );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcBaseFileNotFoundException $e )
        {
            $this->assertEquals( "The mbox file '{$dirname}/data/not-here-at-all' could not be found.", $e->getMessage() );
        }
    }

    public function testfetchFromOffset1()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/testlimit-mbox" );
        try
        {
            $set = $mbox->fetchFromOffset( -1, 10 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailOffsetOutOfRangeException $e )
        {
            $this->assertEquals( "The offset '-1' is outside of the message subset '-1', '10'.", $e->getMessage());
        }
    }

    public function testfetchFromOffset2()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/testlimit-mbox" );
        try
        {
            $set = $mbox->fetchFromOffset( 10, 1 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailOffsetOutOfRangeException $e )
        {
            $this->assertEquals( "The offset '10' is outside of the message subset '10', '1'.", $e->getMessage() );
        }
    }

    public function testfetchFromOffset3()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/testlimit-mbox" );
        try
        {
            $set = $mbox->fetchFromOffset( 0, -1 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailInvalidLimitException $e )
        {
            $this->assertEquals( "The message count '-1' is not allowed for the message subset '0', '-1'.", $e->getMessage() );
        }
    }

    public function testfetchFromOffset4()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/testlimit-mbox" );
        $set = $mbox->fetchFromOffset( 0, 10 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 9, count( $mail ) );
        $this->assertEquals( "[svn-components] 3263 - docs/guidelines [eZComponents: Docs]", $mail[8]->subject );
    }

    public function testfetchFromOffset5()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/testlimit-mbox" );
        $set = $mbox->fetchFromOffset( 0, 0 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 9, count( $mail ) );
        $this->assertEquals( "[svn-components] 3263 - docs/guidelines [eZComponents: Docs]", $mail[8]->subject );
    }

    public function testBrokenFilePointer()
    {
        try
        {
            $set = new ezcMailMboxSet( false, array() );
            self::fail( "Expected exception not thrown" );
        }
        catch ( ezcBaseFileIoException $e )
        {
            self::assertEquals( "An error occurred while reading from 'filepointer'. (The passed filepointer is not a stream resource.)", $e->getMessage() );
        }
    }

    public function testFetchMailTabsInHeaders()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/tab.mbox" );
        $set = $mbox->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $expected = "Re: [Agavi-Dev] [Agavi-Users] Advanced Layout/Layers example (was: Re: IMPORTANT: Breaking changes in 0.11 branch: tons of new features!)";
        $this->assertEquals( $expected, $mail[0]->subject );
        $expected = "<http://example.org/mailman/listinfo/dev>, <mailto:dev-request@example.org?subject=subscribe>";
        $this->assertEquals( $expected, $mail[0]->getHeader( "List-Subscribe" ) );
    }

    public function testFetchMailUnknownCharsets()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/unknown-charsets.mbox" );
        $set = $mbox->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( "x-user-defined", $mail[0]->body->originalCharset );
        $this->assertEquals( "utf-8", $mail[0]->body->charset );
        $this->assertEquals( "Tämä on testiöö1", trim( $mail[0]->body->text ) );
        $this->assertEquals( "unknown-8bit", $mail[1]->body->originalCharset );
        $this->assertEquals( "utf-8", $mail[1]->body->charset );
        $this->assertEquals( "Tämä on testiöö2", trim( $mail[1]->body->text ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailTransportMboxTest" );
    }
}
?>

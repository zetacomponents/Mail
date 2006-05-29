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
class ezcMailTransportMboxTest extends ezcTestCase
{
    public function testFetchMailFromBrokenMbox()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/../parser/data/various/test-filename-with-space" );
        $set = $mbox->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mail ) );
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
            $this->assertEquals( 'The message with ID <-1> could not be found.', $e->getMessage() );
        }

        try
        {
            $set = $mbox->fetchByMessageNr( 3 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailNoSuchMessageException $e )
        {
            $this->assertEquals( 'The message with ID <3> could not be found.', $e->getMessage() );
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
            $this->assertEquals( "The mbox file <$dirname/data/not-here-at-all> could not be found.", $e->getMessage() );
        }
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
            self::assertEquals( "An error occurred while reading from <filepointer>. (The passed filepointer is not a stream resource.)", $e->getMessage() );
        }
    }

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailTransportMboxTest" );
    }
}
?>

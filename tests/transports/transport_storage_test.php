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
class ezcMailTransportStorageTest extends ezcTestCase
{
    private static $sizes = array();

    public static function suite()
    {
        self::$sizes = array( 1539, 64072, 1696, 1725 );

        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testImapMessageSource()
    {
        $transport = new ezcMailImapTransport( "mta1.ez.no" );
        $transport->authenticate( "ezcomponents@mail.ez.no", "ezcomponents" );
        $transport->selectMailbox( "Inbox" );
        $parser = new ezcMailParser();

        $set = new ezcMailStorageSet( $transport->fetchByMessageNr( 1 ), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();

        $source = file_get_contents( $files[0] );
        $this->assertEquals( self::$sizes[0], strlen( $source ) );
    }

    public function testImapMessageSourceFetchAll()
    {
        $transport = new ezcMailImapTransport( "mta1.ez.no" );
        $transport->authenticate( "ezcomponents@mail.ez.no", "ezcomponents" );
        $transport->selectMailbox( "Inbox" );
        $parser = new ezcMailParser();

        $set = new ezcMailStorageSet( $transport->fetchAll(), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();

        $source = file_get_contents( $files[0] );
        $this->assertEquals( self::$sizes[0], strlen( $source ) );
    }

    public function testImapMessageSourceEmptySet()
    {
        $transport = new ezcMailImapTransport( "mta1.ez.no" );
        $transport->authenticate( "ezcomponents@mail.ez.no", "ezcomponents" );
        $transport->createMailbox( "Guybrush" );
        $transport->selectMailbox( "Guybrush" );
        $parser = new ezcMailParser();

        $set = new ezcMailStorageSet( $transport->fetchAll(), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( array(), $mail );
        $transport->selectMailbox( "Inbox" );
        $transport->deleteMailbox( "Guybrush" );
    }

    public function testPop3MessageSource()
    {
        $transport = new ezcMailPop3Transport( "mta1.ez.no" );
        $transport->authenticate( "ezcomponents@mail.ez.no", "ezcomponents" );
        $parser = new ezcMailParser();

        $set = new ezcMailStorageSet( $transport->fetchByMessageNr( 1 ), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();

        $source = file_get_contents( $files[0] );
        $this->assertEquals( self::$sizes[0], strlen( $source ) );
    }

    public function testPop3MessageSourceFetchAll()
    {
        $transport = new ezcMailPop3Transport( "mta1.ez.no" );
        $transport->authenticate( "ezcomponents@mail.ez.no", "ezcomponents" );
        $parser = new ezcMailParser();

        $set = new ezcMailStorageSet( $transport->fetchAll(), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();

        $source = file_get_contents( $files[0] );
        $this->assertEquals( self::$sizes[0], strlen( $source ) );
    }

    public function testMboxMessageSource()
    {
        $transport = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/testlimit-mbox" );
        $parser = new ezcMailParser();

        $set = new ezcMailStorageSet( $transport->fetchByMessageNr( 1 ), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();

        $source = file_get_contents( $files[0] );
        $this->assertEquals( 2609, strlen( $source ) );
    }

    public function testMboxMessageSourceFetchAll()
    {
        $transport = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/testlimit-mbox" );
        $parser = new ezcMailParser();

        $set = new ezcMailStorageSet( $transport->fetchAll(), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();

        $source = file_get_contents( $files[0] );
        $this->assertEquals( 2925, strlen( $source ) );
    }

    public function testMboxMessageEmpty()
    {
        $transport = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/empty.mbox" );
        $parser = new ezcMailParser();

        $set = new ezcMailStorageSet( $transport->fetchAll(), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mail ) );
    }

    public function testVariableMessageSource()
    {
        $parser = new ezcMailParser();
        $message = file_get_contents( dirname( __FILE__ ) . "/data/test-variable" );

        $set = new ezcMailStorageSet( new ezcMailVariableSet( $message ), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();

        $source = file_get_contents( $files[0] );
        $this->assertEquals( 1445, strlen( $source ) );
    }

    public function testVariableMessageSourceEmpty()
    {
        $parser = new ezcMailParser();
        $message = "";

        $set = new ezcMailStorageSet( new ezcMailVariableSet( $message ), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( array(), $mail );
    }

    public function testFileMessageSource()
    {
        $parser = new ezcMailParser();
        $messages = array( dirname( __FILE__ ) . "/data/test-variable" );

        $set = new ezcMailStorageSet( new ezcMailFileSet( $messages ), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();

        $source = file_get_contents( $files[0] );
        $this->assertEquals( 1444, strlen( $source ) );
    }

    public function testFileMessageSourceMultiple()
    {
        $parser = new ezcMailParser();
        $messages = array( dirname( __FILE__ ) . "/data/test-variable", dirname( __FILE__ ) . "/data/test-variable" );

        $set = new ezcMailStorageSet( new ezcMailFileSet( $messages ), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();

        $source = file_get_contents( $files[0] );
        $this->assertEquals( 1444, strlen( $source ) );
        $source = file_get_contents( $files[1] );
        $this->assertEquals( 1444, strlen( $source ) );
    }

    public function testFileMessageSourceEmpty()
    {
        $parser = new ezcMailParser();
        $fileName = $this->tempDir . "/empty-message";
        $fileHandle = fopen( $fileName, "w" );
        fwrite( $fileHandle, "" );
        fclose( $fileHandle );
        $messages = array( $fileName );

        $set = new ezcMailStorageSet( new ezcMailFileSet( $messages ), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( array(), $mail );
    }

    public function testGetSourceFileNames()
    {
        $transport = new ezcMailImapTransport( "mta1.ez.no" );
        $transport->authenticate( "ezcomponents@mail.ez.no", "ezcomponents" );
        $transport->selectMailbox( "Inbox" );
        $parser = new ezcMailParser();

        $set = new ezcMailStorageSet( $transport->fetchAll(), $this->tempDir );
        $mail = $parser->parseMail( $set );
        $files = $set->getSourceFiles();
        $expected = array( "Pine.LNX.4.62.0603161543230.13229@localhost",
                           "Pine.LNX.4.62.0603160934040.13229@localhost",
                           "Pine.LNX.4.62.0603160934280.13229@localhost",
                           "Pine.LNX.4.62.0603161539140.13229@localhost", );

        for ( $i = 0; $i < count( $files ); $i++ )
        {
            $this->assertEquals( $expected[$i], basename( $files[$i] ) );
        }
    }

    public function setUp()
    {
        $this->tempDir = $this->createTempDir( 'ezcMailTransportStorageTest' );
    }

    public function tearDown()
    {
        $this->removeTempDir();

        $transport = new ezcMailImapTransport( "mta1.ez.no" );
        $transport->authenticate( "ezcomponents@mail.ez.no", "ezcomponents" );
        try
        {
            $transport->deleteMailbox( "Guybrush" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }
}
?>

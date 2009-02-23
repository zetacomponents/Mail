<?php
/**
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

include_once( 'wrappers/imap_wrapper.php' );
include_once( 'wrappers/imap_custom_wrapper.php' );

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportImapTest extends ezcTestCase
{
    private static $ids = array();
    private static $sizes = array();

    private static $server = 'mta1.ez.no';
    private static $serverSSL = 'ezctest.ez.no';
    private static $port = 143;
    private static $portSSL = 993;
    private static $user = 'ezcomponents@mail.ez.no';
    private static $password = 'ezcomponents';
    private static $userSSL = 'as';
    private static $passwordSSL = 'wee123';

    public static function suite()
    {
        self::$ids = array( 23, 24, 25, 26 );
        self::$sizes = array( 1539, 64072, 1696, 1725 );

        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function tearDown()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->deleteMailbox( "Guybrush" );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        try
        {
            $imap->deleteMailbox( "Elaine" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    /**
     * Calls $this->onConsecutiveCalls() with the lines from $fileName as
     * parameter list.
     *
     * Used to create a mock object with lots of custom responses
     * (e.g. conversation with a server).
     *
     * @param string $fileName
     * @return PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls
     */
    protected function customMockObjectConversation( $fileName )
    {
        $data = file_get_contents( $fileName );
        $dataArray = explode( "\r\n", $data );

        $returns = array();
        foreach ( $dataArray as $line )
        {
            $returns[] = $this->returnValue( $line . "\r\n" );
        }

        return call_user_func_array( array( $this, 'onConsecutiveCalls' ), $returns );
    }

    public function testWrapperMockConnectionAuthenticateResponseNotOk()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( 'custom response' ) );
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap->setConnection( $connection );

        try
        {
            $imap->authenticate( self::$user, self::$password );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. Unrecognized IMAP response in line: custom response", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionAuthenticateHang()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( '* OK' ) );
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap->setConnection( $connection );
        $result = $imap->authenticate( self::$user, self::$password );
        $imap->disconnect();
        $this->assertEquals( false, $result );
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    /**
     * Test for issue #13878: Endless loop in ezcMailParser.
     */
    public function testWrapperMockConnectionHangFetchEnd()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );

        // create a mock connection which responds to commands with answers from
        // a real conversation recorded with an IMAP server
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->customMockObjectConversation( dirname( __FILE__ ) . '/data/shark' ) );

        $options = new ezcMailImapTransportOptions();
        $options->uidReferencing = true;
        $imap = new ezcMailImapTransportCustomWrapper( self::$server, self::$port, $options );
        $imap->setConnection( $connection );
        $result = $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'INBOX' );
        $set = $imap->fetchByFlag( 'NEW' );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $mail = $mails[0];
        $this->assertEquals( 'test 15', $mail->subject );
    }

    /**
     * Test for issue #13878: Endless loop in ezcMailParser.
     */
    public function testWrapperMockConnectionHangFetchEndWrongBodyEnding()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );

        // create a mock connection which responds to commands with answers from
        // a real conversation recorded with an IMAP server
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->customMockObjectConversation( dirname( __FILE__ ) . '/data/shark_wrong_body_ending' ) );

        $options = new ezcMailImapTransportOptions();
        $options->uidReferencing = true;
        $imap = new ezcMailImapTransportCustomWrapper( self::$server, self::$port, $options );
        $imap->setConnection( $connection );
        $result = $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'INBOX' );
        $set = $imap->fetchByFlag( 'NEW' );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $mail = $mails[0];
        $this->assertEquals( 'test 15', $mail->subject );
    }

    /**
     * Test for issue #14242: Cannot append email through IMAP.
     *
     * The issue was not about the APPEND command, but it was about
     * the handling of broken returned FETCH data from Microsoft Exchange.
     *
     * This test feeds a wireshark output to an IMAP conversation and it will
     * fail because the wireshark output is wrong - Microsoft Exchange
     * outputs an unexpected line ' FLAGS (\Seen))' when the expected
     * IMAP response would be ')'.
     *
     * When a fix will be available for the IMAP problem, this test will not
     * fail anymore.
     */
    public function testWrapperMockConnectionAppend()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );

        // create a mock connection which responds to commands with answers from
        // a real conversation recorded with an IMAP server
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->customMockObjectConversation( dirname( __FILE__ ) . '/data/shark_append' ) );

        $options = new ezcMailImapTransportOptions();
        $imap = new ezcMailImapTransportCustomWrapper( self::$server, self::$port, $options );
        $imap->setConnection( $connection );
        $result = $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'INBOX' );

        try
        {
            $mail = new ezcMail();
            $set = $imap->fetchByMessageNr( '1' );
            $parser = new ezcMailParser();
            $mails = $parser->parseMail( $set );
            $mail = $mails[0];
            $imap->append( 'INBOX', $mail->generate() );
            $this->assertEquals( 'Test', $mail->subject );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( 'Issue #14242 (Cannot append email through IMAP) is not fixed yet.' );
        }
    }

    public function testWrapperMockListMessagesFail()
    {
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'responseType' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'responseType' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( ezcMailImapTransport::RESPONSE_OK ),
                        $this->returnValue( ezcMailImapTransport::RESPONSE_OK ),
                        $this->returnValue( 'custom response' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        try
        {
            $imap->listMessages();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server could not list messages: A0003 * SEARCH.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockListMessagesNotEmptyFail()
    {
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX * SEARCH completed' ),
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        try
        {
            $imap->listMessages();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server could not list messages: XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockListUniqueIdentifiersSingleFail()
    {
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX * SEARCH completed' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        try
        {
            $imap->listUniqueIdentifiers( 1 );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server could not fetch the unique identifiers: XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockSearchMailboxFail()
    {
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX * SEARCH completed' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        try
        {
            $imap->searchMailbox();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server could not search the messages by the specified criteria: XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionExpungeFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array( 'getLine', 'sendData' ), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( "XXXX OK XXXX" ) );
        $connection->expects( $this->any() )
                   ->method( 'sendData' )
                   ->will( $this->returnValue( false ) );
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        $imap->setConnection( $connection );

        try
        {
            $imap->expunge();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. EXPUNGE failed: XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockNoopFail()
    {
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        try
        {
            $imap->noop();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. NOOP failed: XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockGetHierarchyDelimiterFail()
    {
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '* LIST (\Noselect) "/" ""' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );

        try
        {
            $imap->getHierarchyDelimiter();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. Could not retrieve the hierarchy delimiter: XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockGetHierarchyDelimiterWrongFail()
    {
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '* LIST (\Noselect)' )
                   ) );
        $imap->authenticate( self::$user, self::$password );

        try
        {
            $imap->getHierarchyDelimiter();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. Could not retrieve the hierarchy delimiter: * LIST (\\Noselect).", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionAppendFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array( 'getLine', 'sendData' ), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( "+ XXXX" ) );
        $connection->expects( $this->any() )
                   ->method( 'sendData' )
                   ->will( $this->returnValue( false ) );
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        $imap->setConnection( $connection );

        try
        {
            $imap->append( 'Guybrush', 'mail contents' );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server could not append message to mailbox 'Guybrush': XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionCapabilityFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array( 'getLine', 'sendData' ), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->onConsecutiveCalls(
                              $this->returnValue( '+ XXXXX' ),
                              $this->returnValue( '* XXXXX' )
                         ) );
        $connection->expects( $this->any() )
                   ->method( 'sendData' )
                   ->will( $this->returnValue( false ) );
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        $imap->setConnection( $connection );

        try
        {
            $imap->capability();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server responded negative to the CAPABILITY command: XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionSearchByFlagFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array( 'sendData' ), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'sendData' )
                   ->will( $this->returnValue( false ) );
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX * SEARCH Completed' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        $imap->setConnection( $connection );

        try
        {
            $imap->countByFlag( 'RECENT' );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server could not search the messages by flags: XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionClearFlagFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array( 'sendData' ), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'sendData' )
                   ->will( $this->returnValue( false ) );
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        $imap->setConnection( $connection );

        try
        {
            $imap->clearFlag( '1000', 'SEEN' );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server could not clear flag 'SEEN' on the messages '1000': XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionSetFlagFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array( 'sendData' ), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'sendData' )
                   ->will( $this->returnValue( false ) );
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap = $this->getMock( 'ezcMailImapTransportWrapper', array( 'getResponse' ), array( self::$server, self::$port ) );
        $imap->expects( $this->any() )
             ->method( 'getResponse' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'XXXXX OK XXXXX' ),
                        $this->returnValue( 'XXXXX BAD XXXXX' )
                   ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        $imap->setConnection( $connection );

        try
        {
            $imap->setFlag( '1000', 'SEEN' );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server could not set flag 'SEEN' on the messages '1000': XXXXX BAD XXXXX.", $e->getMessage() );
        }
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testGetNextTag()
    {
        $imap = new ezcMailImapTransportWrapper( self::$server, self::$port );
        $imap->setCurrentTag( 'Y9999' );
        for ( $i = 0; $i ^ 10001; ++$i )
        {
            $tag = $imap->getNextTag();
        }
        $this->assertEquals( 'A0001', $tag );
        $imap->setStatus( ezcMailImapTransport::STATE_NOT_CONNECTED );
    }

    public function testInvalidServer()
    {
        try
        {
            $imap = new ezcMailImapTransport( "no.such.server.example.com" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( 'An error occured while sending or receiving mail. Failed to connect to the server: no.such.server.example.com:143.', $e->getMessage() );
        }
    }

    public function testInvalidUsername()
    {
        try
        {
            $imap = new ezcMailImapTransport( self::$server, self::$port );
            $imap->authenticate( "no_such_user", "ezcomponents" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidPassword()
    {
        try
        {
            $imap = new ezcMailImapTransport( self::$server, self::$port );
            $imap->authenticate( "ezcomponents", "no_such_password" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }


    public function testInvalidCallListMessages()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->disconnect();
        try
        {
            $imap->listMessages();
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( 'An error occured while sending or receiving mail. Can\'t call listMessages() on the IMAP transport when a mailbox is not selected.', $e->getMessage() );
        }
    }

    public function testInvalidCallTop()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->disconnect();
        try
        {
            $imap->top( 1, 1 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( 'An error occured while sending or receiving mail. Can\'t call top() on the IMAP transport when a mailbox is not selected.', $e->getMessage() );
        }
    }

    public function testInvalidCallStatus()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->disconnect();
        try
        {
            $imap->status( $a, $b );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( 'An error occured while sending or receiving mail. Can\'t call status() on the IMAP transport when a mailbox is not selected.', $e->getMessage() );
        }
    }

    public function testInvalidCallDelete()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->disconnect();
        try
        {
            $imap->delete( 1000 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallListMailboxes()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->disconnect();
        try
        {
            $imap->listMailboxes();
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testLoginAuthenticated()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->authenticate( self::$user, self::$password );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallListUniqueMessages()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->disconnect();
        try
        {
            $imap->listUniqueIdentifiers();
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( 'An error occured while sending or receiving mail. Can\'t call listUniqueIdentifiers() on the IMAP transport when a mailbox is not selected.', $e->getMessage() );
        }
    }

    public function testInvalidCallSelectMailbox()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->disconnect();
        try
        {
            $imap->selectMailbox( 'inbox' );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( 'An error occured while sending or receiving mail. Can\'t call selectMailbox() when not successfully logged in.', $e->getMessage() );
        }
    }

    public function testInvalidSelectMailbox()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
           $imap->selectMailbox( 'no-such-mailbox' );
           $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testListMailboxes()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $mailboxes = $imap->listMailboxes();
        $this->assertNotEquals( 0, count( $mailboxes ) );
    }

    public function testListMailboxesInvalid()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $mailboxes = $imap->listMailboxes( '"', '*' );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testDefaultPort()
    {
        $imap = new ezcMailImapTransport( self::$server );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testFetchMail()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testListMessages()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $list = $imap->listMessages();
        $this->assertEquals( array( 1 => self::$sizes[0], 2 => self::$sizes[1], 3 => self::$sizes[2], 4 => self::$sizes[3] ), $list );
    }

    public function testListMessagesWithAttachments()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $list = $imap->listMessages( "multipart/mixed" );
        $this->assertEquals( array( 2 => self::$sizes[1], 3 => self::$sizes[2], 4 => self::$sizes[3] ), $list );
    }

    public function testFetchByMessageNr1()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $message = $imap->fetchByMessageNr( -1 );
            $this->fail( 'Expected exception was not thrown' );
        }
        catch ( ezcMailNoSuchMessageException $e )
        {
            $this->assertEquals( "The message with ID '-1' could not be found.", $e->getMessage() );
        }
    }

    public function testFetchByMessageNr2()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $message = $imap->fetchByMessageNr( 0 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailNoSuchMessageException $e )
        {
            $this->assertEquals( "The message with ID '0' could not be found.", $e->getMessage() );
        }
    }

    public function testFetchByMessageNr3()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $message = $imap->fetchByMessageNr( 1 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $message );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( array( 0 => '1' ), $this->readAttribute( $message, 'messages' ) );
        $this->assertEquals( 'ezcMailImapSet', get_class( $message ) );
    }

    public function testFetchFromOffset1()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $set = $imap->fetchFromOffset( -1, 10 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailOffsetOutOfRangeException $e )
        {
            $this->assertEquals( "The offset '-1' is outside of the message subset '-1', '10'.", $e->getMessage());
        }
    }

    public function testFetchFromOffset2()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $set = $imap->fetchFromOffset( 10, 1 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailOffsetOutOfRangeException $e )
        {
            $this->assertEquals( "The offset '10' is outside of the message subset '10', '1'.", $e->getMessage() );
        }
    }

    public function testFetchFromOffset3()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $set = $imap->fetchFromOffset( 0, -1 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailInvalidLimitException $e )
        {
            $this->assertEquals( "The message count '-1' is not allowed for the message subset '0', '-1'.", $e->getMessage() );
        }
    }

    public function testFetchFromOffset4()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchFromOffset( 1, 4 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testFetchFromOffset5()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchFromOffset( 1, 0 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testStatus()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $imap->status( $num, $size, $recent, $unseen );
        $this->assertEquals( 4, $num );
        $this->assertEquals( self::$sizes[0] + self::$sizes[1] + self::$sizes[2] + self::$sizes[3], $size );
        $this->assertEquals( 0, $recent );
        $this->assertEquals( 0, $unseen );
    }

    public function testTop()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $list = $imap->top( 1, 1 );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testTopOnlyHeaders()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $list = $imap->top( 1 );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testInvalidTop()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $imap->top( 1000, 1 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testDelete()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( 'inbox' );
        $imap->copyMessages( 1, "Guybrush" );
        $imap->selectMailbox( "Guybrush" );
        $imap->delete( 1 );
        $imap->selectMailbox( 'inbox' );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testDeleteFail()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $imap->delete( 1000 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testListUniqueIdentifiersSingle()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $uids = $imap->listUniqueIdentifiers( 1 );
        $this->assertEquals( array( 1 => self::$ids[0] ), $uids );
    }

    public function testListUniqueIdentifiersMultiple()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $uids = $imap->listUniqueIdentifiers();
        $this->assertEquals(
            array(
                1 => self::$ids[0],
                2 => self::$ids[1],
                3 => self::$ids[2],
                4 => self::$ids[3],
            ),
            $uids
        );
    }

    public function testInvalidListUniqueIdentifiersSingle()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $uids = $imap->listUniqueIdentifiers( 1000 );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testDisconnect()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->disconnect();
        $imap->disconnect();
    }

    public function testListMessagesReadOnly()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox', true );
        $list = $imap->listMessages();
        $this->assertEquals( array( 1 => self::$sizes[0], 2 => self::$sizes[1], 3 => self::$sizes[2], 4 => self::$sizes[3] ), $list );
    }

    public function testStatusReadOnly()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox', true );
        $imap->status( $num, $size );
        $this->assertEquals( 4, $num );
        $this->assertEquals( self::$sizes[0] + self::$sizes[1] + self::$sizes[2] + self::$sizes[3], $size );
    }

    public function testTopReadOnly()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox', true );
        $list = $imap->top( 1, 1 );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testDeleteReadOnly()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox', true );
        try
        {
            $imap->delete( 1 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testListUniqueIdentifiersReadOnly()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox', true );
        $uids = $imap->listUniqueIdentifiers( 1 );
        $this->assertEquals( array( 1 => self::$ids[0] ), $uids );
    }

    public function testCreateRenameDeleteMailbox()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->renameMailbox( "Guybrush", "Elaine" );
        $imap->deleteMailbox( "Elaine" );
    }

    public function testCreateRenameDeleteMailboxInvalidName()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->createMailbox( "Inbox" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        try
        {
            $imap->renameMailbox( "Inbox", "Elaine" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        try
        {
            $imap->deleteMailbox( "Inbox" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testCreateRenameDeleteMailboxNotAuthenticated()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        try
        {
            $imap->createMailbox( "Inbox" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        try
        {
            $imap->renameMailbox( "Inbox", "Elaine" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        try
        {
            $imap->deleteMailbox( "Inbox" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testRenameDeleteSelectedMailbox()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Guybrush" );

        try
        {
            $imap->renameMailbox( "Guybrush", "Elaine" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        try
        {
            $imap->deleteMailbox( "Guybrush" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testCopyMessages()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( "1", "Guybrush" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testCopyMessagesInvalidDestination()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );

        try
        {
            $imap->copyMessages( "1", "Guybrush" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testCopyMessagesInvalidMessage()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $imap->createMailbox( "Guybrush" );

        try
        {
            $imap->copyMessages( "1000", "Guybrush" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        $imap->deleteMailbox( "Guybrush" );
    }

    public function testCopyMessagesMailboxNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );

        try
        {
            $imap->copyMessages( "1000", "Guybrush" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }

        $imap->deleteMailbox( "Guybrush" );
    }

    public function testFetchByFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "inbox" );
        $set = $imap->fetchByFlag( "undeleted" );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testFetchByFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "inbox" );
        try
        {
            $set = $imap->fetchByFlag( "no such flag" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testFetchByFlagNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $set = $imap->fetchByFlag( "undeleted" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testCountByFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "inbox" );
        $this->assertEquals( 4, $imap->countByFlag( "seen" ) );
    }

    public function testCountByFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "inbox" );
        try
        {
            $count = $imap->countByFlag( "no such flag" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testCountByFlagNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $count = $imap->countByFlag( "undeleted" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testSetFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( "1:4", "Guybrush" );
        $imap->selectMailbox( "Guybrush" );
        $imap->setFlag( "1", "ANSWERED" );
        $imap->setFlag( "1,2", "FLAGGED" );
        $imap->setFlag( "3:4", "DRAFT" );
        $imap->delete( "1" ); // it is not deleted permanently,
                              // but just its flag \Deleted is set
        $this->assertEquals( 2, $imap->countByFlag( "FLAGGED" ) );
        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testSetFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        try
        {
            $imap->setFlag( "1", "no such flag" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testSetFlagNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->setFlag( "1", "ANSWERED" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testClearFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( "1:4", "Guybrush" );
        $imap->selectMailbox( "Guybrush" );
        $imap->clearFlag( "1", "SEEN" );
        $imap->clearFlag( "1,2", "FLAGGED" );
        $imap->clearFlag( "3:4", "DRAFT" );
        $this->assertEquals( 1, $imap->countByFlag( "UNSEEN" ) );
        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testClearFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        try
        {
            $imap->clearFlag( "1000", "no such flag" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testClearFlagNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->clearFlag( "1000", "ANSWERED" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testUnsorted()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );

        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[0]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[2]->subject );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[3]->subject );
    }

    public function testSortFromOffsetInvalidCriteria()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( 1, 4, 'invalid criteria' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testSortFromOffsetDefaultCriteria()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( 1, 4, 'received' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testSortFromOffsetInvalidOffset()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        try
        {
            $imap->sortFromOffset( 10, 4, 'subject' );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailOffsetOutOfRangeException $e )
        {
        }
    }

    public function testSortFromOffsetInvalidCount()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        try
        {
            $imap->sortFromOffset( 1, -1, 'subject' );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailInvalidLimitException $e )
        {
        }
    }

    public function testSortFromOffsetCountZero()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( 1, 0, 'subject' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[2]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[0]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[3]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testSortFromOffsetNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->sortFromOffset( 1, 4, 'subject' );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testSortFromOffsetBySubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( 1, 4, 'subject' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[2]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[0]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[3]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testSortFromOffsetBySubjectReverse()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( 1, 4, 'subject', true );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[1]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[2]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[0]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[3]->subject );
    }

    public function testSortFromOffsetByDate()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( 1, 4, 'date' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[2]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[0]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[3]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testSortFromOffsetByDateReverse()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( 1, 4, 'date', true );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[1]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[2]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[0]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[3]->subject );
    }

    public function testSortMessagesBySubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortMessages( array( 1, 2, 3, 4 ), 'subject' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[2]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[0]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[3]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testSortMessagesBySubjectReverse()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortMessages( array( 1, 2, 3, 4 ), 'subject', true );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[1]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[3]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[0]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[2]->subject );
    }

    public function testSortMessagesOneElement()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortMessages( array( 1 ), 'subject' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[0]->subject );
    }

    public function testSortMessagesEmpty()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        try
        {
            $imap->sortMessages( array(), 'subject' );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testSortMessagesNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->sortMessages( array( 1, 2, 3, 4 ), 'subject' );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testFetchFlags()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $flags = $imap->fetchFlags( array( 1, 2, 3, 4 ) );
        $expected = array( 1 => array( '\Seen' ),
                           2 => array( '\Seen' ),
                           3 => array( '\Seen' ),
                           4 => array( '\Seen' )
                         );
        $this->assertEquals( $expected, $flags );
    }

    public function testFetchFlagsEmpty()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        try
        {
            $imap->fetchFlags( array() );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testFetchFlagsNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->fetchFlags( array( 1, 2, 3, 4 ) );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testFetchSizes()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $flags = $imap->fetchSizes( array( 1, 2, 3, 4 ) );
        $expected = array( 1 => self::$sizes[0],
                           2 => self::$sizes[1],
                           3 => self::$sizes[2],
                           4 => self::$sizes[3]
                         );
        $this->assertEquals( $expected, $flags );
    }

    public function testFetchSizesEmpty()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        try
        {
            $imap->fetchSizes( array() );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testFetchSizesNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->fetchSizes( array( 1, 2, 3, 4 ) );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testGetMessageNumbersFromSet()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->fetchAll();
        $messageNumbers = $set->getMessageNumbers();
        $this->assertEquals( array( 1, 2, 3, 4 ), $messageNumbers );
    }

    public function testNoop()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->noop();
    }

    public function testNoopSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $imap->noop();
    }

    public function testNoopNotConnected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->disconnect();
        try
        {
            $imap->noop();
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testCapability()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $capabilities = $imap->capability();
        $this->assertTrue( in_array( 'IMAP4', $capabilities ) || in_array( 'IMAP4rev1', $capabilities ) );
    }

    public function testCapabilitySelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $capabilities = $imap->capability();
        $this->assertTrue( in_array( 'IMAP4', $capabilities ) || in_array( 'IMAP4rev1', $capabilities ) );
    }

    public function testCapabilityNotConnected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->disconnect();
        try
        {
            $imap->capability();
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAppend()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );

        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'mcfly@example.com', 'Marty McFly' );
        $mail->addTo( new ezcMailAddress( 'doc@example.com', 'Doc' ) );
        $mail->subject = "Do not open until 1985";
        $mail->body = new ezcMailText( "NOBODY calls me a chicken!" );
        $data = $mail->generate();

        $imap->append( "Guybrush", $data );
        $imap->append( "Guybrush", $data, array( 'Answered' ) );

        $imap->selectMailbox( "Guybrush" );
        $imap->status( $numMessages, $sizeMessages );
        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );

        $this->assertEquals( 2, $numMessages );
    }

    public function testAppendInvalidDestination()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $mail = new ezcMail();
        try
        {
            $imap->append( "no such mailbox", $mail->generate() );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAppendInvalidMessage()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $mail = null;
        try
        {
            $imap->append( "Guybrush", $mail );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testAppendNotAuthenticated()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $mail = new ezcMail();
        try
        {
            $imap->append( "Guybrush", $mail->generate() );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testExpunge()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( "1:4", "Guybrush" );
        $imap->selectMailbox( "Guybrush" );
        $this->assertEquals( 4, $imap->countByFlag( "ALL" ) );
        $imap->delete( 1 );
        $imap->expunge();
        $this->assertEquals( 3, $imap->countByFlag( "ALL" ) );
        $set = $imap->fetchByMessageNr( 2, true );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $imap->expunge();
        $this->assertEquals( 2, $imap->countByFlag( "ALL" ) );
        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testExpungeNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->expunge();
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testMessageSize()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $expected = self::$sizes;
        for ( $i = 0; $i < count( $mail ); $i++ )
        {
            $this->assertEquals( $expected[$i], $mail[$i]->size );
        }
        $parts = $mail[1]->fetchParts();
        $this->assertEquals( '45177', $parts[1]->size );
    }

    public function testTransportProperties()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $this->assertEquals( true, isset( $imap->options ) );
        $this->assertEquals( false, isset( $imap->no_such_property ) );

        $options = $imap->options;
        $imap->options = new ezcMailImapTransportOptions();
        $this->assertEquals( $options, $imap->options );

        try
        {
            $imap->options = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'xxx' that you were trying to assign to setting 'options' is invalid. Allowed values are: instanceof ezcMailImapTransportOptions.", $e->getMessage() );
        }

        try
        {
            $imap->no_such_property = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }

        try
        {
            $value = $imap->no_such_property;
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }
    }

    public function testTransportConnectionProperties()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );

        // hack to get the connection property as it is private
        $connection = $this->readAttribute( $imap, 'connection' );
        $this->assertEquals( true, isset( $connection->options ) );
        $this->assertEquals( false, isset( $connection->no_such_property ) );

        $options = $connection->options;
        $connection->options = new ezcMailImapTransportOptions();
        $this->assertEquals( $options, $connection->options );

        try
        {
            $connection->options = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'xxx' that you were trying to assign to setting 'options' is invalid. Allowed values are: instanceof ezcMailTransportOptions.", $e->getMessage() );
        }

        try
        {
            $connection->no_such_property = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }

        try
        {
            $value = $connection->no_such_property;
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }
    }

    public function testServerSSL()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped();
        }
        $imap = new ezcMailImapTransport( self::$serverSSL, self::$portSSL, array( 'ssl' => true ) );
        $imap->authenticate( self::$userSSL, self::$passwordSSL );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 240, $mail->size );
    }

    public function testServerSSLDefaultPort()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped();
        }
        $imap = new ezcMailImapTransport( self::$serverSSL, null, array( 'ssl' => true ) );
        $imap->authenticate( self::$userSSL, self::$passwordSSL );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 240, $mail->size );
    }

    public function testServerSSLInvalidPort()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped();
        }
        try
        {
            $imap = new ezcMailImapTransport( self::$serverSSL, self::$port, array( 'ssl' => true ) );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( 'An error occured while sending or receiving mail. Failed to connect to the server: ezctest.ez.no:143.', $e->getMessage() );
        }
    }

    public function testFixTrailingParanthesis()
    {
        $transport = new ezcMailImapTransport( self::$server, self::$port );
        $transport->authenticate( self::$user, self::$password );
        $transport->selectMailbox( "Inbox" );
        $parser = new ezcMailParser();

        $set = $transport->fetchByMessageNr( 1 );
        $mail = $parser->parseMail( $set );
        $this->assertNotEquals( ')', substr( $mail[0]->body->text, strlen( $mail[0]->body->text ) - 3, 3 ) );
    }

    public function testTopAsPeek()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( "1", "Guybrush" );
        $imap->selectMailbox( "Guybrush" );
        $imap->clearFlag( "1", "SEEN" );
        $this->assertEquals( 0, $imap->countByFlag( "SEEN" ) );
        $src = $imap->top( 1, 1 );
        $this->assertEquals( 0, $imap->countByFlag( "SEEN" ) );
        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testSortWithPeek()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( "1,2", "Guybrush" );
        $imap->selectMailbox( "Guybrush" );
        $imap->clearFlag( "1,2", "SEEN" );
        $this->assertEquals( 0, $imap->countByFlag( "SEEN" ) );
        $src = $imap->sortMessages( array( 1, 2 ), "Subject" );
        $this->assertEquals( 0, $imap->countByFlag( "SEEN" ) );
        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testSearchMailboxEmpty()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        $set = $imap->searchMailbox();
        $this->assertEquals( array( 1, 2, 3, 4 ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mails ) );

        $set = $imap->searchMailbox( ' ' );
        $this->assertEquals( array( 1, 2, 3, 4 ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mails ) );
    }

    public function testSearchMailboxFlagged()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'FLAGGED' );
        $this->assertEquals( array(), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mails ) );
    }

    public function testSearchMailboxSeen()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'SEEN' );
        $this->assertEquals( array( 1, 2, 3, 4 ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mails ) );
    }

    public function testSearchMailboxSubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'SUBJECT "norwegian"' );
        $this->assertEquals( array( 1, 4 ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 2, count( $mails ) );
    }

    public function testSearchMailboxCombineSeenSubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'SEEN SUBJECT "norwegian"' );
        $this->assertEquals( array( 1, 4 ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 2, count( $mails ) );
    }

    public function testSearchMailboxCombineFlaggedSubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'FLAGGED SUBJECT "norwegian"' );
        $this->assertEquals( array(), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mails ) );
    }

    public function testSearchMailboxFail()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );

        try
        {
            $set = $imap->searchMailbox( 'SUBJECT "pine"' );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. Can't call searchMailbox() on the IMAP transport when a mailbox is not selected.", $e->getMessage() );
        }
    }

    public function testGetHierarchyDelimiter()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );
        $delimiter = $imap->getHierarchyDelimiter();
        $this->assertEquals( '.', $delimiter );
    }

    public function testGetHierarchyDelimiterFail()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );

        try
        {
            $imap->getHierarchyDelimiter();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. Can't call getDelimiter() when not successfully logged in.", $e->getMessage() );
        }
    }

    public function testTagInHeadersAndBody()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port );
        $imap->authenticate( self::$user, self::$password );

        $imap->createMailbox( "Guybrush" );

        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'from@example.com', 'From' );
        $mail->addTo( new ezcMailAddress( 'to@example.com', 'To' ) );
        $mail->subject = "A0000 A0001 A0002 A0003 A0004 A0005 A0006 A0007";
        $mail->body = new ezcMailText( "A0000\nA0001\nA0002\nA0003\nA0004\nA0005\nA0006\nA0007" );
        $data = $mail->generate();

        $imap->append( "Guybrush", $data );
        $imap->append( "Guybrush", $data, array( 'Answered' ) );

        $imap->selectMailbox( "Guybrush" );

        $set = $imap->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];

        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );

        $this->assertEquals( 'A0000 A0001 A0002 A0003 A0004 A0005 A0006 A0007', $mail->subject );
    }

    public function testTransportConstructorOptions()
    {
        $options = new ezcMailImapTransportOptions();
        $options->timeout = 10;
        $imap = new ezcMailImapTransport( self::$server, self::$port, $options );

        $options = new stdClass();
        try
        {
            $pop3 = new ezcMailImapTransport( self::$server, self::$port, $options );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'O:8:\"stdClass\":0:{}' that you were trying to assign to setting 'options' is invalid. Allowed values are: ezcMailImapTransportOptions|array.", $e->getMessage() );
        }
    }

    public function testTransportOptions()
    {
        $options = new ezcMailImapTransportOptions();

        try
        {
            $options->uidReferencing = 'wrong value';
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $options->no_such_option = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    /**
     * Test for issue #14360: problems with $imap->top() command in gmail.
     */
    public function testTopGmail()
    {
        $options = new ezcMailImapTransportOptions();
        $options->ssl = true;
        $imap = new ezcMailImapTransport( 'imap.gmail.com', '993', $options );

        // please don't use this account :)
        $imap->authenticate( 'ezcomponents' . '@' . 'gmail.com', 'wee12345' );
        $imap->selectMailbox( 'inbox' );
        $text = $imap->top( 1 );
        $this->assertEquals( 3433, strlen( $text ) );
    }

    /**
     * Test for issue #14360: problems with $imap->top() command in gmail.
     */
    public function testTopGmailHeadersOnly()
    {
        $options = new ezcMailImapTransportOptions();
        $options->ssl = true;
        $imap = new ezcMailImapTransport( 'imap.gmail.com', '993', $options );

        // please don't use this account :)
        $imap->authenticate( 'ezcomponents' . '@' . 'gmail.com', 'wee12345' );
        $imap->selectMailbox( 'inbox' );
        $text = $imap->top( 1, 1 );
        $this->assertEquals( 382, strlen( $text ) );
    }

    public function testSetOptions()
    {
        $options = new ezcMailImapSetOptions();

        try
        {
            $options->uidReferencing = 'wrong value';
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $options->no_such_option = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }

        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );

        $options = new ezcMailImapSetOptions();
        $options->uidReferencing = true;

        $set = new ezcMailImapSet( $connection, array(), false, $options );

        $options = new stdClass();
        try
        {
            $set = new ezcMailImapSet( $connection, array(), false, $options );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'O:8:\"stdClass\":0:{}' that you were trying to assign to setting 'options' is invalid. Allowed values are: ezcMailImapSetOptions|array.", $e->getMessage() );
        }
    }
}
?>

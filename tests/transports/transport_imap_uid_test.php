<?php
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

include_once( 'wrappers/imap_wrapper.php' );

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportImapUidTest extends ezcTestCase
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

    public function testUidGetMessageNumbersFromSet()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->fetchAll();
        $messageNumbers = $set->getMessageNumbers();
        $this->assertEquals( self::$ids, $messageNumbers );
    }

    public function testUidCommandsFetchAll()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchAll();
        $this->assertEquals( array( self::$ids[0], self::$ids[1], self::$ids[2], self::$ids[3] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mails ) );
    }

    public function testUidCommandsFetchByMessageNr()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchByMessageNr( self::$ids[0] );
        $this->assertEquals( array( self::$ids[0] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mails ) );
    }

    public function testFetchByMessageNrNotFound()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidCommandsFetchFromOffsetAll()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchFromOffset( self::$ids[1] );
        $this->assertEquals( array( self::$ids[1], self::$ids[2], self::$ids[3] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 3, count( $mails ) );
    }

    public function testUidCommandsFetchFromOffsetCount()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchFromOffset( self::$ids[0], 2 );
        $this->assertEquals( array( self::$ids[0], self::$ids[1] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 2, count( $mails ) );
    }

    public function testUidCommandsFetchFromOffsetStartOutside()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $set = $imap->fetchFromOffset( 0, 2 );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailOffsetOutOfRangeException $e )
        {
            $this->assertEquals( "The offset '0' is outside of the message subset '0', '2'.", $e->getMessage() );
        }
    }

    public function testUidTop()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $list = $imap->top( self::$ids[0], 1 );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testUidTopOnlyHeaders()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $list = $imap->top( self::$ids[0] );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testUidInvalidTop()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $imap->top( 1, 1 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The IMAP server could not fetch the message '1': A0003 OK Fetch completed..", $e->getMessage() );
        }
    }

    public function testUidSearchMailboxEmpty()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );

        $set = $imap->searchMailbox();
        $this->assertEquals( array( self::$ids[0], self::$ids[1], self::$ids[2], self::$ids[3] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mails ) );

        $set = $imap->searchMailbox( ' ' );
        $this->assertEquals( array( self::$ids[0], self::$ids[1], self::$ids[2], self::$ids[3] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mails ) );
    }

    public function testUidSearchMailboxFlagged()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'FLAGGED' );
        $this->assertEquals( array(), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mails ) );
    }

    public function testUidSearchMailboxSeen()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'SEEN' );
        $this->assertEquals( array( self::$ids[0], self::$ids[1], self::$ids[2], self::$ids[3] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mails ) );
    }

    public function testUidSearchMailboxSubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'SUBJECT "norwegian"' );
        $this->assertEquals( array( self::$ids[0], self::$ids[3] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 2, count( $mails ) );
    }

    public function testUidSearchMailboxCombineSeenSubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'SEEN SUBJECT "norwegian"' );
        $this->assertEquals( array( self::$ids[0], self::$ids[3] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 2, count( $mails ) );
    }

    public function testUidSearchMailboxCombineFlaggedSubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->searchMailbox( 'FLAGGED SUBJECT "norwegian"' );
        $this->assertEquals( array(), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mails = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mails ) );
    }

    public function testUidSearchMailboxFail()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidSortFromOffsetInvalidCriteria()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( self::$ids[0], 4, 'invalid criteria' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    // Test for fixing sortFromOffset() undefined $range variable
    public function testUidSortFromOffsetInvalidCriteriaCountZero()
    {
        $imap = $this->getMock( 'ezcMailImapTransport', array( 'sort' ), array( self::$server, self::$port, array( 'uidReferencing' => true ) ) );
        $imap->expects( $this->any() )
             ->method( 'sort' )
             ->will( $this->returnValue( array() ) );

        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( self::$ids[0], 0, 'invalid criteria' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mail ) );
    }

    public function testUidSortFromOffsetDefaultCriteria()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( self::$ids[0], 4, 'received' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testUidSortFromOffsetInvalidOffset()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidSortFromOffsetInvalidCount()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        try
        {
            $imap->sortFromOffset( self::$ids[0], -1, 'subject' );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailInvalidLimitException $e )
        {
        }
    }

    public function testUidSortFromOffsetCountZero()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( self::$ids[0], 0, 'subject' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[2]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[0]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[3]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testUidSortFromOffsetNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->sortFromOffset( self::$ids[0], 4, 'subject' );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testUidSortFromOffsetBySubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( self::$ids[0], 4, 'subject' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[2]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[0]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[3]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testUidSortFromOffsetBySubjectReverse()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( self::$ids[0], 4, 'subject', true );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[1]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[2]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[0]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[3]->subject );
    }

    public function testUidSortFromOffsetByDate()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( self::$ids[0], 4, 'date' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[2]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[0]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[3]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testUidSortFromOffsetByDateReverse()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( self::$ids[0], 4, 'date', true );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[1]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[2]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[0]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[3]->subject );
    }

    public function testUidSortMessagesBySubject()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortMessages( array( self::$ids[0], self::$ids[1], self::$ids[2], self::$ids[3] ), 'subject' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[2]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[0]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[3]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testUidSortMessagesBySubjectReverse()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortMessages( array( self::$ids[0], self::$ids[1], self::$ids[2], self::$ids[3] ), 'subject', true );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[1]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[3]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[0]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[2]->subject );
    }

    public function testUidSortMessagesOneElement()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortMessages( array( self::$ids[0] ), 'subject' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[0]->subject );
    }

    public function testUidSortMessagesEmpty()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidSortMessagesNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->sortMessages( array( self::$ids[0], self::$ids[1], self::$ids[2], self::$ids[3] ), 'subject' );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testUidFetchByFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "inbox" );
        $set = $imap->fetchByFlag( "undeleted" );
        $this->assertEquals( array( self::$ids[0], self::$ids[1], self::$ids[2], self::$ids[3] ), $set->getMessageNumbers() );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testUidFetchByFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidFetchByFlagNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidCountByFlag()
    {
        $imap = new ezcMailImapTransport( self::$server );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "inbox" );
        $this->assertEquals( 4, $imap->countByFlag( "seen" ) );
    }

    public function testUidCountByFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidCountByFlagNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidFetchFlags()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $flags = $imap->fetchFlags( self::$ids );
        $expected = array( self::$ids[0] => array( '\Seen' ),
                           self::$ids[1] => array( '\Seen' ),
                           self::$ids[2] => array( '\Seen' ),
                           self::$ids[3] => array( '\Seen' )
                         );
        $this->assertEquals( $expected, $flags );
    }

    public function testUidFetchFlagsEmpty()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidFetchFlagsNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->fetchFlags( self::$ids );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testUidFetchSizes()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $flags = $imap->fetchSizes( self::$ids );
        $expected = array( self::$ids[0] => self::$sizes[0],
                           self::$ids[1] => self::$sizes[1],
                           self::$ids[2] => self::$sizes[2],
                           self::$ids[3] => self::$sizes[3]
                         );
        $this->assertEquals( $expected, $flags );
    }

    public function testUidFetchSizesEmpty()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidFetchSizesNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        try
        {
            $imap->fetchSizes( self::$ids );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testUidSetFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( implode( ',', self::$ids ), "Guybrush" );
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

    public function testUidSetFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidSetFlagNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidClearFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( implode( ',', self::$ids ), "Guybrush" );
        $imap->selectMailbox( "Guybrush" );
        $imap->clearFlag( "1", "SEEN" );
        $imap->clearFlag( "1,2", "FLAGGED" );
        $imap->clearFlag( "3:4", "DRAFT" );
        $this->assertEquals( 1, $imap->countByFlag( "UNSEEN" ) );
        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testUidClearFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidClearFlagNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidCopyMessages()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( self::$ids[0], "Guybrush" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testUidCopyMessagesInvalidDestination()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );

        try
        {
            $imap->copyMessages( self::$ids[0], "Guybrush" );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testUidCopyMessagesInvalidMessage()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->selectMailbox( "Inbox" );
        $imap->createMailbox( "Guybrush" );

        try
        {
            $imap->copyMessages( "1000", "Guybrush" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( "BUG? UID COPY does not return BAD response when copying a message with an UID that does not exist." );
        }

        $imap->deleteMailbox( "Guybrush" );
    }

    public function testUidCopyMessagesMailboxNotSelected()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
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

    public function testUidDelete()
    {
        $imap = new ezcMailImapTransport( self::$server, self::$port, array( 'uidReferencing' => true ) );
        $imap->authenticate( self::$user, self::$password );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( 'inbox' );
        $imap->copyMessages( self::$ids[0], "Guybrush" );
        $imap->selectMailbox( "Guybrush" );
        $imap->delete( 1 );
        $imap->selectMailbox( 'inbox' );
        $imap->deleteMailbox( "Guybrush" );
    }
}
?>

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
class ezcMailTransportImapTest extends ezcTestCase
{
    private static $ids = array();

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
            $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
            $imap = new ezcMailImapTransport( "dolly.ez.no" );
            $imap->authenticate( "ezcomponents", "no_such_password" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }


    public function testInvalidCallListMessages()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $imap->authenticate( "ezcomponents", "ezcomponents" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallListUniqueMessages()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $mailboxes = $imap->listMailboxes();
        $this->assertNotEquals( 0, count( $mailboxes ) );
    }

    public function testListMailboxesInvalid()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $mailboxes = $imap->listMailboxes( '"', '*' );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testFetchMail()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testListMessages()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        $list = $imap->listMessages();
        $this->assertEquals( array( 1 => '1542', 2 => '1539', 3 => '1383', 4 => '63913' ), $list );
    }

    public function testListMessagesWithAttachments()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        $list = $imap->listMessages( "multipart/mixed" );
        $this->assertEquals( array( 1 => '1542', 2 => '1539', 4 => '63913' ), $list );
    }

    public function testFetchByMessageNr1()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        try
        {
            $message = $imap->fetchByMessageNr( -1 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailNoSuchMessageException $e )
        {
            $this->assertEquals( "The message with ID '-1' could not be found.", $e->getMessage() );
        }
    }

    public function testFetchByMessageNr2()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        $message = $imap->fetchByMessageNr( 1 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $message );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( array( 0 => '1' ), $this->getAttribute( $message, 'messages' ) );
        $this->assertEquals( 'ezcMailImapSet', get_class( $message ) );
    }

    public function testFetchFromOffset1()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchFromOffset( 1, 4 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testFetchFromOffset5()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        $set = $imap->fetchFromOffset( 1, 0 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testStatus()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        $imap->status( $num, $size, $recent, $unseen );
        $this->assertEquals( 4, $num );
        $this->assertEquals( 68377, $size );
        $this->assertEquals( 0, $recent );
        $this->assertEquals( 0, $unseen );
    }

    public function testTop()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        $list = $imap->top( 1, 1 );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testInvalidTop()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox' );
        $uids = $imap->listUniqueIdentifiers( 1 );
        $this->assertEquals( array( 1 => self::$ids[0] ), $uids );
    }

    public function testListUniqueIdentifiersMultiple()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->disconnect();
        $imap->disconnect();
    }

    public function testListMessagesReadOnly()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox', true );
        $list = $imap->listMessages();
        $this->assertEquals( array( 1 => '1542', 2 => '1539', 3 => '1383', 4 => '63913' ), $list );
    }

    public function testStatusReadOnly()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox', true );
        $imap->status( $num, $size );
        $this->assertEquals( 4, $num );
        $this->assertEquals( 68377, $size );
    }

    public function testTopReadOnly()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox', true );
        $list = $imap->top( 1, 1 );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testDeleteReadOnly()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( 'inbox', true );
        $uids = $imap->listUniqueIdentifiers( 1 );
        $this->assertEquals( array( 1 => self::$ids[0] ), $uids );
    }

    public function testCreateRenameDeleteMailbox()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->createMailbox( "Guybrush" );
        $imap->renameMailbox( "Guybrush", "Elaine" );
        $imap->deleteMailbox( "Elaine" );
    }

    public function testCreateRenameDeleteMailboxInvalidName()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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

    public function testCopyMessage()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( "1", "Guybrush" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testCopyMessageInvalidDestination()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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

    public function testCopyMessageInvalidMessage()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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

    public function testCopyMessageMailboxNotSelected()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "inbox" );
        $set = $imap->fetchByFlag( "undeleted" );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testFetchByFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "inbox" );
        $this->assertEquals( 4, $imap->countByFlag( "seen" ) );
    }

    public function tesCountByFlagInvalidFlag()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "Inbox" );
        try
        {
            $imap->clearFlag( "1", "no such flag" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testClearFlagNotSelected()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $imap->clearFlag( "1", "ANSWERED" );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testUnsorted()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[0]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
        $this->assertEquals( "pine: test 3 with norwegian chars", $mail[2]->subject );
        $this->assertEquals( "pine: Mail with attachment", $mail[3]->subject );
    }

    public function testSortFromOffsetInvalidCriteria()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( 1, 4, 'invalid criteria' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testSortFromOffsetDefaultCriteria()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortFromOffset( 1, 4, 'received' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testSortFromOffsetInvalidOffset()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->sortMessages( array( 1 ), 'subject' );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( "pine: test 2 with 8bit norwegian chars", $mail[0]->subject );
    }

    public function testSortMessagesEmpty()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $imap->fetchFlags( array( 1, 2, 3, 4 ) );
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testGetMessageNumbersFromSet()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "Inbox" );
        $set = $imap->fetchAll();
        $messageNumbers = $set->getMessageNumbers();
        $this->assertEquals( array( 1, 2, 3, 4 ), $messageNumbers );
    }

    public function testNoop()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->noop();
    }

    public function testNoopSelected()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "Inbox" );
        $imap->noop();
    }

    public function testNoopNotConnected()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $capabilities = $imap->capability();
        $this->assertTrue( in_array( 'IMAP4', $capabilities ) || in_array( 'IMAP4rev1', $capabilities ) );
    }

    public function testCapabilitySelected()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->selectMailbox( "Inbox" );
        $capabilities = $imap->capability();
        $this->assertTrue( in_array( 'IMAP4', $capabilities ) || in_array( 'IMAP4rev1', $capabilities ) );
    }

    public function testCapabilityNotConnected()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
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
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        $imap->createMailbox( "Guybrush" );
        $imap->selectMailbox( "Inbox" );
        $imap->copyMessages( "1:4", "Guybrush" );
        $imap->selectMailbox( "Guybrush" );
        $this->assertEquals( 4, $imap->countByFlag( "ALL" ) );
        $imap->delete( 1 );
        $imap->expunge();
        $this->assertEquals( 3, $imap->countByFlag( "ALL" ) );
        $imap->selectMailbox( "Inbox" );
        $imap->deleteMailbox( "Guybrush" );
    }

    public function testExpungeNotSelected()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $imap->expunge();
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function tearDown()
    {
        $imap = new ezcMailImapTransport( "dolly.ez.no" );
        $imap->authenticate( "ezcomponents", "ezcomponents" );
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

    public static function suite()
    {
        self::$ids = array( 15, 16, 17, 18 );
        return new PHPUnit_Framework_TestSuite( "ezcMailTransportImapTest" );
    }
}
?>

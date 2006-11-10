<?php
/**
 * File containing the ezcMailImapTransport class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * ezcMailImapTransport implements IMAP for mail retrieval.
 *
 * The implementation supports most of the basic commands specified in
 * http://www.faqs.org/rfcs/rfc1730.html
 *
 * @todo ignore messages of a certain size?
 * @todo // add support for SSL?
 * @todo // support for signing?
 * @todo listUniqueIdentifiers(): add UIVALIDITY value to UID (like in POP3).
 *       (if necessary).
 * @package Mail
 * @version //autogen//
 * @mainclass
 */
class ezcMailImapTransport
{
    /**
     * Internal state when the IMAP transport is not connected to a server.
     */
    const STATE_NOT_CONNECTED = 1;

    /**
     * Internal state when the IMAP transport is connected to a server,
     * but no successful authentication has been performed.
     */
    const STATE_NOT_AUTHENTICATED = 2;
    /**
     * Internal state when the IMAP transport is connected to a server
     * and authenticated, but no mailbox is selected yet.
     */
    const STATE_AUTHENTICATED = 3;

    /**
     * Internal state when the IMAP transport is connected to a server,
     * authenticated, and a mailbox is selected.
     */
    const STATE_SELECTED = 4;

    /**
     * Internal state when the IMAP transport is connected to a server,
     * authenticated, and a mailbox is selected read only.
     */
    const STATE_SELECTED_READONLY = 5;

    /**
     * Internal state when the LOGOUT command has been issued to the IMAP
     * server, but before the disconnect has taken place.
     */
    const STATE_LOGOUT = 6;

    /**
     * The response sent from the IMAP server is "OK".
     */
    const RESPONSE_OK = 1;

    /**
     * The response sent from the IMAP server is "NO".
     */
    const RESPONSE_NO = 2;

    /**
     * The response sent from the IMAP server is "BAD".
     */
    const RESPONSE_BAD = 3;

    /**
     * The response sent from the IMAP server is untagged (starts with "*").
     */
    const RESPONSE_UNTAGGED = 4;

    /**
     * The response sent from the IMAP server requires the client to send
     * information (starts with "+").
     */
    const RESPONSE_FEEDBACK = 5;

    /**
      * Basic flags are used by {@link setFlag()} and {@link clearFlag()}
      *     ANSWERED   Message has been answered
      *     DELETED    Message is marked to be deleted by later EXPUNGE
      *     DRAFT      Message has marked as a draft
      *     FLAGGED    Message is "flagged" for urgent/special attention
      *     SEEN       Message has been read
      *
      * @var array(int=>string)
      */
    private static $basicFlags = array( 'ANSWERED', 'DELETED', 'DRAFT', 'FLAGGED', 'SEEN' );

    /**
      * Extended flags are used by {@link searchByFlag()}
      *     ANSWERED   Message has been answered
      *     DELETED    Message is marked to be deleted by later EXPUNGE
      *     DRAFT      Message has marked as a draft
      *     FLAGGED    Message is "flagged" for urgent/special attention
      *     RECENT     Message is recent (cannot be set)
      *     SEEN       Message has been read
      *     UNANSWERED, UNDELETED, UNDRAFT, UNFLAGGED, OLD, UNSEEN
      *                Opposites of the above flags
      *     NEW        Equivalent to RECENT + UNSEEN
      *     ALL        All the messages
      *
      * @var array(int=>string)
      */
    private static $extendedFlags = array( 'ALL', 'ANSWERED', 'DELETED', 'DRAFT', 'FLAGGED', 'NEW', 'OLD', 'RECENT', 'SEEN', 'UNANSWERED', 'UNDELETED', 'UNDRAFT', 'UNFLAGGED', 'UNRECENT', 'UNSEEN' );

    /**
     * Used to generate a tag for sending commands to the IMAP server.
     *
     * @var string
     */
    private $currentTag = 'A0000';

    /**
     * Holds the connection state.
     *
     * @var int {@link STATE_NOT_CONNECTED},
     *          {@link STATE_NOT_AUTHENTICATED},
     *          {@link STATE_AUTHENTICATED},
     *          {@link STATE_SELECTED},
     *          {@link STATE_SELECTED_READONLY} or
     *          {@link STATE_LOGOUT}.
     */
    private $state = self::STATE_NOT_CONNECTED;

    /**
     * Holds the currently selected mailbox.
     *
     * @var string
     */
    private $selectedMailbox = null;

    /**
     * The connection to the IMAP server.
     *
     * @var ezcMailTransportConnection
     */
    private $connection = null;

    /**
     * Creates a new IMAP transport and connects to the $server.
     *
     * You can specify the $port if the IMAP server is not on the default port
     * 143. The constructor just calls the {@link connect()} method, and sets
     * the class variables {@link $this->server} and {@link $this->port} to
     * the respective parameters values.
     *
     * @throws ezcMailTransportException
     *         if it was not possible to connect to the server.
     * @param string $server
     * @param int $port
     */
    public function __construct( $server, $port = 143 )
    {
        $this->connection = new ezcMailTransportConnection( $server, $port );
        // get the server greeting
        $response = $this->connection->getLine();
        if ( strpos( $response, "* OK" ) === false )
        {
            throw new ezcMailTransportException( "The connection to the IMAP server is ok, but a negative response from server was received. Try again later." );
        }
        $this->state = self::STATE_NOT_AUTHENTICATED;
    }

    /**
     * Destructs the IMAP transport.
     *
     * If there is an open connection to the IMAP server it is closed.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Disconnects the transport from the IMAP server.
     */
    public function disconnect()
    {
        if ( $this->state != self::STATE_NOT_CONNECTED )
        {
            $tag = $this->getNextTag();
            $this->connection->sendData( "{$tag} LOGOUT" );
            // discard the "bye bye" message ("{$tag} OK Logout completed.")
            $this->getResponse( $tag );
            $this->state = self::STATE_LOGOUT;
            $this->selectedMailbox = null;

            $this->connection->close();
            $this->connection = null;
            $this->state = self::STATE_NOT_CONNECTED;
        }
    }

    /**
     * Authenticates the user to the IMAP server with $user and $password.
     *
     * This method should be called directly after the construction of this
     * object.
     * If authentication does not succeed, an ezcMailTransportException is
     * thrown.
     * If the server is waiting for authentication process to respond, the
     * connection with the IMAP server will be closed, and false is returned,
     * and it is the application's task to reconnect and reauthenticate.
     *
     * @throws ezcMailTransportException
     *         if there is no connection to the server
     *         or if the provided username/password combination did not work
     * @param string $user
     * @param string $password
     * @return bool
     */
    public function authenticate( $user, $password )
    {
        if ( $this->state != self::STATE_NOT_AUTHENTICATED )
        {
            throw new ezcMailTransportException( "Tried to authenticate when there was no connection or when already authenticated." );
        }
        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} LOGIN {$user} {$password}" );
        $response = $this->connection->getLine();
        if  ( strpos( $response, '* OK ' ) !== false )
        {
            // the server is busy waiting for authentication process to
            // respond, so it is a good idea to just close the connection,
            // otherwise the application will be halted until the server
            // recovers
            $this->connection->close();
            $this->connection = null;
            $this->state = self::STATE_NOT_CONNECTED;
            return false;
        }
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server did not accept the username and/or password." );
        }
        else
        {
            $this->state = self::STATE_AUTHENTICATED;
            $this->selectedMailbox = null;
        }
        return true;
    }

    /**
     * Lists the available mailboxes on the IMAP server.
     *
     * Before listing the mailboxes, the connection state ($state) must
     * be at least {@link STATE_AUTHENTICATED} or {@link STATE_SELECTED} or
     * {@link STATE_SELECTED_READONLY}.
     *
     * For more information about $reference and $mailbox, consult
     * the IMAP RFC document (http://www.faqs.org/rfcs/rfc1730.html).
     * By default, $reference is "" and $mailbox is "*".
     * The array returned contains the mailboxes available for the connected
     * user on this IMAP server. Inbox is a special mailbox, and it can be
     * specified upper-case or lower-case or mixed-case. The other mailboxes
     * should be specified as they are (to the selectMailbox() method).
     *
     * @throws ezcMailMailTransportException
     *         if $state is not accepted
     *         or if the combination $reference + $mailbox is not correct
     * @param string $reference
     * @param string $mailbox
     * @return array(int=>string)
     */
    public function listMailboxes( $reference = '', $mailbox = '*' )
    {
        if ( $this->state != self::STATE_AUTHENTICATED &&
             $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't list mailboxes when not successfully logged in." );
        }

        $result = array();
        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} LIST \"{$reference}\" \"{$mailbox}\"" );
        $response = $this->connection->getLine();
        while ( strpos( $response, '* LIST (' ) !== false )
        {
            // only consider the selectable mailboxes
            if ( strpos( $response, "\\Noselect" ) === false )
            {
                $response = substr( $response, strpos( $response, "\" " ) + 2 );
                $response = trim( $response );
                $response = trim( $response, "\"" );
                $result[] = $response;

            }
            $response = $this->connection->getLine();
        }

        $response = $this->getResponse( $tag, $response );
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The LIST parameters <\"{$reference}\"> and <\"{$mailbox}\"> are incorrect for this IMAP server." );
        }
        return $result;
    }

    /**
     * Selects the mailbox $mailbox.
     *
     * This method should be called after authentication, and before fetching
     * any messages.
     * Before selecting the mailbox, the connection state ($state) must
     * be at least {@link STATE_AUTHENTICATED}, {@link STATE_SELECTED} or
     * or {@link STATE_SELECTED_READONLY}.
     * If the selecting of the mailbox fails (with "NO" or "BAD" response
     * from the server), $state revert to STATE_AUTHENTICATED.
     * After successfully selecting a mailbox, $state will be STATE_SELECTED
     * or STATE_SELECTED_READONLY.
     * Inbox is a special mailbox and can be specified in whatever-case.
     *
     * @throws ezcMailMailTransportException
     *         if $state is not accepted
     *         or if $mailbox does not exist
     * @param string $mailbox
     * @param bool $readOnly
     */
    public function selectMailbox( $mailbox, $readOnly = false )
    {
        if ( $this->state != self::STATE_AUTHENTICATED &&
             $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't select a mailbox when not successfully logged in." );
        }

        $tag = $this->getNextTag();
        if ( $readOnly !== true ) 
        {
            $this->connection->sendData( "{$tag} SELECT \"{$mailbox}\"" );
            $state = self::STATE_SELECTED;
        }
        else 
        {
            $this->connection->sendData( "{$tag} EXAMINE \"{$mailbox}\"" );
            $state = self::STATE_SELECTED_READONLY;
        }
        $response = $this->getResponse( $tag );
        if ( $this->responseType( $response ) == self::RESPONSE_OK )
        {
            $this->state = $state;
            $this->selectedMailbox = $mailbox;
        }
        else
        {
            $this->state = self::STATE_AUTHENTICATED;
            $this->selectedMailbox = null;
            throw new ezcMailTransportException( "Mailbox <{$mailbox}> does not exist on the IMAP server." );
        }
    }

    /**
     * Creates the mailbox $mailbox.
     *
     * @throws ezcMailTransportException
     *         if $state is not accepted
     *         or if $mailbox exists
     *         or if $mailbox cannot be created
     * @param string $mailbox
     * @return bool
     */
    public function createMailbox( $mailbox )
    {
        if ( $this->state != self::STATE_AUTHENTICATED &&
             $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't create a mailbox when not successfully logged in." );
        }

        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} CREATE \"{$mailbox}\"" );
        $response = $this->getResponse( $tag );
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not create mailbox <{$mailbox}>: {$response}" );
        }
        return true;
    }

    /**
     * Renames the mailbox $mailbox to $newName.
     *
     * Inbox cannot be renamed.
     *
     * @throws ezcMailTransportException
     *         if $state is not accepted
     *         or if trying to rename the currently selected mailbox
     *         or if $mailbox does not exist
     *         or if $mailbox cannot be renamed
     *         or if mailbox $newName already exists
     * @param string $mailbox
     * @param string $newName
     * @return bool
     */
    function renameMailbox( $mailbox, $newName )
    {
        if ( $this->state != self::STATE_AUTHENTICATED &&
             $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't rename a mailbox when not successfully logged in." );
        }

        if ( strtolower( $this->selectedMailbox ) == strtolower( $mailbox ) )
        {
            throw new ezcMailTransportException( "Can't rename the currently selected mailbox." );
        }

        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} RENAME \"{$mailbox}\" \"{$newName}\"" );
        $response = $this->getResponse( $tag );
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not rename the mailbox <{$mailbox}> to <{$newName}>: {$response}" );
        }
        return true;
    }

    /**
     * Deletes the mailbox $mailbox.
     *
     * Inbox cannot be deleted.
     *
     * @throws ezcMailTransportException
     *         if $state is not accepted
     *         or if trying to delete the currently selected mailbox
     *         or if $mailbox does not exist
     *         or if $mailbox cannot be deleted
     * @param string $mailbox
     * @return bool
     */
    public function deleteMailbox( $mailbox )
    {
        if ( $this->state != self::STATE_AUTHENTICATED &&
             $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't delete a mailbox when not successfully logged in." );
        }

        if ( strtolower( $this->selectedMailbox ) == strtolower( $mailbox ) )
        {
            throw new ezcMailTransportException( "Can't delete the currently selected mailbox." );
        }

        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} DELETE \"{$mailbox}\"" );
        $response = $this->getResponse( $tag );
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not delete the mailbox <{$mailbox}>: {$response}" );
        }
        return true;
    }

    /** 
     * Copies message(s) from the selected mailbox to mailbox $destination.
     *
     * $messages can be:
     * - a single message number (eg: 1)
     * - a message range (eg. 1:4)
     * - a message list (eg. 1,2,4)
     *
     * @throws ezcMailTransportException
     *         if $state is not accepted
     *         or if $destination does not exist
     *         or if storage space is not enough
     *         or if $messages contains unexistent messages
     * @param string $messages
     * @param string $destination
     * @return bool
     */
    public function copyMessages( $messages, $destination )
    {
        if ( $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't copy messages on the IMAP transport when a mailbox is not selected." );
        }
    
        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} COPY {$messages} \"{$destination}\"" );
        
        $response = $this->getResponse( $tag );
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not copy  <{$messages}> to <{$destination}>: {$response}" );
        }
        return true;
    }

    /**
     * Returns a list of the not deleted messages in the current mailbox.
     *
     * It returns only the messages with the flag \Deleted not set.
     * The format of the returned array is array(message_id => size).
     * Eg: ( 2 => 1700, 5 => 1450, 6 => 21043 )
     * If $contentType is set, it returns only the messages with
     * $contentType in the Content-Type header.
     * For example $contentType can be "multipart/mixed" to return only the
     * messages with attachments.
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if the server sent a negative response
     * @param string $contentType
     * @return array(int=>int)
     */
    public function listMessages( $contentType = null )
    {
        if ( $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't call listMessages() on the IMAP transport when a mailbox is not selected." );
        }

        $messageList = array();
        $messages = array();
 
        // get the numbers of the existing messages
        $tag = $this->getNextTag();
        $command = "{$tag} SEARCH UNDELETED";
        if ( !is_null( $contentType ) )
        {
            $command .= " HEADER \"Content-Type\" \"{$contentType}\"";
        }
        $this->connection->sendData( $command );
        $response = $this->getResponse( '* SEARCH' );
        if ( strpos( $response, '* SEARCH' ) !== false )
        {
            $ids = trim( substr( $response, 9 ) );
            if ( trim( $ids ) !== "" )
            {
                $messageList = explode( ' ', $ids );
            }
        }
        // skip the OK response ("{$tag} OK Search completed.")
        $response = $this->getResponse( $tag, $response );
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not list messages: {$response}" );
        }

        if ( !empty( $messageList ) )
        {
            // get the sizes of the messages
            $tag = $this->getNextTag();
            $query = trim( implode( ',', $messageList ) );
            $this->connection->sendData( "{$tag} FETCH {$query} RFC822.SIZE" );
            $response = $this->getResponse( 'FETCH (' );
            $currentMessage = trim( reset( $messageList ) );
            while ( strpos( $response, 'FETCH (' ) !== false )
            {
                $line = $response;
                $line = explode( ' ', $line );
                $line = trim( $line[count( $line ) - 1] );
                $line = substr( $line, 0, strlen( $line ) - 1 );
                $messages[$currentMessage] = intval( $line );
                $currentMessage = next( $messageList );
                $response = $this->connection->getLine();
            }
            // skip the OK response ("{$tag} OK Fetch completed.")
            $response = $this->getResponse( $tag, $response );
            if ( $this->responseType( $response ) != self::RESPONSE_OK )
            {
                throw new ezcMailTransportException( "The IMAP server could not list messages: {$response}" );
            }
        }
        return $messages;
    }

    /**
     * Returns information about messages in the selected mailbox.
     *
     * The information returned through parameters is:
     * $numMessages = number of not deleted messages in the selected mailbox
     * $sizeMessages = sum of the not deleted messages sizes
     * $recent = number of recent and not deleted messages
     * $unseen = number of unseen and not deleted messages
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if the server sent a negative response
     * @param int &$numMessages
     * @param int &$sizeMessages
     * @param int &$recent
     * @param int &$unseen
     * @return bool
     */
    public function status( &$numMessages, &$sizeMessages, &$recent = 0, &$unseen = 0 )
    {
        if ( $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't call status() on the IMAP transport when a mailbox is not selected." );
        }
        $messages = $this->listMessages();
        $numMessages = count( $messages );
        $sizeMessages = array_sum( $messages );
        $messages = array_keys( $messages );
        $recentMessages = array_intersect( $this->searchByFlag( "RECENT" ), $messages );
        $unseenMessages = array_intersect( $this->searchByFlag( "UNSEEN" ), $messages );
        $recent = count( $recentMessages );
        $unseen = count( $unseenMessages );
        return true;
    }

    /**
     * Deletes the message with the message number $msgNum from the server.
     *
     * The message number must be a valid identifier fetched with e.g.
     * listMessages().
     * The message is not physically deleted, but has its \Deleted flag set,
     * and can be later undeleted by clearing its \Deleted flag
     * {@see clearFlag()}.
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if the server sent a negative response
     * @param $msgNum
     * @return bool
     */
    public function delete( $msgNum )
    {
        if ( $this->state != self::STATE_SELECTED )
        {
            throw new ezcMailTransportException( "Can't delete a message when a mailbox is not selected." );
        }
        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} STORE {$msgNum} +FLAGS (\\Deleted)" );

        // get the response (should be "{$tag} OK Store completed.")
        $response = $this->getResponse( $tag );
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not delete the message <{$msgNum}>: {$response}" );
        }
        return true;
    }

    /**
     * Returns the headers and the first characters from message $msgNum.
     *
     * If the command failed or if it was not supported by the server an empty
     * string is returned.
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if the server sent a negative response
     * @param int $msgNum
     * @param int $chars
     * @return string
     */
    public function top( $msgNum, $chars = 0 )
    {
        if ( $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't call top() on the IMAP transport when a mailbox is not selected." );
        }

        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} FETCH {$msgNum} (RFC822.HEADER BODY[TEXT]<0.{$chars}>)" );
        $response = $this->getResponse( 'FETCH (' );
        $message = "";
        if ( strpos( $response, 'FETCH (' ) !== false )
        {
            $response = "";
            while ( strpos( $response, 'BODY[TEXT]' ) === false )
            {
                $message .= $response;
                $response = $this->connection->getLine();
            }

            $response = $this->connection->getLine();
            while ( strpos( $response, $tag ) === false )
            {
                $message .= $response;
                $response = $this->connection->getLine();
            }
        }
        // skip the OK response ("{$tag} OK Fetch completed.")
        $response = $this->getResponse( $tag, $response );
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not fetch the message <{$msgNum}>: {$response}" );
        }
        return $message;
    }

    /**
     * Returns the unique identifiers messages on the IMAP server.
     *
     * You can fetch the unique identifier for a specific message only by
     * providing the $msgNum parameter.
     *
     * The unique identifier can be used to recognize mail from servers
     * between requests. In contrast to the message numbers the unique
     * numbers assigned to an email never changes.
     *
     * The format of the returned array is array(message_num => unique_id)
     *
     * @todo add UIVALIDITY value to UID (like in POP3) (if necessary).
     * 
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     * @param int $msgNum
     * @return array(int=>string)
     */
    public function listUniqueIdentifiers( $msgNum = null )
    {
        if ( $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't call listUniqueIdentifiers() on the IMAP transport when a mailbox is not selected." );
        }

        $result = array();
        if ( $msgNum !== null )
        {
            $tag = $this->getNextTag();
            $this->connection->sendData( "{$tag} UID SEARCH {$msgNum}" );
            $response = $this->getResponse( '* SEARCH' );
            if ( strpos( $response, '* SEARCH' ) !== false )
            {
                $result[(int)$msgNum] = trim( substr( $response, 9 ) );
            }
            $response = $this->getResponse( $tag, $response );
        }
        else
        {
            $uids = array();
            $messages = array_keys( $this->listMessages() );
            $tag = $this->getNextTag();
            $this->connection->sendData( "{$tag} UID SEARCH UNDELETED" );
            $response = $this->getResponse( '* SEARCH' );
            if ( strpos( $response, '* SEARCH' ) !== false )
            {
                $response = trim( substr( $response, 9 ) );
                if ( $response !== "" )
                {
                    $uids = explode( ' ', $response );
                }
                for ( $i = 0; $i < count( $messages ); $i++ )
                {
                    $result[trim( $messages[$i] )] = $uids[$i];
                }
            }
            $response = $this->getResponse( $tag );
        }
        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not fetch the unique identifiers: {$response}" );
        }
        return $result;
    }

    /**
     * Returns a parserset with all the messages on the server.
     *
     * If $deleteFromServer is set to true the mail will be removed from the
     * server after retrieval. If not it will be left.
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     * @param bool $deleteFromServer
     * @return ezcMailParserSet
     */
    public function fetchAll( $deleteFromServer = false )
    {
        $messages = $this->listMessages();
        return new ezcMailImapSet( $this->connection, array_keys( $messages ), $deleteFromServer );
    }

    /**
     * Returns an ezcMailImapSet containing only the $number -th message in
     * the mailbox.
     *
     * If $deleteFromServer is set to true the mail will be removed from the
     * server after retrieval. If not it will be left.
     * Note: for IMAP the first message is 1 (so for $number = 0 the exception
     * will be thrown).
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     * @throws ezcMailNoSuchMessageException
     *         if the message $number is out of range
     * @param int $number
     * @param bool $deleteFromServer
     * @return ezcMailImapSet
     */
    public function fetchByMessageNr( $number, $deleteFromServer = false )
    {
        $messages = $this->listMessages();
        if ( !isset( $messages[$number] ) )
        {
            throw new ezcMailNoSuchMessageException( $number );
        }
        else
        {
            return new ezcMailImapSet( $this->connection, array( 0 => $number ), $deleteFromServer );
        }
    }

    /**
     * Returns an ezcMailImapSet with $count messages starting from $offset.
     *
     * Fetches $count messages starting from the $offset and returns them as a
     * ezcMailImapSet. If $count is not specified or if it is 0, it fetches
     * all messages starting from the $offset.
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     * @throws ezcMailInvalidLimitException
     *         if $count is negative.
     * @throws ezcMailOffsetOutOfRangeException
     *         if $offset is outside of the existing range of messages
     * @param int $offset
     * @param int $count
     * @param bool $deleteFromServer
     * @return ezcMailImapSet
     */
    public function fetchFromOffset( $offset, $count = 0, $deleteFromServer = false )
    {
        if ( $count < 0 )
        {
            throw new ezcMailInvalidLimitException( $offset, $count );
        }
        $messages = array_keys( $this->listMessages() );
        if ( $count == 0 )
        {
            $range = array_slice( $messages, $offset - 1, count( $messages ), true );
        }
        else
        {
            $range = array_slice( $messages, $offset - 1, $count, true );
        }
        if ( !isset( $range[$offset - 1] ) )
        {
            throw new ezcMailOffsetOutOfRangeException( $offset, $count );
        }
        return new ezcMailImapSet( $this->connection, $range, $deleteFromServer );
    }

    /**
     * Fetches $count messages from $offset sorted by $sortCriteria.
     *
     * Fetches $count messages starting from the $offset and returns them as a
     * ezcMailImapSet. If $count is is 0, it fetches all messages starting from
     * the $offset.
     * $sortCriteria is an email header like: Subject, To, From, Date, Sender.
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response.
     * @throws ezcMailInvalidLimitException
     *         if $count is negative.
     * @throws ezcMailOffsetOutOfRangeException
     *         if $offset is outside of the existing range of messages
     * @param int $offset
     * @param int $count
     * @param string $sortCriteria
     * @param bool $reverse
     * @return ezcMailImapSet
     */
    public function sortFromOffset( $offset, $count = 0, $sortCriteria, $reverse = false )
    {
        if ( $count < 0 )
        {
            throw new ezcMailInvalidLimitException( $offset, $count );
        }

        $messageCount = $this->countByFlag( 'ALL' );
        $messages = array_keys( $this->sort( range( 1, $messageCount ), $sortCriteria, $reverse ) );

        if ( $count == 0 )
        {
            $range = array_slice( $messages, $offset - 1, count( $messages ), true );
        }
        else
        {
            $range = array_slice( $messages, $offset - 1, $count, true );
        }
        if ( !isset( $range[$offset - 1] ) )
        {
            throw new ezcMailOffsetOutOfRangeException( $offset, $count );
        }
        return new ezcMailImapSet( $this->connection, $range );
    }

    /**
     * Fetches messages $messages sorted by $sortCriteria.
     *
     * $messages is an array of message numbers, for example:
     *      array( 1, 2, 4 );
     * $sortCriteria is an email header like: Subject, To, From, Date, Sender.
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     *         or if array $messages is empty
     * @param array(int=>int) $messages
     * @param string $sortCriteria
     * @param bool $reverse
     * @return ezcMailImapSet
     */
    public function sortMessages( $messages, $sortCriteria, $reverse = false )
    {
        $messages = $this->sort( $messages, $sortCriteria, $reverse );
        return new ezcMailImapSet( $this->connection, array_keys ( $messages ) );
    }

    /**
     * Fetches messages by a certain flag.
     *
     * $flag can be one of:
     *      ANSWERED   Message has been answered
     *      DELETED    Message is marked to be deleted by later EXPUNGE
     *      DRAFT      Message has marked as a draft
     *      FLAGGED    Message is "flagged" for urgent/special attention
     *      RECENT     Message is recent
     *      SEEN       Message has been read
     *      UNANSWERED, UNDELETED, UNDRAFT, UNFLAGGED, OLD, UNSEEN
     *                 Opposites of the above flags
     *      NEW        Equivalent to RECENT + UNSEEN
     *      ALL        All the messages
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     *         or if $flag is not valid
     * @param string $flag
	 * @return ezcMailImapSet
     */
    public function fetchByFlag( $flag )
    {
        $messages = $this->searchByFlag( $flag );
        return new ezcMailImapSet( $this->connection, $messages );
    }

    /**
     * Wrapper function to fetch count of messages by a certain flag.
     *
     * $flag can be one of:
     *      ANSWERED   Message has been answered
     *      DELETED    Message is marked to be deleted by later EXPUNGE
     *      DRAFT      Message has marked as a draft
     *      FLAGGED    Message is "flagged" for urgent/special attention
     *      RECENT     Message is recent
     *      SEEN       Message has been read
     *      UNANSWERED, UNDELETED, UNDRAFT, UNFLAGGED, OLD, UNSEEN
     *                 Opposites of the above flags
     *      NEW        Equivalent to RECENT + UNSEEN
     *      ALL        All the messages
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     *         or if $flag is not valid
     * @param string $flag
	 * @return int
     */
    public function countByFlag( $flag )
    {
        $flag = $this->normalizeFlag( $flag );
        $messages = $this->searchByFlag( $flag );
        return count( $messages );
    }

    /**
     * Fetches IMAP flags for messages $messages.
     *
     * $messages is an array of message numbers, for example:
     *      array( 1, 2, 4 );
     * The format of the returned array is:
     * array( message_number => array( flags ) )
     * Example:
     * - for
     *      $messages = array( 1, 2, 4 );
     * - the returned flags are
     *      array( 1 => array( '\Seen' ),
     *             2 => array( '\Seen' ),
     *             4 => array( '\Seen', 'NonJunk' )
     *           );
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     * @param array $messages
     * @return array(int=>mixed)
     */
    public function fetchFlags( $messages )
    {
        if ( $this->state != self::STATE_SELECTED &&
			 $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't fetch flags on the IMAP transport when a mailbox is not selected." );
        }

        $flags = array();
        $ids = implode( $messages, ',' );

        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} FETCH {$ids} (FLAGS)" );

        $response = $this->connection->getLine();
        while ( strpos( $response, $tag ) === false )
        {
            if ( strpos( $response, ' FETCH (' ) !== false )
            {
                preg_match( '/\*\s(.*)\sFETCH\s\(FLAGS \((.*)\)/U', $response, $matches );
                $parts = explode( ' ', $matches[2] );
                $flags[intval( $matches[1] )] = $parts;
            }
            $response = $this->connection->getLine();
        }         

        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not fetch flags for the messages: {$response}" );
        }
        return $flags;
    }

    /**
     * Sets $flag on $messages.
     *
     * $messages can be:
     * - a single message number (eg. 1)
     * - a message range (eg. 1:4)
     * - a message list (eg. 1,2,4)
     * $flag can be one of:
     *      ANSWERED   Message has been answered
     *      DELETED    Message is marked to be deleted by later EXPUNGE
     *      DRAFT      Message has marked as a draft
     *      FLAGGED    Message is "flagged" for urgent/special attention
     *      SEEN       Message has been read
     * This function automatically adds the '\' in front of the flag when
     * calling the server command.
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     *         or if $flag is not valid
     * @param string $messages
     * @param string $flag
     * @return bool
     */
    public function setFlag( $messages, $flag )
    {
        if ( $this->state != self::STATE_SELECTED )
        {
            throw new ezcMailTransportException( "Can't set flags when a mailbox is not selected." );
        }

        $flag = $this->normalizeFlag( $flag );
        if( in_array( $flag, self::$basicFlags ) )
        {
            $tag = $this->getNextTag();
            $this->connection->sendData( "{$tag} STORE {$messages} +FLAGS (\\{$flag})" );
            $response = $this->getResponse( $tag );
            if ( $this->responseType( $response ) != self::RESPONSE_OK )
            {
                throw new ezcMailTransportException( "The IMAP server could not set flag <{$flag}> on the messages <{$messages}>: {$response}" );
            }
        }
        else
        {
            throw new ezcMailTransportException( "Flag <{$flag}> is not allowed for setting." );
        }
        return true;
    }

    /**
     * Clears $flag from $messages.
     *
     * $messages can be:
     * - a single message number (eg. 1)
     * - a message range (eg. 1:4)
     * - a message list (eg. 1,2,4)
     * $flag can be one of:
     *      ANSWERED   Message has been answered
     *      DELETED    Message is marked to be deleted by later EXPUNGE
     *      DRAFT      Message has marked as a draft
     *      FLAGGED    Message is "flagged" for urgent/special attention
     *      SEEN       Message has been read
     * This function automatically adds the '\' in front of the flag when
     * calling the server command.
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     *         or if $flag is not valid
     * @param string $messages
     * @param string $flag
     * @return bool
     */
    public function clearFlag( $messages, $flag )
    {
        if ( $this->state != self::STATE_SELECTED )
        {
            throw new ezcMailTransportException( "Can't clear flags when a mailbox is not selected." );
        }

        $flag = $this->normalizeFlag( $flag );
        if( in_array( $flag, self::$basicFlags ) )
        {
            $tag = $this->getNextTag();
            $this->connection->sendData( "{$tag} STORE {$messages} -FLAGS (\\{$flag})" );
            $response = $this->getResponse( $tag );
            if ( $this->responseType( $response ) != self::RESPONSE_OK )
            {
                throw new ezcMailTransportException( "The IMAP server could not clear flag <{$flag}> on the messages <{$messages}>: {$response}" );
            }
        }
        else
        {
            throw new ezcMailTransportException( "Flag <{$flag}> is not allowed for clearing." );
        }
        return true;
    }

    /**
     * Finds messages in the selected mailbox by a certain flag.
     *
     * $flag can be one of:
     *      ANSWERED   Message has been answered
     *      DELETED    Message is marked to be deleted by later EXPUNGE
     *      DRAFT      Message has marked as a draft
     *      FLAGGED    Message is "flagged" for urgent/special attention
     *      RECENT     Message is recent
     *      SEEN       Message has been read
     *      UNANSWERED, UNDELETED, UNDRAFT, UNFLAGGED, OLD, UNSEEN
     *                 Opposites of the above flags
     *      NEW        Equivalent to RECENT + UNSEEN
     *      ALL        All the messages
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     *         or if $flag is not valid
     * @param string $flag
	 * @return array(int=>int)
     */
    private function searchByFlag( $flag )
    {
        if ( $this->state != self::STATE_SELECTED &&
			 $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't search by flags on the IMAP transport when a mailbox is not selected." );
        }

        $matchingMessages = array();
        $flag = $this->normalizeFlag( $flag );
		if( in_array( $flag, self::$extendedFlags ) )
        {
            $tag = $this->getNextTag();
            $this->connection->sendData( "{$tag} SEARCH ({$flag})" );
            $response = $this->getResponse( '* SEARCH' );

            if ( strpos( $response, '* SEARCH' ) !== false )
            {
                $ids = substr( trim( $response ), 9 );
                if ( trim( $ids ) !== "" )
                {
                    $matchingMessages = explode( ' ', $ids );
                }
            }
            $response = $this->getResponse( $tag, $response );
            if ( $this->responseType( $response ) != self::RESPONSE_OK )
            {
                throw new ezcMailTransportException( "The IMAP server could not search the messages by flags: {$response}" );
            }
        }
        else
        {
            throw new ezcMailTransportException( "Flag <{$flag}> is not allowed for searching." );
        }
        return $matchingMessages;
    }

    /**
     * Clears $flag of unwanted characters and makes it uppercase.
     *
     * @param string $flag
     * @return string
     */ 
    private function normalizeFlag( $flag )
    {
        $flag = strtoupper( $flag );
        $flag = str_replace( '\\', '', $flag );
        return $flag;
    }

    /**
     * Sorts message numbers array $messages by the specified $sortCriteria.
     *
     * $messages is an array of message numbers, for example:
     *      array( 1, 2, 4 );
     * $sortCriteria is an email header like: Subject, To, From, Date, Sender.
     * The sorting is done with the php function natcasesort().
     *
     * @throws ezcMailTransportException
     *         if a mailbox is not selected
     *         or if server sent a negative response
     *         or if the array $messages is empty
     * @param array(int=>int) $messages
     * @param string $sortCriteria
     * @param bool $reverse
     * @return array(int=>string)
     */
    private function sort( $messages, $sortCriteria, $reverse = false )
    {
        if ( $this->state != self::STATE_SELECTED &&
             $this->state != self::STATE_SELECTED_READONLY )
        {
            throw new ezcMailTransportException( "Can't sort on the IMAP transport when a mailbox is not selected." );
        }

        $result = array();
        $query = ucfirst( strtolower( $sortCriteria ) );
        $messageNumbers = implode( ',', $messages );

        $tag = $this->getNextTag();
        $this->connection->sendData( "{$tag} FETCH {$messageNumbers} (BODY[HEADER.FIELDS ({$query})])" );

        $response = $this->connection->getLine();
        while ( strpos( $response, $tag ) === false )
        {
            if ( strpos( $response, ' FETCH (' ) !== false )
            {
                preg_match('/^\* ([0-9]+) FETCH/', $response, $matches );
                $messageNumber = $matches[1];
            }

            if ( strpos( $response, $query ) !== false )
            {
                $strippedResponse = trim( trim( str_replace( "{$query}: ", '', $response ) ), '"' );
                switch ( $query )
                {
                    case 'Date':
                        $strippedResponse = strtotime( $strippedResponse );
                        break;
                    case 'Subject':
                    case 'From':
                    case 'Sender':
                    case 'To':
                        $strippedResponse = ezcMailTools::mimeDecode( $strippedResponse );
                        break;
                    default:
                        break;
                }
                $result[$messageNumber] = $strippedResponse;
            }

            // in case the mail doesn't have the $sortCriteria header (like junk mail missing Subject header)
            if ( strpos( $response, ')' ) !== false && !isset( $result[$messageNumber] ) )
            {
                $result[$messageNumber] = '';
            }

            $response = $this->connection->getLine();
        }

        if ( $this->responseType( $response ) != self::RESPONSE_OK )
        {
            throw new ezcMailTransportException( "The IMAP server could not sort the messages: {$response}" );
        }

        if ( $reverse === true )
        {
            natcasesort( $result );
            $result = array_reverse( $result, true );
        }
        else
        {
            natcasesort( $result );
        }
        return $result;
    }

    /**
      * Parses $line to return the response code.
      *
      * Returns one of the following:
      *     {@link RESPONSE_OK}
      *     {@link RESPONSE_NO}
      *     {@link RESPONSE_BAD}
      *     {@link RESPONSE_UNTAGGED}
      *     {@link RESPONSE_FEEDBACK}
      *
      * @throws ezcMailTransportException
      *         if the IMAP response ($line) is not recognized
      * @param string $line
      * @return int
      */
    private function responseType( $line )
    {
        if ( strpos( $line, 'OK ' ) !== false && strpos( $line, 'OK ' ) == 6 )
        {
            return self::RESPONSE_OK;
        }
        if ( strpos( $line, 'NO ' ) !== false && strpos( $line, 'NO ' ) == 6 )
        {
            return self::RESPONSE_NO;
        }
        if ( strpos( $line, 'BAD ' ) !== false && strpos( $line, 'BAD ' ) == 6 )
        {
            return self::RESPONSE_BAD;
        }
        if ( strpos( $line, '* ' ) !== false && strpos( $line, '* ' ) == 0 )
        {
            return self::RESPONSE_UNTAGGED;
        }
        if ( strpos( $line, '+ ' ) !== false && strpos( $line, '+ ' ) == 0 )
        {
            return self::RESPONSE_FEEDBACK;
        }
        throw new ezcMailTransportException( "Unrecognized IMAP response in line: {$line}" );
    }

    /**
      * Reads the responses from the server until encountering $tag.
      *
      * In IMAP, each command sent by the client is prepended with a
      * alphanumeric tag like 'A1234'. The server sends the response
      * to the client command as lines, and the last line in the response
      * is prepended with the same tag, and it contains the status of
      * the command completion ('OK', 'NO' or 'BAD').
      * Sometimes the server sends alerts and response lines from other
      * commands before sending the tagged line, so this method just
      * reads all the responses until it encounters $tag.
      * It returns the tagged line to be processed by the calling method.
      * If $response is specified, then it will not read the response
      * from the server before searching for $tag in $response.
      *
      * @param string $tag
      * @param string $response
      * @return string
      */
    private function getResponse( $tag, $response = null )
    {
        if ( is_null( $response ) )
        {
            $response = $this->connection->getLine();
        }
        while ( strpos( $response, $tag ) === false )
        {
            if ( strpos( $response, ' BAD ' ) !== false ||
                 strpos( $response, ' NO ' ) !== false )
            {
                break;
            }
            $response = $this->connection->getLine();
        }
        return $response;
    }

    /**
      * Generates the next IMAP tag to prepend to client commands.
      *
      * The structure of the IMAP tag is Axxxx, where
      *     A is a letter (uppercase for conformity)
      *     x is a digit from 0 to 9
      * example of generated tag: T5439
      * It uses the class variable {@link $this->currentTag}.
      * Everytime it is called, the tag increases by 1.
      * If it reaches the last tag, it wraps around to the first tag.
      * By default, the first generated tag is A0001.
      *
      * @return string
      */
    private function getNextTag()
    {
        $tagLetter = substr( $this->currentTag, 0, 1 );
        $tagNumber = intval( substr( $this->currentTag, 1 ) );
        $tagNumber++;
        if ( $tagLetter == 'Z' && $tagNumber == 10000 )
        {
            $tagLetter = 'A';
            $tagNumber = 1;
        }
        if ( $tagNumber == 10000 )
        {
            $tagLetter++;
            $tagNumber = 0;
        }
        $this->currentTag = $tagLetter . sprintf( "%04s", $tagNumber );
        return $this->currentTag;
    }
}
?>

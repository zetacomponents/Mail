<?php
/**
 * File containing the ezcMailImapSet class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * ezcMailImapSet is an internal class that fetches a series of mail
 * from the imap server.
 *
 * The imap set works on an existing connection and a list of the messages that
 * the user wants to fetch. The user must accept all the data for each mail for
 * correct behaviour.
 *
 * @package Mail
 * @version //autogen//
 * @access private
 */
class ezcMailImapSet implements ezcMailParserSet
{
    /**
     * Holds the list of messages that the user wants to retrieve from the server.
     *
     * @var array(int)
     */
    private $messages;

    /**
     * Holds the current message the user is fetching.
     *
     * The variable is null before the first message and false after
     * the last message has been fetched.
     *
     * @var int
     */
    private $currentMessage = null;

    /**
     * Holds the line that will be read-ahead in order to determine the trailing paranthesis.
     *
     * @var string
     */
    private $nextData = null;

    /**
     * This variable is true if there is more data in the mail that is being fetched.
     *
     * It is false if there is no mail being fetched currently or if all the data of the current mail
     * has been fetched.
     *
     * @var bool
     */
    private $hasMoreMailData = false;

    /**
     * Holds if mail should be deleted from the server after retrieval.
     *
     * @var bool
     */
    private $deleteFromServer = false;

    /**
     * Used to generate a tag for sending commands to the IMAP server.
     * 
     * @var string
     */
    private $currentTag = 'A0000';

    /**
     * Constructs a new imap parser set that will fetch the messages with the
     * id's.
     *
     * $connection must hold a valid connection to a imap server that is ready to retrieve
     * the messages.
     *
     * If $deleteFromServer is set to true the messages will be deleted after retrieval.
     *
     * @throws ezcMailTransportException
     *         if the server send a negative response.
     * @param ezcMailTransportConnection $connection
     * @param array(int) $messages
     * @param bool $deleteFromServer
     */
    public function __construct( ezcMailTransportConnection $connection, array $messages, $deleteFromServer = false )
    {
        $this->connection = $connection;
        $this->messages = $messages;
        $this->deleteFromServer = $deleteFromServer;
        $this->nextData = null;
    }

    /**
     * Returns true if all the data has been fetched from this set.
     *
     * @return bool
     */
    public function isFinished()
    {
        return $this->currentMessage === false ? true : false;
    }

    /**
     * Returns one line of data from the current mail in the set.
     *
     * Null is returned if there is no current mail in the set or
     * the end of the mail is reached,
     *
     * @return string
     */
    public function getNextLine()
    {
        if ( $this->currentMessage === null )
        {
            // Instead of calling $this->nextMail() in the constructor, it is called
            // here, to avoid sending commands to the server when creating the set, and
            // instead send the server commands when parsing the set (see ezcMailParser).
            $this->nextMail();
        }
        if ( $this->hasMoreMailData )
        {
            $data = ( $this->nextData === null ) ? $this->connection->getLine() : $this->nextData;
            if ( strpos( $data, $this->currentTag ) === false )
            {
                $this->nextData = $this->connection->getLine();
                if ( trim( $data ) === ')' && strpos( $this->nextData, $this->currentTag ) === 0 )
                {
                    $this->hasMoreMailData = false;
                    // remove the mail if required by the user.
                    if ( $this->deleteFromServer === true )
                    {
                        $tag = $this->getNextTag();
                        $this->connection->sendData( "{$tag} STORE {$this->currentMessage} +FLAGS (\\Deleted)" );
                        // skip OK response ("{$tag} OK Store completed.")
                        $response = $this->getResponse( $tag );
                    }
                    return null;
                }
            }
            return $data;
        }
        return null;
    }

    /**
     * Moves the set to the next mail and returns true upon success.
     *
     * False is returned if there are no more mail in the set.
     *
     * @throws ezcMailTransportException
     *         if the server sent a negative response.
     * @return bool
     */
    public function nextMail()
    {
        if ( $this->currentMessage === null )
        {
            $this->currentMessage = reset( $this->messages );
        }
        else
        {
            $this->currentMessage = next( $this->messages );
        }
        $this->nextData = null;
        if ( $this->currentMessage !== false )
        {
            $tag = $this->getNextTag();
            $this->connection->sendData( "{$tag} FETCH {$this->currentMessage} RFC822" );
            $response = $this->connection->getLine();
            if ( strpos( $response, ' NO ' ) !== false ||
                 strpos( $response, ' BAD ') !== false )
            {
                throw new ezcMailTransportException( "The IMAP server sent a negative reply when requesting mail." );
            }
            else
            {
                $response = $this->getResponse( 'FETCH (', $response );
                if ( strpos( $response, 'FETCH (' ) !== false )
                {
                    $this->hasMoreMailData = true;
                    return true;
                }
                else
                {
                    $response = $this->getResponse( $tag );
                    if ( strpos( $response, 'OK ' ) === false )
                    {
                        throw new ezcMailTransportException( "The IMAP server sent a negative reply when requesting mail." );
                    }
                }
            }
        }
        return false;
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
      * reads all the responses until it encountering $tag.
      * It returns the tagged line to be processed by the calling method.
      * If $response is specified, then it will not read the response
      * from the server before searching for $tag in $response.
      *
      * @param string $tag
      * @param string $response
      * @return string
      */
    private function getResponse( $tag = null, $response = null )
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
      *     A is a letter (uppercase for conformity, it can be lowercase also)
      *     x is a digit from 0 to 9
      * example of generated tag: T5439
      * It uses the class variable {@link $this->currentTag}.
      * Everytime it is called, the tag increases by 1.
      * If it reaches the last tag, it wraps around to the first tag.
      * By default, the first tag is A0001.
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

    /**
     * Returns whether the set has mails
     *
     * @return bool
     */
    public function hasData()
    {
        return count( $this->messages );
    }

    /**
     * Returns message numbers for current set.
     *
     * @return array(int=>int)
     */
    public function getMessageNumbers()
    {
        return $this->messages;
    }
}
?>

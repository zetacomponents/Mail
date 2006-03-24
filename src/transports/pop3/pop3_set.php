<?php
/**
 * File containing the ezcMailPop3Set class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * ezcMailPop3Set is an internal class that fetches a series of mail
 * from the pop3 server.
 *
 * The pop3 set works on an existing connection and a list of the messages that
 * the user wants to fetch. The user must accept all the data for each mail for
 * correct behaviour.
 *
 * @package Mail
 * @version //autogen//
 * @access private
 */
class ezcMailPop3Set implements ezcMailParserSet
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
     */
    private $leaveOnServer = false;

    /**
     * Constructs a new pop3 parser set that will fetch the messages with the
     * id's.
     *
     * $connection must hold a valid connection to a pop3 server that is ready to retrieve
     * the messages.
     *
     * If $leaveOnServer is set to true the messages will not be deleted after retrieval.
     *
     * @throws ezcMailTransportException if the server sent a negative reply when requesting the first mail.
     */
    public function __construct( ezcMailTransportConnection $connection, array $messages, $leaveOnServer = false )
    {
        $this->connection = $connection;
        $this->messages = $messages;
        $this->leaveOnServer = $leaveOnServer;
        $this->nextMail();
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
     * Returns one line of data from the current mail in the set
     * excluding the ending linebreak.
     *
     * Null is returned if there is no current mail in the set or
     * the end of the mail is reached,
     *
     * @return string
     */
    public function getNextLine()
    {
        if( $this->hasMoreMailData )
        {
            $data = $this->connection->getLine();
            if( $data === "." )
            {
                $this->hasMoreMailData = false;
                // remove the mail if required by the user.
                if( $this->leaveOnServer == false )
                {
                    $this->connection->sendData( "DELE {$this->currentMessage}" );
                    $response = $this->connection->getLine(); // ignore response
                }

                return null;
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
     * @throws ezcMailTransportException if the server sent a negative reply when requesting a mail.
     * @return bool
     */
    public function nextMail()
    {
        if( $this->currentMessage === null )
        {
            $this->currentMessage = reset( $this->messages );
        }
        else
        {
            $this->currentMessage = next( $this->messages );
        }

        if( is_integer( $this->currentMessage ) )
        {
            $this->connection->sendData( "RETR {$this->currentMessage}" );
            $response = $this->connection->getLine();
            if( strpos( $response, "+OK" ) === 0 )
            {
                $this->hasMoreMailData = true;
                return true;
            }
            else
            {
                throw new ezcMailTransportException( "The POP3 server sent a negative reply when requesting mail." );
            }
        }
        return false;
    }

}

?>

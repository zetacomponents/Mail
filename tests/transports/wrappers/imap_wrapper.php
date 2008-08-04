<?php
/**
 * File containing the ezcMailImapTransportWrapper class.
 *
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 * @package Mail
 * @version 1.5.1
 * @subpackage Tests
 */

/**
 * Class which exposes the protected methods from the IMAP transport and allows
 * setting a custom connection, response, status and tag (in order to use mock
 * objects).
 *
 * For testing purposes only.
 *
 * @package Mail
 * @version 1.5.1
 * @subpackage Tests
 * @access private
 */
class ezcMailImapTransportWrapper extends ezcMailImapTransport
{
    /**
     * Sets the specified connection to the transport.
     *
     * @param ezcMailTransportConnection $connection
     */
    public function setConnection( ezcMailTransportConnection $connection )
    {
        $this->connection = $connection;
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
     * This wrapper function just returns the tag and the expected response
     * to be used in tests with a custom connection.
     *
     * @param string $tag
     * @param string $response
     * @return string
     */
    public function getResponse( $tag, $response = null )
    {
        return "{$tag} {$response}";
    }

    /**
     * Generates the next IMAP tag to prepend to client commands.
     *
     * The structure of the IMAP tag is Axxxx, where
     *  - A is a letter (uppercase for conformity)
     *  - x is a digit from 0 to 9
     * example of generated tag: T5439
     * It uses the class variable {@link $this->currentTag}.
     * Everytime it is called, the tag increases by 1.
     * If it reaches the last tag, it wraps around to the first tag.
     * By default, the first generated tag is A0001.
     *
     * @return string
     */
    public function getNextTag()
    {
        return parent::getNextTag();
    }

    /**
     * Sets the current IMAP tag to the specified tag.
     *
     * @param string $tag
     */
    public function setCurrentTag( $tag )
    {
        $this->currentTag = $tag;
    }

    /**
     * Returns the current state of the IMAP transport.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->state;
    }

    /**
     * Sets the current state of the IMAP transport to the specified state.
     *
     * @param int $status
     */
    public function setStatus( $status )
    {
        $this->state = $status;
    }
}
?>

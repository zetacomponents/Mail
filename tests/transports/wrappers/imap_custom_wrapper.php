<?php
/**
 * File containing the ezcMailImapTransportWrapper class.
 *
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 * @package Mail
 * @version //autogen//
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
 * @version //autogen//
 * @subpackage Tests
 * @access private
 */
class ezcMailImapTransportCustomWrapper extends ezcMailImapTransport
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

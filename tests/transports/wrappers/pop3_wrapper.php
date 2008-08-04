<?php
/**
 * File containing the ezcMailPop3TransportWrapper class.
 *
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 * @package Mail
 * @version //autogen//
 * @subpackage Tests
 */

/**
 * Class which exposes the protected methods from the POP3 transport and allows
 * setting a custom connection, status and greeting (in order to use mock
 * objects).
 *
 * For testing purposes only.
 *
 * @package Mail
 * @version //autogen//
 * @subpackage Tests
 * @access private
 */
class ezcMailPop3TransportWrapper extends ezcMailPop3Transport
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
     * Sets the specified greeting to the transport.
     *
     * @param string $greeting
     */
    public function setGreeting( $greeting )
    {
        $this->greeting = $greeting;
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

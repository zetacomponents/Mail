<?php
/**
 * File containing the ezcMailImapTransportWrapper class.
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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

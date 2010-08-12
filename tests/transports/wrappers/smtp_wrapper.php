<?php
/**
 * File containing the ezcMailSmtpTransportWrapper class.
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
 * Class which exposes the protected methods from the SMTP transport and allows
 * setting a custom status (in order to use mock objects).
 *
 * For testing purposes only.
 *
 * @package Mail
 * @version //autogen//
 * @subpackage Tests
 * @access private
 */
class ezcMailSmtpTransportWrapper extends ezcMailSmtpTransport
{
    /**
     * Creates a connection to the SMTP server and initiates the login
     * procedure.
     *
     * @todo The @ should be removed when PHP doesn't throw warnings for connect problems
     *
     * @throws ezcMailTransportSmtpException
     *         if no connection could be made
     *         or if the login failed
     * @throws ezcBaseExtensionNotFoundException
     *         if trying to use SSL and the openssl extension is not installed
     */
    public function connect()
    {
        parent::connect();
    }

    /**
     * Performs the initial handshake with the SMTP server and
     * authenticates the user, if login data is provided to the
     * constructor.
     *
     * @throws ezcMailTransportSmtpException
     *         if the HELO/EHLO command or authentication fails
     */
    public function login()
    {
        parent::login();
    }

    /**
     * Sends the MAIL FROM command, with the sender's mail address $from.
     *
     * This method must be called once to tell the server the sender address.
     *
     * The sender's mail address $from may be enclosed in angle brackets.
     *
     * @throws ezcMailTransportSmtpException
     *         if there is no valid connection
     *         or if the MAIL FROM command failed
     * @param string $from
     */
    public function cmdMail( $from )
    {
        parent::cmdMail( $from );
    }

    /**
     * Sends the 'RCTP TO' to the server with the address $email.
     *
     * This method must be called once for each recipient of the mail
     * including cc and bcc recipients. The RCPT TO commands control
     * where the mail is actually sent. It does not affect the headers
     * of the email.
     *
     * The recipient mail address $email may be enclosed in angle brackets.
     *
     * @throws ezcMailTransportSmtpException
     *         if there is no valid connection
     *         or if the RCPT TO command failed
     * @param string $email
     */
    public function cmdRcpt( $email )
    {
        parent::cmdRcpt( $email );
    }

    /**
     * Sends the DATA command to the SMTP server.
     *
     * @throws ezcMailTransportSmtpException
     *         if there is no valid connection
     *         or if the DATA command failed
     */
    public function cmdData()
    {
        parent::cmdData();
    }

    /**
     * Returns the current state of the IMAP transport.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the current state of the IMAP transport to the specified state.
     *
     * @param int $status
     */
    public function setStatus( $status )
    {
        $this->status = $status;
    }
}
?>

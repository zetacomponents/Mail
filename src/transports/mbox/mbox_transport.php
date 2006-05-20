<?php
/**
 * File containing the ezcMailMboxTransport class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * ezcMailMboxTransport implements mail retrieval from an mbox file.
 *
 * The mbox set is constructed from a file pointer and iterates over all the
 * messages in an mbox file.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailMboxTransport
{
    /**
     * Holds the filepointer to the mbox
     *
     * @var resource(filepointer)
     */
    public $fh;

    /**
     * Constructs the ezcMailMboxTransport object
     *
     * Opens the mbox $fileName.
     *
     * @throws ezcBaseFileNotFoundException
     *         If the mbox file could not be found.
     * @throws ezcBaseFilePermissionException
     *         If the mbox file could be opened for reading.
     *
     * @param string $fileName
     */
    public function __construct( $fileName )
    {
        if ( !file_exists( $fileName ) )
        {
            throw new ezcBaseFileNotFoundException( $fileName, 'mbox' );
        }
        if ( !is_readable( $fileName ) )
        {
            throw new ezcBaseFilePermissionException( $fileName, ezcBaseFileException::READ );
        }
        $this->fh = fopen( $fileName, 'rt' );
    }

    /**
     * Reads through the Mbox file and stops at the next message.
     *
     * Messages in Mbox files are separated with lines starting with "From "
     * and this function reads to the next "From " marker. It then returns the
     * current posistion in the file. If EOF is detected during reading the
     * function returns false instead.
     *
     * @return int
     */
    private function findNextMessage()
    {
        do
        {
            $data = fgets( $this->fh );
        }
        while ( !feof( $this->fh ) && substr( $data, 0, 5 ) !== "From " );
        if ( feof( $this->fh ) )
        {
            return false;
        }
        return ftell( $this->fh );
    }

    /**
     * This function reads through the whole mbox and returns starting positions of the messages.
     *
     * @return array(int=>int)
     */
    public function listMessages()
    {
        $messages = array();
        fseek( $this->fh, 0 );
        // Skip the first mail as this is the mbox header
        $position = $this->findNextMessage();
        if ( $position === false )
        {
            return $messages;
        }
        // Continue reading through the rest of the mbox
        do
        {
            $position = $this->findNextMessage();
            if ( $position !== false )
            {
                $messages[] = $position;
            }
        }
        while ( $position !== false );
        return $messages;
    }

    /**
     * Returns an ezcMailMboxSet containing all the messages in the mbox.
     *
     * @return ezcMailMboxSet
     */
    public function fetchAll()
    {
        $messages = $this->listMessages();
        return new ezcMailMboxSet( $this->fh, $messages );
    }

    /**
     * Returns an ezcMailMboxSet containing only the $number -th message in the mbox.
     *
     * @throws ezcMailNoSuchMessageException if the message $number is out
     * of range.
     *
     * @param int $number
     * @return ezcMailMboxSet
     */
    public function fetchByMessageNr( $number )
    {
        $messages = $this->listMessages();
        if ( !array_key_exists( $number, $messages ) )
        {
            throw new ezcMailNoSuchMessageException( $number );
        }
        return new ezcMailMboxSet( $this->fh, array( 0 => $messages[$number] ) );
    }
}
?>

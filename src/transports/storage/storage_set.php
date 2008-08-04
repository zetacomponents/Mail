<?php
/**
 * File containing the ezcMailStorageSet class
 *
 * @package Mail
 * @version 1.5.1
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * ezcMailStorageSet is a wrapper around other mail sets and provides saving of
 * mail sources to files.
 *
 * Example:
 *
 * <code>
 * // create a new POP3 transport object and a mail parser object
 * $transport = new ezcMailPop3Transport( "server" );
 * $transport->authenticate( "username", "password" );
 * $parser = new ezcMailParser();
 *
 * // wrap around the set returned by fetchAll()
 * // and specify that the sources are to be saved in the folder /tmp/cache
 * $set = new ezcMailStorageSet( $transport->fetchAll(), '/tmp/cache' );
 *
 * // parse the storage set
 * $mail = $parser->parseMail( $set );
 *
 * // get the filenames of the saved mails in the set
 * // this must be saved somewhere so it can be used on a subsequent request
 * $files = $set->getSourceFiles();
 *
 * // get the source of a the 4th saved mail
 * // this can be on a subsequent request if the $files array was saved from
 * // a previous request
 * $source = file_get_contents( $files[3] );
 * </code>
 *
 * @package Mail
 * @version 1.5.1
 */
class ezcMailStorageSet implements ezcMailParserSet
{
    /**
     * Holds the pointer to the current file which holds the mail source.
     *
     * @var filepointer
     */
    private $writer = null;

    /**
     * Holds the temporary file name where contents are being initially written
     * (until set is parsed and Message-ID is extracted).
     *
     * @var string
     */
    private $file = null;

    /**
     * Holds the path where the files are written (specified in the constructor).
     *
     * @var string
     */
    private $path = null;

    /**
     * Holds the Message-ID of the current message, used to rename the mail source file.
     *
     * @var string
     */
    private $id = null;

    /**
     * This variable is true if there is more data in the mail that is being fetched.
     *
     * @var bool
     */
    private $hasMoreMailData = false;

    /**
     * Holds the location where to store the message sources.
     *
     * @var string
     */
    private $location;

    /**
     * Holds the filenames holding the sources of the mails in this set.
     *
     * @var array(string)
     */
    private $files = null;

    /**
     * Constructs a new storage set around the provided set.
     *
     * $location specifies where to save the message sources. This directory MUST
     * exist and must be writable.
     *
     * @param ezcMailParserSet $set
     * @param string $location
     */
    public function __construct( ezcMailParserSet $set, $location )
    {
        $this->set = $set;
        $this->location = $location;
        $this->path = rtrim( $this->location, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
        $this->hasMoreMailData = false;
    }

    /**
     * Destructs the set.
     *
     * Closes any open files.
     */
    public function __destruct()
    {
        if ( is_resource( $this->writer ) )
        {
            fclose( $this->writer );
            $this->writer = null;
        }
    }

    /**
     * Returns one line of data from the current mail in the set.
     *
     * Null is returned if there is no current mail in the set or
     * the end of the mail is reached,
     *
     * It also writes the line of data to the current file. If the line contains
     * a Message-ID header then the value in the header will be used to rename the
     * file.
     *
     * @return string
     */
    public function getNextLine()
    {
        if ( $this->hasMoreMailData === false )
        {
            $this->nextMail();
            $this->hasMoreMailData = true;
        }
        $line = $this->set->getNextLine();
        if ( $this->id === null && stripos( $line, 'message-id' ) !== false )
        {
            // Temporary value in case the Message-ID cannot be extracted from $line
            $this->id = $this->file;
            preg_match_all( "/^([\w-_]*):\s?(.*)/", $line, $matches, PREG_SET_ORDER );
            if ( count( $matches ) > 0 )
            {
                $this->id = trim( trim( $matches[0][2] ), '<>' );
            }
        }
        fputs( $this->writer, $line );
        return $line;
    }

    /**
     * Moves the set to the next mail and returns true upon success.
     *
     * False is returned if there are no more mail in the set.
     *
     * @return bool
     */
    public function nextMail()
    {
        if ( $this->writer !== null )
        {
            fclose( $this->writer );
            if ( $this->id !== null )
            {
                rename( $this->path . $this->file, $this->path . $this->id );
                $this->files[] = $this->path . $this->id;
            }
            else
            {
                $this->files[] = $this->path . $this->file;
            }
            $this->writer = null;
        }
        $this->id = null;
        $mail = $this->set->nextMail();
        if ( $mail === true || $this->hasMoreMailData === false )
        {
            // Temporary file name until message is parsed and Message-ID is extracted.
            // It could remain the same if the mail doesn't contain a Message-ID header
            $this->file = getmypid() . '.' . time();
            $writer = fopen( $this->path . $this->file, 'w' );
            if ( $writer !== false )
            {
                $this->writer = $writer;
            }
            return $mail;
        }
        return false;
    }

    /**
     * Returns whether the set has mails.
     *
     * @return bool
     */
    public function hasData()
    {
        return $this->set->hasData();
    }

    /**
     * Returns an array of the filenames holding the sources of the mails in this set.
     *
     * The format of the returned array is:
     * array( 0 => 'location/filename1', 1 => 'location/filename2',...)
     *
     * @return array(string)
     */
    public function getSourceFiles()
    {
        return $this->files;
    }
}
?>

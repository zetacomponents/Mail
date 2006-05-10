<?php
declare(encoding="latin1");

/**
 * File containing the ezcMailFileSet class
 *
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @package Mail
 */

/**
 * ezcMailFileSet is an internal class that can be used to parse mail directly from
 * files on disk.
 *
 * Each file should contain only one mail message in RFC822 format. Bad files or
 * non-existing files are ignored.
 *
 * Example:
 *
 * <code>
 * $set = new ezcMailFileSet( array( 'path/to/mail/rfc822message.mail' ) );
 * $parser = new ezcMailParser();
 * $mail = $parser->parseMail( $set );
 * </code>
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailFileSet implements ezcMailParserSet
{
    /**
     * Holds the pointer to the file currently being parsed.
     *
     * @var filepointer
     */
    private $fp = null;

    /**
     * Holds the list of files that the set should serve.
     *
     * @var array(string)
     */
    private $files = array();

    /**
     * Constructs a new set that servers the files specified by $files.
     *
     * The set will start on the first file in the the array.
     *
     * @param array(string) $files
     */
    public function __construct( array $files )
    {
        $this->files = $files;
        reset( $this->files );

        $this->openFile( true );
    }

    /**
     * Destructs the set.
     *
     * Closes any open files.
     * @return void
     */
    public function __destruct()
    {
        if ( $this->fp != null )
        {
            fclose( $this->fp );
            $this->fp = null;
        }
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
        // finished?
        if ( $this->fp == null || feof( $this->fp ) )
        {
            if ( $this->fp != null )
            {
                fclose( $this->fp );
                $this->fp = null;
            }
            return null;
        }

        // get one line
        $next =  fgets( $this->fp );
        if ( $next == "" && feof( $this->fp ) )
        {
            return null;
        }
        return $next;
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
        return $this->openFile();
    }

    /**
     * Opens the next file in the set and returns true on success.
     *
     * @return bool
     */
    private function openFile( $isFirst = false )
    {
        // cleanup file pointer if needed
        if( $this->fp != null )
        {
            fclose( $this->fp );
            $this->fp = null;
        }

        // open the new file
        $file = $isFirst ? current( $this->files ) : next( $this->files );

        // loop until we can open a file.
        while( $this->fp == null && $file !== false )
        {
            if( file_exists( $file ) )
            {
                $fp = fopen( $file, 'r' );
                if ( $fp !== false )
                {
                    $this->fp = $fp;
                    return true;
                }
            }
            $file = next( $this->files );
        }
        return false;
    }
}

?>

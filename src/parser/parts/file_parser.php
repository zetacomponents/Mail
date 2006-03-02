<?php
/**
 * File containing the ezcMailFileParser class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Parses application/image/video and audio parts.
 *
 * @access private
 */
class ezcMailFileParser extends ezcMailPartParser
{
    /**
     * Holds the headers for this part.
     */
    private $headers = null;

    /**
     * Holds the filepointer to the attachment.
     */
    private $fp = null;

    /**
     * Holds the full path and filename of the file to save to.
     */
    private $fileName = null;

    /**
     * Constructs a new ezcMailMultipartMixedParser.
     */
    public function __construct( ezcMailHeadersHolder $headers )
    {
        parent::__construct( $headers );
        $this->headers = $headers;

        // figure out the encoding style
        
        // figure out the filename

        // open the filename
    }

    /**
     * Destructs the parser object.
     *
     * Closes and removes any open file.
     */
    public function __destruct()
    {
        // finish() was not called. The mail is completely broken.
        // we will clean up the mess
        if( $this->fp !== null )
        {
            fclose( $this->fp );
            $this->fp = null;
            if( $this->fileName !== null && file_exists( $this->fileName ) )
            {
                unlink( $this->fileName );
            }
        }
    }

    /**
     * Parse the body of a message line by line.
     *
     * This method is called by the parent part on a push basis. When there
     * are no more lines the parent part will call finish() to retrieve the
     * mailPart.
     *
     * The file will be decoded and saved to the given temporary directory within
     * a directory based on the process ID and the time.
     *
     * @param string $line
     * @return void
     */
    public function parseBody( $line )
    {
    }

    /**
     * Return the result of the parsed file part.
     *
     * This method is called automatically by the parent part.
     *
     * @return ezcMailFilePart
     */
    public function finish()
    {
    }

}

?>

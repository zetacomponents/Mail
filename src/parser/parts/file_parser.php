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
     *
     * @var ezcMailHeadersHolder
     */
    private $headers = null;

    /**
     * Holds the maintype of the parsed part.
     *
     * @var string
     */
    private $mainType = null;

    /**
     * Holds the subtype of the parsed part.
     *
     * @var string
     */
    private $subType = null;

    /**
     * Holds the filepointer to the attachment.
     *
     * @var resource
     */
    private $fp = null;

    /**
     * Holds the full path and filename of the file to save to.
     *
     * @var string
     */
    private $fileName = null;

    /**
     * Static counter used to generate unique directory names.
     *
     * @var int
     */
    private static $counter = 1;

    /**
     * Constructs a new ezcMailFileParser with maintype $mainType subtype $subType
     * and headers $headers..
     *
     * @throws ezcBaseFileNotFoundException if the file attachment file could not be openened.
     * @param string $mainType
     * @param string $subType
     * @param ezcMailHeadersHolder $headers
     */
    public function __construct( $mainType, $subType, ezcMailHeadersHolder $headers )
    {
        $this->mainType = $mainType;
        $this->subType = $subType;
        $this->headers = $headers;

        // figure out the base filename
        // search Content-Disposition first as specified by RFC 2183
        $matches = array();
        if( preg_match( '/\s*filename=([^;\s]*);?/',
                        $this->headers['Content-Disposition'], $matches ) )
        {
            $fileName = trim( $matches[1], '"' );
        }
        // fallback to the name parameter in Content-Type as specified by RFC 2046 4.5.1
        else if( preg_match( '/\s*name=([^;\s]*);?/',
                             $this->headers['Content-Type'], $matches ) )
        {
            $fileName = trim( $matches[1], '"' );
        }
        else // default
        {
            $fileName = "filename";
        }

        $this->fp = $this->openFile( $fileName ); // propagate exception

        // append the correct decoding filter
        switch( strtolower( $headers['Content-Transfer-Encoding'] ) )
        {
            case 'base64':
                stream_filter_append( $this->fp, 'convert.base64-decode');
                break;
            case 'quoted-printable':
                stream_filter_append( $this->fp, 'convert.quoted-printable-decode' );
                break;
            default:
                // the mail is bad, it has no encoding style
                // we'll just go with base64 since that is the most common type
                stream_filter_append( $this->fp, 'convert.base64-decode');
                break;
        }
    }

    /**
     * Returns the filepointer of the opened file $fileName in a unique directory..
     *
     * This method will create a new unique folder in the temporary directory specified in ezcMailParser.
     * The fileName property of this class will be set to the location of the new file.
     *
     * @throws ezcBaseFileNotFoundException if the file could not be opened.
     * @param string $fileName
     * @returns resource
     */
    private function openFile( $fileName )
    {
        // The filename is now relative, we need to extend it with the absolute path.
        // To provide uniqueness we put the file in a directory based on processID and rand.
        $dirName = ezcMailParser::getTmpDir() . getmypid() . '-' . self::$counter++ . '/';
        mkdir( $dirName, 0700 );

        // remove the directory and the file when PHP shuts down
        ezcMailParserShutdownHandler::registerForRemoval( $dirName );
        $this->fileName = $dirName . $fileName;

        $fp = fopen( $this->fileName, 'w');
        if( $this->fp === false )
        {
            throw new ezcBaseFileNotFoundException( $this->fileName );
        }
        return $fp;
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
        if( $line !== '' )
        {
            fwrite( $this->fp, $line );
        }
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
        fclose( $this->fp );
        $this->fp = null;
        $filePart = new ezcMailFile( $this->fileName );

        // set content type
        $filePart->setHeaders( $this->headers->getCaseSensitiveArray() );
        switch( strtolower( $this->mainType ) )
        {
            case 'image':
                $filePart->contentType = ezcMailFile::CONTENT_TYPE_IMAGE;
                break;
            case 'audio':
                $filePart->contentType = ezcMailFile::CONTENT_TYPE_AUDIO;
                break;
            case 'video':
                $filePart->contentType = ezcMailFile::CONTENT_TYPE_VIDEO;
                break;
            case 'application':
                $filePart->contentType = ezcMailFile::CONTENT_TYPE_APPLICATION;
                break;
        }

        // set mime type
        $filePart->mimeType = $this->subType;

        // set inline disposition mode if set.
        $matches = array();
        if( preg_match( '/^\s*inline;?/',
                        $this->headers['Content-Disposition'], $matches ) )
        {
            $filePart->dispositionType = ezcMailFile::DISPLAY_INLINE;
        }

        return $filePart;
    }

}

?>

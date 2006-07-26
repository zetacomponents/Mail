<?php
/**
 * File containing the ezcMailPartParser class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Base class for all parser parts.
 *
 * Parse process
 * 1. Figure out the headers of the next part.
 * 2. Based on the headers, create the parser for the bodyPart corresponding to
 *    the headers.
 * 3. Parse the body line by line. In the case of a multipart or a digest recursively
 *    start this process. Note that in the case of RFC822 messages the body contains
 *    headers.
 * 4. call finish() on the partParser and retrieve the ezcMailPart
 *
 * Each parser part gets the header for that part through the constructor
 * and is responsible for parsing the body of that part.
 * Parsing of the body is done on a push basis trough the parseBody() method
 * which is called repeatedly by the parent part for each line in the message.
 *
 * When there are no more lines the parent part will call finish() and the mail
 * part corresponding to the part you are parsing should be returned.
 *
 * @package Mail
 * @version //autogen//
 * @todo case on headers
 * @access private
 */
abstract class ezcMailPartParser
{
    /**
     * The name of the last header parsed.
     *
     * This variable is used when glueing together multi-line headers.
     *
     * @var string $lastParsedHeader
     */
    private $lastParsedHeader = null;

    /**
     * Parse the body of a message line by line.
     *
     * This method is called by the parent part on a push basis. When there
     * are no more lines the parent part will call finish() to retrieve the
     * mailPart.
     *
     * @param string $line
     * @return void
     */
    abstract public function parseBody( $line );

    /**
     * Return the result of the parsed part.
     *
     * This method is called when all the lines of this part have been parsed.
     *
     * @return ezcMailPart
     */
    abstract public function finish();

    /**
     * Returns a part parser corresponding to the given $headers.
     *
     * @todo rename to createPartParser
     * @throws ezcBaseFileNotFoundException if a neccessary temporary file could not be openened.
     * @return ezcMailPartParser
     */
    static public function createPartParserForHeaders( ezcMailHeadersHolder $headers )
    {
        // default as specified by RFC2045 - #5.2
        $mainType = 'text';
        $subType = 'plain';

        // parse the Content-Type header
        if ( isset( $headers['Content-Type'] ) )
        {
            $matches = array();
            // matches "type/subtype; blahblahblah"
            preg_match_all( '/^(\S+)\/([^;]+)/',
                            $headers['Content-Type'], $matches, PREG_SET_ORDER );
            if ( count( $matches ) > 0 )
            {
                $mainType = strtolower( $matches[0][1] );
                $subType = strtolower( $matches[0][2] );
            }
        }
        $bodyParser = null;

        // create the correct type parser for this the detected type of part
        switch ( $mainType )
        {
            /* RFC 2045 defined types */
            case 'image':
            case 'audio':
            case 'video':
            case 'application':
                $bodyParser = new ezcMailFileParser( $mainType, $subType, $headers );
                break;

            case 'message':
                if( $subType == "rfc822" )
                {
                    $bodyParser = new ezcMailRfc822DigestParser( $headers );
                }
                else
                {
                    $bodyParser = new ezcMailTextParser( $headers );
                }
                break;

            case 'text':
                $bodyParser = new ezcMailTextParser( $subType, $headers );
                break;

            case 'multipart':
                switch ( $subType )
                {
                    case 'mixed':
                        $bodyParser = new ezcMailMultipartMixedParser( $headers );
                        break;
                    case 'alternative':
                        $bodyParser = new ezcMailMultipartAlternativeParser( $headers );
                        break;
                    case 'related':
                        $bodyParser = new ezcMailMultipartRelatedParser( $headers );
                        break;
                    case 'digest':
                        $bodyParser = new ezcMailMultipartDigestParser( $headers );
                            break;
                    default:
                        $bodyParser = new ezcMailMultipartMixedParser( $headers );
                        break;
                }
                break;

                /* extensions */
            default:
                // we treat the body as text if no main content type is set
                // or if it is unknown
                $bodyParser = new ezcMailTextParser( $headers );
                break;
        }
        return $bodyParser;
    }

    /**
     * Parses the header given by $line and adds to $headers.
     *
     * This method is usually used to parse the headers for a subpart. The
     * only exception is RFC822 parts since you know the type in advance.
     *
     * @todo deal with headers that are listed several times
     * @param string $line
     * @param ezcMailHeadersHolder $headers
     * @return void
     */
    protected function parseHeader( $line, ezcMailHeadersHolder $headers )
    {
        $matches = array();
        preg_match_all( "/^([\w-_]*): (.*)/", $line, $matches, PREG_SET_ORDER );
        if ( count( $matches ) > 0 )
        {
            $headers[$matches[0][1]] = trim( $matches[0][2] );
            $this->lastParsedHeader = $matches[0][1];
        }
        else if ( $this->lastParsedHeader !== null ) // take care of folding
        {
            $headers[$this->lastParsedHeader] .= $line;
        }
        // else -invalid syntax, this should never happen.
    }

    /**
     * Scans through $headers and sets any specific header properties on $part.
     *
     * Currently we only have Content-Disposition on the ezcMailPart level.
     * All parser parts must call this method once.
     *
     * @param ezcMailHeadersHolder $headers
     * @param ezcMailPart $part
     * @return void
     */
    static public function parsePartHeaders( ezcMailHeadersHolder $headers, ezcMailPart $part )
    {
        if ( isset( $headers['Content-Disposition'] ) )
        {
            $cd = new ezcMailContentDispositionHeader();
            if ( preg_match( '/^\s*attachment;?/i',
                             $headers['Content-Disposition'], $matches ) )
            {
                $cd->disposition = "attachment";
            }

            // parameters
            if ( preg_match_all( '/\s*(\S*)="?([^;"]*);?/i',
                             $headers['Content-Disposition'], $matches, PREG_SET_ORDER ) )
            {
                foreach ( $matches as $param )
                {
                    switch ( $param[1] )
                    {
                        case 'filename':
                            $cd->fileName = trim( $param[2], '"' );
                            break;
                        case 'creation-date':
                            $cd->creationDate = trim( $param[2], '"' );
                            break;
                        case 'modification-date':
                            $cd->modificationDate = trim( $param[2], '"' );
                            break;
                        case 'read-date':
                            $cd->readDate = trim( $param[2], '"' );
                            break;
                        case 'size':
                            $cd->readDate = trim( $param[2], '"' );
                            break;
                        default:
                            $cd->additionalParameters[$param[1]] = $param[2];
                            break;
                    }
                }
            }// end parameters
            $part->contentDisposition = $cd;
        }
    }
}

?>

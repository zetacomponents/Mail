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
 * @todo case on headers
 * @access private
 */
abstract class ezcMailPartParser
{
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
     * @return ezcMailPartParser
     */
    static public function createPartForHeaders( array $headers )
    {
        // default as specified by RFC2045 - 5.2
        $mainType = 'text';
        $subType = 'plain';

        // parse the Content-Type header
        if( isset( $headers['Content-Type'] ) )
        {
            $matches = array();
            // matches "type/subtype; blahblahblah"
            preg_match_all( '/^(\S+)\/(\S+);(.+)*/',
                            $headers['Content-Type'], $matches, PREG_SET_ORDER );
            if( count( $matches ) > 2 )
            {
                $mainType = $matches[0][1];
                $subType = $matches[0][2];
            }
        }
        $bodyParser = null;

        // create the correct type parser for this the detected type of part
        switch( $mainType )
        {
            /* RFC 2045 defined types */
            case 'image':
            case 'audio':
            case 'video':
            case 'application':
//                $bodyParser = new ezcMailFileParser( $headers );
                break;

            case 'message':
                $bodyParser = new ezcRfc822Parser( $headers );
                break;

            case 'text':
                $bodyParser = new ezcMailTextParser( $headers );
                break;

            case 'multipart':
                switch( $subType )
                {
                    case 'mixed':
                    case 'alternative':
                    case 'related':
                        break;
                    default:
                        break;
                }
                break;
                /* extensions */
            default:
                // todo: treat as plain text?
                break;
        }
        return $bodyParser;
    }
}

?>

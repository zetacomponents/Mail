<?php
/**
 * File containing the ezcMailParser class
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
abstract class ezcMailParserState
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
     * @return ezcMailParserState
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

/**
 * Parses RFC822 messages.
 *
 * @todo split header parsing into separate class? This could also be used by the MultiPart parsers.
 * @access private
 */
class ezcRfc822Parser extends ezcMailParserState
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
     * Holds the headers parsed.
     *
     * The format of the array is array(name=>value)
     *
     * @var array(string=>string)
     */
    private $headers = array();

    /**
     * Stores the state of the parser.
     *
     * Valid states are:
     * - headers - it is currently parsing headers
     * - body - it is currently parsing the body part.
     *
     * @var string
     */
    private $parserState = 'headers'; // todo: change to const

    /**
     * The parser of the body.
     *
     * This will be set after the headers have been parsed.
     *
     * @var ezcMailParserState
     */
    private $bodyParser = null;

    public function parseBody( $line )
    {
        if( $this->parserState == 'headers' && $line == "" )
        {
            $this->parserState = "body";

            // clean up headers for the part
            // the rest of the headers should be set on the mail object.
            // TODO: Change this to Content* ?
            $headers = array();
            $headers['Content-Type'] = $this->headers['Content-Type'];
            $headers['Content-Transfer-Encoding'] = $this->headers['Content-Transfer-Encoding'];
            $headers['Content-Disposition'] = $this->headers['Content-Disposition'];

            // get the correct body type
            $this->bodyParser = self::createPartForHeaders( $headers );
        }
        else if( $this->parserState == 'headers' )
        {
            $this->parseHeader( $line );
        }
        else // we are parsing headers
        {
            $this->bodyParser->parseBody( $line );
        }
    }

    /**
     * Returns an ezcMail corresponding to the parsed message.
     *
     * @return ezcMail
     */
    public function finish()
    {
        // todo: what do we do if finish is called an there is no body?
        // I propose empty body part and write an error.

        $mail = new ezcMail();
        $mail->setHeaders( $this->headers );

        // from
        if( isset( $this->headers['From'] ) )
        {
            $mail->from = ezcMailTools::parseEmailAddress( $this->headers['From'] );
        }
        // to
        if( isset( $this->headers['To'] ) )
        {
            $mail->to = ezcMailTools::parseEmailAddresses( $this->headers['To'] );
        }
        // cc
        if( isset( $this->headers['Cc'] ) )
        {
            $mail->cc = ezcMailTools::parseEmailAddresses( $this->headers['Cc'] );
        }
        // bcc
        if( isset( $this->headers['Bcc'] ) )
        {
            $mail->cc = ezcMailTools::parseEmailAddresses( $this->headers['Bcc'] );
        }
        if( isset( $this->headers['Subject'] ) )
        {
            $mail->subject = iconv_mime_decode( $this->headers['Subject'], 0, 'utf-8' );
            $mail->subjectCharset = 'utf-8';
        }

        $mail->body = $this->bodyParser->finish();
        return $mail;
    }

    /**
     * Parses the header given by $line and adds it to $this->headers
     *
     * @todo: deal with headers that are listed several times
     * @return void
     */
    private function parseHeader( $line )
    {
        $matches = array();
        preg_match_all( "/^([\w-_]*): (.*)/", $line, $matches, PREG_SET_ORDER );
        if( count( $matches ) > 0 )
        {
            $this->headers[$matches[0][1]] = trim( $matches[0][2] );
            $this->lastParsedHeader = $matches[0][1];
        }
        else if( $this->lastParsedHeader !== null )
        {
            $this->headers[$this->lastParsedHeader] .= $line;
        }
    }
}

/**
 * Parses mail parts of type "text".
 *
 * @access private
 */
class ezcMailTextParser extends ezcMailParserState
{
    /**
     * Stores the parsed text of this part.
     *
     * @var string $text
     */
    private $text = null;


    /**
     * Holds the headers of this text part.
     *
     * The format of the array is array(name=>value)
     *
     * @var array(string=>string)
     */
    private $headers = null;

    /**
     * Constructs a new ezcMailTextParser with the headers $headers.
     *
     * @param array(string=>string) $headers
     */
    public function __construct( array $headers )
    {
        $this->headers = $headers;
    }

    /**
     * Adds each line to the body of the text part.
     *
     * @param string $line
     * @return void
     */
    public function parseBody( $line )
    {
        if( $this->text === null )
        {
            $this->text = $line;
        }
        else
        {
            $this->text .= "\n" . $line;
        }
    }

    /**
     * Returns the ezcMailText part corresponding to the parsed message.
     *
     * @return ezcMailText
     */
    public function finish()
    {
        $charset = "us-ascii"; // RFC 2822 default
        if( isset( $this->headers['Content-Type'] ) )
        {
//            preg_match_all( '/\s*(\S+)=([^;\s]*);?/', // matches all headers
            preg_match( '/\s*charset=([^;\s]*);?/',
                            $this->headers['Content-Type'],
                            $parameters );
            if( count( $parameters ) > 0 )
            {
                $charset = trim( $parameters[1], '"' );
            }
        }

        $part = new ezcMailText( $this->text, $charset );
        return $part;
    }
}

/**
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailParser
{
    private $state = null;

    /**
     * Constructs a new ezcMailParser.
     */
    public function __construct()
    {
    }

    /**
     * Returns an array of ezcMail objects parsed from the mail set $set.
     *
     * @param ezcMailParserSet
     * @returns array(ezcMail)
     */
    public function parseMail( ezcMailParserSet $set )
    {
        $mail = array();
        do
        {
            $this->state = new ezcRfc822Parser();
            $data = "";
            while( ($data = $set->getNextLine()) !== null )
            {
                $this->state->parseBody( $data );
            }
            $mail[] = $this->state->finish();
        }while( $set->nextMail() );
        return $mail;
    }
}
?>

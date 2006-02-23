<?php
/**
 * File containing the ezcMailParser class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

abstract class ezcMailParserState
{
    abstract public function parseBody( $line );

    // return the correct type of ezcMailPart
    abstract public function finish();

    static public function createPartForHeaders( array $headers )
    {
        $mainType = 'text';
        $subType = 'plain';

        // parse the Content-Type header
        if( isset( $headers['Content-Type'] ) )
        {
            $matches = array();
            preg_match_all( '/^(\S+)\/(\S+);(.+)*/',
                            $headers['Content-Type'], $matches, PREG_SET_ORDER );
            if( count( $matches ) > 2 )
            {
                $mainType = $matches[0][1];
                $subType = $matches[0][2];
            }
        }
        $bodyParser = null;
        switch( $mainType )
        {
            case 'text':
                $bodyParser = new ezcMailTextParser( $headers );
                break;

            default:
                // todo: treat as plain text?
                break;
        }
        return $bodyParser;
    }
}

// State idea:
// 1. Read headers
// 2. Decide on the content type found in the headers
// 3. Choose the correct parser for the body part, give the part the headers
//    except for the first part.
// 4. Push body lines into that part.
class ezcRfc822Parser extends ezcMailParserState
{
    private $lastParsedHeader = null;
    private $headers = array();
    private $parserState = 'headers'; // todo: change to const
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

    public function finish()
    {
        $mail = new ezcMail();
        $mail->setHeaders( $this->headers );

        // to
        // cc
        // bcc
        // subject
        // subject encoding

        $mail->body = $this->bodyParser->finish();
        return $mail;
    }

    // todo: deal with headers that are listed several times
    public function parseHeader( $line )
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

class ezcMailTextParser extends ezcMailParserState
{
    private $text = null;
    private $headers = null;

    public function __construct( array $headers )
    {
        $this->headers = $headers;
    }

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

    public function finish()
    {
        $parameters = array();
        if( isset( $this->headers['Content-Type'] ) )
        {
            preg_match_all( '/\s*(\S+)=([^;\s]*);?/',
                            $this->headers['Content-Type'],
                            $parameters, PREG_SET_ORDER );
        }

        // todo: fetch encoding and character set from the parameters
        $part = new ezcMailText( $this->text );
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

    public function __construct()
    {
        $this->state = new ezcRfc822Parser();
    }

    /**
     *
     */
    public function parseMail( ezcMailParserSet $set )
    {
        $data = "";
        while( ($data = $set->getNextLine()) !== null )
        {
            $this->state->parseBody( $data );
        }
        return $this->state->finish();
    }
}
?>

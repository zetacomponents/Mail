<?php
/**
 * File containing the ezcMailRfc822Parser class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Parses RFC822 messages.
 *
 * @todo split header parsing into separate class? This could also be used by the MultiPart parsers.
 * @access private
 */
class ezcMailRfc822Parser extends ezcMailPartParser
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
     * @var ezcMailHeadersHolder
     */
    private $headers = null;

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
     * @var ezcMailPartParser
     */
    private $bodyParser = null;

    public function __construct()
    {
        $this->headers = new ezcMailHeadersHolder();
    }

    /**
     * Parses the body of an rfc 2822 message.
     *
     * @param string $line
     * @return void
     */
    public function parseBody( $line )
    {
        if( $this->parserState == 'headers' && $line == "" )
        {
            $this->parserState = "body";

            // clean up headers for the part
            // the rest of the headers should be set on the mail object.
            // TODO: Change this to Content* ?
            $headers = new ezcMailHeadersHolder();
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
        $mail->setHeaders( $this->headers->getCaseSensitiveArray() );

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
        else if( $this->lastParsedHeader !== null ) // take care of folding
        {
            $this->headers[$this->lastParsedHeader] .= $line;
        }
    }
}

?>

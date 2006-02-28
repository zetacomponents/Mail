<?php
/**
 * File containing the ezcMailTextParser class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Parses mail parts of type "text".
 *
 * @access private
 */
class ezcMailTextParser extends ezcMailPartParser
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

?>

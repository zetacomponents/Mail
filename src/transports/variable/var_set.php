<?php
declare(encoding="latin1");

/**
 * File containing the ezcMailVariableSet class
 *
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @package Mail
 */

/**
 * ezcMailVariableSet is an internal class that can be used to parse mail directly from
 * a variable in your script.
 *
 * The variable should contain the complete mail message in RFC822 format.
 *
 * Example:
 *
 * <code>
 * $mail = "To: user@example.com\r\nSubject: Test mail    .....";
 * $set = new ezcMailVariableSet( $mail ) );
 * $parser = new ezcMailParser();
 * $mail = $parser->parseMail( $set );
 * </code>
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailVariableSet implements ezcMailParserSet
{
    /**
     * Holds the mail split by linebreaks.
     *
     * @var array(string)
     */
    private $mail = array();

    /**
     * Constructs a new set that servers the files specified by $files.
     *
     * The set will start on the first file in the the array.
     *
     * @param array(string) $files
     */
    public function __construct( $mail )
    {
        $this->mail = preg_split( "[(\r\n)|(\n)]",$mail, -1, PREG_SPLIT_DELIM_CAPTURE );
        reset( $this->mail );
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
        $line = current( $this->mail );
        next( $this->mail );

        if( $line === false )
        {
            return null;
        }

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
        return false;
    }
}

?>

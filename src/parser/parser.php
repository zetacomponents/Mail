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
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailParser
{
    private $partParser = null;

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
            $this->partParser = new ezcMailRfc822Parser();
            $data = "";
            while( ($data = $set->getNextLine()) !== null )
            {
                $this->partParser->parseBody( $data );
            }
            $mail[] = $this->partParser->finish();
        }while( $set->nextMail() );
        return $mail;
    }
}
?>

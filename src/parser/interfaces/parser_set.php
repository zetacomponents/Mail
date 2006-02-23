<?php
/**
 * File containing the ezcMailParserSet interface
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
interface ezcMailParserSet
{
    /**
     * Returns one line of data from the current mail in the set
     * excluding the ending linebreak.
     *
     * If there is no current mail in the set, null is returned.
     *
     * @return string
     */
    public function getNextLine();

    /**
     * Moves the set to the next mail and returns true upon success.
     *
     * False is returned if there are no more mail in the set.
     *
     * @return bool
     */
    public function nextMail();
}
?>

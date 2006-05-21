<?php
/**
 * File containing the ezcMailCharsetConverter
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * Small internal class for common character set conversion methods inside Mail.
 *
 * @package Mail
 * @version //autogen//
 * @access private
 */
class ezcMailCharsetConverter
{
    /**
     * Converts the $text with the charset $originalCharset to UTF-8
     *
     * @param string $text
     * @param string $originalCharset
     * @return string
     */
    public static function convertToUTF8( $text, $originalCharset )
    {
        return iconv( $originalCharset, 'utf-8', $text );
    }
}

?>

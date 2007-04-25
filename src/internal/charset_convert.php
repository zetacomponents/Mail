<?php
/**
 * File containing the ezcMailCharsetConverter
 *
 * @package Mail
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * Small internal class for common character set conversion methods inside Mail.
 *
 * @package Mail
 * @version //autogentag//
 * @access private
 */
class ezcMailCharsetConverter
{
    /**
     * Converts the $text with the charset $originalCharset to UTF-8
     *
     * In case $originalCharset is 'unknown-8bit' or 'x-user-defined' then
     * it is assumed to be 'latin1' (ISO-8859-1).
     *
     * @param string $text
     * @param string $originalCharset
     * @return string
     */
    public static function convertToUTF8( $text, $originalCharset )
    {
        if ( $originalCharset === 'unknown-8bit' || $originalCharset === 'x-user-defined' )
        {
            $originalCharset = "latin1";
        }
        return @iconv( $originalCharset, 'utf-8', $text );
    }
}
?>

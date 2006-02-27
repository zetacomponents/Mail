<?php
/**
 * File containing the ezcMailTools class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * This class contains static convenience methods for composing addresses
 * and ensuring correct line-breaks in the mail.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailTools
{
    /**
     * Holds the unique ID's.
     *
     * @var int
     */
    private static $idCounter = 0;

    /**
     * The characters to use for line-breaks in the mail.
     *
     * The default is \r\n which is the value specified in RFC822.
     *
     * @var string
     */
    private static $lineBreak = "\r\n";

    /**
     * Returns ezcMailAddress $item as a RFC822 compliant address string.
     *
     * Example:
     * <code>
     * composeEmailAddress( new ezcMailAddress( 'sender@example.com', 'John Doe' ) );
     * </code>
     *
     * Returns:
     * <pre>
     * John Doe <sender@example.com>
     * </pre>
     *
     * @param ezcMailAddress $item
     * @return string
     */
    public static function composeEmailAddress( ezcMailAddress $item )
    {
        if ( $item->name !== '' )
        {
            if ( $item->charset !== 'us-ascii' )
            {
                $preferences = array(
                    'input-charset' => $item->charset,
                    'output-charset' => $item->charset,
                    'scheme' => 'B',
                    'line-break-chars' => ezcMailTools::lineBreak()
                );
                $name = iconv_mime_encode( 'dummy', $item->name, $preferences );
                $name = substr( $name, 7 ); // "dummy: " + 1
                $text = $name . ' <' . $item->email . '>';
            }
            else
            {
                $text = $item->name . ' <' . $item->email . '>';
            }
        }
        else
        {
            $text = $item->email;
        }
        return $text;
    }

    /**
     * Returns the array $items consisting of ezcMailAddress objects
     * as one RFC822 compliant address string.
     *
     * @param array(ezcMailAddress) $items
     * @return string
     */
    public static function composeEmailAddresses( array $items )
    {
        $textElements = array();
        foreach ( $items as $item )
        {
            $textElements[] = ezcMailTools::composeEmailAddress( $item );
        }
        return implode( ', ', $textElements );
    }

    /**
     * Returns an ezcMailAddress objects parsed from the
     * RFC822 compatible address string $address.
     *
     * This method does not perform validation. It will also accept slightly
     * malformed addresses.
     *
     * If the mail address given can not be decoded null is returned.
     *
     * Example:
     * <code>
     * ezcMailTools::parseEmailAddresses( 'John Doe <john@example.com>' );
     * </code>
     *
     * @param string $addresse
     * @return ezcMailAddress
     */
    public static function parseEmailAddress( $address )
    {
        // we don't care about the "group" part of the address since this is not used anywhere

        $matches = array();
        $pattern = '/<?\"?[a-zA-Z0-9!#\$\%\&\'\*\+\-\/=\?\^_`{\|}~\.]+\"?@[a-zA-Z0-9!#\$\%\&\'\*\+\-\/=\?\^_`{\|}~\.]+>?$/';
        if( preg_match( trim( $pattern ), $address, $matches, PREG_OFFSET_CAPTURE ) != 1 )
        {
            return null;
        }
        $name = substr( $address, 0, $matches[0][1] );

        // trim <> from the address and "" from the name
        $name = trim( $name, '" ' );
        $mail = trim( $matches[0][0], '<>' );
        // remove any quotes found in mail addresses like "bah,"@example.com
        $mail = str_replace( '"', '', $mail );

        // the name may contain interesting character encoding. We need to convert it.
        $name = iconv_mime_decode( $name, 0, 'utf-8' );

        $address = new ezcMailAddress( $mail, $name, 'utf-8' );
        return $address;
    }

    /**
     * Returns an array of ezcMailAddress objects parsed from the
     * RFC822 compatible address string $addresses.
     *
     * Example:
     * <code>
     * ezcMailTools::parseEmailAddresses( 'John Doe <john@example.com>' );
     * </code>
     *
     * @todo handle charactersets properly
     * @param string $addresses
     * @return array(ezcMailAddress)
     */
    public static function parseEmailAddresses( $addresses )
    {
        $addressesArray = array();
        $inQuote = false;
        $last = 0; // last hit
        for( $i = 0; $i < strlen( $addresses ); $i++ )
        {
            if( $addresses[$i] == '"' )
            {
                $inQuote = !$inQuote;
            }
            else if( $addresses[$i] == ',' && !$inQuote )
            {
                $addressesArray[] = substr( $addresses, $last, $i - $last );
                $last = $i + 1; // eat comma
            }
        }

        // fetch the last one
        $addressesArray[] = substr( $addresses, $last );

        $addressObjects = array();
        foreach( $addressesArray as $address )
        {
            $addressObject = self::parseEmailAddress( $address );
            if( $addressObject !== null )
            {
                $addressObjects[] = $addressObject;
            }
        }

        return $addressObjects;
    }

    /**
     * Returns an unique message ID to be used for a mail message.
     *
     * The hostname $hostname will be added to the unique ID as required by RFC822.
     * If an e-mail address is provided instead, the hostname is extracted and used.
     *
     * The formula to generate the message ID is: [time_and_date].[process_id].[counter]
     *
     * @param string $hostname
     * @return string
     */
    public static function generateMessageId( $hostname )
    {
        if ( strpos( $hostname, '@' ) !== false )
        {
            $hostname = strstr( $hostname, '@' );
        }
        else
        {
            $hostname = '@' . $hostname;
        }
        return date( 'YmdGHjs' ) . '.' . getmypid() . '.' . self::$idCounter++ . $hostname;
    }

    /**
     * Returns an unique ID to be used for Content-ID headers.
     *
     * The part $partName is default set to "part". Another value can be used to provide,
     * for example, a file name of a part.
     *
     * The formula used is [$partName]."@".[time].[counter]
     *
     * @param  string $partName
     * @return string
     */
    public static function generateContentId( $partName = "part" )
    {
        return $partName . '@' .  date( 'Hjs' ) . self::$idCounter++;
    }

    /**
     * Sets the endLine $character(s) to use when generating mail.
     * The default is to use "\r\n" as specified by RFC 2045.
     *
     * @param string $characters
     * @return void
     */
    public static function setLineBreak( $characters )
    {
        self::$lineBreak = $characters;
    }

    /**
     * Returns one endLine character.
     *
     * The default is to use "\n\r" as specified by RFC 2045.
     *
     * @return string
     */
    public static function lineBreak()
    {
        // Note, this function does deliberately not
        // have a $count parameter because of speed issues.
        return self::$lineBreak;
    }
}
?>

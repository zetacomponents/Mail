<?php
/**
 * File containing the ezcMailParser class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Parses a mail in RFC822 format to an ezcMail structure.
 *
 * If you want to use your own mail class (extended from ezcMail), use
 * ezcMailParserOption. Example:
 * <code>
 * $parser = new ezcMailParser( array( 'mailClass' => 'MyMailClass' ) );
 * // if you want to use MyMailClass which extends ezcMail
 * </code>
 *
 * File attachments will be written to disk in a temporary directory.
 * This temporary directory and the file attachment will be removed
 * when PHP ends execution. If you want to keep the file you should move it
 * to another directory.
 *
 * @property ezcMailParserOptions $options
 *           Holds the options you can set to the mail parser.
 *
 * @package Mail
 * @version //autogen//
 * @mainclass
 */
class ezcMailParser
{
    /**
     * Holds the parser of the current mail.
     *
     * @var ezcMailPart
     */
    private $partParser = null;

    /**
     * Holds the directory where parsed mail should store temporary files.
     *
     * @var string
     */
    private static $tmpDir = null;

    /**
     * Holds options you can be set to the mail parser.
     *
     * @var ezcMailParserOptions
     */
    private $options;

    /**
     * Constructs a new ezcMailParser.
     *
     * @see ezcMailParserOptions for options you can set to the mail parser.
     *
     * @param array(string=>mixed) $options
     */
    public function __construct( array $options = array() )
    {
        $this->options = new ezcMailParserOptions( $options );
    }

    /**
     * Returns the value of the property $name.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name does not exist
     * @param string $name
     * @ignore
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'options':
                return $this->options;
                break;
        }
        throw new ezcBasePropertyNotFoundException( $name );
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name does not exist
     * @throws ezcBaseValueException
     *         if $value is not accepted for the property $name
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'options':
                if ( !( $value instanceof ezcMailParserOptions ) )
                {
                    throw new ezcBaseValueException( 'options', $value, 'instanceof ezcMailParserOptions' );
                }
                $this->options = $value;
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }

    /**
     * Returns true if the property $name is set, otherwise false.
     *
     * @param string $name
     * @return bool
     * @ignore
     */
    public function __isset( $name )
    {
        if ( $name === "options" )
        {
            return true;
        }
        return false;
    }

    /**
     * Returns an array of ezcMail objects parsed from the mail set $set.
     *
     * You can optionally use ezcMailParserOptions to provide an alternate class
     * name which will be instantiated instead of ezcMail, if you need to extend
     * ezcMail.
     *
     * Example:
     * <code>
     * $parser = new ezcMailParser( array( 'mailClass' => 'MyMailClass' ) );
     * // if you want to use MyMailClass which extends ezcMail
     * </code>
     *
     * @apichange Remove second parameter
     *
     * @throws ezcBaseFileNotFoundException
     *         if a neccessary temporary file could not be openened.
     * @param ezcMailParserSet $set
     * @param string $class Deprecated. Use $mailClass in ezcMailParserOptions class instead.
     * @returns array(ezcMail)
     */
    public function parseMail( ezcMailParserSet $set, $class = null )
    {
        $mail = array();
        if ( !$set->hasData() )
        {
            return $mail;
        }
        if ( $class === null )
        {
            $class = $this->options->mailClass;
        }
        do
        {
            $this->partParser = new ezcMailRfc822Parser();
            $data = "";
            $lastData = "";
            $size = 0;
            while ( ( $data = $set->getNextLine() ) !== null )
            {
                $this->partParser->parseBody( $data );
                $size += strlen( $data );
                $lastData = $data;
            }
            $part = $this->partParser->finish( $class );
            $part->size = $size;
            if ( trim( $lastData ) === ')' )
            {
                // IMAP: don't consider the last line: ) CR LF
                $part->size = $part->size - 3;
            }
            $mail[] = $part;
        } while ( $set->nextMail() );
        return $mail;
    }

    /**
     * Sets the temporary directory.
     *
     * The temporary directory must be writeable by PHP. It will be used to store
     * file attachments.
     *
     * @todo throw if the directory is not writeable.
     * @param string $dir
     */
    public static function setTmpDir( $dir )
    {
        self::$tmpDir = $dir;
    }

    /**
     * Returns the temporary directory.
     *
     * If no temporary directory has been set this method defaults to
     * /tmp/ for linux and c:\tmp\ for windows.
     *
     * @returns string
     */
    public static function getTmpDir()
    {
        if ( self::$tmpDir === null )
        {
            $uname = php_uname();
            if ( strtoupper( substr( $uname, 0, 3 ) == "WIN" ) )
            {
                self::$tmpDir = "c:\\tmp\\";
            }
            else
            {
                self::$tmpDir = "/tmp/";
            }

        }
        return self::$tmpDir;
    }
}
?>

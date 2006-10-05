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
 * Parses a mail in RFC822 format to an ezcMail structure.
 *
 * File attachments will be written to disk in a temporary directory.
 * This temporary directory and the file attachment will be removed
 * when PHP ends execution.
 * If you want to keep the file you should move it to another directory.
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
     * Constructs a new ezcMailParser.
     */
    public function __construct()
    {
    }

    /**
     * Returns an array of ezcMail objects parsed from the mail set $set.
     * You can optionally provide an alternate class name, which will be
     * instanciated instead of ezcMail, if you need to extend ezcMail.
     *
     * @throws ezcBaseFileNotFoundException if a neccessary temporary file could not be openened.
     * @param ezcMailParserSet $set
     * @param string $class         A class derived from ezcMail.
     * @returns array(ezcMail)
     */
    public function parseMail( ezcMailParserSet $set, $class = "ezcMail" )
    {
        $mail = array();
        if ( !$set->hasData() )
        {
            return $mail;
        }
        do
        {
            $this->partParser = new ezcMailRfc822Parser();
            $data = "";
            while ( ( $data = $set->getNextLine() ) !== null )
            {
                $this->partParser->parseBody( $data );
            }
            $mail[] = $this->partParser->finish( $class );
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
     * @returns void
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

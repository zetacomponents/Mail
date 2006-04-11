<?php
/**
 * File containing the ezcMailMboxSet class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @access private
 */

/**
 * ezcMailMboxSet is an internal class that fetches a series of mail
 * from an mbox file.
 *
 * The mbox set is constructed from a file pointer and iterates over all the
 * messages in an mbox file.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailMboxSet implements ezcMailParserSet
{
    /**
     * Holds the filepointer to the mbox
     *
     * @var resource(filepointer)
     */
    private $fh;

    /**
     * This variable is true if there is more data in the mail that is being fetched.
     *
     * It is false if there is no mail being fetched currently or if all the data of the current mail
     * has been fetched.
     *
     * @var bool
     */
    private $hasMoreMailData = false;

    /**
     * Records whether we initialized the mbox or not
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Constructs a new mbox parser set
     */
    public function __construct( $fh )
    {
        $this->fh = $fh;
        $this->initialized = false;
        $this->hasMoreMailData = true;
        xdebug_break();
        $this->nextMail();
    }

    /**
     * Returns true if all the data has been fetched from this set.
     *
     * @return bool
     */
    public function isFinished()
    {
        return feof( $this->fh ) ? true : false;
    }

    /**
     * Returns one line of data from the current mail in the set
     * including the ending linebreak.
     *
     * Null is returned if there is no current mail in the set or
     * the end of the mail is reached.
     *
     * @return string
     */
    public function getNextLine()
    {
        if ( $this->hasMoreMailData )
        {
            $data = fgets( $this->fh );
            if ( feof( $this->fh ) || substr( $data, 0, 5 ) === "From " )
            {
                $this->hasMoreMailData = false;

                return null;
            }
            return $data;
        }
        return null;
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
        if ( $this->initialized === false )
        {
            /* We need to skip over the header in the mbox, which is basically
             * skipping the first mail. The first loop reads up until the first
             * "From " marker, which *should* be the first line in the file.
             * The second loop then progresses to the next "From " marker where
             * the first email message starts. */
            while ( ( $data = $this->getNextLine() ) !== null ) {
            }
            $this->hasMoreMailData = true;
            while ( ( $data = $this->getNextLine() ) !== null ) {
            }
            $this->hasMoreMailData = true;
            $this->initialized = true;
        }
        if ( feof( $this->fh ) )
        {
            $this->hasMoreMailData = false;
            return false;
        }
        $this->hasMoreMailData = true;

        return true;
    }

}
?>

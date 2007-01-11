<?php
/**
 * File containing the ezcMailMultipartDigest class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * The mixed multipart type is used to bundle a list of mail objects.
 *
 * Each part will be shown in the mail in the order provided. It is not
 * necessary to bundle digested mail using a digest object. However, it is
 * considered good practice to do so when several digested mail are sent
 * together.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailMultipartDigest extends ezcMailMultipart
{
    /**
     * Constructs a new ezcMailMultipartDigest
     *
     * The constructor accepts an arbitrary number of ezcMail or arrays with ezcMail objects.
     * Parts are added in the order provided. Parameters of the wrong
     * type are ignored.
     *
     * @param ezcMail|array(ezcMail)
     */
    public function __construct()
    {
        $args = func_get_args();
        parent::__construct( array() );
        foreach ( $args as $part )
        {
            if ( $part instanceof ezcMailPart  )
            {
                $this->parts[] = $part;
            }
            elseif( is_array( $part ) ) // add each and everyone of the parts in the array
            {
                foreach ( $part as $array_part )
                {
                    if ( $array_part instanceof ezcMailRfc822Digest )
                    {
                        $this->parts[] = $array_part;;
                    }
                }
            }
        }
    }

    /**
     * Appends a part to the list of parts.
     *
     * @param ezcMailPart $part
     */
    public function appendPart( ezcMailRfc822Digest $part )
    {
        $this->parts[] = $part;
    }

    /**
     * Returns the mail parts associated with this multipart.
     *
     * @return array(ezcMail)
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Returns "mixed".
     *
     * @return string
     */
    public function multipartType()
    {
        return "digest";
    }
}
?>

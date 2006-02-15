<?php
/**
 * File containing the ezcMailMultipartRelated class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * ezcMailMultipartRelated is intended for mail parts consisting of
 * several inter-related body parts.
 *
 * A typical example is an HTML mail with embedded images.
 * When you want to refer to a related part you can use content id's
 * (cid). Set the 'Content-ID' header of the related part to a valid
 * unique url-addr-spec (specified by RFC 822) and refer to it through
 * the form cid:unique-string.
 *
 * Example:
 * This example shows how you can use ezcMailMultipartRelated to create an
 * HTML mail with an inline image.
 * <code>
 * $mail = new ezcMail();
 * $mail->from = new ezcMailAddress( 'sender@example.com', 'Adrian Ripburger' );
 * $mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Maureen Corley' ) );
 * $mail->subject = "Example of an HTML email with attachments";
 * $htmlText = new ezcMailText( "<html>Image <img src='cid:image@12345' /></html>" );
 * $htmlText->subType = 'html';
 * $image = new ezcMailFile( "path_to_my_image.jpg" );
 * $image->contentId = 'image@12345';
 * $mail->body = new ezcMailMultipartRelated( $htmlText, $image );
 * </code>
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailMultipartRelated extends ezcMailMultipart
{
    /**
     * Constructs a new ezcMailMultipartRelated.
     *
     * The constructor accepts an arbitrary number of ezcMailParts or arrays with ezcMailparts.
     * Parts are added in the order provided and the first part will be recognized
     * as the main body. Parameters of the wrong type are ignored.
     *
     * @param ezcMailPart|array(ezcMailPart)
     * @return void
     */
    public function __construct()
    {
        $args = func_get_args();
        parent::__construct( $args );
    }

    /**
     * Sets the main part $part of this alternative multipart.
     *
     * @param ezcMailPart $part
     * @return void
     */
    public function setMainPart( ezcMailPart $part )
    {
        $this->parts[0] = $part;
    }

    /**
     * Adds $part to the list of parts and returns the Content-ID of the part.
     *
     * @param ezcMailPart $part
     * @return string
     */
    public function addRelatedPart( ezcMailPart $part  )
    {
        // it doesn't have a Content-ID, we must set one.
        $contentId = '';
        if ( $part->getHeader( 'Content-ID' ) == '' )
        {
            if ( $part instanceof ezcMailFile )
            {
                $part->contentId = ezcMailTools::generateContentId( basename( $part->fileName ) );
            }
            else
            {
                $part->setHeader( 'Content-ID', ezcMailTools::generateContentId( 'part' ) );
            }
        }
        $contentId = trim( $part->getHeader( 'Content-ID' ), '<>' );

        if ( count( $this->parts ) > 0 )
        {
            $this->parts[] = $part;
        }
        else
        {
            $this->parts[1] = $part;
        }
        return $contentId;
    }

    /**
     * Returns the main part of this multipart or null if there is no such part.
     *
     * @return array(ezcMailPart)
     */
    public function getMainPart()
    {
        if ( isset( $this->parts[0] ) )
        {
            return $this->parts[0];
        }
        return null;
    }

    /**
     * Returns the mail parts associated with this multipart.
     *
     * @return array(ezcMailPart)
     */
    public function getRelatedParts()
    {
        return array_slice( $this->parts, 1 );
    }


    /**
     * Returns "related".
     *
     * @return string
     */
    public function multipartType()
    {
        return "related";
    }
}
?>

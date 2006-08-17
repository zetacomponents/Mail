<?php
/**
 * File containing the ezcMailFile class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Mail part for all forms of binary data.
 *
 * The ezcMailFile class has the following properties:
 * - String <B>fileName</B>, the file on disk.
 * - String <B>mimeType</B>, the mimetype of the file. ezcMailFile tries to
 *                           extract this from the file, but you can override it
 *                           with this property.
 * - String <B>contentType</B>, the content type of the file. ezcMailFile tries to
 *                   extract this from the file, but you can override it
 *                   with this property. Possible values are:
 *                   * CONTENT_TYPE_IMAGE
 *                   * CONTENT_TYPE_VIDEO
 *                   * CONTENT_TYPE_APPLICATION
 * - String <B>dispositionType</B>, if the file should be shown inline in the mail
 *                          or as an attachment. Possible values are:
 *                          * DISPLAY_ATTACHMENT
 *                          * DISPLAY_INLINE
 * - int <B>contentId</B>,  the ID of this part. Used for internal links within an
 *                    email. Setting this also sets the header Content-ID.
 *
 * @todo MimeType recognition
 * @package Mail
 * @version //autogen//
 */
class ezcMailFile extends ezcMailPart
{
    /**
     * Image content type. Use this if the contents of the file is an image.
     */
    const CONTENT_TYPE_IMAGE = "image";

    /**
     * Video content type. Use this if the contents of the file is a video.
     */
    const CONTENT_TYPE_VIDEO = "video";

    /**
     * Audio content type. Use this if the contents of the file is an audio.
     */
    const CONTENT_TYPE_AUDIO = "audio";

    /**
     * Application content type. Use this if the file non of the other
     * content types match.
     */
    const CONTENT_TYPE_APPLICATION = "application";

    /**
     * Use DISPLAY_ATTACHMENT if you want the file to be displayed as an attachment
     * to the recipients of the mail.
     */
    const DISPLAY_ATTACHMENT = "attachment";

    /**
     * Use DISPLAY_INLINE if you want the file to be displayed inline in the mail
     * to the recipients.
     */
    const DISPLAY_INLINE = "inline";

    /**
     * Holds the properties of this class.
     *
     * @var array(string=>mixed)
     */
    private $properties = array();

    /**
     * Constructs a new attachment with $fileName.
     *
     * @param string $fileName
     * @return void
     */
    public function __construct( $fileName /*,$encoding = ezcMail::BASE64*/ )
    {
        parent::__construct();

        // initialize properties that may be touched automatically
        // this is to avoid notices
        $this->properties['contentType'] = null;
        $this->properties['mimeType'] = null;
        $this->properties['dispositionType'] = null;
        $this->properties['contentId'] = null;

        // for the same reason, this must be set first
        $this->fileName = $fileName;

//        $this->encoding = ezcMail::BASE64;
        $this->setHeader( 'Content-Transfer-Encoding', 'base64' );

        $this->dispositionType = self::DISPLAY_ATTACHMENT;
        // default to mimetype application/octet-stream
        $this->contentType = self::CONTENT_TYPE_APPLICATION;
        $this->mimeType = "octet-stream";
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @throws ezcBaseFileNotFoundException when setting the property with an invalid filename.
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'fileName':
                if ( is_readable( $value ) )
                {
                    $this->properties['fileName'] = $value;
                    $this->setHeaderContentType();
                    $this->setHeaderContentDisposition();
                }
                else
                {
                    throw new ezcBaseFileNotFoundException( $value );
                }
                break;
//            case 'encoding':
//                $this->properties['encoding'] = $value;
//                $this->setHeader( 'Content-Transfer-Encoding', $value );
//                break;
            case 'mimeType':
                $this->properties['mimeType'] = $value;
                $this->setHeaderContentType();
                break;
            case 'contentType':
                $this->properties['contentType'] = $value;
                $this->setHeaderContentType();
                break;
            case 'dispositionType':
                $this->properties['dispositionType'] = $value;
                $this->setHeaderContentDisposition();
                break;
            case 'contentId':
                $this->properties['contentId'] = $value;
                $this->setHeader( 'Content-ID', '<' . $value . '>' );
                break;
            default:
                return parent::__set( $name, $value );
                break;
        }
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'fileName':
                return $this->properties['fileName'];
                break;
//            case 'encoding':
//                return $this->properties['encoding'];
//                break;
            case 'mimeType':
                return $this->properties['mimeType'];
                break;
            case 'contentType':
                return $this->properties['contentType'];
                break;
            case 'dispositionType':
                return $this->properties['dispositionType'];
                break;
            case 'contentId':
                return $this->properties['contentId'];
                break;
            default:
                return parent::__get( $name );
                break;
        }
    }

    /**
     * Returns the contents of the file with the correct encoding.
     *
     * @return string
     */
    public function generateBody(  )
    {
        return chunk_split( base64_encode( file_get_contents( $this->fileName ) ), 76, ezcMailTools::lineBreak() );
    }

    /**
     * Sets the Content-Type header based on the contentType, mimeType and fileName.
     *
     * @return void
     */
    private function setHeaderContentType()
    {
        $this->setHeader( 'Content-Type',
                          $this->contentType . '/' . $this->mimeType . '; ' . 'name="' . basename( $this->fileName ) . '"' );
    }

    /**
     * Sets the Content-Disposition header based on the properties: dispositionType and fileName.
     *
     * @return void
     */
    private function setHeaderContentDisposition()
    {
        if ( $this->contentDisposition == null )
        {
            $this->contentDisposition = new ezcMailContentDispositionHeader();
        }
        $this->contentDisposition->disposition = $this->dispositionType;
        $this->contentDisposition->fileName = basename( $this->fileName );

//        $this->setHeader( 'Content-Disposition',
//                          $this->dispositionType .'; ' . 'filename="' . basename( $this->fileName ) . '"' );
    }
}
?>

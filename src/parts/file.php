<?php
/**
 * File containing the ezcMailFilePart class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Mail part for all forms of binary data.
 *
 * @todo MimeType recognition
 *
 * @property string $fileName
 *           The name of the file which is to be attached to the email.
 * @property string $mimeType
 *           The mimetype of the file.
 * @property string $contentType
 *           The content type of the file.
 *           Possible values are: CONTENT_TYPE_IMAGE, CONTENT_TYPE_VIDEO and
 *           CONTENT_TYPE_APPLICATION.
 * @property string $dispositionType
 *           If the file should be shown inline in the mail or as an
 *           attachment. Possible values are: DISPLAY_ATTACHMENT and
 *           DISPLAY_INLINE.
 * @property int $contentId
 *           The ID of this part. Used for internal links within an email.
 *           Setting this also sets the header Content-ID.
 *
 * @package Mail
 * @version //autogen//
 */
abstract class ezcMailFilePart extends ezcMailPart
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
    protected $properties = array();

    /**
     * Constructs a new attachment with $fileName.
     *
     * @param string $fileName
     * @return void
     */
    public function __construct( $fileName )
    {
        parent::__construct();

        // initialize properties that may be touched automatically
        // this is to avoid notices
        $this->properties['contentType'] = null;
        $this->properties['mimeType'] = null;
        $this->properties['dispositionType'] = null;
        $this->properties['contentId'] = null;

        $this->fileName = $fileName;
        $this->setHeader( 'Content-Transfer-Encoding', 'base64' );
        $this->dispositionType = self::DISPLAY_ATTACHMENT;
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'fileName':
                $this->properties['fileName'] = $value;
                $this->setHeaderContentType();
                $this->setHeaderContentDisposition();
                break;
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
     * Returns the value of property $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'fileName':
                return $this->properties['fileName'];
                break;
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
     * Returns true if the property $name is set, otherwise false.
     *
     * @return bool
     * @ignore
     */
    public function __isset( $name )
    {
        switch ( $name )
        {
            case 'fileName':
            case 'mimeType':
            case 'contentType':
            case 'dispositionType':
            case 'contentId':
                return isset( $this->properties[$name] );

            default:
                return parent::__isset( $name );
        }
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
     * Sets the Content-Disposition header
     *
     * Based on the properties $dispositionType and $fileName.
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

        // $this->setHeader( 'Content-Disposition',
        //                  $this->dispositionType .'; ' . 'filename="' . basename( $this->fileName ) . '"' );
    }
}
?>

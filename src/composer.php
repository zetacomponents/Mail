<?php
/**
 * File containing the ezcMailComposer class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Convenience class for writing mail.
 *
 * This class allows you to create
 * text and/or HTML mail with attachments. If you need to create more
 * advanced mail use the ezcMail class and build the body from scratch.
 *
 * ezcMailComposer is used with the following steps:
 * 1. Create a composer object.
 * 2. Set the subject and recipients.
 * 3. Set the plainText and htmlText message parts. You can set only one
 *    or both. If you set both, the client will display the htmlText if it
 *    supports HTML. Otherwise the client will display plainText.
 * 4. Add any attachments.
 * 5. Call the build method.
 *
 * This example shows how to send an HTML mail with a text fallback and
 * attachments. The HTML message has an inline image.
 * <code>
 * $mail = new ezcMailComposer();
 * $mail->from = new ezcMailAddress( 'john@doe.com', 'John Doe' );
 * $mail->addTo( new ezcMailAddress( 'cindy@doe.com', 'Cindy Doe' ) );
 * $mail->subject = "Example of an HTML email with attachments";
 * $mail->plainText = "Here is the text version of the mail. This is displayed if the client can not understand HTML";
 * $mail->htmlText = "<html>Here is the HTML version of your mail with an image: <img src='file://path_to_image.jpg' /></html>";
 * $mail->addAttachment( 'path_to_attachment.file' );
 * $mail->build();
 * $transport = new ezcMailTransportMta();
 * $transport->send( $mail );
 * </code>
 *
 * This class has the following properties:
 * - string <B>plainText</B>, contains the message of the mail in plain text.
 * - string <B>htmlText</B>, contains the message of the mail in HTML. You should
 *          also provide the text of the HTML message in the
 *          plainText property. Both will be sent and the receiver
 *          will see the HTML message if his/her client supports HTML.
 *          If the HTML message contains links to local images and/or
 *          files these will be included into the mail when
 *          generateBody is called. Links to local files must start
 *          with "file://" in order to be recognized.
 *
 *
 * @todo What about character set for the textPart
 * @package Mail
 * @version //autogen//
 */
class ezcMailComposer extends ezcMail
{
    /**
     * Holds the attachments.
     *
     * The array contains relative or absolute paths to the attachments.
     *
     * @var array(string)
     */
    private $attachments = array();

    /**
     * Holds the properties of this class.
     *
     * @var array(string=>mixed)
     */
    private $properties = array();

    /**
     * Constructs an empty ezcMailComposer object.
     */
    public function __construct()
    {
        $this->properties['plainText'] = null;
        $this->properties['htmlText'] = null;
        parent::__construct();
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'plainText':
                $this->properties['plainText'] = $value;
                break;
            case 'htmlText':
                $this->properties['htmlText'] = $value;
                break;
            default:
                parent::__set( $name, $value );
                break;
        }
    }

    /**
     * Returns the property $name.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @return mixed
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'plainText':
                return $this->properties['plainText'];
                break;
            case 'htmlText':
                return $this->properties['htmlText'];
                break;
            default:
                return parent::__get( $name );
                break;
        }
    }

    /**
     * Adds the file $fileName to the list of attachments.
     *
     * @throws ezcBaseFileNotFoundException if $fileName does not exists.
     * @throws ezcBaseFilePermissionProblem if $fileName could not be read.
     * @param string $fileName
     * @return void
     */
    public function addAttachment( $fileName  )
    {
        if ( is_readable( $fileName ) )
        {
            $this->attachments[] = $fileName;
        }
        else
        {
            if ( file_exists( $fileName ) )
            {
                throw new ezcBaseFilePermissionException( $fileName, ezcBaseFileException::READ );
            }
            else
            {
                throw new ezcBaseFileNotFoundException( $fileName );
            }
        }
    }

    /**
     * Builds the complete email message in RFC822 format.
     *
     * This method must be called before the message is sent.
     *
     * @throws ezcBaseFileNotFoundException if any of the attachment files can not be found.
     * @return void
     */
    public function build()
    {
        $mainPart = false;

        // create the text part if there is one
        if ( $this->plainText != '' )
        {
            $mainPart = new ezcMailText( $this->plainText );
        }

        // create the HTML part if there is one
        $htmlPart = false;
        if ( $this->htmlText != '' )
        {
            $htmlPart = $this->generateHtmlPart();

            // create a MultiPartAlternative if a text part exists
            if ( $mainPart != false )
            {
                $mainPart = new ezcMailMultipartAlternative( $mainPart, $htmlPart );
            }
            else
            {
                $mainPart = $htmlPart;
            }
        }

        // build all attachments
        // special case, mail with no text and one attachment
        if ( $mainPart == false && count( $this->attachments ) == 1 )
        {
            $mainPart = new ezcMailFile( $this->attachments[0] );
        }
        else if ( count( $this->attachments ) > 0 )
        {
            $mainPart = ( $mainPart == false )
                ? new ezcMailMultipartMixed()
                : new ezcMailMultipartMixed( $mainPart );

            // add the attachments to the mixed part
            foreach ( $this->attachments as $attachment )
            {
                $mainPart->appendPart( new ezcMailFile( $attachment ) );
            }
        }

        $this->body = $mainPart;
    }

    /**
     * Returns an ezcMailPart based on the HTML provided.
     *
     * This method adds local files/images to the mail itself using a
     * {@link ezcMailMultipartRelated} object.
     *
     * @throws ezcMailException If an inline local file is not found or can't be read.
     * @return ezcMailPart
     */
    private function generateHtmlPart()
    {
        $result = false;
        if ( $this->htmlText != '' )
        {
            // recognize file:// and file:///, pick out the image, add it as a part and then..:)
            preg_match_all( "/file:\/\/[^ >\'\"]+/i", $this->htmlText, $matches );
            // pictures/files can be added multiple times. We only need them once.
            $matches = array_unique( $matches[0] );

            $result = new ezcMailText( $this->htmlText );
            $result->subType = "html";
            if ( count( $matches ) > 0 )
            {
                $htmlPart = $result;
                // wrap already existing message in an alternative part
                $result = new ezcMailMultipartRelated( $result );

                // create a filepart and add it to the related part
                // also store the ID for each part since we need those
                // when we replace the originals in the HTML message.
                foreach ( $matches as $fileName )
                {
                    if ( is_readable( $fileName ) )
                    {
                        $filePart = new ezcMailFile( $fileName );
                        $cid = $result->addRelatedPart( $filePart );
                        // replace the original file reference with a reference to the cid
                        $this->htmlText = str_replace( $fileName, 'cid:' . $cid, $this->htmlText );
                    }
                    else
                    {
                        if ( file_exists( $fileName ) )
                        {
                            throw new ezcBaseFilePermissionException( $fileName, ezcBaseFileException::READ );
                        }
                        else
                        {
                            throw new ezcBaseFileNotFoundException( $fileName );
                        }
                        // throw
                    }
                }
                // update mail, with replaced url's
                $htmlPart->text = $this->htmlText;
            }
        }
        return $result;
    }
}
?>

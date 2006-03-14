<?php
/**
 * File containing the ezcMailRfc822Digest class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Mail part or mail digest parts.
 *
 * This class is used to insert mail into mail.
 *
 * The ezcMailFile class has the following properties:
 * - String <B>mail</B>, the mail object to digest.
 *
 * This example assumes that the mail object to digest is availble in the $digest variable:
 * <code>
 * $mail = new ezcMail();
 * $mail->from = new ezcMailAddress( 'sender@example.com', 'Largo LaGrande' );
 * $mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Wally B. Feed' ) );
 * $mail->subject = "This is the subject of the mail with a mail digest.";
 * $textPart = new ezcMailText( "This is the body of the mail with a mail digest." );
 *
 * $mail->body = new ezcMailMultipartMixed( $textPart, new ezcMailRfc822Digest( $digest ) );
 *
 * $transport = new ezcMailTransportMta();
 * $transport->send( $mail );
 * </code>
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailRfc822Digest extends ezcMailPart
{
    /**
     * Holds the properties of this class.
     *
     * @var array(string=>mixed)
     */
    private $properties = array();

    /**
     * Constructs a new ezcMailDigest with the mail $mail.
     *
     * @param ezcMail $mail
     */
    public function __construct( ezcMail $mail )
    {
        $this->mail = $mail;
        $this->setHeader( 'Content-Type', 'message/rfc822' );
        $this->setHeader( 'Content-Disposition', 'inline' );
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
            case 'mail':
                $this->properties[$name] = $value;
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $name );
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
            case 'mail':
                return $this->properties[$name];
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $name );
                break;
        }
    }


    /**
     * Returns the body part of this mail consisting of the digested mail.
     *
     * @return string
     */
    public function generateBody()
    {
        return $this->mail->generate();
    }
}
?>

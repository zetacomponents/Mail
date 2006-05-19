<?php
/**
 * File containing the ezcMail class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * The main mail class.
 *
 * You can use ezcMail together with the other classes derived from ezcMailPart
 * to build email messages. When the mail is built, use the Transport classes
 * to send the mail.
 *
 * This example builds and sends a simple text mail message:
 * <code>
 * $mail = new ezcMail;
 * $mail->from = new ezcMailAddress( 'sender@example.com', 'Adrian Ripburger' );
 * $mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Maureen Corley' ) );
 * $mail->subject = "Hi";
 * $mail->body = new ezcMailText( "I just mail to say I love you!" );
 * $transport = new ezcMailTransportMta();
 * $transport->send( $mail );
 * </code>
 *
 * You can also derive your own mail classes from this class if you have special
 * requirements. An example of this is the ezcMailComposer class which is a convenience
 * class to send simple mail structures and HTML mail.
 *
 * The ezcMail class has the following properties:
 * - <b>from</b> <i>ezcMailAddress</i>, contains the from address as an ezcMailaddress object.
 * - <b>to</b> <i>array(ezcMailAddress)</i>, contains an array of ezcMailAddress objects.
 * - <b>cc</b> <i>array(ezcMailAddress)</i>, contains an array of ezcMailAddress objects.
 * - <b>bcc</b> <i>array(ezcMailAddress)</i>, contains an array of ezcMailAddress objects.
 * - <b>subject</b> <i>string</i>, contains the subject of the e-mail. Use setSubject if you require a special encoding.
 * - <b>subjectCharset</b> <i>string</i> The encoding of the subject.
 * - <b>messageID</b> <i>string</i> The message ID of the message. Treat as read-only unless you're 100% sure what you're doing.
 * - <b>timestamp</b> <i>integer</i> The date/time of when the message was sent as Unix Timestamp. (read-only)
 * - <b>body</b> <i>ezcMailPart</i> The body part of the message.
 *
 * There are several headers you can set on the mail object to achieve various effects:
 * - Reply-To - Set this to an email address if you want people to reply to an address
 *              other than the from address.
 * - Errors-To - If the mail can not be delivered the error message will be sent to this address.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMail extends ezcMailPart
{
    /**
     * 7 bit encoding.
     */
    const SEVEN_BIT = "7bit";

    /**
     * 8 bit encoding.
     */
    const EIGHT_BIT = "8bit";

    /**
     * Binary encoding.
     */
    const BINARY = "binary";

    /**
     * Quoted printable encoding.
     */
    const QUOTED_PRINTABLE = "quoted_printable";

    /**
     * Base 64 encoding.
     */
    const BASE64 = "base64";

    /**
     * Holds the properties of this class.
     *
     * @var array(string=>mixed)
     */
    private $properties = array();

    /**
     * Constructs an empty ezcMail object.
     */
    public function __construct( )
    {
        $this->properties['from'] = null;
        $this->properties['to'] = array();
        $this->properties['cc'] = array();
        $this->properties['bcc'] = array();
        $this->properties['subject'] = null;
        $this->properties['subjectCharset'] = 'us-ascii';
        $this->properties['body'] = null;
        $this->properties['messageID'] = null;
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
            case 'from':
                $this->properties['from'] = $value;
                break;

            case 'to':
                $this->properties['to'] = $value;
                break;

            case 'cc':
                $this->properties['cc'] = $value;
                break;

            case 'bcc':
                $this->properties['bcc'] = $value;
                break;

            case 'subject':
                $this->properties['subject'] = trim( $value );
                break;

            case 'subjectCharset':
                $this->properties['subjectCharset'] = $value;
                break;

            case 'body':
                $this->properties['body'] = $value;
                break;

            case 'messageID':
                $this->properties['messageID'] = $value;
                break;

            case 'timestamp':
                throw new ezcBasePropertyPermissionException( $name, ezcBasePropertyPermissionException::READ );
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $name );
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
            case 'from':
                return $this->properties['from'];
                break;

            case 'to':
                return $this->properties['to'];
                break;

            case 'cc':
                return $this->properties['cc'];
                break;

            case 'bcc':
                return $this->properties['bcc'];
                break;

            case 'subject':
                return $this->properties['subject'];
                break;

            case 'subjectCharset':
                return $this->properties['subjectCharset'];
                break;

            case 'body':
                return $this->properties['body'];
                break;

            case 'messageID':
                return $this->properties['messageID'];
                break;

            case 'timestamp':
                return strtotime( $this->getHeader( "Date" ) );
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $name );
                break;
        }
    }

    /**
     * Adds the ezcMailAddress $address to the list of 'to' recipients.
     *
     * @param ezcMailAddress $address
     * @return void
     */
    public function addTo( ezcMailAddress $address )
    {
        $this->properties['to'][] = $address;
    }

    /**
     * Adds the ezcMailAddress $address to the list of 'cc' recipients.
     *
     * @param ezcMailAddress $address
     * @return void
     */
    public function addCc( ezcMailAddress $address )
    {
        $this->properties['cc'][] = $address;
    }

    /**
     * Adds the ezcMailAddress $address to the list of 'bcc' recipients.
     *
     * @param ezcMailAddress $address
     * @return void
     */
    public function addBcc( ezcMailAddress $address )
    {
        $this->properties['bcc'][] = $address;
    }

    /**
     * Returns the generated body part of this mail.
     *
     * Returns an empty string if no body has been set.
     *
     * @return string
     */
    public function generateBody()
    {
        if ( is_subclass_of( $this->body, 'ezcMailPart' ) )
        {
            return $this->body->generateBody();
        }
        return '';
    }

    /**
     * Returns the generated headers for the mail.
     *
     * This method is called automatically when the mail message is built.
     * You can re-implement this method in subclasses if you wish to set different
     * mail headers than ezcMail.
     *
     * @return string
     */
    public function generateHeaders()
    {
        // set our headers first.
        $this->setHeader( "From", ezcMailTools::composeEmailAddress( $this->from ) );
        $this->setHeader( "To", ezcMailTools::composeEmailAddresses( $this->to ) );
        if ( count( $this->cc ) )
        {
            $this->setHeader( "Cc", ezcMailTools::composeEmailAddresses( $this->cc ) );
        }
        if ( count( $this->bcc ) )
        {
            $this->setHeader( "Bcc", ezcMailTools::composeEmailAddresses( $this->bcc ) );
        }

        // build subject header
        if ( $this->subjectCharset !== 'us-ascii' )
        {
            $preferences = array(
                'input-charset' => $this->subjectCharset,
                'output-charset' => $this->subjectCharset,
                'line-length' => 76,
                'scheme' => 'B',
                'line-break-chars' => ezcMailTools::lineBreak()
            );
            $subject = iconv_mime_encode( 'dummy', $this->subject, $preferences );
            $this->setHeader( 'Subject', substr( $subject, 7 ) ); // "dummy: " + 1
        }
        else
        {
            $this->setHeader( 'Subject', $this->subject );
        }

        $this->setHeader( 'MIME-Version', '1.0' );
        $this->setHeader( 'User-Agent', 'eZ components' );
        $this->setHeader( 'Date', date( 'r' ) );
        $idhost = $this->from->email != '' ? $this->from->email : 'localhost';
        if ( is_null( $this->messageID ) )
        {
            $this->setHeader( 'Message-Id', '<' . ezcMailTools::generateMessageId( $idhost ) . '>' );
        }
        else
        {
            $this->setHeader( 'Message-Id', $this->messageID );
        }

        // if we have a body part, include the headers of the body
        if ( is_subclass_of( $this->body, "ezcMailPart" ) )
        {
            return parent::generateHeaders() . $this->body->generateHeaders();
        }
        return parent::generateHeaders();
    }
}
?>

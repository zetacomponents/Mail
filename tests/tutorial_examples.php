<?php
declare(encoding="latin1");
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */
class RFC822Digest extends ezcMailPart
{
    private $mail = null;
    public function __construct( ezcMail $mail )
    {
        $this->mail = $mail;
        $this->setHeader( 'Content-Type', 'message/rfc822' );
        $this->setHeader( 'Content-Disposition', 'inline' );
    }

        public function generateBody()
    {
        return $this->mail->generate();
    }
}



/**
 * @package Mail
 * @subpackage Tests
 *
 * If you change any of these, remember to update the tutorial as well.
 */
class ezcMailTutorialExamples extends ezcTestCase
{

    public function testComposer()
    {
        $mail = new ezcMailComposer();
        $mail->from = new ezcMailAddress( 'sender@example.com', 'Adrian Ripburger' );
        $mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Maureen Corley' ) );
        $mail->subject = "This is the subject of the example mail";
        $mail->plainText = "This is the body of the example mail.";
        $mail->build();
        $transport = new ezcMailMtaTransport();
//        $transport->send( $mail );
    }

    public function testMail1()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'sender@example.com', 'Boston Low' );
        $mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Maggie Robbins' ) );
        $mail->subject = "This is the subject of the example mail";
        $mail->body = new ezcMailText( "This is the body of the example mail." );
        $transport = new ezcMailMtaTransport();
//        $transport->send( $mail );
    }

    public function testMail2()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'sender@example.com', 'Bernard Bernoulli' );
        $mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Wendy' ) );
        $mail->subject = "This is the subject of the example mail";
        $textPart = new ezcMailText( "This is the body of the example mail." );
//        $fileAttachment = new ezcMailFile( "~/myfile.jpg" );
        $fileAttachment = new ezcMailFile( dirname( __FILE__) . "/parts/data/fly.jpg" );

        $mail->body = new ezcMailMultipartMixed( $textPart, $fileAttachment );
        $transport = new ezcMailMtaTransport();
//        $transport->send( $mail );
    }

    public function testMail3()
    {
        $digest = new ezcMail();
        $digest->from = new ezcMailAddress( 'sender@example.com', 'Adrian Ripburger' );
        $digest->addTo( new ezcMailAddress( 'fh@ez.no', 'Maureen Corley' ) );
        $digest->subject = "This is the subject of the example mail";
        $digestTextPart = new ezcMailText( "This is the body of the example mail." );
//        $fileAttachment = new ezcMailFile( "~/myfile.jpg" );
        $fileAttachment = new ezcMailFile( dirname( __FILE__) . "/parts/data/fly.jpg" );

        $digest->body = new ezcMailMultipartMixed( $digestTextPart, $fileAttachment );

        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'sender@example.com', 'Largo LaGrande' );
        $mail->addTo( new ezcMailAddress( 'receiver@example.com', 'Wally B. Feed' ) );
        $mail->subject = "This is the subject of the mail with a mail digest.";
        $textPart = new ezcMailText( "This is the body of the mail with a mail digest." );

        $mail->body = new ezcMailMultipartMixed( $textPart,
                                                  new RFC822Digest( $digest ) );
        $transport = new ezcMailMtaTransport();
//        $transport->send( $mail );
    }

    public function testMail4()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'sender@example.com', 'Norwegian characters: æøå', 'iso-8859-1' );
        $mail->addTo( new ezcMailAddress( 'reciever@example.com', 'More norwegian characters: æøå', 'iso-8859-1' ) );
        $mail->subject = 'Oslo ligger sør i Norge og har vært landets hovedstad i over 600 år.';
        $mail->subjectCharset = 'iso-8859-1';
        $mail->body = new ezcMailText( 'Oslo be grunnlagt rundt 1048 av Harald Hardråde.', 'iso-8859-1' );
        $transport = new ezcMailMtaTransport();
//        $transport->send( $mail );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailTutorialExamples" );
    }
}
?>

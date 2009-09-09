<?php
declare(encoding="latin1");
/**
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

require dirname( __FILE__ ) . '/data/classes/custom_classes.php';

/**
 * @package Mail
 * @subpackage Tests
 */
// TODO: check cc && bcc
class ezcMailParserTest extends ezcTestCase
{
    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailParserTest" );
    }

    // 
    // Kmail
    // 
    public function testKmail1()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'kmail/simple_mail_with_text_subject_and_body.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Simple mail with text subject and body', $mail->subject );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailText );
        $this->assertEquals( "This is the body\n", $mail->body->text );
        $this->assertEquals( "utf-8", $mail->body->charset );
        $this->assertEquals( "us-ascii", $mail->body->originalCharset );
        $this->assertEquals( 'plain', $mail->body->subType );
        $this->assertEquals( '<200602061533.27600.fh@ez.no>', $mail->messageID );
        $this->assertEquals( 1139236407, $mail->timestamp );
        $this->assertEquals( 1139236407, strtotime( $mail->getHeader( 'Date' ) ) );
        $this->assertEquals( strtotime( 'Mon, 06 Feb 2006 15:33:27 +0100' ), $mail->timestamp );
    }

    public function testKmail2()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'kmail/mail_with_iso-8859-1_encoding.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailText );
        $this->assertEquals( "This is the body: æøå\n", $mail->body->text );
        $this->assertEquals( "utf-8", $mail->body->charset );
        $this->assertEquals( "iso-8859-1", $mail->body->originalCharset );
        $this->assertEquals( 'plain', $mail->body->subType );
        $this->assertEquals( '<200602061537.45162.fh@ez.no>', $mail->messageID );
    }

    public function testKmail3()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'kmail/mail_with_attachment.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Mail with attachment', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailFile );
        $this->assertEquals( 'Boundary-00=_M715D0nt6IAUljt', $mail->body->boundary );

        // check the body
        $this->assertEquals( "This is the body\n", $parts[0]->text );

        // check the file
        $this->assertEquals( 'tur.jpg', strstr( $parts[1]->fileName, 'tur.jpg' ) );
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_IMAGE, $parts[1]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[1]->dispositionType );
        $this->assertEquals( 'jpeg', $parts[1]->mimeType );
        $this->assertEquals( '<200602061535.56671.fh@ez.no>', $mail->messageID );
    }

    public function testKmail4()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'kmail/mail_with_digest.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Mail with digest', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailRfc822Digest );

        // check the body
        $this->assertEquals( "This is the body\n", $parts[0]->text );

        // continue checking the contents of the mail here.. it should be the same as for testKmail3()
        $mail = $parts[1]->mail;
        $this->assertEquals( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Mail with attachment', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailFile );

        // check the body
        $this->assertEquals( "This is the body\n", $parts[0]->text );

        // check the file
        $this->assertEquals( 'tur.jpg', strstr( $parts[1]->fileName, 'tur.jpg' ) );
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_IMAGE, $parts[1]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[1]->dispositionType );
        $this->assertEquals( 'jpeg', $parts[1]->mimeType );
        $this->assertEquals( '<200602061535.56671.fh@ez.no>', $mail->messageID );

    }

    public function testKmail5()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'kmail/html_mail.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'HTML mail', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartAlternative );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailText );

        // text part
        $this->assertEquals( 'plain', $parts[0]->subType );
        $this->assertEquals( "This is the body", $parts[0]->text );

        $this->assertEquals( 'html', $parts[1]->subType );
        $this->assertEquals( '<html>', substr( $parts[1]->text, 0, 6 ) );
        $this->assertEquals( '<200602061538.16305.fh@ez.no>', $mail->messageID );
    }

    // 
    // Mail.app
    // 

    public function testMailApp1()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'mail.app/html_mail_with_image.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'xxx@ez.no', 'Ole Marius Smestad', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'HTML mail with inline image Mail.app', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartAlternative );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailMultipartRelated );
        $this->assertEquals( 'Apple-Mail-7-898127351', $mail->body->boundary );

        // check the text
        $this->assertEquals( 'utf-8', $parts[0]->charset );
        $this->assertEquals( 'plain', $parts[0]->subType );

        // check the multipart related
        $mainPart = $parts[1]->getMainPart();
        $this->assertEquals( true, $mainPart instanceof ezcMailText );
        $this->assertEquals( 'iso-8859-1', $mainPart->originalCharset );
        $this->assertEquals( 'utf-8', $mainPart->charset );
        $this->assertEquals( 'html', $mainPart->subType );
        $this->assertEquals( 'Apple-Mail-8-898127352', $parts[1]->boundary );

        $this->assertEquals( 1, count( $parts[1]->getRelatedParts() ) );
        // chech the multipart related file
        $filePart = $parts[1]->getRelatedParts();
        $filePart = $filePart[0]; // only one
        $this->assertEquals( true, $filePart instanceof ezcMailFile );
        $this->assertEquals( 'tur.jpg', strstr( $filePart->fileName, 'tur.jpg' ) );
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_IMAGE, $filePart->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_INLINE, $filePart->dispositionType );
        $this->assertEquals( 'jpeg', $filePart->mimeType );
        $this->assertEquals( '689D8AD3-D129-443F-94F7-90037B82B429@ez.no', $filePart->contentId );

        $this->assertEquals( $filePart, $parts[1]->getRelatedPartByID( '689D8AD3-D129-443F-94F7-90037B82B429@ez.no' ) );
        $this->assertEquals( 1142414084, $mail->timestamp );
        $this->assertEquals( 1142414084, strtotime( $mail->getHeader( 'Date' ) ) );
    }

    // 
    // Gmail
    // 
    public function testGmail1()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'gmail/simple_mail_with_text_subject_and_body.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'sender@gmail.com', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Gmail: Simple mail with text subject and body', $mail->subject );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailText );
        $this->assertEquals( "This is the body", $mail->body->text );
        $this->assertEquals( "iso-8859-1", $mail->body->originalCharset );
        $this->assertEquals( "utf-8", $mail->body->charset );
        $this->assertEquals( 'plain', $mail->body->subType );

        $this->assertEquals( 1142502574, $mail->timestamp );
        $this->assertEquals( 1142502574, strtotime( $mail->getHeader( 'Date' ) ) );
    }

    public function testGmail2()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'gmail/mail_with_norwegian_characters.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'sender@gmail.com', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
//        var_dump( $mail->subject );
//        $this->assertEquals( 'Simple mail with text subject and body', $mail->subject );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartAlternative );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( "This is the body: æøå\n", $parts[0]->text );
        $this->assertEquals( "iso-8859-1", $parts[0]->originalCharset );
        $this->assertEquals( "utf-8", $parts[0]->charset );
        $this->assertEquals( 'plain', $parts[0]->subType );
    }

    public function testGmail3()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'gmail/mail_with_attachment.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'sender@gmail.com', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Gmail: Mail with attachment', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailMultipartAlternative );
        $this->assertEquals( true, $parts[1] instanceof ezcMailFile );

        $altParts = $parts[0]->getParts();
        // check the body
        $this->assertEquals( "This is the body\n", $altParts[0]->text );

        // check the file
        $this->assertEquals( 'tur.jpg', strstr( $parts[1]->fileName, 'tur.jpg' ) );
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_IMAGE, $parts[1]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[1]->dispositionType );
        $this->assertEquals( 'jpeg', $parts[1]->mimeType );
    }

    public function testgmail4()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'gmail/html_mail.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'sender@gmail.com', 'Frederik Holljen', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Gmail: HTML mail', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartAlternative );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailText );

        // text part
        $this->assertEquals( 'plain', $parts[0]->subType );
        $this->assertEquals( "This is the body\n", $parts[0]->text );

        $this->assertEquals( 'html', $parts[1]->subType );
        $this->assertEquals( '<span', substr( $parts[1]->text, 0, 5 ) );
    }

    // 
    // Opera
    // 

    public function testOpera()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'opera/simple_mail_with_text_subject_and_body.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'sender@example.com', 'Terje Gunrell-Kaste', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'fh@ez.no', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Opera: Simple mail with text subject and body', $mail->subject );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailText );
        $this->assertEquals( "This the body", $mail->body->text );
        $this->assertEquals( "iso-8859-15", $mail->body->originalCharset );
        $this->assertEquals( "utf-8", $mail->body->charset );
        $this->assertEquals( 'plain', $mail->body->subType );

        $this->assertEquals( 1142504273, $mail->timestamp );
        $this->assertEquals( 1142504273, strtotime( $mail->getHeader( 'Date' ) ) );
    }

    public function testOpera2()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'opera/mail_with_norwegian_characters.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'sender@example.com', 'Terje Gunrell-Kaste', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'fh@ez.no', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
//        var_dump( $mail->subject );
//        $this->assertEquals( 'Simple mail with text subject and body', $mail->subject );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailText );
        $this->assertEquals( "This is the body: æøå", $mail->body->text );
        $this->assertEquals( "iso-8859-15", $mail->body->originalCharset );
        $this->assertEquals( "utf-8", $mail->body->charset );
        $this->assertEquals( 'plain', $mail->body->subType );
    }

    public function testOpera3()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'opera/mail_with_attachment.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'sender@example.com', 'Terje Gunrell-Kaste', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'fh@ez.no', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Opera: Mail with attachment', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailFile );

        // check the body
        $this->assertEquals( "This is the body", $parts[0]->text );

        // check the file
        $this->assertEquals( 'tur.jpg', strstr( $parts[1]->fileName, 'tur.jpg' ) );
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_IMAGE, $parts[1]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[1]->dispositionType );
        $this->assertEquals( 'jpeg', $parts[1]->mimeType );
    }

    // 
    // Pine
    // 
    public function testPine1()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'pine/simple_mail_with_text_subject_and_body.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'dr@ez.no', 'Derick Rethans', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'pine: Simple mail with text subject and body', $mail->subject );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailText );
        $this->assertEquals( "this is a body", $mail->body->text );
        $this->assertEquals( "us-ascii", $mail->body->originalCharset );
        $this->assertEquals( "utf-8", $mail->body->charset );
        $this->assertEquals( 'plain', $mail->body->subType );

        $this->assertEquals( 1142497778, $mail->timestamp );
        $this->assertEquals( 1142497778, strtotime( $mail->getHeader( 'Date' ) ) );
    }

    public function testPine2()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'pine/mail_with_norwegian_characters.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'dr@ez.no', 'Derick Rethans', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
//        var_dump( $mail->subject );
//        $this->assertEquals( 'Simple mail with text subject and body', $mail->subject );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( "This is the body with æøå", $parts[0]->text );
        $this->assertEquals( "iso-8859-15", $parts[0]->originalCharset );
        $this->assertEquals( "utf-8", $parts[0]->charset );
        $this->assertEquals( 'plain', $parts[0]->subType );
    }

    public function testPine3()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'pine/mail_with_attachment.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'dr@ez.no', 'Derick Rethans', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'pine: Mail with attachment', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailFile );

        // check the body
        $this->assertEquals( "This is the body", $parts[0]->text );

        // check the file
        $this->assertEquals( 'tur.jpg', strstr( $parts[1]->fileName, 'tur.jpg' ) );
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_IMAGE, $parts[1]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[1]->dispositionType );
        $this->assertEquals( 'jpeg', $parts[1]->mimeType );
    }

    public function testPine4()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'pine/three_message_digest.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'dr@ez.no', 'Derick Rethans', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ),
                                    new ezcMailAddress( 'dr@ez.no', 'Derick Rethans', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'pine: 3 forwarded messages... + attachment', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailMultipartDigest );
        $this->assertEquals( true, $parts[2] instanceof ezcMailFile );

        // check the body
        $this->assertEquals( "This is the body", $parts[0]->text );

        // check the file
        $this->assertEquals( 'tur.jpg', strstr( $parts[2]->fileName, 'tur.jpg' ) );
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_IMAGE, $parts[2]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[2]->dispositionType );
        $this->assertEquals( 'jpeg', $parts[2]->mimeType );


        // check the digest
        $this->assertEquals( 3, count( $parts[1]->getParts() ) );

        $parts = $parts[1]->getParts();



        // we'll check the last of the messages, it is the attachment message
        $this->assertEquals( true, $parts[2] instanceof ezcMailRfc822Digest );
        $mail = $parts[2]->mail;

        $this->assertEquals( new ezcMailAddress( 'dr@ez.no', 'Derick Rethans', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'pine: Mail with attachment', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );

        // check the body
        $this->assertEquals( "This is the body with æøå", $parts[0]->text );
    }

    // 
    // Hotmail
    // 
    public function testHotmail1()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'hotmail/simple_mail_with_text_subject_and_body.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'example@hotmail.com', 'Kristian Hole', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Simple mail with text subject and body', $mail->subject );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailText );
        $this->assertEquals( "This is the body", $mail->body->text );
        $this->assertEquals( "iso-8859-1", $mail->body->originalCharset );
        $this->assertEquals( "utf-8", $mail->body->charset );
        $this->assertEquals( 'plain', $mail->body->subType );

        $this->assertEquals( 1142498606, $mail->timestamp );
        $this->assertEquals( 1142498606, strtotime( $mail->getHeader( 'Date' ) ) );
    }

    public function testHotmail2()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'hotmail/mail_with_norwegian_characters.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'example@hotmail.com', 'Kristian Hole', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
//        var_dump( $mail->subject );
//        $this->assertEquals( 'Simple mail with text subject and body', $mail->subject );
        $this->assertEquals( 'utf-8', $mail->subjectCharset );
        $this->assertEquals( true, $mail->body instanceof ezcMailText );
        $this->assertEquals( "This is the body: æøå", $mail->body->text );
        $this->assertEquals( "iso-8859-1", $mail->body->originalCharset );
        $this->assertEquals( "utf-8", $mail->body->charset );
        $this->assertEquals( 'plain', $mail->body->subType );
    }

    public function testHotmail3()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'hotmail/mail_with_attachment.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'example@hotmail.com', 'Kristian Hole', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'Mail with attachment', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailFile );

        // check the body
        $this->assertEquals( "This is the body\n", $parts[0]->text );

        // check the file
        $this->assertEquals( 'tur.jpg', strstr( $parts[1]->fileName, 'tur.jpg' ) );
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_IMAGE, $parts[1]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[1]->dispositionType );
        $this->assertEquals( 'jpeg', $parts[1]->mimeType );
    }

    public function testDraft1Bcc()
    {
        $mbox = new ezcMailMboxTransport( dirname( __FILE__ ) . "/data/drafts/postponed-msgs-pine.mbox" );
        $set = $mbox->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( array( new ezcMailAddress( 'sb@example.com', 'Sebastian Bergmann', 'utf-8' ) ), $mail[0]->bcc );
    }

    // Comment: The CC string is in iso-8859-1 not in UTF-8 as it says it is. Is this our
    // problem or not?!? Anyway, we need to check for false return everywhere we use the iconv method.
    public function testVarious1()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-broken-mime-encoded-string' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( array( new ezcMailAddress( 'xx@ez.no', 'Bård Farsted', 'utf-8' ) ), $mail->cc );

        $this->assertEquals( 1101976145, $mail->timestamp );
        $this->assertEquals( 1101976145, strtotime( $mail->getHeader( 'Date' ) ) );
    }

    public function testVarious2()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-filename-with-space' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $parts = $mail->body->getParts();

        // check the file
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_APPLICATION, $parts[1]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[1]->dispositionType );
        $this->assertEquals( 'vnd.oasis.opendocument.text', $parts[1]->mimeType );
        $this->assertEquals( 'bildemanipulering med php.odt', strstr( $parts[1]->fileName, 'bildemanipulering med php.odt' ) );
    }

    // this should be fixed in iconv and not in our code.
    public function testVarious3()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-lowercase-hex-chars-in-mime-encoding' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( "[Flickr] You are xxxx's newest contact!", $mail->subject );
    }

    // same as above.. but how to fix?
    public function testVarious4()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-mime-header-x-unknown' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( new ezcMailAddress( 'foobar@example.hu', 'FOO Bár', 'utf-8' ), $mail->from );
    }

    public function testVarious5()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-text-lineendings' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $parts = $mail->body->getParts();

        // check the file
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_APPLICATION, $parts[2]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[2]->dispositionType );
        $this->assertEquals( 'octet-stream', $parts[2]->mimeType );
        $this->assertEquals( 'gimme.jl', strstr( $parts[2]->fileName, 'gimme.jl' ) );
        $expected = ";; gimme.jl -- fast window access keyboard accelerators -*- lisp -*-\n;; \$Id: gimme.jl,v 1.1 2002/04/15 08:38:57 ssb Exp \$\n";
        $this->assertEquals( $expected, substr( file_get_contents( $parts[2]->fileName ), 0, strlen( $expected ) ) );
    }
    public function testVarious6()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-soft-lineending' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $parts = $mail->body->getParts();

        // check the file
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_APPLICATION, $parts[1]->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_ATTACHMENT, $parts[1]->dispositionType );
        $this->assertEquals( 'octet-stream', $parts[1]->mimeType );
        $this->assertEquals( 'SPOOFING.INI', strstr( $parts[1]->fileName, 'SPOOFING.INI' ) );
        $expected = <<<END
[ language.ini ]
config language=en

[ script.ini ]
add name=autopvc_add_qos index=0 command="qosbook add name rx_peakrate $4 rx_sustrate $5 rx_maxburst $6 dynamic yes"


END;
        $this->assertEquals( $expected, file_get_contents( $parts[1]->fileName ) );

    }

    public function testVarious7()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-html-text-and-attachment' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $parts = $mail->body->getParts();

        $this->assertEquals( 2, count( $parts ) );
        $this->assertEquals( 'ezcMailMultipartAlternative', get_class( $parts[0] ) );

        $subParts = $parts[0]->getParts();
        $this->assertEquals( 2, count( $subParts ) );
        $this->assertEquals( 'ezcMailText', get_class( $subParts[0] ) );
        $this->assertEquals( 'ezcMailMultipartRelated', get_class( $subParts[1] ) );

        $subMainPart = $subParts[1]->getMainPart();
        $this->assertEquals( 'ezcMailText', get_class( $subMainPart ) );

        $subRelatedParts = $subParts[1]->getRelatedParts();
        $this->assertEquals( 1, count( $subRelatedParts ) );
        $this->assertEquals( 'ezcMailFile', get_class( $subRelatedParts[0] ) );
        $this->assertEquals( 'consoletools-table.png', basename( $subRelatedParts[0]->fileName ) );

        $this->assertEquals( 'ezcMailFile', get_class( $parts[1] ) );
        $this->assertEquals( 'mail.php', basename( $parts[1]->fileName ) );
    }

    // we currently don't have PGP support
    // check that the signature does not show up in the multipart body
    public function testPGPSignature()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-mbox-PGP' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( 1, count( $mail[0]->body->getParts() ) );
    }

    public function testPGPEncryptedMail()
    {
    }

    // This test tests that folding works correctly
    public function testVarious9()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-mbox-russian' );
        $mail = $parser->parseMail( $set );

        $this->assertEquals( 1, count( $mail ) );

        // subject string should be the same as in email (with line break)
        $subject = "Re: =?koi8-r?b?7c7FIM7BxM8g1crUySDOwSDewdMg0yAxMi4wMCDQzw==?=" .
            " =?koi8-r?b?IM/Sx8HOydrBw8nPzs7ZzQ==?= =?koi8-r?b?INfP0NLP08HNLi4u?=";

        $this->assertEquals( $subject, $mail[0]->getHeader('Subject' ) );
    }

    public function testContentDisposition()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'kmail/mail_with_attachment.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartMixed );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailFile );
        // check the mail, it should not have a Content-Disposition field
        $this->assertEquals( null, $mail->contentDisposition );

        // check the body, it should have a Content-Disposition field with 'inline' set to 1
        $this->assertEquals( 'inline', $parts[0]->contentDisposition->disposition );

        // check the file, it should have a content disposition
        $this->assertEquals( 'attachment', $parts[1]->contentDisposition->disposition );
        $this->assertEquals( 'tur.jpg', $parts[1]->contentDisposition->fileName );
    }

    public function testDigestInDigest()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-digest-in-digest' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );

        $parts = $mail[0]->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailRfc822Digest );

        // check the digest
        $parts = $parts[1]->mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailRfc822Digest );
    }

    /**
     * Test for issue #12903: Size of a mail is calculated twice
     */
    public function testDigestInDigestCalculateSizes()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-digest-in-digest' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( 26865, $mail[0]->size );

        $parts = $mail[0]->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailRfc822Digest );
        $this->assertEquals( 24860, $parts[1]->size );

        // check the digest
        $parts = $parts[1]->mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailRfc822Digest );
        $this->assertEquals( 23563, $parts[1]->size );
    }

    public function testVarious10()
    {
        $parser = new ezcMailParser();
        try
        {
            $set = new SingleFileSet( 'various/test-bounced' );
            $mail = $parser->parseMail( $set );
            $this->assertEquals( 1, count( $mail ) );
        }
        catch ( Exception $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testVarious11()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-RFC2184-header' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $parts = $mail[0]->body->getParts();
        $this->assertEquals( true, $parts[1] instanceof ezcMailFile );
        $this->assertEquals( "this_thing_has_a_very_long_file_name.jpg",
                             $parts[1]->contentDisposition->fileName, "Fails until I figure out what the RFC means." );
    }

    public function testExtendedMailClass()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-text-lineendings' );
        $mail = $parser->parseMail( $set, "ExtendedMail" );
        foreach ( $set as $mail )
        {
            $this->assertType(
                "ExtendedMail",
                $mail,
                "Parser did not create instance of extended mail class."
            );
        }

    }
    
    public function testHeadersHolder()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'kmail/simple_mail_with_text_subject_and_body.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertType(
            "ezcMailHeadersHolder",
            $mail[0]->headers
        );
    }

    public function testReturnPath()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'gmail/html_mail.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( 'sender@gmail.com', $mail->returnPath->email );
    }

    public function testGetPartsNoFilterNoDigest()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-html-text-and-attachment' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( null, false );
        $expected = array( 'ezcMailText',
                           'ezcMailText',
                           'ezcMailFile',
                           'ezcMailFile'
                           );
        $this->assertEquals( 4, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    public function testGetPartsFilterNoDigest()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-html-text-and-attachment' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( array( 'ezcMailText' ), false );
        $expected = array( 'ezcMailText',
                           'ezcMailText'
                           );
        $this->assertEquals( 2, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    public function testGetPartsFilterNoDigestIncludeDigests()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-html-text-and-attachment' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( array( 'ezcMailText' ), true );
        $expected = array( 'ezcMailText',
                           'ezcMailText'
                           );
        $this->assertEquals( 2, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    public function testGetPartsNoFilter()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'pine/three_message_digest.mail' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( null, false );
        $expected = array( 'ezcMailText',
                           'ezcMailRfc822Digest',
                           'ezcMailRfc822Digest',
                           'ezcMailRfc822Digest',
                           'ezcMailFile'
                         );
        $this->assertEquals( 5, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    public function testGetPartsNoFilterIncludeDigests()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'pine/three_message_digest.mail' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( null, true );
        $expected = array( 'ezcMailText',
                           'ezcMailText',
                           'ezcMailText',
                           'ezcMailFile',
                           'ezcMailText',
                           'ezcMailFile'
                         );
        $this->assertEquals( 6, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    public function testGetPartsFilter()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'pine/three_message_digest.mail' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( array( 'ezcMailFile' ), false );
        $expected = array( 'ezcMailFile'
                         );
        $this->assertEquals( 1, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    public function testGetPartsFilterIncludeDigests()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'pine/three_message_digest.mail' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( array( 'ezcMailFile' ), true );
        $expected = array( 'ezcMailFile',
                           'ezcMailFile'
                         );
        $this->assertEquals( 2, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    public function testGetPartsFilterOnlyDigestIncludeDigests()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'pine/three_message_digest.mail' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( array( 'ezcMailRfc822Digest' ), true );
        $this->assertEquals( 0, count( $parts ) );
    }

    public function testGetPartsDigestInDigestNoFilterIncludeDigests()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-digest-in-digest' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( null, true );
        $expected = array( 'ezcMailText',
                           'ezcMailText',
                           'ezcMailText',
                           'ezcMailFile',
                           'ezcMailFile',
                           'ezcMailFile'
                         );
        $this->assertEquals( 6, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    public function testMultipartReport()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/multipart-report' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( null, true );
        $expected = array( 'ezcMailText',
                           'ezcMailDeliveryStatus',
                           'ezcMailText'
                         );
        $this->assertEquals( 3, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
        $this->assertEquals( "dns; www.brssolutions.com", $parts[1]->message["Reporting-MTA"] );
        $this->assertEquals( "failed", $parts[1]->recipients[0]["Action"] );
    }

    public function testMultipartReportMultipleDeliveries()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/multipart-report-multiple-deliveries' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( null, true );
        $expected = array( 'ezcMailText',
                           'ezcMailDeliveryStatus',
                           'ezcMailText'
                         );
        $this->assertEquals( 3, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
        $this->assertEquals( 3, count( $parts[1]->recipients ) );
        $this->assertEquals( "dns; cs.utk.edu", $parts[1]->message["Reporting-MTA"] );
        $this->assertEquals( "5.0.0 (permanent failure)", $parts[1]->recipients[0]["Status"] );
        $this->assertEquals( "delayed", $parts[1]->recipients[1]["Action"] );
        $this->assertEquals( "smtp; 550 user unknown", $parts[1]->recipients[2]["Diagnostic-Code"] );
    }

    public function testMultipartReportParts()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/multipart-report-multiple-deliveries' );
        $mail = $parser->parseMail( $set );
        $report = $mail[0]->body;
        $this->assertEquals( "ezcMailMultipartReport", get_class( $report ) );
        $this->assertEquals( 62, strpos( $report->getReadablePart()->text, "arathib@vnet.ibm.com" ) );
        $delivery = $report->getMachinePart();
        $this->assertEquals( "dns; cs.utk.edu", $delivery->message["Reporting-MTA"] );
        $this->assertEquals( "rfc822;arathib@vnet.ibm.com", $delivery->recipients[0]["Final-Recipient"] );
        $this->assertEquals( null, $delivery->recipients[0]["no such header"] );
        $original = $report->getOriginalPart();
        $this->assertEquals( "[original message goes here]", trim( $original->mail->body->text ) );
    }

    public function testMessageSize()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-html-text-and-attachment' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 7646, $mail->size );
        $expected = array( 93, 115, 2313, 822 );
        $parts = $mail->fetchParts();
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertequals( $expected[$i], $parts[$i]->size );
        }
    }

    public function testParserOptionsExtendedMail()
    {
        $parser = new ezcMailParser( array( 'mailClass' => 'ExtendedMail' ) );
        $set = new SingleFileSet( 'various/test-html-text-and-attachment' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 'ExtendedMail', get_class( $mail ) );
    }

    public function testParserConstructorOptions()
    {
        $options = new ezcMailParserOptions();
        $options->mailClass = 'ExtendedMail';

        $parser = new ezcMailParser( $options );
        $set = new SingleFileSet( 'various/test-html-text-and-attachment' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 'ExtendedMail', get_class( $mail ) );

        $options = new stdClass();
        try
        {
            $parser = new ezcMailParser( $options );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'O:8:\"stdClass\":0:{}' that you were trying to assign to setting 'options' is invalid. Allowed values are: ezcMailParserOptions|array.", $e->getMessage() );
        }
    }

    public function testParserProperties()
    {
        $parser = new ezcMailParser();
        $this->assertEquals( true, isset( $parser->options ) );
        $this->assertEquals( false, isset( $parser->no_such_property ) );

        $options = $parser->options;
        $parser->options = new ezcMailParserOptions();
        $this->assertEquals( $options, $parser->options );

        try
        {
            $parser->options = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'xxx' that you were trying to assign to setting 'options' is invalid. Allowed values are: instanceof ezcMailParserOptions.", $e->getMessage() );
        }

        try
        {
            $parser->no_such_property = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }

        try
        {
            $value = $parser->no_such_property;
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }
    }

    public function testDefaultDispositionHeaderBug()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/default-disposition-header' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->body->getRelatedParts();
        $filePart = $parts[0];
        $this->assertEquals( null, $filePart->contentDisposition );
    }

    public function testDefaultUnrecognizedMainTypeParserBugWithFileInfo()
    {
        if ( ezcBaseFeatures::hasExtensionSupport( 'fileinfo' ) )
        {
            $parser = new ezcMailParser();
            $set = new SingleFileSet( 'various/test-unrecognized-mime' );
            $mail = $parser->parseMail( $set );
            $mail = $mail[0];
            $parts = $mail->body->getParts();
            $this->assertEquals( 'ezcMailFile', get_class( $parts[1] ) );
            $this->assertEquals( 'text', $parts[1]->contentType );
            $this->assertEquals( 'unknown', $parts[1]->mimeType );
            $this->assertEquals( 'unknown/unknown; name="unknown.dat"', $parts[1]->getHeader( "Content-Type" ) );
        }
        else
        {
            $this->markTestSkipped( "This test only runs if fileinfo is supported" );
        }
    }

    public function testDefaultUnrecognizedMainTypeParserBugWithoutFileInfo()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'fileinfo' ) )
        {
            $parser = new ezcMailParser();
            $set = new SingleFileSet( 'various/test-unrecognized-mime' );
            $mail = $parser->parseMail( $set );
            $mail = $mail[0];
            $parts = $mail->body->getParts();
            $this->assertEquals( 'ezcMailFile', get_class( $parts[1] ) );
            $this->assertEquals( 'application', $parts[1]->contentType );
            $this->assertEquals( 'unknown', $parts[1]->mimeType );
            $this->assertEquals( 'unknown/unknown; name="unknown.dat"', $parts[1]->getHeader( "Content-Type" ) );
        }
        else
        {
            $this->markTestSkipped( "This test only runs if fileinfo is not supported" );
        }
    }

    public function testDefaultUnrecognizedMessageSubTypeWithFileInfo()
    {
        if ( ezcBaseFeatures::hasExtensionSupport( 'fileinfo' ) )
        {
            $parser = new ezcMailParser();
            $set = new SingleFileSet( 'various/test-unrecognized-subtype' );
            $mail = $parser->parseMail( $set );
            $mail = $mail[0];
            $parts = $mail->body->getParts();
            $this->assertEquals( 'ezcMailFile', get_class( $parts[1] ) );
            $this->assertEquals( 'text', $parts[1]->contentType );
            $this->assertEquals( 'unrecognized', $parts[1]->mimeType );
            $this->assertEquals( 'message/unrecognized; name="unknown.dat"', $parts[1]->getHeader( "Content-Type" ) );
        }
        else
        {
            $this->markTestSkipped( "This test only runs if fileinfo is supported" );
        }
    }

    public function testDefaultUnrecognizedMessageSubTypeWithoutFileInfo()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'fileinfo' ) )
        {
            $parser = new ezcMailParser();
            $set = new SingleFileSet( 'various/test-unrecognized-subtype' );
            $mail = $parser->parseMail( $set );
            $mail = $mail[0];
            $parts = $mail->body->getParts();
            $this->assertEquals( 'ezcMailFile', get_class( $parts[1] ) );
            $this->assertEquals( 'application', $parts[1]->contentType );
            $this->assertEquals( 'unrecognized', $parts[1]->mimeType );
            $this->assertEquals( 'message/unrecognized; name="unknown.dat"', $parts[1]->getHeader( "Content-Type" ) );
        }
        else
        {
            $this->markTestSkipped( "This test only runs if fileinfo is not supported" );
        }
    }

    public function testMultipartRelated()
    {
        $parser = new ezcMailParser();
        $fh = fopen( dirname( __FILE__ ) . '/data/various/test-broken-multipart-related', 'r' );
        $src = '';
        do
        {
            $src .= fgets( $fh );
        } while ( strstr( $src, 'X-UID' ) === false );
        fclose( $fh );
        $set = new ezcMailVariableSet( $src );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 1194, $mail->size );
        $this->assertEquals( 1193, strlen( $src ) );
    }

    public function testIconvCharsetConverterIconv1()
    {
        ezcMailCharsetConverter::setConvertMethod( array( 'myConverter', 'convertToUTF8Iconv' ) );
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-broken-iconv-1' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 63, strlen( $mail->body->text ) );
        ezcMailCharsetConverter::setConvertMethod( array( 'ezcMailCharsetConverter', 'convertToUTF8Iconv' ) );
    }

    public function testIconvCharsetConverterIconv2()
    {
        ezcMailCharsetConverter::setConvertMethod( array( 'myConverter', 'convertToUTF8Iconv' ) );
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-broken-iconv-2' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 38, strlen( $mail->body->text ) );
        ezcMailCharsetConverter::setConvertMethod( array( 'ezcMailCharsetConverter', 'convertToUTF8Iconv' ) );
    }

    public function testIconvCharsetConverterIconvIgnore1()
    {
        ezcMailCharsetConverter::setConvertMethod( array( 'myConverter', 'convertToUTF8IconvIgnore' ) );
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-broken-iconv-1' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 450, strlen( $mail->body->text ) );
        ezcMailCharsetConverter::setConvertMethod( array( 'ezcMailCharsetConverter', 'convertToUTF8Iconv' ) );
    }

    public function testIconvCharsetConverterIconvIgnore2()
    {
        ezcMailCharsetConverter::setConvertMethod( array( 'myConverter', 'convertToUTF8IconvIgnore' ) );
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-broken-iconv-2' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 97, strlen( $mail->body->text ) );
        ezcMailCharsetConverter::setConvertMethod( array( 'ezcMailCharsetConverter', 'convertToUTF8Iconv' ) );
    }
    

    public function testIconvCharsetConverterIconvTranslit1()
    {
        ezcMailCharsetConverter::setConvertMethod( array( 'myConverter', 'convertToUTF8IconvTranslit' ) );
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-broken-iconv-1' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 63, strlen( $mail->body->text ) );
        ezcMailCharsetConverter::setConvertMethod( array( 'ezcMailCharsetConverter', 'convertToUTF8Iconv' ) );
    }

    public function testIconvCharsetConverterIconvTranslit2()
    {
        ezcMailCharsetConverter::setConvertMethod( array( 'myConverter', 'convertToUTF8IconvTranslit' ) );
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-broken-iconv-2' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 38, strlen( $mail->body->text ) );
        ezcMailCharsetConverter::setConvertMethod( array( 'ezcMailCharsetConverter', 'convertToUTF8Iconv' ) );
    }

    public function testMbstringCharsetConverter1()
    {
        if ( ezcBaseFeatures::hasExtensionSupport( 'mbstring' ) )
        {
            ezcMailCharsetConverter::setConvertMethod( array( 'myConverter', 'convertToUTF8Mbstring' ) );
            $parser = new ezcMailParser();
            $set = new SingleFileSet( 'various/test-broken-iconv-1' );
            $mail = $parser->parseMail( $set );
            $mail = $mail[0];
            $this->assertEquals( 468, strlen( $mail->body->text ) );
            ezcMailCharsetConverter::setConvertMethod( array( 'ezcMailCharsetConverter', 'convertToUTF8Iconv' ) );
        }
        else
        {
            $this->markTestSkipped( "This test doesn't work without the mbstring extension. PHP must be compiled with --enable-mbstring." );
        }

    }

    public function testMbstringCharsetConverter2()
    {
        if ( ezcBaseFeatures::hasExtensionSupport( 'mbstring' ) )
        {
            ezcMailCharsetConverter::setConvertMethod( array( 'myConverter', 'convertToUTF8Mbstring' ) );
            $parser = new ezcMailParser();
            $set = new SingleFileSet( 'various/test-broken-iconv-2' );
            $mail = $parser->parseMail( $set );
            $mail = $mail[0];
            $this->assertEquals( 99, strlen( $mail->body->text ) );
            ezcMailCharsetConverter::setConvertMethod( array( 'ezcMailCharsetConverter', 'convertToUTF8Iconv' ) );
        }
        else
        {
            $this->markTestSkipped( "This test doesn't work without the mbstring extension. PHP must be compiled with --enable-mbstring." );
        }
    }

    public function testShutdownHandler()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'gmail/mail_with_attachment.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );

        // call the registered shutdown function which deletes the temporary files
        ezcMailParserShutdownHandler::shutdownCallback();

        // try calling a second time, to account for the case of the temp dir missing
        ezcMailParserShutdownHandler::shutdownCallback();
    }

    public function testHeaderNoSpace()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-header-no-space' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertNotNull( $mail->from );
        $this->assertEquals( '5551112222@messaging.sprintpcs.com', $mail->from->email );
    }

    public function testHeaderWithTab()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-header-with-tab' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertNotNull( $mail->from );
        $this->assertEquals( '5551112222@messaging.sprintpcs.com', $mail->from->email );
    }

    public function testAttachmentWithSlash()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/attachment_with_slash.mail' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $parts = $mail->body->getParts();
        $this->assertEquals( 'cam_data_photo067.jpg', basename( $parts[1]->fileName ) );
    }

    /**
     * Test for bug #12844.
     */
    public function testTempDirWindows()
    {
        if ( ezcBaseFeatures::os() !== 'Windows' )
        {
            self::markTestSkipped( 'Test is for Windows only' );
        }

        ezcMailParser::setTmpDir( null );
        $dir = getenv( "TEMP" );
        if ( substr( $dir, strlen( $dir ) - 1 ) !== DIRECTORY_SEPARATOR )
        {
            $dir = $dir . DIRECTORY_SEPARATOR;
        }
        $this->assertEquals( $dir, ezcMailParser::getTmpDir() );
    }

    /**
     * Test for bug #12844.
     */
    public function testTempDirNonWindows()
    {
        if ( ezcBaseFeatures::os() === 'Windows' )
        {
            self::markTestSkipped( 'Test is for non-Windows only' );
        }

        ezcMailParser::setTmpDir( null );
        $this->assertEquals( '/tmp/', ezcMailParser::getTmpDir() );
    }

    /**
     * Test for issue #13038: Error parsing non us-ascii attachment files names.
     */
    public function testUtf8InFileName()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-utf8-in-filename' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $parts = $mail->body->getParts();

        // check the body
        $this->assertEquals( "CV Robert Dom" . chr( 0xE9 ) . "nie.doc", $parts[1]->contentDisposition->fileName );
        $this->assertEquals( "CV Robert Doménie.doc", $parts[1]->contentDisposition->displayFileName );
    }

    /**
     * Test for issue #13038: Error parsing non us-ascii attachment files names.
     */
    public function testUtf8InFileName2()
    {
        $parser = new ezcMailParser();
        $messages = array(
            array( "content-disposition: attachment;
                    filename*=ISO-8859-1''CV%20Robert%20Dom%E9nie.doc",
                   "CV Robert Doménie.doc" ),

            array( 'Content-disposition: attachment;
                    filename="=?iso-8859-1?Q?Val=E9rie_TEST_CV=2Epdf?="',
                   "Valérie TEST CV.pdf" ),

            array( 'Content-Disposition: attachment;
                    filename="=?iso-8859-1?q?Lettre=20de=20motivation=20directeur=20de=20client=E8le.doc?="',
                   "Lettre de motivation directeur de clientèle.doc" ),

            // broken header, not tested
            /*
            array( 'Content-Disposition: attachment;
                    filename="=?iso-8859-1?q?Lettre=20de=20motivation=20directeur=20de=20client=E8le.do?=
                    c?="',
                   "Lettre de motivation directeur de clientèle.doc" ),
            */

            array( 'Content-Disposition: attachment;
                    filename="=?ISO-8859-1?Q?Copie_de_im=E0ge=5Faccentu=E9.jpg?="',
                    'Copie de imàge_accentué.jpg' ),

            // not supported yet
            /*
            array( "Content-Type: application/x-stuff;
                    title*1*=us-ascii'en'This%20is%20even%20more%20
                    title*2*=%2A%2A%2Afun%2A%2A%2A%20
                    title*3=\"isn't it!",
                    "CV" ),
            */
        );

        foreach ( $messages as $msg )
        {
            $set = new ezcMailVariableSet( $msg[0] );
            $mail = $parser->parseMail( $set );
            $mail = $mail[0];

            // check the body
            $this->assertEquals( $msg[1], $mail->contentDisposition->displayFileName );
        }
    }

    /**
     * Test for issue #13329: ezcMail fetchParts() generates an error when parsing a mail with an empty body
     */
    public function testFetchPartsEmptyBody()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-fetch-parts-empty-body' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $parts = $mail->fetchParts();
        $this->assertEquals( array(), $mail->fetchParts() );
    }

    /**
     * Test for issue #13553: Invalid mime subject header containing iso-8859-1 characters
     */
    public function testMimeSubjectBroken()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/test-mime-subject-broken' );
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
        $mail = $mail[0];
        $this->assertEquals( 'Un Fax a été émis', $mail->getHeader( 'Subject' ) );
        $this->assertEquals( 'Un Fax a t mis', $mail->subject );
    }

    /**
     * Test for issue #13539: Add new mail parser option fileClass.
     */
    public function testParserCustomFileClass()
    {
        $parser = new ezcMailParser();
        $parser->options->fileClass = 'myCustomFileClass';

        // to catch also the case with a custom mail class (it doesn't influence the test)
        $parser->options->mailClass = 'ExtendedMail'; 

        $set = new SingleFileSet( 'various/test-html-text-and-attachment' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $parts = $mail->fetchParts( null, false );
        $expected = array( 'ezcMailText',
                           'ezcMailText',
                           'myCustomFileClass',
                           'myCustomFileClass'
                           );
        $this->assertEquals( 4, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    /**
     * Test for issue #14257: Problem accessing multiple headers with same headername.
     */
    public function testParserMultipleReceivedHeaders()
    {
        $parser = new ezcMailParser();

        $set = new SingleFileSet( 'various/multiple-received-headers' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];

        // get the value of the header Received (the first value as it appears)
        // (this is the default behaviour)
        $received = $mail->getHeader( 'Received' );
        $expected = "from punisher.example.com (punisher.example.com [66.33.206.109]) by fractured.example.com (Postfix) with ESMTP id B84ED80EBE for <helpdesk@example.org>; Mon, 17 Jul 2006 12:35:07 -0700 (PDT)";
        $this->assertEquals( $expected, $received );

        // get all values of the header Received as an array
        $received = $mail->getHeader( 'Received', true );
        $expected = array(
            "from punisher.example.com (punisher.example.com [66.33.206.109]) by fractured.example.com (Postfix) with ESMTP id B84ED80EBE for <helpdesk@example.org>; Mon, 17 Jul 2006 12:35:07 -0700 (PDT)",
            "from localhost (localhost [127.0.0.1]) by punisher.example.com (Postfix) with ESMTP id 67FEC67392 for <helpdesk@example.org>; Mon, 17 Jul 2006 12:35:07 -0700 (PDT)",
            "from punisher.example.com ([127.0.0.1]) by localhost (punisher [127.0.0.1]) (amavisd-new, port 10024) with ESMTP id 18012-11 for <helpdesk@example.org>; Mon, 17 Jul 2006 12:35:07 -0700 (PDT)",
            "from www.example.com (unknown [216.198.224.130]) by punisher.example.com (Postfix) with ESMTP id 0449E67401 for <helpdesk@example.org>; Mon, 17 Jul 2006 12:35:06 -0700 (PDT)",
            "from localhost (localhost) by www.example.com (8.13.4/8.13.4) id k6HJpREA009057; Mon, 17 Jul 2006 14:51:27 -0500"
            );
        $this->assertEquals( $expected, $received );
    }

    /**
     * Test for issue #14794: Add an option to parse text attachments as file part instead of text part
     */
    public function testParseBodyAsFile()
    {
        $parser = new ezcMailParser();
        $parser->options->parseTextAttachmentsAsFiles = true;
        $set = new SingleFileSet( 'kmail/simple_mail_with_text_subject_and_body.mail' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];

        $parts = $mail->fetchParts();
        $expected = array( 'ezcMailFile',
                           );

        $this->assertEquals( 1, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    /**
     * Test for issue #14794: Add an option to parse text attachments as file part instead of text part
     */
    public function testParseBodyAsFileDefaultAfterSetting()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'kmail/simple_mail_with_text_subject_and_body.mail' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];

        $parts = $mail->fetchParts();
        $expected = array( 'ezcMailText',
                           );

        $this->assertEquals( 1, count( $parts ) );
        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expected[$i], get_class( $parts[$i] ) );
        }
    }

    /**
     * Test for issue #15341: ezcMailFileParser class function appendStreamFilters not working properly for quoted-printable
     */
    public function testParseQuotedPrintableMac()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/quoted-printable-mac' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];

        $parts = $mail->fetchParts();
        
        // $parts[2] is an ezcMailFile object
        $cd = $parts[2]->contentDisposition;
        $body = trim( file_get_contents( $parts[2]->fileName ) );

        $this->assertEquals( "PART#,DESCRIPTION,LIST PRICE,NET PRICE\r1234,LIGHTSABER,89.99,109.99", $body );
    }

    /**
     * Test for issue 15456: Problems with parsing emails that have "charset = " instead of "charset="
     */
    public function testCharsetHeader()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSet( 'various/mail-with-broken-charset-header' );
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];

        $parts = $mail->fetchParts();
        $this->assertEquals( "wir können Ihnen mitteilen, dass einer Ihrer\n", $parts[0]->text );
    }
}
?>

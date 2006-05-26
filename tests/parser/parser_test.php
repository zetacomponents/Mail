<?php
declare(encoding="latin1");
/**
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

class SingleFileSet implements ezcMailParserSet
{
    private $fp = null;

    public function __construct( $file )
    {
        $fp = fopen( dirname( __FILE__ ). '/data/' . $file, 'r' );
        if ( $fp == false )
        {
            throw new Exception( "Could not open file $file for testing." );
        }
        $this->fp = $fp;

//        while (!feof($fp)) {
//        $buffer = fgets($fp, 4096);
//        echo $buffer;
//    }
    }

    public function getNextLine()
    {
        if ( feof( $this->fp ) )
        {
            if ( $this->fp != null )
            {
                fclose( $this->fp );
                $this->fp = null;
            }
            return null;
        }
        $next =  fgets( $this->fp );
        if ( $next == "" && feof( $this->fp ) ) // eat last linebreak
        {
            return null;
        }
        return $next;
    }

    public function nextMail()
    {
        return false;
    }
}


/**
 * @package Mail
 * @subpackage Tests
 */
// TODO: check cc && bcc
class ezcMailParserTest extends ezcTestCase
{
    public static function suite()
    {
         return new ezcTestSuite( "ezcMailParserTest" );
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
//        var_dump( $mail->subject );
//        $this->assertEquals( 'Simple mail with text subject and body', $mail->subject );
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
        $this->assertEquals( new ezcMailAddress( 'oms@ez.no', 'Ole Marius Smestad', 'utf-8' ), $mail->from );
        $this->assertEquals( array( new ezcMailAddress( 'fh@ez.no', '', 'utf-8' ) ), $mail->to );
        $this->assertEquals( array(), $mail->cc );
        $this->assertEquals( array(), $mail->bcc );
        $this->assertEquals( 'HTML mail with inline image Mail.app', $mail->subject );
        $this->assertEquals( true, $mail->body instanceof ezcMailMultipartAlternative );
        $parts = $mail->body->getParts();
        $this->assertEquals( true, $parts[0] instanceof ezcMailText );
        $this->assertEquals( true, $parts[1] instanceof ezcMailMultipartRelated );

        // check the text
        $this->assertEquals( 'utf-8', $parts[0]->charset );
        $this->assertEquals( 'plain', $parts[0]->subType );

        // check the multipart related
        $mainPart = $parts[1]->getMainPart();
        $this->assertEquals( true, $mainPart instanceof ezcMailText );
        $this->assertEquals( 'iso-8859-1', $mainPart->originalCharset );
        $this->assertEquals( 'utf-8', $mainPart->charset );
        $this->assertEquals( 'html', $mainPart->subType );

        $this->assertEquals( 1, count( $parts[1]->getRelatedParts() ) );
        // chech the multipart related file
        $filePart = $parts[1]->getRelatedParts();
        $filePart = $filePart[0]; // only one
        $this->assertEquals( true, $filePart instanceof ezcMailFile );
        $this->assertEquals( 'tur.jpg', strstr( $filePart->fileName, 'tur.jpg' ) );
        $this->assertEquals( ezcMailFile::CONTENT_TYPE_IMAGE, $filePart->contentType );
        $this->assertEquals( ezcMailFile::DISPLAY_INLINE, $filePart->dispositionType );
        $this->assertEquals( 'jpeg', $filePart->mimeType );

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

        //subject string should be the same as in email (with line break)
        $subject = "Re: =?koi8-r?b?7c7FIM7BxM8g1crUySDOwSDewdMg0yAxMi4wMCDQzw==?=" .
            "\t=?koi8-r?b?IM/Sx8HOydrBw8nPzs7ZzQ==?= =?koi8-r?b?INfP0NLP08HNLi4u?=";

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
}
?>

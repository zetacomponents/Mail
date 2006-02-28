<?php
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
        if( $fp == false )
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
        if( feof( $this->fp ) )
        {
            if( $this->fp != null )
            {
                fclose( $this->fp );
                $this->fp = null;
            }
            return null;
        }
        $next =  rtrim( fgets( $this->fp ), "\r\n" );
        if( $next == "" && feof( $this->fp ) ) // eat last linebreak
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
        $this->assertEquals( "us-ascii", $mail->body->charset );
        $this->assertEquals( 'plain', $mail->body->subType );
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
        $this->assertEquals( "This is the body: זרו\n", $mail->body->text );
        $this->assertEquals( "iso-8859-1", $mail->body->charset );
        $this->assertEquals( 'plain', $mail->body->subType );
    }
}

?>

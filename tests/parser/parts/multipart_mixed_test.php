<?php
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

// TODO.. remove this
class SingleFileSetMP implements ezcMailParserSet
{
    private $fp = null;

    public function __construct( $file )
    {
        $fp = fopen( dirname( __FILE__ ).'/..' .'/data/' . $file, 'r' );
        if ( $fp == false )
        {
            throw new Exception( "Could not open file '{$file}' for testing." );
        }
        $this->fp = $fp;

//        while (!feof($fp)) {
//        $buffer = fgets($fp, 4096);
//        echo $buffer;
//    }
    }

    public function hasData()
    {
        return !feof( $this->fp );
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
        $next =  rtrim( fgets( $this->fp ), "\r\n" );
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
 * These tests just test the overall functionality of the multipart functionality.
 *
 * @package Mail
 * @subpackage Tests
 */
class ezcMailMultipartMixedParserTest extends ezcTestCase
{
    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailMultipartMixedParserTest" );
    }

    public function testKmail1()
    {
        $parser = new ezcMailParser();
        $set = new SingleFileSetMP( 'kmail/mail_with_attachment.mail' );
        $mail = $parser->parseMail( $set );
    }
}

?>

<?php
/**
 * Classes used in Mail/tests/parser/parser_test.php
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 */
class SingleFileSet implements ezcMailParserSet
{
    private $fp = null;

    public function __construct( $file )
    {
        $fp = fopen( dirname( __FILE__ ). '../../' . $file, 'r' );
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

class ExtendedMail extends ezcMail
{

}

class myConverter
{
    public static function convertToUTF8Iconv( $text, $originalCharset )
    {
        if ( $originalCharset === 'unknown-8bit' || $originalCharset === 'x-user-defined' )
        {
            $originalCharset = "latin1";
        }
        // '@' is to avoid notices on broken input - see issue #8369
        return @iconv( $originalCharset, 'utf-8', $text );
    }

    public static function convertToUTF8IconvIgnore( $text, $originalCharset )
    {
        if ( $originalCharset === 'unknown-8bit' || $originalCharset === 'x-user-defined' )
        {
            $originalCharset = "latin1";
        }
        // '@' is to avoid notices on broken input - see issue #8369
        return @iconv( $originalCharset, 'utf-8//IGNORE', $text );
    }

    public static function convertToUTF8IconvTranslit( $text, $originalCharset )
    {
        if ( $originalCharset === 'unknown-8bit' || $originalCharset === 'x-user-defined' )
        {
            $originalCharset = "latin1";
        }
        // '@' is to avoid notices on broken input - see issue #8369
        return @iconv( $originalCharset, 'utf-8//TRANSLIT', $text );
    }

    public static function convertToUTF8Mbstring( $text, $originalCharset )
    {
        return mb_convert_encoding( $text, "UTF-8", $originalCharset );
    }
}
class myCustomFileClass extends ezcMailFile
{
}
?>

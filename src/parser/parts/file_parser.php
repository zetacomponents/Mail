<?php
/**
 * File containing the ezcMailFileParser class
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
 * @package Mail
 * @version //autogen//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Parses application/image/video and audio parts.
 *
 * @package Mail
 * @version //autogen//
 * @access private
 */
class ezcMailFileParser extends ezcMailPartParser
{
    /**
     * Default class to handle file attachments when parsing mails
     * is ezcMailFile.
     *
     * Change this to your own file class with:
     * <code>
     * $parser = new ezcMailParser();
     * $parser->options->fileClass = 'myCustomFileClass';
     * // call $parser->parseMail( $set );
     * </code>
     *
     * where myCustomFileClass extends ezcMailFile.
     *
     * @var string
     */
    public static $fileClass = 'ezcMailFile';

    /**
     * Holds the headers for this part.
     *
     * @var ezcMailHeadersHolder
     */
    private $headers = null;

    /**
     * Holds the maintype of the parsed part.
     *
     * @var string
     */
    private $mainType = null;

    /**
     * Holds the subtype of the parsed part.
     *
     * @var string
     */
    private $subType = null;

    /**
     * Holds the filepointer to the attachment.
     *
     * @var resource
     */
    private $fp = null;

    /**
     * Holds the full path and filename of the file to save to.
     *
     * @var string
     */
    private $fileName = null;

    /**
     * Static counter used to generate unique directory names.
     *
     * @var int
     */
    private static $counter = 1;

    /**
     * Constructs a new ezcMailFileParser with maintype $mainType subtype $subType
     * and headers $headers.
     *
     * @throws ezcBaseFileNotFoundException
     *         if the file attachment file could not be openened.
     * @param string $mainType
     * @param string $subType
     * @param ezcMailHeadersHolder $headers
     */
    public function __construct( $mainType, $subType, ezcMailHeadersHolder $headers )
    {
        $this->mainType = $mainType;
        $this->subType = $subType;
        $this->headers = $headers;

        // figure out the base filename
        // search Content-Disposition first as specified by RFC 2183
        $matches = array();
        if ( preg_match( '/\s*filename=\s?"?([^;"]*);?/i',
                        $this->headers['Content-Disposition'], $matches ) )
        {
            $fileName = trim( $matches[1], '"' );
        }
        // fallback to the name parameter in Content-Type as specified by RFC 2046 4.5.1
        else if ( preg_match( '/\s*name=\s?"?([^;"]*);?/i',
                             $this->headers['Content-Type'], $matches ) )
        {
            $fileName = trim( $matches[1], '"' );
        }

        // default
        if (empty($fileName))
        {
            $fileName = "filename";
        }

        // hash the file name to ensure uniqueness and safety saving on file system
        $fileName = md5( $fileName );

        $this->fp = $this->openFile( $fileName ); // propagate exception
    }

    /**
     * Returns the filepointer of the opened file $fileName in a unique directory..
     *
     * This method will create a new unique folder in the temporary directory specified in ezcMailParser.
     * The fileName property of this class will be set to the location of the new file.
     *
     * @throws ezcBaseFileNotFoundException
     *         if the file could not be opened.
     * @param string $fileName
     * @return resource
     */
    private function openFile( $fileName )
    {
        // The filename is now relative, we need to extend it with the absolute path.
        // To provide uniqueness we put the file in a directory based on processID and rand.
        $dirName = ezcMailParser::getTmpDir() . getmypid() . '-' . self::$counter++ . '/';
        if ( !is_dir( $dirName ) )
        {
            mkdir( $dirName, 0700 );
        }

        // remove the directory and the file when PHP shuts down
        ezcMailParserShutdownHandler::registerForRemoval( $dirName );
        $this->fileName = $dirName . $fileName;

        $fp = fopen( $this->fileName, 'w' );
        if ( $this->fp === false )
        {
            throw new ezcBaseFileNotFoundException( $this->fileName );
        }
        return $fp;
    }

    /**
     * Destructs the parser object.
     *
     * Closes and removes any open file.
     */
    public function __destruct()
    {
        // finish() was not called. The mail is completely broken.
        // we will clean up the mess
        if ( $this->fp !== null )
        {
            fclose( $this->fp );
            $this->fp = null;
            if ( $this->fileName !== null && file_exists( $this->fileName ) )
            {
                unlink( $this->fileName );
            }
        }
    }

    /**
     * Parse the body of a message line by line.
     *
     * This method is called by the parent part on a push basis. When there
     * are no more lines the parent part will call finish() to retrieve the
     * mailPart.
     *
     * The file will be decoded and saved to the given temporary directory within
     * a directory based on the process ID and the time.
     *
     * @param string $line
     */
    public function parseBody( $line )
    {
        if ( $line !== '' )
        {
            try
            {
                switch ( strtolower( $this->headers['Content-Transfer-Encoding'] ) )
                {
                    case 'base64':
                        $line = base64_decode($line);
                        break;
                    case 'quoted-printable':
                        $line = quoted_printable_decode($line);
                        break;
                }

                fwrite($this->fp, $line );
            }
            catch (\Exception $e)
            {
                // do nothing for now
            }
        }
    }

    /**
     * Return the result of the parsed file part.
     *
     * This method is called automatically by the parent part.
     *
     * @return ezcMailFile
     */
    public function finish()
    {
        fclose( $this->fp );
        $this->fp = null;


        // FIXME: DIRTY PGP HACK
        // When we have PGP support these lines should be removed. They are here now to hide
        // PGP parts since they will show up as file attachments if not.
        if ( $this->mainType == "application" &&
            ( $this->subType == 'pgp-signature'
              || $this->subType == 'pgp-keys'
              || $this->subType == 'pgp-encrypted' ) )
        {
            return null;
        }
        // END DIRTY PGP HACK

        $filePart = new self::$fileClass( $this->fileName );

        // set content type
        $filePart->setHeaders( $this->headers->getCaseSensitiveArray() );
        ezcMailPartParser::parsePartHeaders( $this->headers, $filePart );
        switch ( strtolower( $this->mainType ) )
        {
            case 'image':
                $filePart->contentType = ezcMailFile::CONTENT_TYPE_IMAGE;
                break;
            case 'audio':
                $filePart->contentType = ezcMailFile::CONTENT_TYPE_AUDIO;
                break;
            case 'video':
                $filePart->contentType = ezcMailFile::CONTENT_TYPE_VIDEO;
                break;
            case 'application':
                $filePart->contentType = ezcMailFile::CONTENT_TYPE_APPLICATION;
                break;
        }

        // set mime type
        $filePart->mimeType = $this->subType;

        // set inline disposition mode if set.
        $matches = array();
        if ( preg_match( '/^\s*inline;?/i',
                        $this->headers['Content-Disposition'], $matches ) )
        {
            $filePart->dispositionType = ezcMailFile::DISPLAY_INLINE;
        }
        if ( preg_match( '/^\s*attachment;?/i',
                        $this->headers['Content-Disposition'], $matches ) )
        {
            $filePart->dispositionType = ezcMailFile::DISPLAY_ATTACHMENT;
        }
        $filePart->size = filesize( $this->fileName );
        return $filePart;
    }
}
?>

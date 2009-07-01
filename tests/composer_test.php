<?php
/**
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailComposerTest extends ezcTestCase
{
    private $mail;

	protected function setUp()
	{
        $this->mail = new ezcMailComposer();
	}

    /**
     * Parses $mailSource to a mail object and checks that the mail contains
     * the $expectedParts mail parts in order.
     *
     * Example (which checks if $mail contains a text part and an attachment part):
     * <code>
     * $this->parseAndCheck( $mail->generate(), array( 'ezcMailText', 'ezcMailFile' ) );
     * </code>
     */
    protected function parseAndCheckParts( $mailSource, array $expectedParts )
    {
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( new ezcMailVariableSet( $mailSource ) );
        $parts = $mail[0]->fetchParts();

        $this->assertEquals( count( $expectedParts ), count( $parts ) );

        for ( $i = 0; $i < count( $parts ); $i++ )
        {
            $this->assertEquals( $expectedParts[$i], get_class( $parts[$i] ) );
        }
    }

    /**
     * Test the properties of the Composer
     */
    public function testProperties()
    {
        $this->assertSetPropertyFails( $this->mail, "this_does_not_exist", array( 42 ) );
        $this->assertSetProperty( $this->mail, 'plainText',
                                  array( 'Doesn\'t look as if it\'s ever used.' ) );
        $this->assertSetProperty( $this->mail, 'htmlText',
                                  array( "That thing's WATCHING me... Good thing I'm naturally PHOTOGENIC!" ) );
        $this->assertSetProperty( $this->mail, 'charset',
                                  array( "us-ascii" ) );
        $this->assertSetPropertyFails( $this->mail, 'options', array( "wrong value" ) );
        $this->assertSetProperty( $this->mail, 'options', array( new ezcMailComposerOptions() ) );
        $this->assertEquals( true, isset( $this->mail->options ) );
    }

    /**
     * Test that inherited properties from ezcMail work.
     */
    public function testInheritedProperties()
    {
        $this->assertSetProperty( $this->mail, "to", array( array( new ezcMailAddress( 'fh@ez.no' ) ) ) );
    }

    /**
     * Tests adding a valid attachment.
     */
    public function testAddAttachmentValid()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile' ) );
    }

    /**
     * Tests adding a valid attachment and setting content&mime type.
     */
    public function testAddAttachmentValidSetMime()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg", null, "image", "jpeg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile' ) );
    }

    /**
     * Tests adding a valid attachment, but without read permissions.
     */
    public function testAddAttachmentUnreadable()
    {
        $tempDir = $this->createTempDir( 'ezcMailComposerTest' );
        $fileName = $tempDir . "/fly_unreadable.jpg";
        $fileHandle = fopen( $fileName, "wb" );
        fwrite( $fileHandle, "some contents" );
        fclose( $fileHandle );
        chmod( $fileName, 0 );
        try
        {
            $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
            $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
            $this->mail->subject = "Message with invalid files..";
            $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
            $this->mail->addAttachment( realpath( $fileName ) );
            $this->mail->build();
        }
        catch ( ezcBaseFilePermissionException $e )
        {
            $this->removeTempDir();
            return;
        }
        $this->removeTempDir();
        $this->fail( "Adding unreadable attachments did not fail.\n" );
    }

    /**
     * Tests adding an invalid attachment.
     */
    public function testAddAttachmentInValid()
    {
        try
        {
            $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
            $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
            $this->mail->subject = "Message with invalid files..";
            $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
            $this->mail->addAttachment( dirname( __FILE__) . "/does_not_exist.jpg" );
            $this->mail->build();
        }
        catch ( ezcBaseFileNotFoundException $e )
        {
            return;
        }
        $this->fail( "Adding broken attachments did not fail.\n" );
    }

    /**
     * Tests adding an HTML mail with invalid file/images
     */
    public function testAddHtmlInValid()
    {
        try
        {
            $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
            $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
            $this->mail->subject = "HTML with invalid local files..";
            $this->mail->htmlText = "<html>Some text before the simage: <img src=\"file://"
                . dirname( __FILE__  )
                . "/no_such_file.jpg\" /></html>";
            $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
            $this->mail->build();
        }
        catch ( ezcBaseFileNotFoundException $e )
        {
            return;
        }
        $this->fail( "HTML with broken local links did not cause exception.\n" );
    }

    /**
     * Test a complete mail with ascii text only
     */
    public function testMailTextOnly()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Text only.";
        $this->mail->plainText = "Text only. Should not have a multipart body.";
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText' ) );
    }

    /**
     * Test a complete mail with html text only
     */
    public function testMailHtmlOnly()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML only..";
        $this->mail->htmlText = "<html><i><b>HTML only. Should not have a multipart body.</b></i></html>";
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText' ) );
    }

    /**
     * Test a complete mail with one attachment only
     */
    public function testMailOneAttachmentNoText()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "One attachments only.";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailFile' ) );
    }

    /**
     * Test a complete mail with many attachments only
     */
    public function testMailManyAttachmentsNoText()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Many attachments only.";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailFile', 'ezcMailFile' ) );
    }

    /**
     * Test a complete mail with txt and html but no attachments
     */
    public function testMailTextAndHtmlNoAttachments()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Alternative HTML/Text message.";
        $this->mail->plainText = "Plain text message. Your client should show the HTML message if it supports HTML mail.";
        $this->mail->htmlText = "<html><i><b>HTML message. Your client should show this if it supports HTML.</b></i></html>";
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailText' ) );
    }

    /**
     * Test a complete mail with txt and html and set charset for both
     */
    public function testMailTextAndHtmlSetCharset()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Alternative HTML/Text message.";
        $this->mail->plainText = "Plain text message. Your client should show the HTML message if it supports HTML mail.";
        $this->mail->htmlText = "<html><i><b>HTML message. Your client should show this if it supports HTML.</b></i></html>";
        $this->mail->charset = 'iso-8859-1';
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailText' ) );
    }

    /**
     * Test a complete mail with txt, html and attachments
     */
    public function testMailTextHtmlAndAttachments()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Alternative HTML/Text message and attachments.";
        $this->mail->plainText = "Plain text message. Your client should show the HTML message if it supports HTML mail.";
        $this->mail->htmlText = "<html><i><b>HTML message. Your client should show this if it supports HTML.</b></i></html>";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailText', 'ezcMailFile', 'ezcMailFile' ) );
    }

    /**
     * Tests a complete mail with html images and files
     * http://www.apps.ietf.org/msglint.html - validator
     */
    public function testMailHtmlWithImagesAndFiles()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->htmlText = "<html>Some text before the simage: <img src=\"file://"
                                   . dirname( __FILE__  )
                                   . "/parts/data/fly.jpg\" />Here is some text after the image. Here is the <a href=\"file://"
                                   . dirname( __FILE__  )
                                   . "/parts/data/fly.jpg\">file.</a></html>";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile', 'ezcMailFile' ) );
    }

    public function testMailHtmlWithImagesAndFilesOutsideImg()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'as@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->htmlText = "<html>Some text before the image: file://" . dirname( __FILE__  ) . "/parts/data/fly.jpg </html>";
        $this->mail->build();
        $set = new ezcMailVariableSet( $this->mail->generate() );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( "<html>Some text before the image: file://" . dirname( __FILE__ ) . "/parts/data/fly.jpg </html>", $mail->body->text );
    }

    /**
     * Tests a complete mail with html images and files
     * http://www.apps.ietf.org/msglint.html - validator
     */
    public function testMailHtmlWithImagesNoExtensionWithFileInfo()
    {
        if ( ezcBaseFeatures::hasExtensionSupport( 'fileinfo' ) )
        {
            $tempDir = $this->createTempDir( 'ezcMailComposerTest' );
            $fileName = $tempDir . "/fly_no_extension";
            $fileHandle = fopen( $fileName, "wb" );
            fwrite( $fileHandle, "some contents" );
            fclose( $fileHandle );
            $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
            $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
            $this->mail->subject = "HTML message with embeded files and images.";
            $this->mail->htmlText = "<html>Some text before the simage: <img src=\"file://"
                                       . realpath( $fileName ) . " />Here is some text after the image. Here is the <a href=\"file://"
                                       . dirname( __FILE__  )
                                       . "/parts/data/fly.jpg\">file.</a></html>";
            $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
            $this->mail->build();
            $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailText', 'ezcMailFile' ) );

            $this->removeTempDir();
        }
        else
        {
            $this->markTestSkipped( "This test is supposed to run only when the fileinfo extension is available." );
        }
    }

    /**
     * Tests a complete mail with html images and files
     * http://www.apps.ietf.org/msglint.html - validator
     */
    public function testMailHtmlWithImagesNoExtensionWithoutFileInfo()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'fileinfo' ) )
        {
            $tempDir = $this->createTempDir( 'ezcMailComposerTest' );
            $fileName = $tempDir . "/fly_no_extension";
            $fileHandle = fopen( $fileName, "wb" );
            fwrite( $fileHandle, "some contents" );
            fclose( $fileHandle );
            $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
            $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
            $this->mail->subject = "HTML message with embeded files and images.";
            $this->mail->htmlText = "<html>Some text before the simage: <img src=\"file://"
                                       . realpath( $fileName ) . " />Here is some text after the image. Here is the <a href=\"file://"
                                       . dirname( __FILE__  )
                                       . "/parts/data/fly.jpg\">file.</a></html>";
            $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
            $this->mail->build();
            $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile', 'ezcMailFile' ) );

            $this->removeTempDir();
        }
        else
        {
            $this->markTestSkipped( "This test is supposed to run only when the fileinfo extension is not available." );
        }
    }

    /**
     * Tests a mail with unreadable html images.
     */
    public function testMailHtmlWithImagesUnreadable()
    {
        $tempDir = $this->createTempDir( 'ezcMailComposerTest' );
        $fileName = $tempDir . "/fly_unreadable.jpg";
        $fileHandle = fopen( $fileName, "wb" );
        fwrite( $fileHandle, "some contents" );
        fclose( $fileHandle );
        chmod( $fileName, 0 );
        try
        {
            $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
            $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
            $this->mail->subject = "HTML message with embeded unreadable images.";
            $this->mail->htmlText = "<html>Some text before the image: <img src=\"file://"
                                       . realpath( $fileName ). "\" /></html>";
            $this->mail->build();
        }
        catch ( ezcBaseFilePermissionException $e )
        {
            $this->removeTempDir();
            return;
        }
        $this->removeTempDir();
        $this->fail( "Adding unreadable images did not fail.\n" );
    }

    /**
     * Tests adding a valid virtual attachment.
     */
    public function testAddVirtualAttachmentValid()
    {
        $contents = file_get_contents( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
        $this->mail->addAttachment( "fly.jpg", $contents );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile' ) );
    }

    /**
     * Tests adding a valid virtual attachment and setting content&mime type.
     */
    public function testAddVirtualAttachmentValidSetMime()
    {
        $contents = file_get_contents( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
        $this->mail->addAttachment( "fly.jpg", $contents, "image", "jpeg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile' ) );
    }

    /**
     * Test a complete mail with one virtual attachment only
     */
    public function testMailOneVirtualAttachmentNoText()
    {
        $contents = file_get_contents( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "One attachments only.";
        $this->mail->addAttachment( "fly.jpg", $contents );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailFile' ) );
    }

    /**
     * Test a complete mail with many virtual attachments only
     */
    public function testMailManyVirtualAttachmentsNoText()
    {
        $contents = file_get_contents( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Many attachments only.";
        $this->mail->addAttachment( "fly.jpg", $contents );
        $this->mail->addAttachment( "fly.jpg", $contents );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailFile', 'ezcMailFile' ) );
    }

    /**
     * Test a complete mail with txt, html and attachments (virtual and not).
     */
    public function testMailTextHtmlAndVirtualAttachments()
    {
        $contents = file_get_contents( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Alternative HTML/Text message and attachments.";
        $this->mail->plainText = "Plain text message. Your client should show the HTML message if it supports HTML mail.";
        $this->mail->htmlText = "<html><i><b>HTML message. Your client should show this if it supports HTML.</b></i></html>";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg", $contents );
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailText', 'ezcMailFile', 'ezcMailFile' ) );
    }

    /**
     * Tests a complete mail with html images and files
     * http://www.apps.ietf.org/msglint.html - validator
     */
    public function testMailHtmlWithImagesAndVirtualFiles()
    {
        $contents = file_get_contents( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->htmlText = "<html>Some text before the simage: <img src=\"file://"
                                   . dirname( __FILE__  )
                                   . "/parts/data/fly.jpg\" />Here is some text after the image. Here is the <a href=\"file://"
                                   . dirname( __FILE__  )
                                   . "/parts/data/fly.jpg\">file.</a></html>";
        $this->mail->addAttachment( "fly.jpg", $contents );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile', 'ezcMailFile' ) );
    }

    /**
     * Tests adding a valid stream attachment.
     */
    public function testAddStreamAttachmentValid()
    {
        $file = fopen( dirname( __FILE__) . "/parts/data/fly.jpg", "r" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
        $this->mail->addAttachment( "fly.jpg", $file );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile' ) );
    }

    /**
     * Tests adding a valid stream attachment and setting content&mime type.
     */
    public function testAddStreamAttachmentValidSetMime()
    {
        $file = fopen( dirname( __FILE__) . "/parts/data/fly.jpg", "r" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
        $this->mail->addAttachment( "fly.jpg", $file, "image", "jpeg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile' ) );
    }

    /**
     * Test a complete mail with one stream attachment only
     */
    public function testMailOneStreamAttachmentNoText()
    {
        $file = fopen( dirname( __FILE__) . "/parts/data/fly.jpg", "r" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "One attachments only.";
        $this->mail->addAttachment( "fly.jpg", $file );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailFile' ) );
    }

    /**
     * Test a complete mail with many stream attachments only
     */
    public function testMailManyStreamAttachmentsNoText()
    {
        $file = fopen( dirname( __FILE__) . "/parts/data/fly.jpg", "r" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Many attachments only.";
        $this->mail->addAttachment( "fly.jpg", $file );
        $this->mail->addAttachment( "fly.jpg", $file );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailFile', 'ezcMailFile' ) );
    }

    /**
     * Test a complete mail with txt, html and attachments (virtual, stream, file).
     */
    public function testMailTextHtmlAndStreamAttachments()
    {
        $contents = file_get_contents( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $file = fopen( dirname( __FILE__) . "/parts/data/fly.jpg", "r" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Alternative HTML/Text message and attachments.";
        $this->mail->plainText = "Plain text message. Your client should show the HTML message if it supports HTML mail.";
        $this->mail->htmlText = "<html><i><b>HTML message. Your client should show this if it supports HTML.</b></i></html>";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg", $contents );
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg", $file );
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailText', 'ezcMailFile', 'ezcMailFile', 'ezcMailFile' ) );
    }

    /**
     * Tests a complete mail with html images and files
     * http://www.apps.ietf.org/msglint.html - validator
     */
    public function testMailHtmlWithImagesAndStreamFiles()
    {
        $file = fopen( dirname( __FILE__) . "/parts/data/fly.jpg", "r" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->htmlText = "<html>Some text before the simage: <img src=\"file://"
                                   . dirname( __FILE__  )
                                   . "/parts/data/fly.jpg\" />Here is some text after the image. Here is the <a href=\"file://"
                                   . dirname( __FILE__  )
                                   . "/parts/data/fly.jpg\">file.</a></html>";
        $this->mail->addAttachment( "fly.jpg", $file );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile', 'ezcMailFile' ) );
    }

    public function testIsSet()
    {
        $mail = new ezcMailComposer();
        $this->assertEquals( false, isset( $mail->plainText ) );
        $this->assertEquals( false, isset( $mail->htmlText ) );
        $this->assertEquals( true, isset( $mail->charset ) );
        $this->assertEquals( false, isset( $mail->no_such_property ) );
    }

    /**
     * Test for issue #14025: Problem with ezcMailComposer::addAttachment when
     * use the fifth param to change the file name
     */
    public function testContentDispositionCustomAttachmentName()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'john.doe@example.com' );
        $mail->subject = "Subject";
        $mail->addTo( new ezcMailAddress( 'john.doe@example.com' ) );
        $file = new ezcMailFile( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $file->contentDisposition = new ezcMailContentDispositionHeader(
            'attachment',
            'custom_attachment_name.jpg' );
        $mail->body = new ezcMailMultipartMixed(
            new ezcMailText( 'xxx' ),
            $file );
        $msg = $mail->generate();
        $set = new ezcMailVariableSet( $msg );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $parts = $mail[0]->fetchParts();

        // for issue #13038, displayFileName was added to contentDisposition
        $file->contentDisposition->displayFileName = 'custom_attachment_name.jpg';

        $contentType = 'image/jpeg';
        if ( !ezcBaseFeatures::hasExtensionSupport( 'fileinfo' ) )
        {
            $contentType = 'application/octet-stream';
        }
        if ( version_compare( phpversion(), '5.3.0', '>=' ) )
        {
            $contentType .= '; charset=binary';
        }
        $this->assertEquals( $contentType . '; name="custom_attachment_name.jpg"', $parts[1]->getHeader( "Content-Type" ) );
        $this->assertEquals( $file->contentDisposition, $parts[1]->contentDisposition );
    }

    public function testContentDisposition()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'john.doe@example.com' );
        $mail->subject = "яверасфăîţâşåæøåöä";
        $mail->addTo( new ezcMailAddress( 'john.doe@example.com' ) );
        $file = new ezcMailFile( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $file->contentDisposition = new ezcMailContentDispositionHeader(
            'attachment',
            'яверасфăîţâşåæøåöä.jpg',
            null,
            null,
            null,
            null,
            array(),
            'no',
            'utf-8' );
        $mail->body = new ezcMailMultipartMixed(
            new ezcMailText( 'xxx' ),
            $file );
        $msg = $mail->generate();
        $set = new ezcMailVariableSet( $msg );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $parts = $mail[0]->fetchParts();

        // for issue #13038, displayFileName was added to contentDisposition
        $file->contentDisposition->displayFileName = 'яверасфăîţâşåæøåöä.jpg';
        $this->assertEquals( $file->contentDisposition, $parts[1]->contentDisposition );
    }

    public function testContentDispositionSimple()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'john.doe@example.com' );
        $mail->subject = "яверасфăîţâşåæøåöä";
        $mail->addTo( new ezcMailAddress( 'john.doe@example.com' ) );
        $file = new ezcMailFile( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $file->contentDisposition = new ezcMailContentDispositionHeader(
            'attachment',
            'яверасфăîţâşåæøåöä.jpg' );
        $mail->body = new ezcMailMultipartMixed(
            new ezcMailText( 'xxx' ),
            $file );
        $msg = $mail->generate();
        $set = new ezcMailVariableSet( $msg );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $parts = $mail[0]->fetchParts();

        // for issue #13038, displayFileName was added to contentDisposition
        $file->contentDisposition->displayFileName = 'яверасфăîţâşåæøåöä.jpg';
        $this->assertEquals( $file->contentDisposition, $parts[1]->contentDisposition );
    }

    public function testContentDispositionAttach()
    {
        $mail = new ezcMailComposer();
        $mail->from = new ezcMailAddress( 'john.doe@example.com' );
        $mail->subject = "яверасфăîţâşåæøåöä";
        $mail->addTo( new ezcMailAddress( 'john.doe@example.com' ) );
        $contentDisposition = new ezcMailContentDispositionHeader(
            'attachment',
            'яверасфăîţâşåæøåöä.jpg',
            null,
            null,
            null,
            null,
            array(),
            'no',
            'utf-8' );
        $mail->plainText = 'xxx';
        $mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg", null, null, null, $contentDisposition );
        $mail->build();
        $msg = $mail->generate();
        $set = new ezcMailVariableSet( $msg );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $parts = $mail[0]->fetchParts();

        // for issue #13038, displayFileName was added to contentDisposition
        $contentDisposition->displayFileName = 'яверасфăîţâşåæøåöä.jpg';
        $this->assertEquals( $contentDisposition, $parts[1]->contentDisposition );
    }

    public function testContentDispositionSimpleAttach()
    {
        $mail = new ezcMailComposer();
        $mail->from = new ezcMailAddress( 'john.doe@example.com' );
        $mail->subject = "яверасфăîţâşåæøåöä";
        $mail->addTo( new ezcMailAddress( 'john.doe@example.com' ) );
        $contentDisposition = new ezcMailContentDispositionHeader(
            'attachment',
            'яверасфăîţâşåæøåöä.jpg' );
        $mail->plainText = 'xxx';
        $mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg", null, null, null, $contentDisposition );
        $mail->build();
        $msg = $mail->generate();
        $set = new ezcMailVariableSet( $msg );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $parts = $mail[0]->fetchParts();

        // for issue #13038, displayFileName was added to contentDisposition
        $contentDisposition->displayFileName = 'яверасфăîţâşåæøåöä.jpg';
        $this->assertEquals( $contentDisposition, $parts[1]->contentDisposition );
    }

    public function testContentDispositionLongHeader()
    {
        $mail = new ezcMail();
        $mail->from = new ezcMailAddress( 'john.doe@example.com' );
        $mail->subject = "яверасфăîţâşåæøåöä";
        $mail->addTo( new ezcMailAddress( 'john.doe@example.com' ) );
        $file = new ezcMailFile( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $file->contentDisposition = new ezcMailContentDispositionHeader(
            'attachment',
            'яверасфăîţâşåæøåöäabcdefghijklmnopqrstuvwxyz ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz.jpg' );
        $mail->body = new ezcMailMultipartMixed(
            new ezcMailText( 'xxx' ),
            $file );
        $msg = $mail->generate();
        $set = new ezcMailVariableSet( $msg );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $parts = $mail[0]->fetchParts();

        // for issue #13038, displayFileName was added to contentDisposition
        $file->contentDisposition->displayFileName = 'яверасфăîţâşåæøåöäabcdefghijklmnopqrstuvwxyz ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz abcdefghijklmnopqrstuvwxyz.jpg';
        $this->assertEquals( $file->contentDisposition, $parts[1]->contentDisposition );
    }

    public function testGeneratedContentIdBug()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML only..";
        $this->mail->htmlText = "<html><i><b>HTML only. Should not have a multipart body.</b></i><img src=\"file://"
                                   . dirname( __FILE__  )
                                   . "/parts/data/fly.jpg\" /></html>";
        $this->mail->build();

        $parts = $this->mail->body->getRelatedParts();
        $filePart = $parts[0];
        $this->assertEquals( 0, strpos( $filePart->contentId, 'Zmx5LmpwZw@' . date( 'His' ) ) );
    }

    /**
     * Tests for feature request #11937.
     */
    public function testMailSafeModeComposerAutomaticImageIncludeFalse()
    {
        $this->mail->options->automaticImageInclude = false;
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'No Spam' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->htmlText = "<html>Some text before the simage: <img src=\"file://" . dirname( __FILE__  ) . "/parts/data/fly.jpg\" /> Here is the picture.";
        $this->mail->build();
        $this->assertEquals( true, 445 <= strlen( $this->mail->generate() ) && strlen( $this->mail->generate() ) <= 489 );
    }

    /**
     * Tests for feature request #11937.
     */
    public function testMailSafeModeComposerAutomaticImageIncludeDefault()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'No Spam' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->htmlText = "<html>Some text before the simage: <img src=\"file://" . dirname( __FILE__  ) . "/parts/data/fly.jpg\" /> Here is the picture.";
        $this->mail->build();
        $this->assertEquals( true, 62701 <= strlen( $this->mail->generate() ) && strlen( $this->mail->generate() ) <= 62733 );
    }

    /**
     * Tests for feature request #11937.
     */
    public function testComposerOptionsDefault()
    {
        $options = new ezcMailComposerOptions();
        $this->assertEquals( true, $options->automaticImageInclude );
    }

    /**
     * Tests for feature request #11937.
     */
    public function testComposerOptionsSet()
    {
        $options = new ezcMailComposerOptions();
        $options->automaticImageInclude = false;
        $this->assertEquals( false, $options->automaticImageInclude );
    }

    /**
     * Tests for feature request #11937.
     */
    public function testComposerOptionsSetInvalid()
    {
        $options = new ezcMailComposerOptions();
        try
        {
            $options->automaticImageInclude = "wrong value";
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'wrong value' that you were trying to assign to setting 'automaticImageInclude' is invalid. Allowed values are: bool.", $e->getMessage() );
        }
    }

    /**
     * Tests for feature request #11937.
     */
    public function testComposerOptionsSetNotExistent()
    {
        $options = new ezcMailComposerOptions();
        try
        {
            $options->no_such_option = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_option'.", $e->getMessage() );
        }
    }

    /**
     * Test for issue #14023: Split ezcMailComposer's addAttachment into a function for adding file attachments and for adding attachments from strings
     */
    public function testAddFileAttachment()
    {
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
        $this->mail->addFileAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile' ) );
    }

    /**
     * Test for issue #14023: Split ezcMailComposer's addAttachment into a function for adding file attachments and for adding attachments from strings
     */
    public function testAddStringAttachment()
    {
        $contents = file_get_contents( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->from = new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
        $this->mail->addStringAttachment( "fly.jpg", $contents );
        $this->mail->build();

        $this->parseAndCheckParts( $this->mail->generate(), array( 'ezcMailText', 'ezcMailFile' ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailComposerTest" );
    }
}
?>

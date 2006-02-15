<?php
/**
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
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

	public function setUp()
	{
        $this->mail = new ezcMailComposer();
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
    }

    /**
     * Test that inherited properties from ezcMail work.
     */
    public function testInheritedProperties()
    {
        $this->assertSetProperty( $this->mail, "to", array( array( 'email' => 'fh@ez.no' ) ) );
    }

    /**
     * Tests adding a valid attachment.
     */
    public function testAddAttachmentValid()
    {
        $this->mail->from = array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML message with embeded files and images.";
        $this->mail->plainText = "Naked people with extra parts! The things folk do for fashion!!";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();
    }

    /**
     * Tests adding an invalid attachment.
     */
    public function testAddAttachmentInValid()
    {
        try
        {
            $this->mail->from = array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
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
            $this->mail->from = array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
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
        $this->mail->from = array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Text only.";
        $this->mail->plainText = "Text only. Should not have a multipart body.";
        $this->mail->build();
//        echo "\n---------------\n";
//        echo $this->mail->generate();
//        echo "---------------\n";
        // let's try to send the thing
        $transport = new ezcMailTransportMta();
//        $transport->send( $this->mail );
    }

    /**
     * Test a complete mail with html text only
     */
    public function testMailHtmlOnly()
    {
        $this->mail->from = array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "HTML only..";
        $this->mail->htmlText = "<html><i><b>HTML only. Should not have a multipart body.</b></i></html>";
        $this->mail->build();
//        echo "\n---------------\n";
//        echo $this->mail->generate();
//        echo "---------------\n";
        // let's try to send the thing
        $transport = new ezcMailTransportMta();
//        $transport->send( $this->mail );
    }

    /**
     * Test a complete mail with one attachment only
     */
    public function testMailOneAttachmentNoText()
    {
        $this->mail->from = array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "One attachments only.";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();
//        echo "\n---------------\n";
//        echo $this->mail->generate();
//        echo "---------------\n";
        // let's try to send the thing
        $transport = new ezcMailTransportMta();
//        $transport->send( $this->mail );
    }

    /**
     * Test a complete mail with many attachments only
     */
    public function testMailManyAttachmentsNoText()
    {
        $this->mail->from = array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Many attachments only.";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();
//        echo "\n---------------\n";
//        echo $this->mail->generate();
//        echo "---------------\n";
        // let's try to send the thing
        $transport = new ezcMailTransportMta();
//        $transport->send( $this->mail );
    }

    /**
     * Test a complete mail with txt and html but no attachments
     */
    public function testMailTextAndHtmlNoAttachments()
    {
        $this->mail->from = array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Alternative HTML/Text message.";
        $this->mail->plainText = "Plain text message. Your client should show the HTML message if it supports HTML mail.";
        $this->mail->htmlText = "<html><i><b>HTML message. Your client should show this if it supports HTML.</b></i></html>";
        $this->mail->build();

//        echo "\n---------------\n";
//        echo $this->mail->generate();
//        echo "---------------\n";
        // let's try to send the thing
        $transport = new ezcMailTransportMta();
//        $transport->send( $this->mail );
    }

    /**
     * Test a complete mail with txt, html and attachments
     */
    public function testMailTextHtmlAndAttachments()
    {
        $this->mail->from = array( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->addTo( new ezcMailAddress( 'fh@ez.no', 'Frederik Holljen' ) );
        $this->mail->subject = "Alternative HTML/Text message and attachments.";
        $this->mail->plainText = "Plain text message. Your client should show the HTML message if it supports HTML mail.";
        $this->mail->htmlText = "<html><i><b>HTML message. Your client should show this if it supports HTML.</b></i></html>";
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->addAttachment( dirname( __FILE__) . "/parts/data/fly.jpg" );
        $this->mail->build();
//        echo "\n---------------\n";
//        echo $this->mail->generate();
//        echo "---------------\n";
        // let's try to send the thing
        $transport = new ezcMailTransportMta();
//        $transport->send( $this->mail );
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
//        echo "\n---------------\n";
//        echo $this->mail->generate();
//        echo "\n---------------\n";
        // let's try to send the thing
//        $transport = new ezcMailTransportSmtp( "smtp.ez.no" );
    }

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailComposerTest" );
    }
}
?>

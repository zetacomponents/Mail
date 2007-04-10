<?php
/**
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
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
class ezcMailTransportSmtpTest extends ezcTestCase
{
    private $transport;
    private $mail;

    const HOST = "10.0.2.35";

    const PORT = 2525;

    protected function setUp()
    {
        if ( @fsockopen( ezcMailTransportSmtpTest::HOST, ezcMailTransportSmtpTest::PORT, $errno, $errstr, 1 ) === false )
        {
            $this->markTestSkipped( "No connection to SMTP server " . ezcMailTransportSmtpTest::HOST . ":" . ezcMailTransportSmtpTest::PORT . "." );
        }

        $this->transport = new ezcMailTransportSmtp( ezcMailTransportSmtpTest::HOST, '', '', ezcMailTransportSmtpTest::PORT );
        $this->mail = new ezcMail();
        $this->mail->from = new ezcMailAddress( 'nospam@ez.no', 'Unit testing' );
        $this->mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'Foster' ) );
        $this->mail->subject = "[Components test] SMTP test";
        $this->mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
    }

    public function testProperties()
    {
        try
        {
            $x = $this->transport->invalid_property;
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }

        try
        {
            $this->transport->invalid_property = '';
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    // Tests sending a complete mail message.
    public function testFullMail()
    {
        try
        {
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending a complete mail message with CCs.
    public function testFullMailCc()
    {
        $this->mail->addCc( new ezcMailAddress( 'nospam@ez.no', 'Foster Cc' ) );
        try
        {
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending a complete mail message with BCCs.
    public function testFullMailBcc()
    {
        $this->mail->addBcc( new ezcMailAddress( 'nospam@ez.no', 'Foster Bcc' ) );
        try
        {
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending a complete mail message with CCs and BCCs.
    public function testFullMailCcBcc()
    {
        $this->mail->addCc( new ezcMailAddress( 'nospam@ez.no', 'Foster Cc' ) );
        $this->mail->addBcc( new ezcMailAddress( 'nospam@ez.no', 'Foster Bcc' ) );
        try
        {
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending several complete mail messages.
    public function testFullMailMultiple()
    {
        try
        {
            $this->transport->send( $this->mail );
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending several complete mail messages with keep connection.
    public function testFullMailMultipleKeepConnection()
    {
        try
        {
            $this->transport->keepConnection();
            $this->transport->send( $this->mail );
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending a mail to an invalid host.
    public function testInvalidHost()
    {
        $transport = new ezcMailTransportSmtp( "invalidhost.online.no" );
        try
        {
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            // great, it failed.
            return;
        }
        $transport = new ezcMailTransportSmtp( "10.0.2.35", '', '', 26 ); // wrong port
        try
        {
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            // great, it failed.
            return;
        }
        $this->fail( "SMTP connect to an invalidhost did not fail." );
    }

    // Tests sending a mail with empty to field.
    public function testInvalidMail1()
    {
        $this->mail->to = array();
        $this->mail->subject = "No recepients";
        try
        {
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            return;
        }
        $this->fail( "SMTP send without recipients did not fail." );
    }

    // Tests sending a mail with to not set.
    public function testInvalidMail2()
    {
        $this->mail->to = null;
        $this->mail->subject = "No recepients";
        try
        {
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            return;
        }
        $this->fail( "SMTP send without recipients did not fail." );
    }

    // Tests sending a complete mail message with Return-Path set.
    public function testFullMailReturnPath()
    {
        $this->mail->returnPath = new ezcMailAddress( 'returnpath@ez.no' );
        try
        {
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testIsSet()
    {
        $this->assertEquals( true, isset( $this->transport->user ) );
        $this->assertEquals( true, isset( $this->transport->password ) );
        $this->assertEquals( true, isset( $this->transport->senderHost ) );
        $this->assertEquals( true, isset( $this->transport->serverHost ) );
        $this->assertEquals( true, isset( $this->transport->serverPort ) );
        $this->assertEquals( true, isset( $this->transport->timeout ) );
        $this->assertEquals( true, isset( $this->transport->options ) );
        $this->assertEquals( false, isset( $this->transport->no_such_property ) );
    }

    public function testTransportProperties()
    {
        $smtp = new ezcMailSmtpTransport( ezcMailTransportSmtpTest::HOST, '', '', ezcMailTransportSmtpTest::PORT );

        $options = $smtp->options;
        $smtp->options = new ezcMailSmtpTransportOptions();
        $this->assertEquals( $options, $smtp->options );
        $this->assertEquals( 5, $smtp->options->timeout );
        $this->assertEquals( 5, $smtp->timeout );
        $smtp->timeout = 10;
        $this->assertEquals( 10, $smtp->options->timeout );
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_PLAIN, $smtp->options->connectionType );
        $this->assertEquals( array(), $smtp->options->connectionOptions );

        try
        {
            $smtp->options = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'xxx' that you were trying to assign to setting 'options' is invalid. Allowed values are: instanceof ezcMailSmtpTransportOptions.", $e->getMessage() );
        }

        try
        {
            $smtp->no_such_property = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }

        try
        {
            $value = $smtp->no_such_property;
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }
    }

    public function testConnectionSSL()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped( "No SSL support in PHP." );
        }
        try
        {
            $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSL ) );
            $mail = new ezcMail();
            $mail->from = new ezcMailAddress( 'nospam@ez.no', 'From' );
            $mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'To' ) );
            $mail->subject = "SMTP SSL test";
            $mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
            $mail->generate();
            $smtp->send( $mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testConnectionSSLOptions()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped( "No SSL support in PHP." );
        }
        try
        {
            $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', 465,
                        array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSL,
                               'connectionOptions' => array( 'wrapper_name' => array( 'option_name' => 'value' ) ) ) );
            $mail = new ezcMail();
            $mail->from = new ezcMailAddress( 'nospam@ez.no', 'From' );
            $mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'To' ) );
            $mail->subject = "SMTP SSL test";
            $mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
            $mail->generate();
            $smtp->send( $mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testConnectionSSLv2()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped( "No SSL support in PHP." );
        }
        try
        {
            $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV2 ) );
            $mail = new ezcMail();
            $mail->from = new ezcMailAddress( 'nospam@ez.no', 'From' );
            $mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'To' ) );
            $mail->subject = "SMTP SSLv2 test";
            $mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
            $mail->generate();
            $smtp->send( $mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testConnectionSSLv3()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped( "No SSL support in PHP." );
        }
        try
        {
            $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', 465, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV3 ) );
            $mail = new ezcMail();
            $mail->from = new ezcMailAddress( 'nospam@ez.no', 'From' );
            $mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'To' ) );
            $mail->subject = "SMTP SSLv3 test";
            $mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
            $mail->generate();
            $smtp->send( $mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testConnectionTLS()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped( "No SSL support in PHP." );
        }
        try
        {
            $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
            $mail = new ezcMail();
            $mail->from = new ezcMailAddress( 'nospam@ez.no', 'From' );
            $mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'To' ) );
            $mail->subject = "SMTP TLS test";
            $mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
            $mail->generate();
            $smtp->send( $mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testConnectionTLSWrongPort()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped( "No SSL support in PHP." );
        }
        try
        {
            $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', 25, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
            $mail = new ezcMail();
            $mail->from = new ezcMailAddress( 'nospam@ez.no', 'From' );
            $mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'To' ) );
            $mail->subject = "SMTP TLS test";
            $mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
            $mail->generate();
            $smtp->send( $mail );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testConnectionPlain()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
            $mail = new ezcMail();
            $mail->from = new ezcMailAddress( 'nospam@ez.no', 'From' );
            $mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'To' ) );
            $mail->subject = "SMTP plain test";
            $mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
            $mail->generate();
            $smtp->send( $mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testConstructorPort()
    {
        $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
        $this->assertEquals( 25, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSL ) );
        $this->assertEquals( 465, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV2 ) );
        $this->assertEquals( 465, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV3 ) );
        $this->assertEquals( 465, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
        $this->assertEquals( 465, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', 465, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
        $this->assertEquals( 465, $smtp->serverPort );
    }

    public function testConstructorOptions()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', 465, array( 'connection' => ezcMailSmtpTransport::CONNECTION_TLS ) );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }

        try
        {
            $smtp = new ezcMailSmtpTransport( 'ezctest.ez.no', '', '', 465, array( 'connectionOptions' => 'xxx' ) );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailTransportSmtpTest" );
    }
}
?>

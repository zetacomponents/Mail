<?php
/**
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

include_once( 'wrappers/smtp_wrapper.php' );

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportSmtpTest extends ezcTestCase
{
    private $transport;
    private $mail;

    const HOST = '10.0.2.35';
    const PORT = 2525;

    const HOST_SSL = 'ezctest.ez.no';
    const PORT_SSL = 465;

    protected function setUp()
    {
        if ( @fsockopen( self::HOST, self::PORT, $errno, $errstr, 1 ) === false )
        {
            $this->markTestSkipped( "No connection to SMTP server " . self::HOST . ":" . self::PORT . "." );
        }

        $this->transport = new ezcMailTransportSmtp( self::HOST, '', '', self::PORT );
        $this->mail = new ezcMail();
        $this->mail->from = new ezcMailAddress( 'nospam@ez.no', 'Unit testing' );
        $this->mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'Foster' ) );
        $this->mail->subject = "[Components test] SMTP test";
        $this->mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
    }

    
    public function testWrapperMockLoginAuthenticateFail250()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransport', array( 'getReplyCode', 'sendData' ), array( self::HOST, 'user', 'password', self::PORT ) );
        $smtp->expects( $this->any() )
             ->method( 'getReplyCode' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'custom response' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        try
        {
            $smtp->login();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "HELO/EHLO failed with error: .", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testWrapperMockLoginAuthenticateFail334()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST, 'user', 'password', self::PORT ) );
        $smtp->expects( $this->any() )
             ->method( 'getReplyCode' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '250' ),
                        $this->returnValue( 'custom response' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        try
        {
            $smtp->login();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "SMTP server does not accept AUTH LOGIN.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testWrapperMockLoginAuthenticateFailUser334()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST, 'user', 'password', self::PORT ) );
        $smtp->expects( $this->any() )
             ->method( 'getReplyCode' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '250' ),
                        $this->returnValue( '334' ),
                        $this->returnValue( 'custom response' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        try
        {
            $smtp->login();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "SMTP server does not accept login: user.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testWrapperMockLoginAuthenticateFailPassword334()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST, 'user', 'password', self::PORT ) );
        $smtp->expects( $this->any() )
             ->method( 'getReplyCode' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '250' ),
                        $this->returnValue( '334' ),
                        $this->returnValue( '334' ),
                        $this->returnValue( 'custom response' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        try
        {
            $smtp->login();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "SMTP server does not accept the password.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testWrapperMockLoginAuthenticateSucceed()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST, 'user', 'password', self::PORT ) );
        $smtp->expects( $this->any() )
             ->method( 'getReplyCode' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '250' ),
                        $this->returnValue( '334' ),
                        $this->returnValue( '334' ),
                        $this->returnValue( '235' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        $smtp->login();
        $this->assertEquals( ezcMailSmtpTransport::STATUS_AUTHENTICATED, $smtp->getStatus() );
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
        $this->assertEquals( ezcMailSmtpTransport::STATUS_NOT_CONNECTED, $smtp->getStatus() );
    }

    public function testWrapperMockCmdMailFail()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST, 'user', 'password', self::PORT ) );
        $smtp->expects( $this->any() )
             ->method( 'getReplyCode' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'custom response' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        $smtp->setStatus( ezcMailSmtpTransport::STATUS_AUTHENTICATED );

        try
        {
            $smtp->cmdMail( 'nospam@ez.no' );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "MAIL FROM failed with error: .", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testWrapperMockCmdRpctFail()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST, 'user', 'password', self::PORT ) );
        $smtp->expects( $this->any() )
             ->method( 'getReplyCode' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'custom response' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        $smtp->setStatus( ezcMailSmtpTransport::STATUS_AUTHENTICATED );

        try
        {
            $smtp->cmdRcpt( 'nospam@ez.no' );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "RCPT TO failed with error: .", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testWrapperMockCmdDataFail()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST, 'user', 'password', self::PORT ) );
        $smtp->expects( $this->any() )
             ->method( 'getReplyCode' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( 'custom response' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        $smtp->setStatus( ezcMailSmtpTransport::STATUS_AUTHENTICATED );

        try
        {
            $smtp->cmdData();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "DATA failed with error: .", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
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
        $transport = new ezcMailTransportSmtp( self::HOST, '', '', 26 ); // wrong port
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
        $smtp = new ezcMailSmtpTransport( self::HOST, '', '', self::PORT );

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
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSL ) );
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
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', self::PORT_SSL,
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
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV2 ) );
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
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', self::PORT_SSL, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV3 ) );
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
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
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
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', self::PORT, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
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
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
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
        $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
        $this->assertEquals( 25, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSL ) );
        $this->assertEquals( self::PORT_SSL, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV2 ) );
        $this->assertEquals( self::PORT_SSL, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV3 ) );
        $this->assertEquals( self::PORT_SSL, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
        $this->assertEquals( self::PORT_SSL, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', self::PORT_SSL, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
        $this->assertEquals( self::PORT_SSL, $smtp->serverPort );
    }

    public function testConstructorOptions()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', self::PORT_SSL, array( 'connection' => ezcMailSmtpTransport::CONNECTION_TLS ) );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, '', '', self::PORT_SSL, array( 'connectionOptions' => 'xxx' ) );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public function testTransportOptionsDefault()
    {
        $options = new ezcMailSmtpTransportOptions();
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_PLAIN, $options->connectionType );
        $this->assertEquals( array(), $options->connectionOptions );
    }

    public function testTransportOptionsSet()
    {
        $options = new ezcMailSmtpTransportOptions();
        $options->connectionType = ezcMailSmtpTransport::CONNECTION_TLS;
        $options->connectionOptions = array( 'wrapper' => array( 'option' => 'value' ) );
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_TLS, $options->connectionType );
        $this->assertEquals( array( 'wrapper' => array( 'option' => 'value' ) ), $options->connectionOptions );
        $options->ssl = true;
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_SSL, $options->connectionType );
        $options->ssl = false;
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_PLAIN, $options->connectionType );
    }

    public function testTransportOptionsSetInvalid()
    {
        $options = new ezcMailSmtpTransportOptions();
        try
        {
            $options->connectionOptions = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $options->ssl = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public function testTransportOptionsSetNotExistent()
    {
        $options = new ezcMailSmtpTransportOptions();
        try
        {
            $options->no_such_option = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailTransportSmtpTest" );
    }
}
?>

<?php
/**
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
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
    // This server is not accessible for the outside world
    const HOST_NO_AUTH = '10.0.2.35';
    const PORT_NO_AUTH = 2525;
    const USER_NO_AUTH = 'user';
    const PASS_NO_AUTH = 'password';

    // This server is not accessible for the outside world
    const HOST_SSL = 'ezctest.ez.no';
    const PORT_SSL = 465;
    const USER_SSL = '';
    const PASS_SSL = '';

    const HOST_PLAIN = 'mta1.ez.no';
    const PORT_PLAIN = 25;
    const USER_PLAIN = 'ezcomponents@mail.ez.no';
    const PASS_PLAIN = 'ezcomponents';

    const HOST_CRYPT = 'smtp.ez.no';
    const PORT_CRYPT = 25;
    const USER_CRYPT = 'ezcomponents';
    const PASS_CRYPT = 'ezcomponents';

    private $transport;
    private $mail;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    protected function skipIfNotInNetwork( $server, $port )
    {
        if ( @fsockopen( $server, $port, $errno, $errstr, 1 ) === false )
        {
            $this->markTestSkipped( "No connection to SMTP server " . $server . ":" . $port . "." );
        }
    }

    protected function setUp()
    {
        $this->mail = new ezcMail();
        $this->mail->from = new ezcMailAddress( 'nospam@ez.no', 'Unit testing' );
        $this->mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'Foster' ) );
        $this->mail->subject = "[Components test] SMTP test";
        $this->mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
    }

    public function testWrapperMockAuthLoginFail250()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN ) );

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

    public function testWrapperMockAuthLoginFailAuthNotSupported()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN ) );

        $smtp->expects( $this->any() )
             ->method( 'getReplyCode' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '250' )
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
            $this->assertEquals( "SMTP server does not accept the AUTH command.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testWrapperMockAuthLoginFailAuthMethodNotSupported()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'sortAuthMethods' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN ) );

        $smtp->expects( $this->any() )
             ->method( 'sortAuthMethods' )
             ->will( $this->returnValue( array( 'no such authentication method' ) ) );

        try
        {
            $smtp->connect();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "Unsupported AUTH method 'no such authentication method'.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testWrapperMockAuthLoginFailAllMethods()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'auth' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN ) );

        $smtp->expects( $this->any() )
             ->method( 'auth' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( false )
                   ) );

        try
        {
            $smtp->connect();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "SMTP server did not respond correctly to any of the authentication methods LOGIN, PLAIN.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    /**
     * Test for issue #12930: test that the sorting of the SMTP authentication
     * methods supported by the SMTP server is done properly in decreasing order
     * of strength.
     */
    public function testWrapperMockAuthLoginFailSortMethods()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'auth', 'getData' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN ) );

        // mock that the SMTP server will return the supported authentication methods
        // in this order: PLAIN LOGIN DIGEST-MD5 NTLM CRAM-MD5
        $smtp->expects( $this->any() )
             ->method( 'getData' )
             ->will( $this->returnValue( '250-AUTH PLAIN LOGIN DIGEST-MD5 NTLM CRAM-MD5' ) );

        $smtp->expects( $this->any() )
             ->method( 'auth' )
             ->will( $this->returnValue( false ) );

        try
        {
            $smtp->connect();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            // compare the exception message with the one returned from the SMTP transport
            // in order to see if the sorting of the supported authentication methods
            // was done properly in decreasing order of strength.
            // initially supported (from mock): PLAIN LOGIN DIGEST-MD5 NTLM CRAM-MD5
            // SMTP transport sorted: DIGEST-MD5, CRAM-MD5, NTLM, LOGIN, PLAIN
            $this->assertEquals( "SMTP server did not respond correctly to any of the authentication methods DIGEST-MD5, CRAM-MD5, NTLM, LOGIN, PLAIN.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testWrapperMockCmdMailFail()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN ) );

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
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN ) );

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
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN ) );

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

    // Tests sending a complete mail message.
    public function testFullMail()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        try
        {
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending a complete mail message with CCs.
    public function testFullMailCc()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        $this->mail->addCc( new ezcMailAddress( 'nospam@ez.no', 'Foster Cc' ) );
        try
        {
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending a complete mail message with BCCs.
    public function testFullMailBcc()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        $this->mail->addBcc( new ezcMailAddress( 'nospam@ez.no', 'Foster Bcc' ) );
        try
        {
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending a complete mail message with CCs and BCCs.
    public function testFullMailCcBcc()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        $this->mail->addCc( new ezcMailAddress( 'nospam@ez.no', 'Foster Cc' ) );
        $this->mail->addBcc( new ezcMailAddress( 'nospam@ez.no', 'Foster Bcc' ) );
        try
        {
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending several complete mail messages.
    public function testFullMailMultiple()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        try
        {
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending several complete mail messages with keep connection.
    public function testFullMailMultipleKeepConnection()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        try
        {
            $transport->keepConnection();
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    // Tests sending a mail to an invalid host.
    public function testInvalidHost()
    {
        $transport = new ezcMailSmtpTransport( "invalidhost.online.no" );
        try
        {
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            // great, it failed.
            return;
        }
    }

    // Tests sending a mail to an existing host with an invalid port.
    public function testInvalidPort()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, 26 ); // wrong port

        try
        {
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
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
    public function testInvalidMailToEmptyArray()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        $this->mail->to = array();
        try
        {
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            return;
        }
        $this->fail( "SMTP send without recipients did not fail." );
    }

    // Tests sending a mail with to not set.
    public function testInvalidMailToNull()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        try
        {
            $this->mail->to = null;
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
        }
        catch ( ezcBaseValueException $e )
        {
            return;
        }
        $this->fail( "SMTP send without recipients did not fail." );
    }

    // Tests sending a complete mail message with Return-Path set.
    public function testFullMailReturnPath()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        $this->mail->returnPath = new ezcMailAddress( 'returnpath@ez.no' );
        try
        {
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testConnectionSSL()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped( "PHP not compiled with --with-openssl." );
        }

        $this->skipIfNotInNetwork( self::HOST_SSL, self::PORT_SSL );

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, self::USER_SSL, self::PASS_SSL, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSL ) );
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $smtp->send( $this->mail );
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
            $this->markTestSkipped( "PHP not compiled with --with-openssl." );
        }

        $this->skipIfNotInNetwork( self::HOST_SSL, self::PORT_SSL );

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, self::USER_SSL, self::PASS_SSL, self::PORT_SSL,
                        array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSL,
                               'connectionOptions' => array( 'wrapper_name' => array( 'option_name' => 'value' ) ) ) );
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $smtp->send( $this->mail );
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
            $this->markTestSkipped( "PHP not compiled with --with-openssl." );
        }

        $this->skipIfNotInNetwork( self::HOST_SSL, self::PORT_SSL );

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, self::USER_SSL, self::PASS_SSL, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV2 ) );
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $smtp->send( $this->mail );
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
            $this->markTestSkipped( "PHP not compiled with --with-openssl." );
        }

        $this->skipIfNotInNetwork( self::HOST_SSL, self::PORT_SSL );

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, self::USER_SSL, self::PASS_SSL, self::PORT_SSL, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV3 ) );
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $smtp->send( $this->mail );
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
            $this->markTestSkipped( "PHP not compiled with --with-openssl." );
        }

        $this->skipIfNotInNetwork( self::HOST_SSL, self::PORT_SSL );

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, self::USER_SSL, self::PASS_SSL, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $smtp->send( $this->mail );
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
            $this->markTestSkipped( "PHP not compiled with --with-openssl." );
        }

        $this->skipIfNotInNetwork( self::HOST_SSL, self::PORT_SSL );

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, self::USER_SSL, self::PASS_SSL, self::PORT_NO_AUTH, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testConnectionPlain()
    {
        $this->skipIfNotInNetwork( self::HOST_SSL, self::PORT_SSL );

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_SSL, self::USER_SSL, self::PASS_SSL, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
            $this->mail->subject = __CLASS__ . ':' . __FUNCTION__;
            $smtp->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->fail( $e->getMessage() );
        }
    }

    public function testTransportProperties()
    {
        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );

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

    public function testIsSet()
    {
        $transport = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
        $this->assertEquals( true, isset( $transport->user ) );
        $this->assertEquals( true, isset( $transport->password ) );
        $this->assertEquals( true, isset( $transport->senderHost ) );
        $this->assertEquals( true, isset( $transport->serverHost ) );
        $this->assertEquals( true, isset( $transport->serverPort ) );
        $this->assertEquals( true, isset( $transport->timeout ) );
        $this->assertEquals( true, isset( $transport->options ) );
        $this->assertEquals( false, isset( $transport->no_such_property ) );
    }

    public function testConstructorPort()
    {
        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
        $this->assertEquals( 25, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSL ) );
        $this->assertEquals( self::PORT_SSL, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV2 ) );
        $this->assertEquals( self::PORT_SSL, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_SSLV3 ) );
        $this->assertEquals( self::PORT_SSL, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, null, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_TLS ) );
        $this->assertEquals( self::PORT_SSL, $smtp->serverPort );

        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN, array( 'connectionType' => ezcMailSmtpTransport::CONNECTION_PLAIN ) );
        $this->assertEquals( self::PORT_PLAIN, $smtp->serverPort );
    }

    public function testConstructorOptions()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN, array( 'wrong_option' => ezcMailSmtpTransport::CONNECTION_TLS ) );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN, array( 'connectionOptions' => 'wrong value' ) );
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }

        $options = new ezcMailSmtpTransportOptions();
        $options->connectionType = ezcMailSmtpTransport::CONNECTION_SSL;
        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN, $options );
        $this->assertEquals( ezcMailSmtpTransport::CONNECTION_SSL, $smtp->options->connectionType );

        $options = new stdClass();
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN, $options );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'O:8:\"stdClass\":0:{}' that you were trying to assign to setting 'options' is invalid. Allowed values are: ezcMailSmtpTransportOptions|array.", $e->getMessage() );
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
            $options->preferredAuthMethod = 'wrong value';
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
}
?>

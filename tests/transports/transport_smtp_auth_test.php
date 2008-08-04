<?php
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

include_once( 'wrappers/smtp_wrapper.php' );

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportSmtpAuthTest extends ezcTestCase
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

    private $mail;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    protected function setUp()
    {
        $this->mail = new ezcMail();
        $this->mail->from = new ezcMailAddress( 'nospam@ez.no', 'Unit testing' );
        $this->mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'Foster' ) );
        $this->mail->subject = "[Components test] SMTP test";
        $this->mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
    }

    public function testAuthAuto()
    {
        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN );
        $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
        $smtp->send( $this->mail );
    }

    public function testAuthNtlm()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'mcrypt' ) )
        {
            $this->markTestSkipped( "PHP not compiled with --with-mcrypt." );
        }

        $smtp = new ezcMailSmtpTransport( self::HOST_CRYPT, self::USER_CRYPT, self::PASS_CRYPT, self::PORT_CRYPT );
        $smtp->options->preferredAuthMethod = ezcMailSmtpTransport::AUTH_NTLM;
        $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
        $smtp->send( $this->mail );
    }

    public function testAuthNtlmWrongUsername()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'mcrypt' ) )
        {
            $this->markTestSkipped( "PHP not compiled with --with-mcrypt." );
        }

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_CRYPT, 'this user could not possible exist', self::PASS_CRYPT, self::PORT_CRYPT );
            $smtp->options->preferredAuthMethod = ezcMailSmtpTransport::AUTH_NTLM;
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAuthNtlmWrongPassword()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'mcrypt' ) )
        {
            $this->markTestSkipped( "PHP not compiled with --with-mcrypt." );
        }

        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_CRYPT, self::USER_CRYPT, 'wrong password', self::PORT_CRYPT );
            $smtp->options->preferredAuthMethod = ezcMailSmtpTransport::AUTH_NTLM;
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAuthNtlmWrapperMockFail334()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'mcrypt' ) )
        {
            $this->markTestSkipped( "PHP not compiled with --with-mcrypt." );
        }

        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_CRYPT, self::USER_CRYPT, self::PASS_CRYPT, self::PORT_CRYPT ) );
        $smtp->options->preferredAuthMethod = ezcMailSmtpTransport::AUTH_NTLM;
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
            $this->assertEquals( "SMTP server does not accept AUTH NTLM.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testAuthDigestMd5()
    {
        $smtp = new ezcMailSmtpTransport( self::HOST_CRYPT, self::USER_CRYPT, self::PASS_CRYPT, self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_DIGEST_MD5 ) );
        $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
        $smtp->send( $this->mail );
    }

    public function testAuthDigestMd5WrongUsername()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_CRYPT, 'this user could not possible exist', self::PASS_CRYPT, self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_DIGEST_MD5 ) );
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAuthDigestMd5WrongPassword()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_CRYPT, self::USER_CRYPT, 'wrong password', self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_DIGEST_MD5 ) );
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAuthDigestMd5WrapperMockFail334()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_CRYPT, self::USER_CRYPT, self::PASS_CRYPT, self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_DIGEST_MD5 ) ) );
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
            $this->assertEquals( "SMTP server does not accept AUTH DIGEST-MD5.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testAuthDigestMd5WrapperMockFailRequiredParameters()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getData', 'sendData' ), array( self::HOST_CRYPT, self::USER_CRYPT, self::PASS_CRYPT, self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_DIGEST_MD5 ) ) );

        $smtp->expects( $this->any() )
             ->method( 'getData' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '220 smtp.ez.no ESMTP Postfix (Debian/GNU)' ),
                        $this->returnValue( '250-AUTH DIGEST-MD5' ),
                        $this->returnValue( '334 cmVhbG09InNtdHAuZXoubm8iLHFvcD0iYXV0aCIsY2hhcnNldD11dGYtOCxhbGdvcml0aG09bWQ1LXNlc3M=' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        try
        {
            $smtp->connect();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "SMTP server did not send a correct DIGEST-MD5 challenge.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testAuthDigestMd5WrapperMockFailRspauth()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getData', 'sendData' ), array( self::HOST_CRYPT, self::USER_CRYPT, self::PASS_CRYPT, self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_DIGEST_MD5 ) ) );

        $smtp->expects( $this->any() )
             ->method( 'getData' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '220 smtp.ez.no ESMTP Postfix (Debian/GNU)' ),
                        $this->returnValue( '250-AUTH DIGEST-MD5' ),
                        $this->returnValue( '334 bm9uY2U9Ik8zOWd0NzM3Q0lmWEdHcFlMSVVnaTVTYTRtZWduUVYvNC92alZLb1JFbTA9IixyZWFsbT0ic210cC5lei5ubyIscW9wPSJhdXRoIixjaGFyc2V0PXV0Zi04LGFsZ29yaXRobT1tZDUtc2Vzcw==' ),
                        $this->returnValue( '334 cnNwYXV0aD0yYmVhODcxNjgxZDYzNTlkM2I5NDE2OTkwZTc1MjQwMQ==' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        try
        {
            $smtp->connect();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "SMTP server did not responded correctly to the DIGEST-MD5 authentication.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testAuthDigestMd5WrapperMockFail235()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getData', 'sendData', 'generateNonce' ), array( self::HOST_CRYPT, self::USER_CRYPT, self::PASS_CRYPT, self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_DIGEST_MD5 ) ) );

        $smtp->expects( $this->any() )
             ->method( 'getData' )
             ->will( $this->onConsecutiveCalls(
                        $this->returnValue( '220 smtp.ez.no ESMTP Postfix (Debian/GNU)' ),
                        $this->returnValue( '250-AUTH DIGEST-MD5' ),
                        $this->returnValue( '334 bm9uY2U9ImtobjNCWVpxaTNseVBETlVGZGV3a1R5djJaeE1iWUwzOFV0QlA0VzBiWkU9IixyZWFsbT0ic210cC5lei5ubyIscW9wPSJhdXRoIixjaGFyc2V0PXV0Zi04LGFsZ29yaXRobT1tZDUtc2Vzcw==' ),
                        $this->returnValue( '334 cnNwYXV0aD1iMmQ0NWMyOTJjYzRiY2VkOTY1NGU3NmM2MDU3ZGMwMQ==' ),
                        $this->returnValue( 'custom response' )
                   ) );

        $smtp->expects( $this->any() )
             ->method( 'sendData' )
             ->will( $this->returnValue( false ) );

        $smtp->expects( $this->any() )
             ->method( 'generateNonce' )
             ->will( $this->returnValue( 'r5MQKF00HSPIPTjRIb1zQMdd0oYaZNC5' ) );

        try
        {
            $smtp->connect();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportSmtpException $e )
        {
            $this->assertEquals( "SMTP server did not allow DIGEST-MD5 authentication.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testAuthCramMd5()
    {
        $smtp = new ezcMailSmtpTransport( self::HOST_CRYPT, self::USER_CRYPT, self::PASS_CRYPT, self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_CRAM_MD5 ) );
        $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
        $smtp->send( $this->mail );
    }

    public function testAuthCramMd5WrongUsername()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_CRYPT, 'this user could not possible exist', self::PASS_CRYPT, self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_CRAM_MD5 ) );
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAuthCramMd5WrongPassword()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_CRYPT, self::USER_CRYPT, 'wrong password', self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_CRAM_MD5 ) );
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAuthCramMd5WrapperMockFail334()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_CRYPT, self::USER_CRYPT, self::PASS_CRYPT, self::PORT_CRYPT, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_CRAM_MD5 ) ) );
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
            $this->assertEquals( "SMTP server does not accept AUTH CRAM-MD5.", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testAuthLogin()
    {
        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_LOGIN ) );
        $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
        $smtp->send( $this->mail );
    }

    public function testAuthLoginWrongUsername()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, 'this user could not possible exist', self::PASS_PLAIN, self::PORT_PLAIN, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_LOGIN ) );
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAuthLoginWrongPassword()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, 'wrong password', self::PORT_PLAIN, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_LOGIN ) );
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAuthLoginWrapperMockFail334()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_LOGIN ) ) );
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

    public function testAuthLoginWrapperMockFailUser334()
    {
        $smtp = $this->getMock( 'ezcMailSmtpTransportWrapper', array( 'getReplyCode', 'sendData' ), array( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_LOGIN ) ) );
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
            $this->assertEquals( "SMTP server did not accept login: " . self::USER_PLAIN . ".", $e->getMessage() );
        }
        $smtp->setStatus( ezcMailSmtpTransport::STATUS_NOT_CONNECTED );
    }

    public function testAuthPlain()
    {
        $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, self::PASS_PLAIN, self::PORT_PLAIN, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_PLAIN ) );
        $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
        $smtp->send( $this->mail );
    }

    public function testAuthPlainWrongUsername()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, 'this user could not possible exist', self::PASS_PLAIN, self::PORT_PLAIN, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_PLAIN ) );
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testAuthPlainWrongPassword()
    {
        try
        {
            $smtp = new ezcMailSmtpTransport( self::HOST_PLAIN, self::USER_PLAIN, 'wrong password', self::PORT_PLAIN, array( 'preferredAuthMethod' => ezcMailSmtpTransport::AUTH_PLAIN ) );
            $this->mail->subject = __CLASS__ . ' - ' . __FUNCTION__;
            $smtp->send( $this->mail );
            $this->fail( 'Expected message was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }
}
?>

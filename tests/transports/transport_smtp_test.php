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
        $this->assertEquals( false, isset( $this->transport->no_such_property ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailTransportSmtpTest" );
    }
}
?>

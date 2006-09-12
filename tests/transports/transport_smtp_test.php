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
class ezcMailTransportSmtpTest extends ezcTestCase
{
    private $transport;
    private $mail;

    const HOST = "10.0.2.35";

    const PORT = 2525;

    public function setUp()
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

    /**
     * Tests sending a complete mail message.
     */
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

    /**
     * Tests sending several complete mail messages.
     */
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

    /**
     * Tests sending several complete mail messages with keep connection.
     */
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

    /**
     * Tests sending a mail to an invalid host.
     */
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

    /**
     * Tests sending an invalid mail.
     */
    public function testInvalidMail()
    {
        $this->mail->to = array();
        $this->mail->subject = "No recepients";
        try
        {
            $this->transport->send( $this->mail );
        }
        catch ( ezcMailTransportException $e )
        {
            // great, it failed. Let's check that we got the correct error.
            if ( strstr( $e->getMessage(), 'recipients' ) )
            {
                return;
            }
        }

        $this->fail( "SMTP connect to an invalidhost did not fail." );
    }

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailTransportSmtpTest" );
    }
}
?>

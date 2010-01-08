<?php
/**
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
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
class ezcMailTransportMtaTest extends ezcTestCase
{
    private $transport;
    private $mail;

    protected function setUp()
    {
        if ( !function_exists( 'mail' ) )
        {
            $this->markTestSkipped( 'mail() function not available.' );
        }

        $this->transport = new ezcMailMtaTransport();
        $this->mail = new ezcMail();
        $this->mail->from = new ezcMailAddress( 'nospam@ez.no', 'Unit testing' );
        $this->mail->addTo( new ezcMailAddress( 'nospam@ez.no', 'Foster' ) );
        $this->mail->subject = "[Components test] Mta test";
        $this->mail->body = new ezcMailText( "It doesn't look as if it's ever used." );
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
        $this->fail( 'MTA send without recipients did not fail.' );
    }

    // Tests sending a mail with null to field.
    public function testInvalidMail2()
    {
        try
        {
            $this->mail->to = null;
            $this->mail->subject = "No recepients";
            $this->transport->send( $this->mail );
        }
        catch ( ezcBaseValueException $e )
        {
            return;
        }
        $this->fail( 'MTA send without recipients did not fail.' );
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

    // Test with utf8 chars in to/from and subject
    public function testEncodedHeaders()
    {
        $m = new ezcMailComposer;
        $m->from = new ezcMailAddress( 'freya@ez.no', 'Frøya', 'utf-8' );
        $m->addTo( new ezcMailAddress( 'nospam@ez.no', 'Óðinn', 'utf-8' ) );
        $m->subject = "Blót";
        $m->subjectCharset = 'utf-8';

        $this->transport->send( $m );

        $this->assertEquals( "=?utf-8?Q?Bl=C3=B3t?=", $m->getHeader( 'Subject' ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcMailTransportMtaTest" );
    }
}
?>

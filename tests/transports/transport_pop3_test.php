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
class ezcMailTransportPop3Test extends ezcTestCase
{

	public function setUp()
	{
	}

    public function testInvalidServer()
    {
        try
        {
            $pop3 = new ezcMailPop3Transport( "no.such.server.example.com" );
        } catch( ezcMailTransportException $e ) { return; }
        $this->fail( "Didn't get exception when expected" );
    }

    public function testInvalidUsername()
    {
        try
        {
            $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
            $pop3->authenticate( "no_such_user", "ezcomponents" );
        } catch( ezcMailTransportException $e ) { return; }
        $this->fail( "Didn't get exception when expected" );
    }

    public function testInvalidPassword()
    {
        try
        {
            $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
            $pop3->authenticate( "ezcomponents", "no_such_password" );
        } catch( ezcMailTransportException $e ) { return; }
        $this->fail( "Didn't get exception when expected" );
    }


    public function testInvalidCallListMessages()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->listMessages();
        } catch( ezcMailTransportException $e ) {return; }
        $this->fail( "Didn't get exception when expected" );
    }

    public function testInvalidCallTop()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->top( 1, 1 );
        } catch( ezcMailTransportException $e ) {return; }
        $this->fail( "Didn't get exception when expected" );
    }

    public function testInvalidCallStatus()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->status( $a, $b );
        } catch( ezcMailTransportException $e ) {return; }
        $this->fail( "Didn't get exception when expected" );
    }


    public function testInvalidCallListUniqueMessages()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->listUniqueIdentifiers();
        } catch( ezcMailTransportException $e ) {return; }
        $this->fail( "Didn't get exception when expected" );
    }


    public function testFetchMail()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $set = $pop3->fetchAll( true /*leaveOnServer*/  );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 2, count( $mail ) );
    }

    public function testListMessages()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $list = $pop3->listMessages();
        $this->assertEquals( array( 1=> "1439", 2 => "2416" ), $list );
    }

    public function testStatus()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $pop3->status( $num, $size );
        $this->assertEquals( 2, $num );
        $this->assertEquals( 3855, $size );
    }

    public function testTop()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $list = $pop3->top( 1, 1 );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testListUniqueIdentifiersSingle()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $this->assertEquals( array( 1 => "1143007546.3" ), $pop3->listUniqueIdentifiers( 1 ) );
    }

    public function testListUniqueIdentifiersMultiple()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $this->assertEquals( array( 1 => "1143007546.3",
                                    2 => "1143007546.4"), $pop3->listUniqueIdentifiers() );
    }

    public function testApop()
    {
        try
        {
            $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
            $pop3->authenticate( "ezcomponents", "ezcomponents", ezcMailPop3Transport::AUTH_APOP );
        }catch( ezcMailTransportException $e ){return;}
        $this->fail( "Did not get excepted exception" );
    }

    public function testDisconnect()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $pop3->disconnect();
        $pop3->disconnect();
    }

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailTransportPop3Test" );
    }
}
?>

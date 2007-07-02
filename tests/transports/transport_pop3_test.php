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
class ezcMailTransportPop3Test extends ezcTestCase
{
    private static $ids = array();

    public function testInvalidServer()
    {
        try
        {
            $pop3 = new ezcMailPop3Transport( "no.such.server.example.com" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidUsername()
    {
        try
        {
            $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
            $pop3->authenticate( "no_such_user", "ezcomponents" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidPassword()
    {
        try
        {
            $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
            $pop3->authenticate( "ezcomponents", "no_such_password" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallListMessages()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->listMessages();
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallTop()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->top( 1, 1 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallStatus()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->status( $a, $b );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallDelete()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->delete( 1000 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testLoginAuthenticated()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $pop3->authenticate( "ezcomponents", "ezcomponents" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallListUniqueMessages()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->listUniqueIdentifiers();
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testFetchMail()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $set = $pop3->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testListMessages()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $list = $pop3->listMessages();
        $this->assertEquals( array( 1 => '1542', 2 => '1539', 3 => '1383', 4 => '63913' ), $list );
    }

    public function testFetchByMessageNr1()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $message = $pop3->fetchByMessageNr( -1 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailNoSuchMessageException $e )
        {
            $this->assertEquals( "The message with ID '-1' could not be found.", $e->getMessage() );
        }
    }

    public function testFetchByMessageNr2()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $message = $pop3->fetchByMessageNr( 0 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailNoSuchMessageException $e )
        {
            $this->assertEquals( "The message with ID '0' could not be found.", $e->getMessage() );
        }
    }

    public function testFetchByMessageNr3()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $message = $pop3->fetchByMessageNr( 1 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $message );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( array( 0 => '1' ), $this->readAttribute( $message, 'messages' ) );
        $this->assertEquals( 'ezcMailPop3Set', get_class( $message ) );
    }
    
    public function testfetchFromOffset1()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $set = $pop3->fetchFromOffset( -1, 10 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailOffsetOutOfRangeException $e )
        {
            $this->assertEquals( "The offset '-1' is outside of the message subset '-1', '10'.", $e->getMessage());
        }
    }

    public function testfetchFromOffset2()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $set = $pop3->fetchFromOffset( 10, 1 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailOffsetOutOfRangeException $e )
        {
            $this->assertEquals( "The offset '10' is outside of the message subset '10', '1'.", $e->getMessage() );
        }
    }

    public function testfetchFromOffset3()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $set = $pop3->fetchFromOffset( 0, -1 );
            $this->assertEquals( 'Expected exception was not thrown' );
        }
        catch ( ezcMailInvalidLimitException $e )
        {
            $this->assertEquals( "The message count '-1' is not allowed for the message subset '0', '-1'.", $e->getMessage() );
        }
    }

    public function testfetchFromOffset4()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $set = $pop3->fetchFromOffset( 1, 4 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testfetchFromOffset5()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $set = $pop3->fetchFromOffset( 1, 0 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testStatus()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $pop3->status( $num, $size );
        $this->assertEquals( 4, $num );
        $this->assertEquals( 68377, $size );
    }

    public function testTop()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $list = $pop3->top( 1, 1 );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testInvalidTop()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $pop3->top( 1000, 1 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testDelete()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        try
        {
            $pop3->delete( 1000 );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }
    
    public function testListUniqueIdentifiersSingle()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $this->assertEquals( array( 1 => self::$ids[0] ), $pop3->listUniqueIdentifiers( 1 ) );
    }

    public function testListUniqueIdentifiersMultiple()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $this->assertEquals(
            array(
                1 => self::$ids[0],
                2 => self::$ids[1],
                3 => self::$ids[2],
                4 => self::$ids[3],
            ),
            $pop3->listUniqueIdentifiers()
        );
    }

    public function testApop()
    {
        try
        {
            $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
            $pop3->authenticate( "ezcomponents", "ezcomponents", ezcMailPop3Transport::AUTH_APOP );
            $this->fail( "Did not get excepted exception" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testDisconnect()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $pop3->disconnect();
        $pop3->disconnect();
    }

    public function testGetMessageNumbersFromSet()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $set = $pop3->fetchAll();
        $messageNumbers = $set->getMessageNumbers();
        $this->assertEquals( array( 1, 2, 3, 4 ), $messageNumbers );
    }

    public function testNoop()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $pop3->noop();
    }

    public function testNoopNotConnected()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->disconnect();
        try
        {
            $pop3->noop();
            $this->fail( "Expected exception was not thrown." );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testMessageSize()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $pop3->authenticate( "ezcomponents", "ezcomponents" );
        $set = $pop3->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $expected = array( 1542, '1539', '1383', '63913' );
        for ( $i = 0; $i < count( $mail ); $i++ )
        {
            $this->assertequals( $expected[$i], $mail[$i]->size );
        }
        $parts = $mail[3]->fetchParts();
        $this->assertEquals( '45177', $parts[1]->size );
    }

    public function testTransportProperties()
    {
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no" );
        $this->assertEquals( true, isset( $pop3->options ) );
        $this->assertEquals( false, isset( $pop3->no_such_property ) );

        $options = $pop3->options;
        $pop3->options = new ezcMailPop3TransportOptions();
        $this->assertEquals( $options, $pop3->options );
        $this->assertEquals( ezcMailPop3Transport::AUTH_PLAIN_TEXT, $pop3->options->authenticationMethod );
        $this->assertEquals( 5, $pop3->options->timeout );

        try
        {
            $pop3->options = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'xxx' that you were trying to assign to setting 'options' is invalid. Allowed values are: instanceof ezcMailPop3TransportOptions.", $e->getMessage() );
        }

        try
        {
            $pop3->no_such_property = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }

        try
        {
            $value = $pop3->no_such_property;
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }
    }

    public function testTransportPropertiesBefore()
    {
        $options = array( 'authenticationMethod' => ezcMailPop3Transport::AUTH_PLAIN_TEXT );
        $pop3 = new ezcMailPop3Transport( "dolly.ez.no", null, $options );
    }

    public function testTransportConnection()
    {
        $connection = new ezcMailTransportConnection( "dolly.ez.no", 143 );
        $expected = new ezcMailTransportOptions();
        $this->assertEquals( $expected, $connection->options );
    }

    public function testServerSSL()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped();
        }
        $pop3 = new ezcMailPop3Transport( "ezctest.ez.no", null, array( 'ssl' => true ) );
        $pop3->authenticate( "as", "wee123" );
        $set = $pop3->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $mail = $mail[0];
        $this->assertEquals( 240, $mail->size );
    }

    public function testServerSSLInvalidPort()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped();
        }
        try
        {
            $pop3 = new ezcMailPop3Transport( "ezctest.ez.no", 110, array( 'ssl' => true ) );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( 'An error occured while sending or receiving mail. Failed to connect to the server: ezctest.ez.no:110.', $e->getMessage() );
        }
    }

    public static function suite()
    {
        self::$ids = array( 508, 509, 510, 511 );
        for ( $i = 0; $i < count( self::$ids ); $i++ )
        {
            $messageNr = str_pad( sprintf( "%x", self::$ids[$i] ), 8, '0', STR_PAD_LEFT );
            self::$ids[$i] = "{$messageNr}4420e93a";
        }

        return new PHPUnit_Framework_TestSuite( "ezcMailTransportPop3Test" );
    }
}
?>

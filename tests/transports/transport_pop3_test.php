<?php
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

include_once( 'wrappers/pop3_wrapper.php' );

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportPop3Test extends ezcTestCase
{
    private static $ids = array();
    private static $sizes = array();

    private static $server = 'mta1.ez.no';
    private static $serverSSL = 'ezctest.ez.no';
    private static $port = 110;
    private static $portSSL = 955;
    private static $user = 'ezcomponents@mail.ez.no';
    private static $password = 'ezcomponents';
    private static $userSSL = 'as';
    private static $passwordSSL = 'wee123';

    public static function suite()
    {
        self::$ids = array( 23, 24, 25, 26 );
        self::$sizes = array( 1539, 64072, 1696, 1725 );

        for ( $i = 0; $i < count( self::$ids ); $i++ )
        {
            $messageNr = str_pad( sprintf( "%x", self::$ids[$i] ), 8, '0', STR_PAD_LEFT );
            self::$ids[$i] = "{$messageNr}468e011a";
        }

        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testWrapperMockConnectionConstructResponseNotOk()
    {
        try
        {
            $pop3 = $this->getMock( 'ezcMailPop3TransportWrapper', array( 'isPositiveResponse' ), array( self::$server, self::$port ) );
            $pop3->expects( $this->any() )
                 ->method( 'isPositiveResponse' )
                 ->will( $this->returnValue( false ) );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The connection to the POP3 server is ok, but a negative response from server was received: '+OK eZ.no'. Try again later.", str_replace( array( "\n", "\r" ), '', $e->getMessage() ) );
        }
    }

    public function testWrapperMockConnectionAuthenticateResponseNotOk()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( 'custom response' ) );
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );
        $pop3->setConnection( $connection );

        try
        {
            $pop3->authenticate( self::$user, self::$password );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The POP3 server did not accept the username: custom response.", $e->getMessage() );
        }
        $pop3->setStatus( ezcMailPop3Transport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionAuthenticateApopFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( 'custom response' ) );
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );
        $pop3->setConnection( $connection );
        $pop3->setGreeting( '+OK POP3 server ready <1896.697170952@dbc.mtview.ca.us>');

        try
        {
            $pop3->authenticate( self::$user, self::$password, ezcMailPop3Transport::AUTH_APOP );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The POP3 server did not accept the APOP login: custom response.", $e->getMessage() );
        }
        $pop3->setStatus( ezcMailPop3Transport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionAuthenticateApopOk()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( '+OK custom response' ) );
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );
        $pop3->setConnection( $connection );
        $pop3->setGreeting( '+OK POP3 server ready <1896.697170952@dbc.mtview.ca.us>');
        $pop3->authenticate( self::$user, self::$password, ezcMailPop3Transport::AUTH_APOP );
        $this->assertEquals( ezcMailPop3Transport::STATE_TRANSACTION, $pop3->getStatus() );
        $pop3->disconnect();
        $this->assertEquals( ezcMailPop3Transport::STATE_NOT_CONNECTED, $pop3->getStatus() );
    }

    public function testWrapperMockConnectionAuthenticateFailInvalidMethod()
    {
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );

        try
        {
            $pop3->authenticate( self::$user, self::$password, 'wrong method' );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. Invalid authentication method provided.", $e->getMessage() );
        }
        $pop3->setStatus( ezcMailPop3Transport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionDeleteOk()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( '+OK custom response' ) );
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );
        $pop3->setConnection( $connection );
        $pop3->authenticate( self::$user, self::$password );
        $this->assertEquals( ezcMailPop3Transport::STATE_TRANSACTION, $pop3->getStatus() );
        $pop3->delete( 1000 );
        $pop3->setStatus( ezcMailPop3Transport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionAuthenticateOkListMessagesFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( 'custom response' ) );
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );
        $pop3->authenticate( self::$user, self::$password );
        $this->assertEquals( ezcMailPop3Transport::STATE_TRANSACTION, $pop3->getStatus() );
        $pop3->setConnection( $connection );

        try
        {
            $pop3->listMessages();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The POP3 server sent a negative response to the LIST command: custom response.", $e->getMessage() );
        }
        $pop3->setStatus( ezcMailPop3Transport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionAuthenticateOkListUniqueIdentifiersFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( 'custom response' ) );
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );
        $pop3->authenticate( self::$user, self::$password );
        $this->assertEquals( ezcMailPop3Transport::STATE_TRANSACTION, $pop3->getStatus() );
        $pop3->setConnection( $connection );

        try
        {
            $pop3->listUniqueIdentifiers();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The POP3 server sent a negative response to the UIDL command: custom response.", $e->getMessage() );
        }
        $pop3->setStatus( ezcMailPop3Transport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionAuthenticateOkListUniqueIdentifiersSingleFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( 'custom response' ) );
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );
        $pop3->authenticate( self::$user, self::$password );
        $this->assertEquals( ezcMailPop3Transport::STATE_TRANSACTION, $pop3->getStatus() );
        $pop3->setConnection( $connection );

        try
        {
            $pop3->listUniqueIdentifiers( 1 );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The POP3 server sent a negative response to the UIDL command: custom response.", $e->getMessage() );
        }
        $pop3->setStatus( ezcMailPop3Transport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionAuthenticateOkStatusFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( 'custom response' ) );
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );
        $pop3->authenticate( self::$user, self::$password );
        $this->assertEquals( ezcMailPop3Transport::STATE_TRANSACTION, $pop3->getStatus() );
        $pop3->setConnection( $connection );

        try
        {
            $pop3->status( $numMessages, $sizeMessages );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The POP3 server did not respond with a status message: custom response.", $e->getMessage() );
        }
        $pop3->setStatus( ezcMailPop3Transport::STATE_NOT_CONNECTED );
    }

    public function testWrapperMockConnectionAuthenticateOkNoopFail()
    {
        $connection = $this->getMock( 'ezcMailTransportConnection', array(), array( self::$server, self::$port ) );
        $connection->expects( $this->any() )
                   ->method( 'getLine' )
                   ->will( $this->returnValue( 'custom response' ) );
        $pop3 = new ezcMailPop3TransportWrapper( self::$server, self::$port );
        $pop3->authenticate( self::$user, self::$password );
        $this->assertEquals( ezcMailPop3Transport::STATE_TRANSACTION, $pop3->getStatus() );
        $pop3->setConnection( $connection );

        try
        {
            $pop3->noop();
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( "An error occured while sending or receiving mail. The POP3 server sent a negative response to the NOOP command: custom response.", $e->getMessage() );
        }
        $pop3->setStatus( ezcMailPop3Transport::STATE_NOT_CONNECTED );
    }

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
            $pop3 = new ezcMailPop3Transport( self::$server );
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
            $pop3 = new ezcMailPop3Transport( self::$server );
            $pop3->authenticate( "ezcomponents", "no_such_password" );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallListMessages()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        try
        {
            $pop3->authenticate( self::$user, self::$password );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testInvalidCallListUniqueMessages()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $set = $pop3->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
    }

    public function testListMessages()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $list = $pop3->listMessages();
        $this->assertEquals( array( 1 => self::$sizes[0], 2 => self::$sizes[1], 3 => self::$sizes[2], 4 => self::$sizes[3] ), $list );
    }

    public function testFetchByMessageNr1()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $message = $pop3->fetchByMessageNr( 1 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $message );
        $this->assertEquals( 1, count( $mail ) );
        $this->assertEquals( array( 0 => '1' ), $this->readAttribute( $message, 'messages' ) );
        $this->assertEquals( 'ezcMailPop3Set', get_class( $message ) );
    }
    
    public function testfetchFromOffset1()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $set = $pop3->fetchFromOffset( 1, 4 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testfetchFromOffset5()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $set = $pop3->fetchFromOffset( 1, 0 );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 4, count( $mail ) );
        $this->assertEquals( "pine: Mail with attachment", $mail[1]->subject );
    }

    public function testStatus()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $pop3->status( $num, $size );
        $this->assertEquals( 4, $num );
        $this->assertEquals( self::$sizes[0] + self::$sizes[1] + self::$sizes[2] + self::$sizes[3], $size );
    }

    public function testTop()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $list = $pop3->top( 1, 1 );
        // we do a simple test here.. Any non-single line reply here is 99.9% certainly a good reply
        $this->assertEquals( true, count( explode( "\n", $list ) ) > 1 );
    }

    public function testInvalidTop()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $this->assertEquals( array( 1 => self::$ids[0] ), $pop3->listUniqueIdentifiers( 1 ) );
    }

    public function testListUniqueIdentifiersMultiple()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
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
            $pop3 = new ezcMailPop3Transport( self::$server );
            $pop3->authenticate( self::$user, self::$password, ezcMailPop3Transport::AUTH_APOP );
            $this->fail( "Did not get excepted exception" );
        }
        catch ( ezcMailTransportException $e )
        {
        }
    }

    public function testDisconnect()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $pop3->disconnect();
        $pop3->disconnect();
    }

    public function testGetMessageNumbersFromSet()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $set = $pop3->fetchAll();
        $messageNumbers = $set->getMessageNumbers();
        $this->assertEquals( array( 1, 2, 3, 4 ), $messageNumbers );
    }

    public function testNoop()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $pop3->noop();
    }

    public function testNoopNotConnected()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
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
        $pop3 = new ezcMailPop3Transport( self::$server );
        $pop3->authenticate( self::$user, self::$password );
        $set = $pop3->fetchAll();
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $expected = self::$sizes;
        for ( $i = 0; $i < count( $mail ); $i++ )
        {
            $this->assertequals( $expected[$i], $mail[$i]->size );
        }
        $parts = $mail[1]->fetchParts();
        $this->assertEquals( '45177', $parts[1]->size );
    }

    public function testTransportProperties()
    {
        $pop3 = new ezcMailPop3Transport( self::$server );
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
        $pop3 = new ezcMailPop3Transport( self::$server, null, $options );
    }

    public function testTransportConnection()
    {
        $connection = new ezcMailTransportConnection( self::$server, 143 );
        $expected = new ezcMailTransportOptions();
        $this->assertEquals( $expected, $connection->options );
    }

    public function testServerSSL()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'openssl' ) )
        {
            $this->markTestSkipped();
        }
        $pop3 = new ezcMailPop3Transport( self::$serverSSL, null, array( 'ssl' => true ) );
        $pop3->authenticate( self::$userSSL, self::$passwordSSL );
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
            $pop3 = new ezcMailPop3Transport( self::$serverSSL, self::$port, array( 'ssl' => true ) );
            $this->fail( "Didn't get exception when expected" );
        }
        catch ( ezcMailTransportException $e )
        {
            $this->assertEquals( 'An error occured while sending or receiving mail. Failed to connect to the server: ezctest.ez.no:110.', $e->getMessage() );
        }
    }

    public function testTransportConstructorOptions()
    {
        $options = new ezcMailPop3TransportOptions();
        $options->authenticationMethod = ezcMailPop3Transport::AUTH_APOP;
        $pop3 = new ezcMailPop3Transport( self::$server, self::$port, $options );

        $options = new stdClass();
        try
        {
            $pop3 = new ezcMailPop3Transport( self::$server, self::$port, $options );
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'O:8:\"stdClass\":0:{}' that you were trying to assign to setting 'options' is invalid. Allowed values are: ezcMailPop3TransportOptions|array.", $e->getMessage() );
        }
    }

    public function testTransportOptionsDefault()
    {
        $options = new ezcMailPop3TransportOptions();
        $this->assertEquals( ezcMailPop3Transport::AUTH_PLAIN_TEXT, $options->authenticationMethod );
    }

    public function testTransportOptionsSet()
    {
        $options = new ezcMailPop3TransportOptions();
        $options->authenticationMethod = ezcMailPop3Transport::AUTH_APOP;
        $this->assertEquals( ezcMailPop3Transport::AUTH_APOP, $options->authenticationMethod );
    }

    public function testTransportOptionsSetInvalid()
    {
        $options = new ezcMailPop3TransportOptions();
        try
        {
            $options->authenticationMethod = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public function testTransportOptionsSetNotExistent()
    {
        $options = new ezcMailPop3TransportOptions();
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

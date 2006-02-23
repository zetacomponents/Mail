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
class ezcMailToolsTest extends ezcTestCase
{

    /**
     * Tests if ezcMailTools::composeEmailAddress works as it should
     * @todo test if no 'email' is given.
     */
    public function testComposeEmailAddress()
    {
        $address = new ezcMailAddress( 'john@doe.com', 'John Doe' );
        $this->assertEquals( 'John Doe <john@doe.com>', ezcMailTools::composeEmailAddress( $address ) );

        $address = new ezcMailAddress( 'john@doe.com' );
        $this->assertEquals( 'john@doe.com', ezcMailTools::composeEmailAddress( $address ) );
    }

    /**
     * Tests if ezcMailTools::composeEmailAddresses works as it should
     * @todo test if no 'email' is given.
     */
    public function testComposeEmailAddresses()
    {
        $addresses = array( new ezcMailAddress( 'john@doe.com', 'John Doe' ),
                            new ezcMailAddress( 'debra@doe.com' ) );

        $this->assertEquals( 'John Doe <john@doe.com>, debra@doe.com',
                             ezcMailTools::composeEmailAddresses( $addresses ) );
    }

    public function testParseEmailAddresses()
    {
        $addresses = array( new ezcMailAddress( 'john@doe.com', 'John Doe' ),
                            new ezcMailAddress( 'debra@doe.com' ) );
        $this->assertEquals( $addresses,
                             ezcMailTools::parseEmailAddresses('John Doe <john@doe.com>, debra@doe.com' ) );
    }

    /**
     * Tests if generateContentId works as it should.
     * Somewhat hard to test since it is supposed to return a unique string.
     * We simply test if two calls return different strings.
     */
    public function testGenerateContentId()
    {
        if ( ezcMailTools::generateContentID() === ezcMailTools::generateContentID() )
        {
            $this->fail( "testGenerateMessageID generated the same ID twice" );
        }
    }

    /**
     * Tests if generateMessageId works as it should.
     * Somewhat hard to test since it is supposed to return a unique string.
     * We simply test if two calls return different strings.
     */
    public function testGenerateMessageId()
    {
        if ( ezcMailTools::generateMessageID( "doe.com" ) === ezcMailTools::generateMessageID( "doe.com") )
        {
            $this->fail( "testGenerateMessageID generated the same ID twice" );
        }
    }

    /**
     *
     */
    public function testEndline()
    {
        // defaul is \n\r as specified in RFC2045
        $this->assertEquals( "\r\n", ezcMailTools::lineBreak() );

        // now let's set it and check that it works
        ezcMailTools::setLineBreak( "\n" );
        $this->assertEquals( "\n", ezcMailTools::lineBreak() );
    }

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailToolsTest" );
    }
}

?>

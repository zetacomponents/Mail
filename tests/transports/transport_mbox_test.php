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
class ezcMailTransportMboxTest extends ezcTestCase
{
    public function testFetchMailFromBrokenMbox()
    {
        $set = new ezcMailMboxSet( fopen( dirname( __FILE__ ) . "/../parser/data/various/test-filename-with-space", "rt" ) );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 0, count( $mail ) );
    }

    public function testFetchMail()
    {
        $set = new ezcMailMboxSet( fopen( dirname( __FILE__ ) . "/data/test-mbox", "rt" ) );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 2, count( $mail ) );
    }

    public function testFetchMail2()
    {
        $set = new ezcMailMboxSet( fopen( dirname( __FILE__ ) . "/data/test-mbox-PGP", "rt" ) );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        $this->assertEquals( 1, count( $mail ) );
    }

    public function testFetchMail3()
    {
        $set = new ezcMailMboxSet( fopen( dirname( __FILE__ ) . "/data/test-mbox-russian", "rt" ) );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );

        $this->assertEquals( 1, count( $mail ) );

        //subject string should be the same as in email (with line break)
        $subject = "Re: =?koi8-r?b?7c7FIM7BxM8g1crUySDOwSDewdMg0yAxMi4wMCDQzw==?="."\n".
                   "    =?koi8-r?b?IM/Sx8HOydrBw8nPzs7ZzQ==?= =?koi8-r?b?INfP0NLP08HNLi4u?=";

        $this->assertEquals( $subject, $mail[0]->getHeader('Subject' ) );
    }

    public function testBrokenFilePointer()
    {
        try
        {
            $set = new ezcMailMboxSet( false );
            self::fail( "Expected exception not thrown" );
        }
        catch ( ezcBaseFileIoException $e )
        {
            self::assertEquals( "An error occurred while reading from <filepointer>. (The passed filepointer is not a stream resource.)", $e->getMessage() );
        }
    }

    public static function suite()
    {
         return new ezcTestSuite( "ezcMailTransportMboxTest" );
    }
}
?>

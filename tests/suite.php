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
 * Including the tests
 */
require_once( "mail_test.php" );
require_once( "composer_test.php" );
require_once( "interfaces/part_test.php" );
require_once( "parts/text_part_test.php" );
require_once( "parts/multipart_test.php" );
require_once( "parts/file_part_test.php" );
require_once( "parts/rfc822_digest_test.php" );
require_once( "tools_test.php" );
require_once( "transports/transport_smtp_test.php" );
require_once( "transports/transport_pop3_test.php" );
require_once( "transports/transport_mbox_test.php" );
require_once( "transports/transport_file_test.php" );
require_once( "tutorial_examples.php" );
require_once( "parser/parser_test.php" );
require_once( "parser/headers_holder_test.php" );
require_once( "parser/parts/multipart_mixed_test.php" );

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailSuite extends ezcTestSuite
{
	public function __construct()
	{
        parent::__construct();
        $this->setName("Mail");

		$this->addTest( ezcMailTest::suite() );
		$this->addTest( ezcMailComposerTest::suite() );
        $this->addTest( ezcMailPartTest::suite() );
        $this->addTest( ezcMailTextTest::suite() );
        $this->addTest( ezcMailMultiPartTest::suite() );
        $this->addTest( ezcMailFileTest::suite() );
        $this->addTest( ezcMailRfc822DigestTest::suite() );
        $this->addTest( ezcMailToolsTest::suite() );
        $this->addTest( ezcMailTransportSmtpTest::suite() );
        $this->addTest( ezcMailTransportPop3Test::suite() );
        $this->addTest( ezcMailTransportMboxTest::suite() );
        $this->addTest( ezcMailTransportFileTest::suite() );
        $this->addTest( ezcMailTutorialExamples::suite() );
        $this->addTest( ezcMailParserTest::suite() );
        $this->addTest( ezcMailHeadersHolderTest::suite() );
        $this->addTest( ezcMailMultipartMixedParserTest::suite() );
	}

    public static function suite()
    {
        return new ezcMailSuite();
    }
}

?>

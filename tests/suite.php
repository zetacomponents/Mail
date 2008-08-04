<?php
/**
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.5.1
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
require_once( "options/transport_options_test.php" );
require_once( "options/parser_options_test.php" );
require_once( "parts/text_part_test.php" );
require_once( "parts/multipart_test.php" );
require_once( "parts/multipart_digest_test.php" );
require_once( "parts/file_part_test.php" );
require_once( "parts/virtual_file_part_test.php" );
require_once( "parts/stream_file_part_test.php" );
require_once( "parts/rfc822_digest_test.php" );
require_once( "tools_test.php" );
require_once( "transports/transport_mta_test.php" );
require_once( "transports/transport_smtp_test.php" );
require_once( "transports/transport_smtp_auth_test.php" );
require_once( "transports/transport_pop3_test.php" );
require_once( "transports/transport_mbox_test.php" );
require_once( "transports/transport_file_test.php" );
require_once( "transports/transport_imap_test.php" );
require_once( "transports/transport_imap_uid_test.php" );
require_once( "transports/transport_storage_test.php" );
require_once( "transports/transport_variable_test.php" );
require_once( "tutorial_examples.php" );
require_once( "parser/parser_test.php" );
require_once( "parser/headers_holder_test.php" );
require_once( "parser/walk_context_test.php" );
require_once( "parser/parts/multipart_mixed_test.php" );
require_once( "parser/rfc2231_implementation_test.php" );
require_once( "header_folder_test.php" );

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailSuite extends PHPUnit_Framework_TestSuite
{
	public function __construct()
	{
        parent::__construct();
        $this->setName("Mail");

		$this->addTest( ezcMailTest::suite() );
		$this->addTest( ezcMailComposerTest::suite() );
        $this->addTest( ezcMailPartTest::suite() );
        $this->addTest( ezcMailTransportOptionsTest::suite() );
        $this->addTest( ezcMailParserOptionsTest::suite() );
        $this->addTest( ezcMailTextTest::suite() );
        $this->addTest( ezcMailMultiPartTest::suite() );
        $this->addTest( ezcMailFileTest::suite() );
        $this->addTest( ezcMailVirtualFileTest::suite() );
        $this->addTest( ezcMailStreamFileTest::suite() );
        $this->addTest( ezcMailRfc822DigestTest::suite() );
        $this->addTest( ezcMailMultipartDigestTest::suite() );
        $this->addTest( ezcMailToolsTest::suite() );
        $this->addTest( ezcMailTransportMtaTest::suite() );
        $this->addTest( ezcMailTransportSmtpTest::suite() );
        $this->addTest( ezcMailTransportSmtpAuthTest::suite() );
        $this->addTest( ezcMailTransportPop3Test::suite() );
        $this->addTest( ezcMailTransportImapTest::suite() );
        $this->addTest( ezcMailTransportImapUidTest::suite() );
        $this->addTest( ezcMailTransportMboxTest::suite() );
        $this->addTest( ezcMailTransportFileTest::suite() );
        $this->addTest( ezcMailTransportStorageTest::suite() );
        $this->addTest( ezcMailTransportVariableTest::suite() );
        $this->addTest( ezcMailTutorialExamples::suite() );
        $this->addTest( ezcMailParserTest::suite() );
        $this->addTest( ezcMailPartWalkContextTest::suite() );
        $this->addTest( ezcMailHeadersHolderTest::suite() );
        $this->addTest( ezcMailMultipartMixedParserTest::suite() );
        $this->addTest( ezcMailRfc2231ImplementationTest::suite() );
        $this->addTest( ezcMailHeaderFolderTest::suite() );
	}

    public static function suite()
    {
        return new ezcMailSuite();
    }
}

?>

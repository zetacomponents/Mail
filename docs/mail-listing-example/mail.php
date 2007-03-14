<?php
// Change this to where you keep the eZ Components
ini_set( 'include_path', '/home/as/dev/ezcomponents/trunk:.' );
require 'Base/src/base.php';

// Include the PagingLinks custom block
require 'app/paging_links.php';

// Required method to be able to use the eZ Components
function __autoload( $className )
{
        ezcBase::autoload( $className );
}

// Start counting how much the execution time will be
$start = microtime( true );

// Read configuration file app.ini with the Configuration component
$iniFile = 'app';
$config = ezcConfigurationManager::getInstance();
$config->init( 'ezcConfigurationIniReader', dirname( __FILE__ ) );
$options = array( 'templatePath' => dirname( __FILE__ ) . $config->getSetting( $iniFile, 'TemplateOptions', 'TemplatePath' ),
                  'compilePath' => dirname( __FILE__ ) . $config->getSetting( $iniFile, 'TemplateOptions', 'CompilePath' ),
                  'server' => $config->getSetting( $iniFile, 'MailOptions', 'Server' ),
                  'user' => $config->getSetting( $iniFile, 'MailOptions', 'User' ),
                  'password' => $config->getSetting( $iniFile, 'MailOptions', 'Password' ),
                  'mailbox' => isset( $_GET['mailbox'] ) ? $_GET['mailbox'] : $config->getSetting( $iniFile, 'MailOptions', 'Mailbox' ),
                  'pageSize' => $config->getSetting( $iniFile, 'MailOptions', 'PageSize' ),
                  'currentPage' => isset( $_GET['page'] ) ? $_GET['page'] : null
                  );

// Create a mail IMAP transport object
$transport = new ezcMailImapTransport( $options["server"] );
$transport->authenticate( $options["user"], $options["password"] );
$transport->selectMailbox( $options["mailbox"] );

// Get the mailboxes names from the server
$mailboxes = $transport->listMailboxes();
sort( $mailboxes );

// Get the UIDs of the messages in the selected mailbox
// and the sizes of the messages
$mailIDs = $transport->listUniqueIdentifiers();
$messages = $transport->listMessages();

// Calculate how many pages of mails there will be based on pageSize
$numberOfPages = (int) floor( count( $messages ) / $options["pageSize"] + 1 );

// See if currentPage fits in the range 1..numberOfPages
if ( $options["currentPage"] <= 0 || $options["currentPage"] > $numberOfPages ||
     ( count( $messages ) % $options["pageSize"] === 0 && $options["currentPage"] >= $numberOfPages ) )
{
    $options["currentPage"] = 1;
}

// Slice the array to the range defined by currentPage
$sizes = array_slice( array_values( $messages ), ( $options["currentPage"] - 1 ) * $options["pageSize"], $options["pageSize"] );
$mailIDs = array_slice( $mailIDs, ( $options["currentPage"] - 1 ) * $options["pageSize"], $options["pageSize"] );
$messages = array_keys( $messages );

// Read and parse the headers of the mails in the currentPage from the IMAP server
$mails = array();
$parser = new ezcMailParser();
for ( $i = ( $options["currentPage"] - 1 ) * $options["pageSize"]; $i < min( $options["currentPage"] * $options["pageSize"], count( $messages ) ); $i++ )
{
    $msg = $transport->top( $messages[$i] );
    $lines = preg_split( "/\r\n|\n/", $msg );
    $msg = null;
    foreach ( $lines as $line )
    {
        // eliminate the line that contains "Content-Type" at it would throw
        // a notice for "multipart/related" (because the multipart object cannot
        // be created due to missing the body)
        if ( stripos( $line, "Content-Type:" ) === false )
        {
            $msg .= $line . PHP_EOL;
        }
        else
        {
            // insert code to analyse the Content-Type of the mail
            // and add an "attachment" icon in case it is "multipart"
        }
    }
    $set = new ezcMailVariableSet( $msg );
    $mail = $parser->parseMail( $set );
    $mails[] = $mail[0];
}

// Create some debug information (how many miliseconds the parsing took)
$end = microtime( true );
$debug = sprintf( "Execution time (without template): %.0f ms", ( $end - $start ) * 1000 ) . "\n";

// Create a template configuration object based on $options
$templateConfig = ezcTemplateConfiguration::getInstance();
$templateConfig->templatePath = $options["templatePath"];
$templateConfig->compilePath = $options["compilePath"];
$templateConfig->context = new ezcTemplateXhtmlContext();
$templateConfig->addExtension( "PagingLinks" );

// Create a template object based on $templateConfig
$template = new ezcTemplate();
$template->configuration = $templateConfig;

// Assign the template variables with the script variables
$template->send->debug = $debug;
$template->send->mailbox = $options["mailbox"];
$template->send->mailboxes = $mailboxes;
$template->send->selected = $options["currentPage"];
$template->send->pageSize = $options["pageSize"];
$template->send->mailCount = count( $messages );
$template->send->numberOfPages = $numberOfPages;

// Create an array to be passed to the template, which holds the headers the mails
// in currentPage and other useful information like mail IDs
$mailListing = array();
for ( $i = 0; $i < count( $mails ); $i++ )
{
    $mailListing[$i] = array( 'number' => $messages[$i],
                              'id' => $mailIDs[$i],
                              'from' => $mails[$i]->from,
                              'subject' => $mails[$i]->subject,
                              'size' => $sizes[$i],
                              'received' => $mails[$i]->timestamp
                            );
}
$template->send->mails = $mailListing;

// Process the template
$template->process( "mail_listing.ezt" );

// Display the output of the template
echo $template->output;
?>

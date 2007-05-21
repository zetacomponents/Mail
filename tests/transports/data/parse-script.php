<?php
require dirname( __FILE__ ) . '/../../../../Base/src/base.php';
function __autoload( $className )
{
    ezcBase::autoload( $className );
}
$parser = new ezcMailParser();

$set = new ezcMailFileSet( array( 'php://stdin' ) );

$mail = $parser->parseMail( $set );

echo $mail[0]->from, "\n";
echo $mail[0]->subject, "\n";
?>

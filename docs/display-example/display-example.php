<?php
/**
 * You can run this file for example with:
 * php display-example.php ../../tests/parser/data/gmail/mail_with_attachment.mail
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 */

require_once "../tutorial/tutorial_autoload.php";

$set = new ezcMailFileSet( array( $argv[1] ) );
$parser = new ezcMailParser();
$mail = $parser->parseMail( $set );
echo formatMail( $mail[0] );

function formatMail( $mail )
{
    $t = '';
    $t .= "From:      ". formatAddress( $mail->from ). "\n";
    $t .= "To:        ". formatAddresses( $mail->to ). "\n";
    $t .= "Cc:        ". formatAddresses( $mail->cc ). "\n";
    $t .= "Bcc:       ". formatAddresses( $mail->bcc ). "\n";
    $t .= 'Date:      '. date( DATE_RFC822, $mail->timestamp ). "\n";
    $t .= 'Subject:   '. $mail->subject . "\n";
    $t .= "MessageId: ". $mail->messageId . "\n";
    $t .= "\n";
    $t .= formatMailPart( $mail->body );
    return $t;
}

function formatMailPart( $part )
{
    if ( $part instanceof ezcMail )
        return formatMail( $part );

    if ( $part instanceof ezcMailText )
        return formatMailText( $part );

    if ( $part instanceof ezcMailFile )
        return formatMailFile( $part );

    if ( $part instanceof ezcMailRfc822Digest )
        return formatMailRfc822Digest( $part );

    if ( $part instanceof ezcMailMultiPart )
        return formatMailMultipart( $part );

    die( "No clue about the ". get_class( $part ) . "\n" );
}

function formatMailMultipart( $part )
{
    if ( $part instanceof ezcMailMultiPartAlternative )
        return formatMailMultipartAlternative( $part );

    if ( $part instanceof ezcMailMultiPartDigest )
        return formatMailMultipartDigest( $part );

    if ( $part instanceof ezcMailMultiPartRelated )
        return formatMailMultipartRelated( $part );

    if ( $part instanceof ezcMailMultiPartMixed )
        return formatMailMultipartMixed( $part );

    die( "No clue about the ". get_class( $part ) . "\n" );
}

function formatMailMultipartMixed( $part )
{
    $t = '';
    foreach ( $part->getParts() as $key => $alternativePart )
    {
        $t .= "-MIXED-$key------------------------------------------------------------------\n";
        $t .= formatMailPart( $alternativePart );
    }
    $t .= "-MIXED END----------------------------------------------------------\n";
    return $t;
}

function formatMailMultipartRelated( $part )
{
    $t = '';
    $t .= "-RELATED MAIN PART-----------------------------------------------------------\n";
    $t .= formatMailPart( $part->getMainPart() );
    foreach ( $part->getRelatedParts() as $key => $alternativePart )
    {
        $t .= "-RELATED PART $key-----------------------------------------------------\n";
        $t .= formatMailPart( $alternativePart );
    }
    $t .= "-RELATED END--------------------------------------------------------\n";
    return $t;
}

function formatMailMultipartDigest( $part )
{
    $t = '';
    foreach ( $part->getParts() as $key => $alternativePart )
    {
        $t .= "-DIGEST-$key-----------------------------------------------------------------\n";
        $t .= formatMailPart( $alternativePart );
    }
    $t .= "-DIGEST END---------------------------------------------------------\n";
    return $t;
}

function formatMailRfc822Digest( $part )
{
    $t = '';
    $t .= "-DIGEST-ITEM-$key------------------------------------------------------------\n";
    $t .= "Item:\n\n";
    $t .= formatMailpart( $part->mail );
    $t .= "-DIGEST ITEM END----------------------------------------------------\n";
    return $t;
}

function formatMailMultipartAlternative( $part )
{
    $t = '';
    foreach ( $part->getParts() as $key => $alternativePart )
    {
        $t .= "-ALTERNATIVE ITEM $key-------------------------------------------------------\n";
        $t .= formatMailPart( $alternativePart );
    }
    $t .= "-ALTERNATIVE END----------------------------------------------------\n";
    return $t;
}

function formatMailText( $part )
{
    $t = '';
    $t .= "Original Charset: {$part->originalCharset}\n";
    $t .= "Charset:          {$part->charset}\n";
    $t .= "Encoding:         {$part->encoding}\n";
    $t .= "Type:             {$part->subType}\n";
    $t .= "\n{$part->text}\n";
    return $t;
}

function formatMailFile( $part )
{
    $t = '';
    $t .= "Disposition Type: {$part->dispositionType}\n";
    $t .= "Content Type:     {$part->contentType}\n";
    $t .= "Mime Type:        {$part->mimeType}\n";
    $t .= "Content ID:       {$part->contentId}\n";
    $t .= "Filename:         {$part->fileName}\n";
    $t .= "\n";
    return $t;
}

function formatAddresses( $addresses )
{
    $fa = array();
    foreach ( $addresses as $address )
    {
        $fa[] = formatAddress( $address );
    }
    return implode( ', ', $fa );
}

function formatAddress( $address )
{
    $name = '';
    if ( !empty( $address->name ) )
    {
        $name = "{$address->name} ";
    }
    return $name . "<{$address->email}>";    
}
?>

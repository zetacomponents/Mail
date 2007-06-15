<?php

class RFC822Digest extends ezcMailPart
{
    private $mail = null;
    public function __construct( ezcMail $mail )
    {
        $this->mail = $mail;
        $this->setHeader( 'Content-Type', 'message/rfc822' );
        $this->setHeader( 'Content-Disposition', 'inline' );
    }

    public function generateBody()
    {
        return $this->mail->generate();
    }
}

?>

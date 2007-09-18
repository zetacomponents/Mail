<?php
/**
 * File containing the ezcMailException class
 *
 * @package Mail
 * @version 1.4alpha1
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */
/**
 * ezcMailExceptions are thrown when an exceptional state
 * occures in the Mail package.
 *
 * @package Mail
 * @version 1.4alpha1
 */
class ezcMailException extends ezcBaseException
{
    /**
     * Constructs a new ezcMailException with error message $message.
     *
     * @param string $message
     */
    public function __construct( $message )
    {
        parent::__construct( $message );
    }
}
?>

<?php
/**
 * File containing the ezcMailParserShutdownHandler class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Removes temporary files and directories when PHP shuts down.
 *
 * @access private
 */
class ezcMailParserShutdownHandler
{
    /**
     * Holds if the handler is registered or not.
     *
     * @var boolean
     */
    private static $isRegistered = false;

    /**
     * Holds the array of directories that are marked for removal
     * when PHP shuts down.
     *
     * @var array(string)
     */
    private static $directories = array();

    /**
     * Registers the directory $dir for removal when PHP shuts down.
     *
     * The directory and all of its contents will be removed recursively.
     *
     * @param string $dir
     */
    public static function registerForRemoval( $dir )
    {
        if( self::$isRegistered === false )
        {
            register_shutdown_function( "ezcMailParserShutdownHandler::shutdownCallback" );
            self::$isRegistered = true;
        }
        self::$directories[] = $dir;
    }

    /**
     * Recursively deletes all registered folders and any contents of the registered
     * directories.
     *
     * Files or directories that can't be deleted are left without warning.
     *
     * @returns void
     */
    public static function shutdownCallback()
    {
        foreach( self::$directories as $directory )
        {
            self::remove( $directory );
        }
    }

    /**
     * Recursively removes a directory and its contents or files.
     *
     * Returns true on success and false on error.
     *
     * @param string $dir
     * @return bool
     */
    public static function remove( $itemName )
    {
        $returnVar = true;
        if( !is_dir( $itemName ) && file_exists( $itemName ) )
        {
            unlink( $itemName );
            return;
        }

        $dir = dir( $itemName );
        $item = $dir->read();
        while( $item !== false )
        {
            if( $item != '.' && $item != '..' )
            {
                self::remove( $dir->path . DIRECTORY_SEPARATOR . $item );
                $returnVar = false;
            }
            $item = $dir->read();
        }

        $dir->close();
        $returnVar = rmdir( $itemName ) && $returnVar ? true : false; // if rmdir succeeds and everything else succeeded
        return $returnVar;
    }
}

?>

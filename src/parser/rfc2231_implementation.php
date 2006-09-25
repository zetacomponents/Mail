<?php
/**
 * File containing the ezcMailRfc2231Implementation class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * This class parses header fields that conform to RFC2231.
 *
 * Headers confirming to this specification are Content-Type and Content-Disposition.
 *
 * @package Mail
 * @version //autogen//
 * @access private
 */
class ezcMailRfc2231Implementation
{
    /**
     * Returns the parsed header $header according to RFC 2231.
     *
     * This method returns the parsed header as a structured array and is
     * intended for internal usage. Use parseContentDisposition and
     * parseContentType to retrieve the correct header structs directly.
     *
     * @return array( 'argument', array( 'paramName' => array( value => string, charset => string,
     * language => string ) ) );
     */
    public static function parseHeader( $header )
    {
        $result = array();
        // argument
        if ( preg_match( '/^\s*([^;]*);?/i', $header, $matches ) )
        {
            $result[0] = $matches[1];
        }

        // We must go through all parameters and store this data because
        // parameters can be unordered. We will store them in this buffer
        // array( paramName => array( array( value => string, encoding ) ) )
        $parameterBuffer = array();

        // parameters
        if ( preg_match_all( '/\s*(\S*)="?([^;"]*);?/i', $header, $matches, PREG_SET_ORDER ) )
        {
            foreach( $matches as $parameter )
            {
                // if normal parameter, simply add it
                if ( !preg_match( '/([^\*]+)\*(\d+)?(\*)?/', $parameter[1], $metaData ) )
                {
                    $result[1][$parameter[1]] = array( 'value' => $parameter[2] );
                }
                else // coded and/or folded
                {
                    // metaData [1] holds the param name
                    // metaData [2] holds the count or is not set in case of charset only
                    // metaData [3] holds '*' if there is charset in addition to folding
                    if( isset( $metaData[2] ) ) // we have folding
                    {
                        $parameterBuffer[$metaData[1]][$metaData[2]]['value'] = $parameter[2];
                        $parameterBuffer[$metaData[1]][$metaData[2]]['encoding'] =
                            isset( $metaData[3] ) ? true : false;;
                    }
                    else
                    {
                        $parameterBuffer[$metaData[1]][0]['value'] = $parameter[2];
                        $parameterBuffer[$metaData[1]][0]['encoding'] = true;
                    }
                }
            }

            // whohooo... we have all the parameters nicely sorted.
            // Now we must go through them all and convert them into the end result
            foreach( $parameterBuffer as $paramName => $parts )
            {
                // fetch language and encoding if we have it
                // syntax: '[charset]'[language]'encoded_string
                $language = null;
                $charset = null;
                if( $parts[0]['encoding'] == true )
                {
                    preg_match( "/(\S*)'(\S*)'(.*)/", $parts[0]['value'], $matches );
                    $charset = $matches[1];
                    $language = $matches[2];
                    $parts[0]['value'] = $matches[3]; // rewrite value: todo: decoding
                    $result[1][$paramName] = array( 'value' => $parts[0]['value'] );
                }

                $result[1][$paramName] = array( 'value' => $parts[0]['value'] );
                if( strlen( $charset ) > 0 )
                {
                    $result[1][$paramName]['charset'] = $charset;
                }
                if( strlen( $language ) > 0 )
                {
                    $result[1][$paramName]['language'] = $language;
                }

                if( count( $parts > 1 ) )
                {
                    for( $i = 1; $i < count( $parts ); $i++ )
                    {
                        // TODO: encoding
                        $result[1][$paramName]['value'] .= $parts[$i]['value'];
                    }
                }
            }
        }
        return $result;
    }

    public static function parseContentDisposition( $header )
    {
    }

    public static function parseContentType( $header )
    {
    }
}
?>

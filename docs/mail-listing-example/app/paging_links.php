<?php
/**
 * Provides a custom block for templates used to insert page links for a provided mailbox.
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
class PagingLinks implements ezcTemplateCustomBlock
{
    /**
     * What characters to use to separate the page links (default: 1 - 2 - 3).
     */
    const DEFAULT_DELIMITER = '-';

    /**
     * Required method to implement.
     *
     * @param string $name
     * @return ezcTemplateCustomBlockDefinition|false
     */
    public static function getCustomBlockDefinition( $name )
    {
        switch ( $name )
        {
            case "paging_links":
                $def = new ezcTemplateCustomBlockDefinition();
                $def->class = __CLASS__;
                $def->method = "htmlPagingLinks";
                $def->hasCloseTag = false;
                $def->requiredParameters = array( "selected", "numberOfPages", "pagesize" );
                $def->optionalParameters = array( "delimiter", "mailbox" );
                return $def;
        }
        return false;
    }

    /**
     * Create a list of page links for a provided mailbox.
     *
     * @param array(string=>mixed) $params
     * @return string
     */
    public static function htmlPagingLinks( $params )
    {
        $selected = (int) $params["selected"];
        $numberOfPages = (int) $params["numberOfPages"];
        $pageSize = (int) $params["pagesize"];
        $delimiter = ( isset( $params["delimiter"] ) ) ? $params["delimiter"] : self::DEFAULT_DELIMITER;
        $mailbox = ( isset( $params["mailbox"] ) ) ? $params["mailbox"] : 'INBOX';

        $result = "";
        for ( $i = 1; $i <= $numberOfPages; $i++ )
        {
            if ( $selected === $i )
            {
                $result .= "{$i}";
            }
            else
            {
                $result .= "<a href=\"?mailbox={$mailbox}&page={$i}\">{$i}</a>";
            }
            if ( $i < $numberOfPages )
            {
                $result .= " {$delimiter} ";
            }
        }
        return $result;
    }
}
?>

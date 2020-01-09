<?php
/**
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
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version //autogentag//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportVariableTest extends ezcTestCase
{
    public static function suite()
    {
         return new PHPUnit\Framework\TestSuite( "ezcMailTransportVariableTest" );
    }

    public function testOneLine()
    {
        $reference = "Line1\n";
        $input = "Line1";
        $set = new ezcMailVariableSet( $input );
        $result = '';

        $line = $set->getNextLine();
        while ( $line !== null )
        {
            $result .= $line;
            $line = $set->getNextLine();
        }
        $this->assertEquals( $reference, $result );
        $this->assertEquals( false, $set->nextMail() );
    }

    public function testMultiLineCRLF()
    {
        $input = "Line1\r\nLine2";
        $reference = "Line1\nLine2\n";
        $set = new ezcMailVariableSet( $input );
        $result = '';

        $line = $set->getNextLine();
        while ( $line !== null )
        {
            $result .= $line;
            $line = $set->getNextLine();
        }
        $this->assertEquals( $reference, $result );
        $this->assertEquals( false, $set->nextMail() );
    }

    public function testMultiLineLF()
    {
        $reference = "Line1\nLine2\n";
        $input = "Line1\nLine2";
        $set = new ezcMailVariableSet( $input );
        $result = '';

        $line = $set->getNextLine();
        while ( $line !== null )
        {
            $result .= $line;
            $line = $set->getNextLine();
        }
        $this->assertEquals( $reference, $result );
        $this->assertEquals( false, $set->nextMail() );
    }

    public function testFromProcMail()
    {
        $mail_msg = file_get_contents( dirname( __FILE__ ) . '/data/test-variable' );
        $set = new ezcMailVariableSet( $mail_msg );
        $parser = new ezcMailParser();
        $mail = $parser->parseMail( $set );
        // check that we have no extra linebreaks
        $this->assertEquals( "notdisclosed@mydomain.com", $mail[0]->from->email );
    }
}
?>

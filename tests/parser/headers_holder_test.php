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
class ezcMailHeadersHolderTest extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit\Framework\TestSuite( "ezcMailHeadersHolderTest" );
    }

    public function testSet()
    {
        $reference = array( 'Subject' => 2, 'tO' => 3 , 'trimTest' => 'foo', 'trimTestArray' => array('foo'));
        $map = new ezcMailHeadersHolder();
        $map['Subject'] = 1;
        $map['suBject'] = 2;
        $map['tO'] = 3;
        $map['trimTest'] = ' foo ';
        $map['trimTestArray'] = array(' foo ');
        $this->assertEquals( $reference, $map->getCaseSensitiveArray() );
    }

    public function testGet()
    {
        $map = new ezcMailHeadersHolder();
        $map['Subject'] = 1;
        $map['suBject'] = 2;
        $this->assertEquals( 2, $map['subject'] );
    }

    public function testGetEmpty()
    {
        $map = new ezcMailHeadersHolder();
        $this->assertEquals( null, $map['subject'] );
    }


    public function testUnset()
    {
        $reference = array();
        $map = new ezcMailHeadersHolder();
        $map['Subject'] = 1;
        $map['suBject'] = 2;
        unset( $map['subject'] );
        $this->assertEquals( $reference, $map->getCaseSensitiveArray() );
    }

    public function testKeyExists()
    {
        $reference = array( 'Subject' => 2, 'tO' => 3 );
        $map = new ezcMailHeadersHolder();
        $map['Subject'] = 1;
        $this->assertEquals( false, isset( $map['Muha'] ) );
        $this->assertEquals( false, isset( $map['Muha'] ) ); // check that checking for not-set does not set it
        $this->assertEquals( true, isset( $map['subject'] ) );
    }
}

?>

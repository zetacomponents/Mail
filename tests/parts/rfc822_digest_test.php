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

// special mail class which generates to "headers" and "body" only.
class DigestTestMail extends ezcMail
{
    public function generateHeaders()
    {
        return 'headers';
    }

    public function generateBody()
    {
        return 'body';
    }
}

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailRfc822DigestTest extends ezcTestCase
{
    public function testDefault()
    {
        $digest = new ezcMailRfc822Digest( new DigestTestMail() );
//        file_put_contents( dirname( __FILE__ ) . "/data/ezcMailRfc822DigestTest_testDefault.data", $digest->generate() );
        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . "/data/ezcMailRfc822DigestTest_testDefault.data" ),
                             $digest->generate() );
    }

    public function testProperties()
    {
        $digest = new ezcMailRfc822Digest( new DigestTestMail() );
        try
        {
            $digest->no_such_property = 'xxx';
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    public function testIsSet()
    {
        $digest = new ezcMailRfc822Digest( new DigestTestMail() );
        $this->assertEquals( true, isset( $digest->mail ) );
        $this->assertEquals( false, isset( $digest->no_such_property ) );
    }

    public static function suite()
    {
         return new PHPUnit\Framework\TestSuite( "ezcMailRfc822DigestTest" );
    }
}
?>

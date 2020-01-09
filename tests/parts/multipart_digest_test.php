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

// Needed for static boundary string...
class MultipartDigestTestClass extends ezcMailMultipartDigest
{
    protected static function generateBoundary()
    {
        return "boundaryString";
    }
}

// special mail class which generates to "headers" and "body" only.
class MultipartDigestTestMail extends ezcMail
{
    private $num;

    public function __construct( $num )
    {
        $this->num = $num;
    }

    public function generateHeaders()
    {
        return "headers-{$this->num}";
    }

    public function generateBody()
    {
        return "body-{$this->num}";
    }
}

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailMultipartDigestTest extends ezcTestCase
{
    // single ezcMail
    public function testSingleEzcMail()
    {
        $digest = new MultipartDigestTestMail( 1 );
        $multipart = new MultipartDigestTestClass( $digest );
        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . "/data/ezcMailMultipartDigestTest_singleMail.data" ),
                             $multipart->generate() );
    }

    // single ezcMailRfc822Digest
    public function testSingleEzcRfc822DigestMail()
    {
        $digest = new ezcMailRfc822Digest( new MultipartDigestTestMail( 1 ) );
        $multipart = new MultipartDigestTestClass( $digest );
        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . "/data/ezcMailMultipartDigestTest_singleMail.data" ),
                             $multipart->generate() );
    }

    // multiple array ezcMail and ezcMailRfc822Digest objects
    public function testMultipleDigestObjects()
    {
        $mail1 = new MultipartDigestTestMail( 1 );
        $mail2 = new ezcMailRfc822Digest( new MultipartDigestTestMail( 2 ) );
        $mail34 = array( new ezcMailRfc822Digest( new MultipartDigestTestMail( 3 ) ), new MultipartDigestTestMail( 4 ) );
        $multipart = new MultipartDigestTestClass( $mail1, $mail2, $mail34 );
//        file_put_contents( dirname( __FILE__ ) . "/data/ezcMailMultipartDigestTest_multiple_digest.data", $multipart->generate() );
        $this->assertEquals( file_get_contents( dirname( __FILE__ ) . "/data/ezcMailMultipartDigestTest_multiple_digest.data" ),
                             $multipart->generate() );
    }

    public static function suite()
    {
         return new PHPUnit\Framework\TestSuite( "ezcMailMultipartDigestTest" );
    }
}
?>

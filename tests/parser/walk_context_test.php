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
class ezcMailPartWalkContextTest extends ezcTestCase
{
    public function testProperties()
    {
        $context = new ezcMailPartWalkContext( array( 'WalkContextTestApp', 'saveMailPart' ) );
        $context->includeDigests = true;
        $context->level = 3;
        $context->filter = array( 'ezcMailFile' );
        $this->assertEquals( true, $context->includeDigests );
        $this->assertEquals( 3, $context->level );
        $this->assertEquals( array( 'ezcMailFile' ), $context->filter );
    }

    public function testPropertiesInvalid()
    {
        $context = new ezcMailPartWalkContext( array( 'WalkContextTestApp', 'saveMailPart' ) );
        try
        {
            $context->no_such_property = true;
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }

        try
        {
            $context->level = -1;
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $context->includeDigests = "yes";
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $test = $context->no_such_property;
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    public function testIsSet()
    {
        $context = new ezcMailPartWalkContext( array( 'WalkContextTestApp', 'saveMailPart' ) );
        $this->assertEquals( true, isset( $context->includeDigests ) );
        $this->assertEquals( true, isset( $context->filter ) );
        $this->assertEquals( true, isset( $context->level ) );
        $this->assertEquals( true, isset( $context->callbackFunction ) );
        $this->assertEquals( false, isset( $context->no_such_property ) );
    }

    public function testWalkPartsForVirtualFile()
    {
        // create mail instance
        $mail = new ezcMailComposer();
        $mail->addAttachment("file.txt", "content");
        $mail->build();
        // create the testing context
        $context = new ezcMailPartWalkContext(
            function ($ctx, $part) {
                $this->assertInstanceOf('ezcMailVirtualFile', $part);
            }
        );
        $context->filter = ['ezcMailVirtualFile'];
        // test it
        $mail->walkParts($context, $mail);
    }

    public function testWalkPartsForStreamFile()
    {
        // create a temporary file
        $tmpFile = tempnam(sys_get_temp_dir(), "stream");
        file_put_contents($tmpFile, "content");
        // create mail instance
        $mail = new ezcMailComposer();
        $mail->addAttachment($tmpFile);
        $mail->build();
        // create the testing context
        $context = new ezcMailPartWalkContext(
            function ($ctx, $part) {
                $this->assertInstanceOf('ezcMailStreamFile', $part);
            }
        );
        $context->filter = ['ezcMailStreamFile'];
        // test it
        $mail->walkParts($context, $mail);
        // remove the temporary file
        @unlink($tmpFile);
    }

    public static function suite()
    {
         return new PHPUnit\Framework\TestSuite( "ezcMailPartWalkContextTest" );
    }
}

/**
 * Test class.
 */
class WalkContextTestApp
{
    public static function saveMailPart()
    {
    }
}
?>

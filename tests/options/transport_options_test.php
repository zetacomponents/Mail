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
 * @version //autogen//
 * @filesource
 * @package Mail
 * @subpackage Tests
 */

/**
 * @package Mail
 * @subpackage Tests
 */
class ezcMailTransportOptionsTest extends ezcTestCase
{
    public function testTransportOptionsDefault()
    {
        $options = new ezcMailTransportOptions();
        $this->assertEquals( 5, $options->timeout );
        $this->assertEquals( false, $options->ssl );
    }

    public function testTransportOptionsSet()
    {
        $options = new ezcMailTransportOptions();
        $options->timeout = 10;
        $options->ssl = true;
        $this->assertEquals( 10, $options->timeout );
        $this->assertEquals( true, $options->ssl );
    }

    public function testTransportOptionsSetInvalid()
    {
        $options = new ezcMailTransportOptions();
        try
        {
            $options->timeout = 0;
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $options->timeout = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }

        try
        {
            $options->ssl = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public function testTransportOptionsSetNotExistent()
    {
        $options = new ezcMailTransportOptions();
        try
        {
            $options->no_such_option = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
        }
    }

    public static function suite()
    {
         return new PHPUnit\Framework\TestSuite( "ezcMailTransportOptionsTest" );
    }
}
?>

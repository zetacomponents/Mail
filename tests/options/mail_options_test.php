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
class ezcMailOptionsTest extends ezcTestCase
{
    public function testMailOptionsDefault()
    {
        $options = new ezcMailOptions();
        $this->assertEquals( false, $options->stripBccHeader );
    }

    public function testMailOptionsSet()
    {
        $options = new ezcMailOptions();
        $options->stripBccHeader = true;
        $this->assertEquals( true, $options->stripBccHeader );
    }

    public function testMailOptionsSetInvalid()
    {
        $options = new ezcMailOptions();
        try
        {
            $options->stripBccHeader = 'xxx';
            $this->fail( "Expected exception was not thrown" );
        }
        catch ( ezcBaseValueException $e )
        {
        }
    }

    public function testMailOptionsSetNotExistent()
    {
        $options = new ezcMailOptions();
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
         return new PHPUnit\Framework\TestSuite( __CLASS__ );
    }
}
?>

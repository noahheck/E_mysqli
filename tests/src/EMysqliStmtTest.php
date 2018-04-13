<?php
/**
 * Copyright 2017 github.com/noahheck
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace EMysqli\Test;

use PHPUnit_Framework_TestCase;

use EMysqli\EMysqli;

class EMysqliStmtTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var EMysqli
     */
    protected $eMysqli;

    /**
     * @return array
     */
    protected function getConfig()
    {
        return require dirname(__FILE__) . "/../config/config.php";
    }

    /**
     * Returns an instance of the EMysqli object - if this is the first time asking for it, a new instance is created
     * and cached to be returned on subsequent calls
     *
     * @return EMysqli
     */
    protected function getEMysqli()
    {
        if ($this->eMysqli) {
            return $this->eMysqli;
        }

        $config = $this->getConfig();

        $host     = ($config['db']['host'])     ? $config['db']['host']     : null;
        $username = ($config['db']['username']) ? $config['db']['username'] : null;
        $password = ($config['db']['password']) ? $config['db']['password'] : null;
        $dbname   = ($config['db']['dbname'])   ? $config['db']['dbname']   : null;
        $port     = ($config['db']['port'])     ? $config['db']['port']     : null;
        $socket   = ($config['db']['socket'])   ? $config['db']['socket']   : null;

        $this->eMysqli = new EMysqli($host, $username, $password, $dbname, $port, $socket);

        return $this->eMysqli;
    }

    protected function getPreparedStatement($query)
    {
        $db = $this->getEMysqli();

        return $db->prepare($query);
    }



    public function testQuotedStringValuesGetInterpolatedIntoQueryWhenBoundAsAGroup()
    {
        $query = "INSERT INTO contacts SET first_name = ?, last_name = ?";

        $firstName = "Noah";
        $lastName  = "Heck";

        $expected = "INSERT INTO contacts SET first_name = 'Noah', last_name = 'Heck'";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("ss", $firstName, $lastName);

        $result = $stmt->interpolateQuery();

        $this->assertEquals($expected, $result);
    }



    public function testQuotedStringValuesGetInterpolatedIntoQueryWhenBoundIndividually()
    {
        $query = "INSERT INTO contacts SET first_name = ?, last_name = ?";

        $firstName = "Noah";
        $lastName  = "Heck";

        $expected = "INSERT INTO contacts SET first_name = 'Noah', last_name = 'Heck'";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("s", $firstName);
        $stmt->bind_param("s", $lastName);

        $result = $stmt->interpolateQuery();

        $this->assertEquals($expected, $result);
    }



    public function testUnquotedIntegerValuesGetInterpolatedIntoQueryWhenBoundAsAGroup()
    {
        $query = "SELECT * FROM contacts WHERE contacts_id IN (?, ?)";

        $contactId1 = 1;
        $contactId2 = 2;

        $expected = "SELECT * FROM contacts WHERE contacts_id IN (1, 2)";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("ii", $contactId1, $contactId2);

        $result = $stmt->interpolateQuery();

        $this->assertEquals($expected, $result);
    }



    public function testUnquotedIntegerValuesGetInterpolatedIntoQueryWhenBoundIndividually()
    {
        $query = "SELECT * FROM contacts WHERE contacts_id IN (?, ?)";

        $contactId1 = 1;
        $contactId2 = 2;

        $expected = "SELECT * FROM contacts WHERE contacts_id IN (1, 2)";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("i", $contactId1);
        $stmt->bind_param("i", $contactId2);

        $result = $stmt->interpolateQuery();

        $this->assertEquals($expected, $result);
    }



    public function testValuesGetInterpolatedIntoQueryAndQuotedCorrectlyWhenMixedArgumentsBoundAsAGroup()
    {
        $query = "SELECT * FROM contacts WHERE contacts_id = ? OR first_name = ?";

        $contactId = 1;
        $firstName = "Noah";

        $expected = "SELECT * FROM contacts WHERE contacts_id = 1 OR first_name = 'Noah'";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("is", $contactId, $firstName);

        $result = $stmt->interpolateQuery();

        $this->assertEquals($expected, $result);
    }



    public function testValuesGetInterpolatedIntoQueryAndQuotedCorrectlyWhenMixedArgumentsBoundIndividually()
    {
        $query = "SELECT * FROM contacts WHERE contacts_id = ? OR first_name = ?";

        $contactId = 1;
        $firstName = "Noah";

        $expected = "SELECT * FROM contacts WHERE contacts_id = 1 OR first_name = 'Noah'";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("i", $contactId);
        $stmt->bind_param("s", $firstName);

        $result = $stmt->interpolateQuery();

        $this->assertEquals($expected, $result);
    }



    public function testInterpolationAllowsSuccessfulExecutionOfQueries()
    {
        $query = "SELECT ? + ? + ?, ?";

        $int1   = 1;
        $int2   = 1;
        $int3   = 1;
        $string = "Some String";

        $expected = "SELECT 1 + 1 + 1, 'Some String'";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("iiis", $int1, $int2, $int3, $string);

        $stmt->execute();

        $this->assertEquals($expected, $stmt->fullQuery);

        $stmt->bind_result($three, $someString);

        $stmt->fetch();

        $this->assertEquals("3", $three);
        $this->assertEquals($string, $someString);
    }



    public function testInterpolationSucceedsEvenWhenReplacementValueContainsAPlaceholderCharacter()
    {
        $query = "INSERT INTO notes SET note = ?, contact_id = ?";

        $note      = "Some string that contains a ?";
        $contactId = 1;

        $expected = "INSERT INTO notes SET note = 'Some string that contains a ?', contact_id = 1";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("si", $note, $contactId);

        $result = $stmt->interpolateQuery();

        $this->assertEquals($expected, $result);
    }



    public function testInterpolationSucceedsEvenWhenReplacementValueContainsAPlaceholderCharacterAndQuerySpansMultipleLines()
    {
        $query = "INSERT INTO notes SET note = ?, contact_id = ?";

        $note      = "Some string that contains a ?
                        or two ? and spans multiple lines";
        $contactId = 1;

        $expected = "INSERT INTO notes SET note = 'Some string that contains a ?
                        or two ? and spans multiple lines', contact_id = 1";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("si", $note, $contactId);

        $result = $stmt->interpolateQuery();

        /**
         * The str_replace is here because mysqli::real_escape_string escapes \n characters as well, and this makes the
         * test more readable (?)
         */
        $this->assertEquals($expected, str_replace("\\n", "\n", $result));
    }



    public function testQueryIsNotChangedIfNoParametersUsedInQuery()
    {
        $query = "INSERT INTO contacts SET first_name = 'Noah', last_name = 'Heck'";

        $stmt = $this->getPreparedStatement($query);

        $this->assertEquals($query, $stmt->interpolateQuery());
    }



    public function testNullValuesAreInterpolatedIntoQuerySuccessfullyAsDBNullValues()
    {
        $query = "INSERT INTO contacts SET first_name = ?, last_name = ?, email = ?";

        $firstName = "Noah";
        $lastName  = null;
        $email     = "";

        $expected = "INSERT INTO contacts SET first_name = 'Noah', last_name = NULL, email = ''";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("sss", $firstName, $lastName, $email);

        $result = $stmt->interpolateQuery();

        $this->assertEquals($expected, $result);
    }

    public function testDollarSignBackReferenceSyntaxGetsOutputCorrectlyInFullQuery()
    {
        $hashedPassword = '$2y$10$yOwqQMxRo0AveSZ6I6Yhn.aMqbtGYrKQvcLGEtanplhdboUM1ffGi';

        $query = "UPDATE users SET password = ?";

        $stmt = $this->getPreparedStatement($query);

        $stmt->bind_param("s", $hashedPassword);

        $result = $stmt->interpolateQuery();

        $this->assertEquals("UPDATE users SET password = '$hashedPassword'", $result);
    }
}

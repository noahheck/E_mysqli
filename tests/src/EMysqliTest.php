<?php
/**
 * Copyright 2016 github.com/noahheck
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
use EMysqli\EMysqliStmt;

class EMysqliTest extends PHPUnit_Framework_TestCase
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
			return $this->getEMysqli;
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

	public function testPrepareReturnsInstanceOfEMysqliStmt()
	{
		$db = $this->getEMysqli();

		$query = "SELECT 2";

		$stmt = $db->prepare($query);

		$this->assertTrue($stmt instanceof EMysqliStmt);
	}
}

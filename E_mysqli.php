<?php
/**
 * Copyright 2014 github.com/noahheck
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

class E_mysqli extends \mysqli
{
	/**
	 * We just want to overwrite the default prepare method to return our own mysqli_stmt class
	 * @param 	string $query - the query string
	 * @return 	E_mysqli_stmt
	 */
	public function prepare($query)
	{
		return new E_mysqli_stmt($this, $query);
	}
}

class E_mysqli_stmt extends \mysqli_stmt
{
	/**
	 * @var E_mysqli $mysqli
	 */
	private $mysqli;

	/**
	 * @var string $queryString - the unchanged query string provided to the constructor
	 */
	public $queryString;

	/**
	 * @var string $fullQuery - the query string with bound parameters interpolated
	 */
	public $fullQuery;

	/**
	 * @var array $boundParams - array of arrays containing values that have been bound to the query as parameters
	 */
	public $boundParams = array();

	/**
	 * The first argument should be an instance of mysqli or descendent. If so, we'll cache it's reference locally to be
	 * able to escape the parameters later. Also, we accept and store the query string.
	 * @param mysqli $mysqli
	 * @param string $query
	 */
	public function __construct(mysqli $mysqli, $query)
	{
		$this->mysqli 		= $mysqli;
		$this->queryString 	= $query;
		parent::__construct($mysqli, $query);
	}

	/**
	 * We overwrite the default bind_param method so we can cache the parameters for our needs later. We also extend
	 * the method to allow passing in $value arguments as an array. If $values are passed as an array, or if we are 
	 * supplied only the $type and $value parameters, we are able to use those parameters as references to their runtime
	 * variable names. If a variable number of arguments is provided, we don't have a way (yet; php 5.6 addresses this)
	 * to get the parameters as references.
	 * @param string $type
	 * @param mixed $value
	 */
	public function bind_param($type, &$value)
	{
		$numArgs = func_num_args();

		/**
		 * We do our best to support variable numbers of function arguments. This is the best we can do. Variables will
		 * not be passed by reference though (php does not support this until 5.6 [using ...], which is still in beta).
		 */
		if ($numArgs > 2)
		{
			$args 	= func_get_args();

			$type 	= $args[0];

			$value 	= array();

			for ($x = 1; $x < $numArgs; $x++)
			{
				$value[] = &$args[$x];
			}
		}
		
		/**
		 * If $value is an array (either because it was passed that way or we generated one from the list of variable
		 * parameters), we store an instance of each, along with that values bound parameter type. Otherwise, single
		 * parameters are stored referencing the passed values.
		 */
		if (is_array($value))
		{
			$types 	= str_split($type);

			$arg 	= 0;

			foreach ($types as $type)
			{
				$val 	= &$value[$arg];
				$this->boundParams[] = array(
					  "type" 	=> $type
					, "value" 	=> &$val
				);

				$arg++;
			}
		}
		else
		{
			$this->boundParams[] = array(
				  "type" 	=> $type
				, "value" 	=> &$value
			);
		}
		
		return true;
	}

	/**
	 * We interpolate our values into the fullQuery variable, then call the parent::bind_param so the values are
	 * actually sent to the db server. Then we execute the query statement.
	 * @return parent::execute response
	 */
	public function execute()
	{
		$this->interpolateQuery();

		$params 	= $this->_buildArguments();

		call_user_func_array(array('parent', "bind_param"), $params);

		return parent::execute();
	}

	/**
	 * Copies $this->queryString then replaces bound markers with associated values ($this->queryString is not modified
	 * but the resulting query string is assigned to $this->fullQuery)
	 * @return str $testQuery - interpolated db query string
	 */
	public function interpolateQuery()
	{
		$testQuery 	= $this->queryString;

		if ($this->boundParams)
		{
			foreach ($this->boundParams as $param)
			{
				$type 	= $param['type'];
				$value 	= $param['value'];

				$replValue 	= $this->_prepareValue($value, $type);

				$testQuery 	= preg_replace("/\?/", $replValue, $testQuery, 1);
			}
		}

		$this->fullQuery = $testQuery;

		return $testQuery;
	}

	/**
	 * Combines the values stored in $this->boundParams into one array suitable for pushing as the input arguments to
	 * parent::bind_param when used with call_user_func_array
	 * @return array
	 */
	private function _buildArguments()
	{
		$arguments 		= array();
		$arguments[0] 	= "";
		
		foreach ($this->boundParams as $param)
		{
			$arguments[0] 	.= $param['type'];
			$arguments[] 	= &$param['value'];
		}

		return $arguments;
	}

	/**
	 * Escapes the supplied value according to mysqli::real_escape_string which we should have cached a reference to an
	 * active connection at construct. The result of that is wrapped in apostrophes as well if the bound parameter is
	 * not identified as an integer. We've also provided an unsafe method for escaping the value in case our object is
	 * modified to not have reference to a mysqli, but hopefully that won't be the case.
	 * 
	 *  	addslashes is not suitable for production logging, etc. You can update this method to perform the necessary
	 * 		escaping translations for your database driver. Please consider updating your processes to provide a valid
	 * 		PDO object that can perform the necessary translations and can be updated with your i.e. package management,
	 * 		PEAR updates, etc.
	 * 
	 * @param mixed $value
	 * @param string $type (one of 'i', 'b', 's', 'd')
	 * @return string $value escaped for insertion into the interpolated query string
	 */
	private function _prepareValue($value, $type)
	{
		if ($this->mysqli)
		{
			$value = $this->mysqli->real_escape_string($value);

			if ('i' !== $type)
			{
				$value = "'" . $value . "'";
			}
		}
		else
		{
			$value = "'" . addslashes($value) . "'";
		}

		return $value;
	}
}

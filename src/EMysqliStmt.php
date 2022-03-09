<?php
/**
 * Copyright 2018 github.com/noahheck
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

namespace EMysqli;

use mysqli;
use mysqli_stmt;

class EMysqliStmt extends mysqli_stmt
{
    /**
     * @var EMysqli
     */
    private $mysqli;

    /**
     * @var string - the unchanged query string provided to the constructor
     */
    private $queryString;

    /**
     * @var array - array of arrays containing values that have been bound to the query as parameters
     */
    private $boundParams = array();

    /**
     * @var string - the query string with bound parameters interpolated
     */
    public $fullQuery;

    /**
     * The first argument should be an instance of mysqli or descendent. If so, we'll cache it's reference locally to be
     * able to escape the parameters later. Also, we accept and store the query string.
     * @param mysqli $mysqli
     * @param string $query
     */
    public function __construct(mysqli $mysqli, $query)
    {
        $this->mysqli      = $mysqli;
        $this->queryString = $query;
        
        parent::__construct($mysqli, $query);
    }

    /**
     * We overwrite the default bind_param method so we can cache the parameters for our needs later. We also extend
     * the method to allow binding parameters
     *
     * @param string $type
     * @param mixed $vars
     *
     * @return bool
     */
    public function bind_param($type, &...$vars) : bool
    {
        if ($vars) {
            $types = str_split($type);
            $arg   = 0;

            foreach ($types as $type) {

                $this->boundParams[] = [
                    "type"  => $type,
                    "value" => &$vars[$arg],
                ];

                $arg++;
            }
        }

        return true;
    }

    /**
     * We interpolate our values into the fullQuery variable, then call the parent::bind_param so the values are
     * actually sent to the db server. Then we execute the query statement.
     *
     * @return bool
     */
    public function execute(?array $params = null) : bool
    {
        $this->interpolateQuery();

        $params = $this->buildArguments();

        if (count($params) > 1) {
            call_user_func_array(array('parent', "bind_param"), $params);
        }

        return parent::execute();
    }

    /**
     * Copies $this->queryString then replaces bound markers with associated values ($this->queryString is not modified
     * but the resulting query string is assigned to $this->fullQuery)
     *
     * The regex here was modified from this StackOverflow answer: http://stackoverflow.com/a/632552/2422852
     *
     * @return string $testQuery - interpolated db query string
     */
    public function interpolateQuery()
    {
        $testQuery 	= $this->queryString;

        // Back references may be replaced in the resultant interpolatedQuery, so we need to sanitize that syntax -
        // This is the character map to sanitize the back reference syntax
        $cleanBackRefCharMap = ['%'=>'%%', '$'=>'$%', '\\'=>'\\%'];

        $pattern = "/(\?)(?=(?:[^\"']|[\"'][^\"']*[\"'])*$)/";

        if ($this->boundParams) {

            foreach ($this->boundParams as $key => $param) {
                $type  = $param['type'];
                $value = $param['value'];

                $replValue = $this->prepareValue($value, $type);

                $backReferenceSafeReplValue = strtr($replValue, $cleanBackRefCharMap);

                $testQuery = preg_replace($pattern, $backReferenceSafeReplValue, $testQuery, 1);
            }
        }

        $testQuery = strtr($testQuery, array_flip($cleanBackRefCharMap));

        $this->fullQuery = $testQuery;

        return $testQuery;
    }



    /**
     * Combines the values stored in $this->boundParams into one array suitable for pushing as the input arguments to
     * parent::bind_param when used with call_user_func_array
     * @return array
     */
    private function buildArguments()
    {
        $arguments    = array();
        $arguments[0] = "";

        foreach ($this->boundParams as $param) {
            $arguments[0] .= $param['type'];
            $arguments[]   = &$param['value'];
        }

        return $arguments;
    }

    /**
     * Escapes the supplied value according to mysqli::real_escape_string which we should have cached a reference to an
     * active connection at construct. The result of that is wrapped in apostrophes as well if the bound parameter is
     * not identified as an integer.
     *
     * @param mixed $value
     * @param string $type (one of 'i', 'b', 's', 'd')
     * @return string $value escaped for insertion into the interpolated query string
     */
    private function prepareValue($value, $type)
    {
        if ($value === NULL) {
            return 'NULL';
        }

        if ('i' === $type) {
            return (int) $value;
        }

        return "'" . $this->mysqli->real_escape_string($value) . "'";
    }
}

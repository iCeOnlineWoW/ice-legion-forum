<?php
/**
*
* @package dbal
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

include_once(PHPBB_ROOT_PATH . 'includes/db/dbal.' . PHP_EXT);

/**
* Unified ODBC functions
* Unified ODBC functions support any database having ODBC driver, for example Adabas D, IBM DB2, iODBC, Solid, Sybase SQL Anywhere...
* Here we only support MSSQL Server 2000+ because of the provided schema
*
* @note number of bytes returned for returning data depends on odbc.defaultlrl php.ini setting.
* If it is limited to 4K for example only 4K of data is returned max, resulting in incomplete theme data for example.
* @note odbc.defaultbinmode may affect UTF8 characters
*
* @package dbal
*/
class dbal_mssql_odbc extends dbal
{
	var $dbms_type = 'mssql';

	/**
	* Connect to server
	*/
	function sql_connect($sqlserver, $sqluser, $sqlpassword, $database, $port = false, $persistency = false, $new_link = false)
	{
		$this->persistency = $persistency;
		$this->user = $sqluser;
		$this->server = $sqlserver . (($port) ? ':' . $port : '');
		$this->dbname = $database;

		$max_size = @ini_get('odbc.defaultlrl');
		if (!empty($max_size))
		{
			$unit = strtolower(substr($max_size, -1, 1));
			$max_size = (int) $max_size;

			if ($unit == 'k')
			{
				$max_size = floor($max_size / 1024);
			}
			else if ($unit == 'g')
			{
				$max_size *= 1024;
			}
			else if (is_numeric($unit))
			{
				$max_size = floor((int) ($max_size . $unit) / 1048576);
			}
			$max_size = max(8, $max_size) . 'M';

			@ini_set('odbc.defaultlrl', $max_size);
		}

		$this->db_connect_id = ($this->persistency) ? @odbc_pconnect($this->server, $this->user, $sqlpassword) : @odbc_connect($this->server, $this->user, $sqlpassword);

		return ($this->db_connect_id) ? $this->db_connect_id : $this->sql_error('');
	}

	/**
	* Version information about used database
	*/
	function sql_server_info()
	{
		$result_id = @odbc_exec($this->db_connect_id, "SELECT SERVERPROPERTY('productversion'), SERVERPROPERTY('productlevel'), SERVERPROPERTY('edition')");

		$row = false;
		if ($result_id)
		{
			$row = @odbc_fetch_array($result_id);
			@odbc_free_result($result_id);
		}

		if ($row)
		{
			return 'MSSQL (ODBC)<br />' . implode(' ', $row);
		}

		return 'MSSQL (ODBC)';
	}

	/**
	* SQL Transaction
	* @access private
	*/
	function _sql_transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				return @odbc_exec($this->db_connect_id, 'BEGIN TRANSACTION');
			break;

			case 'commit':
				return @odbc_exec($this->db_connect_id, 'COMMIT TRANSACTION');
			break;

			case 'rollback':
				return @odbc_exec($this->db_connect_id, 'ROLLBACK TRANSACTION');
			break;
		}

		return true;
	}

	/**
	* Base query method
	*
	* @param	string	$query		Contains the SQL query which shall be executed
	* @param	int		$cache_ttl	Either 0 to avoid caching or the time in seconds which the result shall be kept in cache
	* @return	mixed				When casted to bool the returned value returns true on success and false on failure
	*
	* @access	public
	*/
	function sql_query($query = '', $cache_ttl = 0)
	{
		if ($query != '')
		{
			global $cache;

			// EXPLAIN only in extra debug mode
			if (defined('DEBUG_EXTRA'))
			{
				$this->sql_report('start', $query);
			}

			$this->query_result = ($cache_ttl && method_exists($cache, 'sql_load')) ? $cache->sql_load($query) : false;
			$this->sql_add_num_queries($this->query_result);

			if ($this->query_result === false)
			{
				if (($this->query_result = @odbc_exec($this->db_connect_id, $query)) === false)
				{
					$this->sql_error($query);
				}

				if (defined('DEBUG_EXTRA'))
				{
					$this->sql_report('stop', $query);
				}

				if ($cache_ttl && method_exists($cache, 'sql_save'))
				{
					$this->open_queries[(int) $this->query_result] = $this->query_result;
					$cache->sql_save($query, $this->query_result, $cache_ttl);
				}
				else if (strpos($query, 'SELECT') === 0 && $this->query_result)
				{
					$this->open_queries[(int) $this->query_result] = $this->query_result;
				}
			}
			else if (defined('DEBUG_EXTRA'))
			{
				$this->sql_report('fromcache', $query);
			}
		}
		else
		{
			return false;
		}

		return ($this->query_result) ? $this->query_result : false;
	}

	/**
	* Build LIMIT query
	*/
	function _sql_query_limit($query, $total, $offset = 0, $cache_ttl = 0)
	{
		$this->query_result = false;

		// Since TOP is only returning a set number of rows we won't need it if total is set to 0 (return all rows)
		if ($total)
		{
			// We need to grab the total number of rows + the offset number of rows to get the correct result
			if (strpos($query, 'SELECT DISTINCT') === 0)
			{
				$query = 'SELECT DISTINCT TOP ' . ($total + $offset) . ' ' . substr($query, 15);
			}
			else
			{
				$query = 'SELECT TOP ' . ($total + $offset) . ' ' . substr($query, 6);
			}
		}

		$result = $this->sql_query($query, $cache_ttl);

		// Seek by $offset rows
		if ($offset)
		{
			// We do not fetch the row for rownum == 0 because then the next resultset would be the second row
			for ($i = 0; $i < $offset; $i++)
			{
				if (!$this->sql_fetchrow($result))
				{
					return false;
				}
			}
		}

		return $result;
	}

	/**
	* Return number of affected rows
	*/
	function sql_affectedrows()
	{
		return ($this->db_connect_id) ? @odbc_num_rows($this->query_result) : false;
	}

	/**
	* Fetch current row
	* @note number of bytes returned depends on odbc.defaultlrl php.ini setting. If it is limited to 4K for example only 4K of data is returned max.
	*/
	function sql_fetchrow($query_id = false, $debug = false)
	{
		global $cache;

		if ($query_id === false)
		{
			$query_id = $this->query_result;
		}

		if (isset($cache->sql_rowset[$query_id]))
		{
			return $cache->sql_fetchrow($query_id);
		}

		return ($query_id !== false) ? @odbc_fetch_array($query_id) : false;
	}

	/**
	* Get last inserted id after insert statement
	*/
	function sql_nextid()
	{
		$result_id = @odbc_exec($this->db_connect_id, 'SELECT @@IDENTITY');

		if ($result_id)
		{
			if (@odbc_fetch_array($result_id))
			{
				$id = @odbc_result($result_id, 1);
				@odbc_free_result($result_id);
				return $id;
			}
			@odbc_free_result($result_id);
		}

		return false;
	}

	/**
	* Free sql result
	*/
	function sql_freeresult($query_id = false)
	{
		global $cache;

		if ($query_id === false)
		{
			$query_id = $this->query_result;
		}

		if (isset($cache->sql_rowset[$query_id]))
		{
			return $cache->sql_freeresult($query_id);
		}

		if (isset($this->open_queries[(int) $query_id]))
		{
			unset($this->open_queries[(int) $query_id]);
			return @odbc_free_result($query_id);
		}

		return false;
	}

	/**
	* Escape string used in sql query
	*/
	function sql_escape($msg)
	{
		return str_replace("'", "''", $msg);
	}

	/**
	* Expose a DBMS specific function
	*/
	function sql_function($type, $col)
	{
		switch ($type)
		{
			case 'length_varchar':
			case 'length_text':
				return 'DATALENGTH(' . $col . ')';
			break;
		}
	}

	function sql_handle_data($type, $table, $data, $where = '')
	{
		if ($type === 'INSERT')
		{
			$stmt = odbc_prepare($this->db_connect_id, "INSERT INTO $table (". implode(', ', array_keys($data)) . ") VALUES (" . substr(str_repeat('?, ', sizeof($data)) ,0, -1) . ')');
		}
		else
		{
			$query = "UPDATE $table SET ";

			$set = array();
			foreach (array_keys($data) as $key)
			{
				$set[] = "$key = ?";
			}
			$query .= implode(', ', $set);

			if ($where !== '')
			{
				$query .= $where;
			}

			$stmt = odbc_prepare($this->db_connect_id, $query);
		}

		// get the stmt onto the top of the function arguments
		array_unshift($data, $stmt);

		call_user_func_array('odbc_execute', $data);
	}

	/**
	* Build LIKE expression
	* @access private
	*/
	function _sql_like_expression($expression)
	{
		return $expression . " ESCAPE '\\'";
	}

	/**
	* Build db-specific query data
	* @access private
	*/
	function _sql_custom_build($stage, $data)
	{
		return $data;
	}

	/**
	* return sql error array
	* @access private
	*/
	function _sql_error()
	{
		return array(
			'message'	=> @odbc_errormsg(),
			'code'		=> @odbc_error()
		);
	}

	/**
	* Close sql connection
	* @access private
	*/
	function _sql_close()
	{
		return @odbc_close($this->db_connect_id);
	}

	/**
	* Build db-specific report
	* @access private
	*/
	function _sql_report($mode, $query = '')
	{
		switch ($mode)
		{
			case 'start':
			break;

			case 'fromcache':
				$endtime = explode(' ', microtime());
				$endtime = $endtime[0] + $endtime[1];

				$result = @odbc_exec($this->db_connect_id, $query);
				while ($void = @odbc_fetch_array($result))
				{
					// Take the time spent on parsing rows into account
				}
				@odbc_free_result($result);

				$splittime = explode(' ', microtime());
				$splittime = $splittime[0] + $splittime[1];

				$this->sql_report('record_fromcache', $query, $endtime, $splittime);

			break;
		}
	}
}

?>
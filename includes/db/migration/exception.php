<?php
/**
*
* @package db
* @copyright (c) 2012 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* The migrator is responsible for applying new migrations in the correct order.
*
* @package db
*/
class phpbb_db_migration_exception extends \Exception
{
	/** @var array Extra parameters sent to exception to aid in debugging */
	protected $parameters;

	/**
	* Throw an exception.
	*
	* First argument is the error message.
	* Additional arguments will be output with the error message.
	*/
	public function __construct()
	{
		$parameters = func_get_args();
		$message = array_shift($parameters);
		parent::__construct($message);

		$this->parameters = $parameters;
	}

	/**
	* Output the error as a string
	*/
	public function __toString()
	{
		return $this->message . ': ' . var_export($this->parameters, true);
	}
}

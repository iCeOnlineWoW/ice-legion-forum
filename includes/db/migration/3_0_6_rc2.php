<?php
/**
*
* @package migration
* @copyright (c) 2012 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2
*
*/

class phpbb_db_migration_3_0_6_rc2 extends phpbb_db_migration
{
	function depends_on()
	{
		return array('phpbb_db_migration_3_0_6_rc1');
	}

	function update_schema()
	{
		return array();
	}

	function update_data()
	{
	}
}

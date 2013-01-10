<?php
/**
*
* @package migration
* @copyright (c) 2012 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2
*
*/

class phpbb_db_migration_data_3_0_6_rc3 extends phpbb_db_migration
{
	function depends_on()
	{
		return array('phpbb_db_migration_data_3_0_6_rc2');
	}

	function update_schema()
	{
		return array();
	}

	function update_data()
	{
		return array(
			array('custom', array(array(&$this, 'update_cp_fields'))),

			array('config.update', array('version', '3.0.6-rc3')),
		);
	}

	function update_cp_fields()
	{
		// Update the Custom Profile Fields based on previous settings to the new format
		$sql = 'UPDATE ' . PROFILE_FIELDS_TABLE . '
			SET field_show_on_vt = 1
			WHERE field_hide = 0
				AND (field_required = 1 OR field_show_on_reg = 1 OR field_show_profile = 1)';
		$this->sql_query($sql);
	}
}

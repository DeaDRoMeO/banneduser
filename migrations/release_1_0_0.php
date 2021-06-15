<?php
/**
*
* @package BannedUser
* @copyright (c) 2021 DeaDRoMeO ; hello-vitebsk.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace deadromeo\banneduser\migrations;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
    exit;
}

class release_1_0_0 extends \phpbb\db\migration\migration
{
	public function update_schema()
	{
		if (!$this->db_tools->sql_column_exists($this->table_prefix . 'banlist', 'ban_banner'))
		{
			return 	array(
				'add_columns' => array(
					$this->table_prefix . 'banlist' => array(
						'ban_banner' => array('UINT',0),

					),
				),
			);
		}
	}
	public function revert_schema()
	{
		return 	array(
			'drop_columns' => array(
				$this->table_prefix . 'banlist' => array('ban_banner'),
			),
		);
		}
		
		public function update_data()
	{
		return array(

			// Add new config vars
			array('config.add', array('bu_version', '1.0.0')),
			array('config.add', array('bu_p', 0)),
			array('config.add', array('bu_u', 5)),
			array('permission.add', array('u_viewban')),
			// Add new modules
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'BU_ACP'
			)),

			array('module.add', array(
				'acp',
				'BU_ACP',
				array(
					'module_basename'	=> '\deadromeo\banneduser\acp\banneduser_module',
					'modes'	=> array('banneduser_config'),
				),
			)),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.remove', array('bu_version')),
			array('config.remove', array('bu_p')),
			array('config.remove', array('bu_u')),
		array('permission.remove', array('u_viewban')),
			array('module.remove', array(
				'acp',
				'BU_ACP',
				array(
					'module_basename'	=> '\deadromeo\banneduser\acp\banneduser_module',
					'modes'	=> array('banneduser_config'),
				),
			)),
			array('module.remove', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'BU_ACP'
			)),
		);
	}
		
}
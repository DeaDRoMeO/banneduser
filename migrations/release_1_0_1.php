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

class release_1_0_1 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return (isset($this->config['bu_version']) && version_compare($this->config['bu_version'], '1.0.1', '>='));
	}
static public function depends_on()
	{
		return array('\deadromeo\banneduser\migrations\release_1_0_0');
	}


	public function update_data()
	{
		return array(
			array('config.add', array('bu_version', '1.0.1')),
			array('if', array(
				(isset($this->config['bu_version']) && version_compare($this->config['bu_version'], '1.0.1', '<')),
				array('config.update', array('bu_version', '1.0.1')),
			)),
			
		);
	}
}
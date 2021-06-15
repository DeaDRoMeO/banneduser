<?php

/**
*
* @package BannedUser
* @copyright (c) 2021 DeaDRoMeO ; hello-vitebsk.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace deadromeo\banneduser\acp;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
    exit;
}

class banneduser_info
{
	function module()
	{
		return array(
			'filename'	=> '\deadromeo\banneduser\banneduser_module',
			'title'		=> 'BU_ACP',
			'modes'		=> array(
				'banneduser_config' => array('title' => 'BU_CONFIG', 'auth' => 'ext_deadromeo/banneduser && acl_a_board', 'cat' => array('BU_ACP')),
			),
		);
	}
}

?>
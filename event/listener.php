<?php

/**
*
* @package BannedUser
* @copyright (c) 2021 DeaDRoMeO ; hello-vitebsk.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace deadromeo\banneduser\event;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
    exit;
}

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $banneduser_controller;
	protected $config;
	protected $request;
	protected $db;
	protected $auth;
	protected $template;
	protected $user;
	protected $phpbb_root_path;
	
	public function __construct(\deadromeo\banneduser\controller\banneduser $controller,\phpbb\config\config $config, \phpbb\request\request $request,\phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, $phpbb_root_path)
	{
		$this->banneduser_controller = $controller;
		$this->config = $config;
		$this->request = $request;
		$this->db = $db;
		$this->auth = $auth;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_root_path = $phpbb_root_path;
	}
	
	static public function getSubscribedEvents()
	{
		return array(
		'core.index_modify_page_title'			=> 'ban',	
		'core.user_setup'						=> 'load_language_on_setup',
		'core.page_header'						=> 'add_page_header_link',
		'core.memberlist_view_profile'			=> 'banmem',
		'core.viewtopic_modify_post_data'		=> 'viewtopic_modify_post_data',
		'core.viewtopic_modify_post_row'		=> 'banmem2',
		'core.permissions'						=> 'add_permissions',
		);
	}
	
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'deadromeo/banneduser',
			'lang_set' => 'banneduser',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}	
	
	public function add_permissions($event)
	{
		$permissions = $event['permissions'];
		$permissions['u_viewban'] = array('lang' => 'ACL_U_VIEWBAN', 'cat' => 'misc');
		$event['permissions'] = $permissions;
	}

	public function ban($event)
	{
		$sql = 'SELECT COUNT(ban_userid) as total_banned_users
			FROM ' . BANLIST_TABLE . '
			WHERE ban_exclude = 0 AND ban_userid > 0 AND (ban_end >= ' . time() . ' OR ban_end = 0)';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$total_banned_users = (int) $row['total_banned_users'];
		$this->db->sql_freeresult($result);	
		$this->template->assign_vars(array(
			'TOTAL_BANNED_USERS'	=> $total_banned_users,
		));
	}
	
	public function add_page_header_link($event)
	{
		$this->template->assign_vars(array(
			'BU_P'		=> $this->config['bu_p'],
			'U_BAN' => append_sid("{$this->phpbb_root_path}banneduser"),
			'S_DISPLAY_BAN'		=> $this->auth->acl_get('u_viewban'),
		));
	}
	
	public function banmem($event)
	{
		$member = $event['member'];
		$user_id = (int) $member['user_id'];
		$sql = 'SELECT ban_userid, ban_reason, ban_end
			FROM ' . BANLIST_TABLE . '
			WHERE ban_userid = ' . $user_id . ' AND ban_exclude = 0 AND (ban_end >= ' . time() . ' OR ban_end = 0)';			
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$ban_end = $row['ban_end'];
			if ($ban_end == 0)
			{
				$ban_end = (string)$this->user->lang['USBU'];
			}
			else
			{
				$time_left = (int) $row['ban_end'] - time();
				$days_left = $minutes_left = $seconds_left = 0;
				$remaining_time = '';
				if ($time_left)
				{
					$days_left = floor($time_left / 86400);
					if ($days_left)
					{
						$days = $this->user->lang('DAYLEFT', $days_left);
						$time_left = $time_left - ($days_left * 86400);
						$remaining_time .= $days;
					}
					$hours_left = floor($time_left / 3600);
					if ($hours_left)
					{
						$hours = $this->user->lang('HOURLEFT', $hours_left);
						$time_left = $time_left - ($hours_left * 3600);
						$remaining_time .= $hours;
					}
					$minutes_left = ceil($time_left / 60);
					if ($minutes_left)
					{
						$minutes = $this->user->lang('MINLEFT', $minutes_left);
						$remaining_time .= $minutes;
					}
				}
				$ban_end =' '. (string)($this->user->lang['USBD']) . '' . $remaining_time . '. ';
			}
			$this->template->assign_vars(array(
				'BU_VI'		=> $this->config['bu_vi'],
				'USBU' => $ban_end,
				'USERB_ID' => $row['ban_userid'],
				'BRS' => $row['ban_reason'],
			));
		}
		$sql2 = 'SELECT COUNT(warning_id) as total_warn
			FROM ' . WARNINGS_TABLE . '
			WHERE user_id = ' . $user_id . '';				
		$result2 = $this->db->sql_query($sql2);
		$row2 = $this->db->sql_fetchrow($result2);
		$total_warn = (int) $row2['total_warn'];
		$total_warning = $this->user->lang('WARN_COUNT', $total_warn);
		if ($total_warn != 0)
		{
			$total_w = true;
		}
		else
		{
			$total_w = false;
		}
		$this->template->assign_vars(array(
			'TOTAL_WARN'		=> $total_warning,
			'S_TOTAL_WARN'		=> $total_w,
		));			
		$sql3 = 'SELECT warning_time, post_id
			FROM ' . WARNINGS_TABLE . '
			WHERE user_id = ' . $user_id . '';				
		$result3 = $this->db->sql_query($sql3);
		while ($row3 = $this->db->sql_fetchrow($result3))
		{
			$warn_t = $this->user->format_date($row3['warning_time']);
			$post_id = $row3['post_id'];
			if(!empty($row3['post_id']))
			{
				$post_link = ', ' . $this->user->lang['WARN_POST'] . ' <a href="./viewtopic.php?p=' . $row3['post_id'] . '#p' . $row3['post_id'] . '">#' . $row3['post_id'] . '</a>;';
			}
			else
			{
				$post_link = $this->user->lang['WARN_PROF'];
			}
			$this->template->assign_block_vars('warn', array(
				'WARN_T'			=> $warn_t,
				'POST_LINK'			=> $post_link,
			));
		}
	}	
	
	public function viewtopic_modify_post_data($event)
	{
		$user_ids = array();
		$rowset = $event['rowset'];
		$post_list = $event['post_list'];
		for ($i = 0, $end = sizeof($post_list); $i < $end; ++$i)
		{
			if (!isset($rowset[$post_list[$i]]))
			{
				continue;
			}
			$row = $rowset[$post_list[$i]];
			$poster_id = $row['user_id'];
			if ($poster_id != ANONYMOUS && !$row['foe'] && !$row['hide_post'])
			{
				$user_ids[] = $poster_id;
			}
			unset($rowset[$post_list[$i]]);
		}
		if (sizeof($user_ids))
		{
			$sql = 'SELECT ban_userid
				FROM ' . BANLIST_TABLE . '
				WHERE ' . $this->db->sql_in_set('ban_userid', $user_ids) . '
					AND ban_exclude = 0 AND (ban_end >= ' . time() . ' OR ban_end = 0)';
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->ban_userid[$row['ban_userid']] = $row['ban_userid'];
			}
			$this->db->sql_freeresult($result);
		}
	}
	
	public function banmem2($event)
	{
		$this->template->assign_vars(array(
			'BU_VI'		=> $this->config['bu_vi'],
		));
		if (!empty($this->ban_userid[$event['poster_id']]))
		{
			$post_row = array(
				'USERB_ID' => $this->ban_userid[$event['poster_id']],
			);
			$event['post_row'] += $post_row;
		}
	}
}
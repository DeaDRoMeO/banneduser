<?php
/**
*
* @package BannedUser
* @copyright (c) 2021 DeaDRoMeO ; hello-vitebsk.ru
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace deadromeo\banneduser\controller;

use Symfony\Component\HttpFoundation\Response;

	class banneduser
	{
		protected $request;
		protected $config;
		protected $pagination;
		protected $db;
		protected $auth;
		protected $template;
		protected $user;
		protected $helper;
		protected $phpbb_root_path;
		protected $php_ext;

		public function __construct(\phpbb\request\request_interface $request,\phpbb\config\config $config, \phpbb\pagination $pagination, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, \phpbb\controller\helper $helper, $phpbb_root_path, $php_ext, $table_prefix)
		{
			$this->request = $request;
			$this->config = $config;
			$this->pagination = $pagination;
			$this->db = $db;
			$this->auth = $auth;
			$this->template = $template;
			$this->user = $user;
			$this->helper = $helper;
			$this->phpbb_root_path = $phpbb_root_path;
			$this->php_ext = $php_ext;
			$this->table_prefix = $table_prefix;
		}
	
	public function main()
	{
		if (!$this->auth->acl_gets('u_viewban'))
		{
			if ($this->user->data['user_id'] != ANONYMOUS)
			{
				trigger_error('NOT_AUTHORISED');
			}
			login_box('', $this->user->lang['LOGIN_EXPLAIN_VIEWBAN']);
		}
		$start	= request_var('start', 0);
		$mode   = request_var('mode', '');
		$per_page	= $this->config['bu_u'];
		$sql = 'SELECT COUNT(ban_userid) as total_banned_users
			FROM ' . BANLIST_TABLE . '
			WHERE ban_exclude = 0 AND ban_userid > 0 AND (ban_end >= ' . time() . ' OR ban_end = 0)';
		$result3 = $this->db->sql_query($sql);
		$row3 = $this->db->sql_fetchrow($result3);
		$total_banned_users = (int) $row3['total_banned_users'];
		$this->db->sql_freeresult($result3);	
		$default_key = 'a';
		$sort_key = $this->request->variable('sk', 'a');
		$sort_dir = $this->request->variable('sd', 'd');
		$sort_key_text = array('a' => $this->user->lang['SORT_USERNAME'], 'b' => $this->user->lang['BAN_START_DATE']);
		$sort_key_sql = array('a' => 'u.username_clean', 'b' => 'b.ban_start');
		$sort_dir_text = array('a' => $this->user->lang['ASCENDING'], 'd' => $this->user->lang['DESCENDING']);
		$s_sort_key = '';
		foreach ($sort_key_text as $key => $value)
		{
			$selected = ($sort_key == $key) ? ' selected="selected"' : '';
			$s_sort_key .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}
		$s_sort_dir = '';
		foreach ($sort_dir_text as $key => $value)
		{
			$selected = ($sort_dir == $key) ? ' selected="selected"' : '';
			$s_sort_dir .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}
		$first_char = request_var('first_char', '');
		$sql_where = ' AND b.ban_userid = u.user_id AND u.user_type <> ' . USER_IGNORE;
		if ($first_char == 'other')
		{
			for ($i = 97; $i < 123; $i++)
				{
					$sql_where .= ' AND u.username_clean NOT ' . $this->db->sql_like_expression(chr($i) . $db->any_char);
				}
		}
		else if ($first_char)
		{
			$sql_where .= ' AND u.username_clean ' . $this->db->sql_like_expression(substr($first_char, 0, 1) . $db->any_char);
		}
		if (!isset($sort_key_sql[$sort_key]))
		{
			$sort_key = $default_key;
		}
		$order_by = $sort_key_sql[$sort_key] . ' ' . (($sort_dir == 'a') ? 'ASC' : 'DESC');
		$params = $sort_params = array();
		$check_params = array(
			'sk'			=> array('sk', $default_key),
			'sd'			=> array('sd', 'a'),
			'username'		=> array('username', '', true),
			'ban_start'		=> array('ban_start', '', true),
			'first_char'	=> array('first_char', ''),
		);
		$u_first_char_params = array();
		foreach ($check_params as $key => $call)
		{
			if (!isset($_REQUEST[$key]))
			{
				continue;
			}
		
			$param = call_user_func_array('request_var', $call);
			$param = urlencode($key) . '=' . ((is_string($param)) ? urlencode($param) : $param);
			$params[] = $param;
			if ($key != 'sk' && $key != 'sd')
			{
				$sort_params[] = $param;
			}
		}
		$sort_params[] = "mode=$mode";
		$pagination_url = append_sid("{$this->phpbb_root_path}banneduser", implode('&amp;', $params));
		$sort_url = append_sid("{$this->phpbb_root_path}banneduser");
		unset($params, $sort_params);
		$this->template->assign_vars(array(
			'S_SORT_OPTIONS'		=> $s_sort_key
		));
		$sql_ary = array(
			'SELECT'	=> 'b.*, u.user_id, u.username, u.username_clean, u.user_colour, u.user_warnings, u.user_last_warning',
			'FROM'		=> array(BANLIST_TABLE => 'b', USERS_TABLE => 'u'),
			'WHERE'		=> '(b.ban_end >= ' . time() . '
			OR b.ban_end = 0) AND ban_exclude <> 1' . $sql_where,
			'ORDER_BY'	=> $order_by,
		);
		$result = $this->db->sql_query_limit($this->db->sql_build_query('SELECT', $sql_ary), $per_page, $start);
		$row_number = $start;
		if ($row = $this->db->sql_fetchrow($result))
		{
			do
				{
					$ban_end = $row['ban_end'];
					$banstart = $row['ban_start'];
					if ($ban_end == 0)
						{
							$ban_end = (string)$this->user->lang['PERM'];
						}
						else
						{
							$ban_end = $this->user-> format_date ($row['ban_end']);
						}
					$last_warn = $row['user_last_warning'];
					if ($last_warn == 0)
					{
						$last_warn = false;
					}
					else
					{
						$last_warn = $this->user->format_date($row['user_last_warning']);
					}
					$warn = $row['user_warnings'];
					if ($warn == 0)
					{
						$warn = (string)$this->user->lang['WARN_NO'];
					}
					else
					{
						$warn = $row['user_warnings'];
					}
					$row_number++;
					$this->template->assign_block_vars('banlist_row', array(
						'ROW_NUMBER'			=> $row_number,
						'BAN_START'				=> $this->user->format_date($row['ban_start']),
						'BAN_END'				=> $ban_end,
						'WARN'			=> $warn,
						'LASTWARN'			=> $last_warn,
						'BAN_REASON'			=> $row['ban_give_reason'],
						'USERNAME_FULL'			=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
					));
				}
			while ($row = $this->db->sql_fetchrow($result));
			$this->db->sql_freeresult($result);
		}
		$this->template->assign_vars(array(
			'PAGINATION'		=> $this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_banned_users, $per_page, $start),
			'PAGE_NUMBER'		=> $this->pagination->on_page($total_banned_users, $per_page, $start),
			'U_SORT_USERNAME'	=> $sort_url . '?mode=&amp;sk=a&amp;sd=' . (($sort_key == 'a' && $sort_dir == 'a') ? 'd' : 'a'),
			'U_SORT_BAN_START'	=> $sort_url . '?mode=&amp;sk=b&amp;sd=' . (($sort_key == 'b' && $sort_dir == 'a') ? 'd' : 'a'),
			'TOTAL_USERS'		=> $total_banned_users,
			'S_MODE_SELECT'		=> $s_sort_key,
			'S_ORDER_SELECT'	=> $s_sort_dir,
			'S_MODE_ACTION'		=> $pagination_url
		));
		page_header($this->user->lang('BANU'));
		$this->template->set_filenames(array(
			'body' => 'banneduser_body.html'
		));
		page_footer();
		return new Response($this->template->return_display('body'), 200);
	}
}
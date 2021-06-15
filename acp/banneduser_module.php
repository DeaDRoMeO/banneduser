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

/**
* @package acp
*/
class banneduser_module
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	public $u_action;

	function main($id, $mode)
	{
		global $config, $request, $template, $user;

		$this->config = $config;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;

		$this->user->add_lang('acp/common');
		$this->tpl_name = 'acp_banneduser';
		$this->page_title = $this->user->lang('BU_ACP');

		$form_key = 'acp_banneduser';
		add_form_key($form_key);
		if ($this->request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}
			$bu_u = $this->request->variable('bu_u', '');
			$this->config->set('bu_u', $bu_u);
			$bu_p = $this->request->variable('bu_p', 0);
			$this->config->set('bu_p', $bu_p);
			$bu_vi = $this->request->variable('bu_vi', 0);
			$this->config->set('bu_vi', $bu_vi);
			
					
			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}
		$template->assign_vars(array(
		    'BU_VERSION'			=> isset($this->config['bu_version']) ? $this->config['bu_version'] : '',
			'BU_P'				=> isset($this->config['bu_p']) ? $this->config['bu_p'] : '',
			'BU_U'				=> isset($this->config['bu_u']) ? $this->config['bu_u'] : '',	
			'BU_VI'				=> isset($this->config['bu_vi']) ? $this->config['bu_vi'] : '',			
			'U_ACTION'				=> $this->u_action,
		));
	}
}

?>
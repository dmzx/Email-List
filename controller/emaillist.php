<?php
/**
*
* @package phpBB Extension - Email List
* @copyright (c) 2015 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\emaillist\controller;

use phpbb\exception\http_exception;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\template\template;
use phpbb\user;
use phpbb\db\driver\driver_interface as db_interface;
use phpbb\request\request_interface;
use phpbb\pagination;

class emaillist
{
	/** @var config */
	protected $config;

	/** @var helper */
	protected $helper;

	/** @var template */
	protected $template;

	/** @var user */
	protected $user;

	/** @var db_interface */
	protected $db;

	/** @var request_interface */
	protected $request;

	/** @var pagination */
	protected $pagination;

	/**
	* Constructor
	*
	* @param config					$config
	* @param helper					$helper
	* @param template				$template
	* @param user					$user
	* @param db_interface			$db
	* @param request_interface		$request
	* @param pagination				$pagination
	*
	*/
	public function __construct(
		config $config,
		helper $helper,
		template $template,
		user $user,
		db_interface $db,
		request_interface $request,
		pagination $pagination
	)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->db = $db;
		$this->request = $request;
		$this->pagination = $pagination;
	}

	public function handle_emaillist()
	{
		// Founders only access
		if ($this->user->data['user_type'] != USER_FOUNDER)
		{
			throw new http_exception(401, 'NOT_AUTHORISED');
		}

		// add lang file
		$this->user->add_lang_ext('dmzx/emaillist', 'common');

		$start = $this->request->variable('start', 0);
		$group_id = $this->request->variable('group_select', 0);

		//Pull Users from the database
		$sql = $this->sql_emaillist($group_id);
		$result = $this->db->sql_query_limit($sql, $this->config['posts_per_page'], $start);

		// Assign specific vars
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('list', array(
				'ID'				=> $row['user_id'],
				'EMAIL'				=> $row['user_email'],
				'REGDATE'			=> $this->user->format_date($row['user_regdate']),
				'LASTVISIT'			=> (!empty($row['user_lastvisit'])) ? $this->user->format_date($row['user_lastvisit']) : '-',
				'USERNAME_FULL'		=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'USERNAME'			=> get_username_string('username', $row['user_id'], $row['username'], $row['user_colour']),
				'USER_COLOR'		=> get_username_string('colour', $row['user_id'], $row['username'], $row['user_colour']),
				'U_VIEW_PROFILE'	=> get_username_string('profile', $row['user_id'], $row['username'], $row['user_colour']),
			));
		}
		$this->db->sql_freeresult($result);

		// for counting of total users
		$result = $this->db->sql_query($sql);
		$row2 = $this->db->sql_fetchrowset($result);
		$total_users = sizeof($row2);
		$this->db->sql_freeresult($result);
		unset($row2);

		//Start pagination
		$this->pagination->generate_template_pagination($this->helper->route('dmzx_emaillist_controller'), 'pagination', 'start', $total_users, $this->config['posts_per_page'], $start);

		$this->template->assign_vars(array(
			'TOTAL_USERS'		=> $this->user->lang('USER_COUNT', (int) $total_users),
			'GROUPS_SELECT'		=> (!empty($group_id)) ? $this->get_groups($group_id) : $this->get_groups(0),
			'U_CSV_LIST'		=> (!empty($total_users)) ? $this->helper->route('dmzx_emaillist_csv', array('group_id' => $group_id)) : '',
			'U_GROUPS'			=> $this->helper->route('dmzx_emaillist_controller'),
		));

		$this->template->assign_block_vars('navlinks', array(
			'FORUM_NAME' 	=> ($this->user->lang['EMAIL_LIST']),
			'U_VIEW_FORUM'	=> $this->helper->route('dmzx_emaillist_controller'),
		));

		return $this->helper->render('email_list_body.html', $this->user->lang('EMAIL_LIST'));		// Output page
	}

	public function csv_list($group_id = 0)
	{
		// Founders only access
		if ($this->user->data['user_type'] != USER_FOUNDER)
		{
			throw new http_exception(401, 'NOT_AUTHORISED');
		}

		$csv_output = trim($this->config['sitename']) . ' ' . $this->user->lang['EMAIL'];
		$csv_output .= "\n";
		$csv_output .= '#,' . $this->user->lang['USERNAME'] . ',' . $this->user->lang['EMAIL_ADDRESS'] . ',' . $this->user->lang['SORT_JOINED'] . ',' . $this->user->lang['LAST_VISIT'];
		$csv_output .= "\n";

		$sql = $this->sql_emaillist($group_id);
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$csv_output .= $row['user_id'] . ',' . $row['username'] . ',' . $row['user_email'] . ',' . gmdate("Y-m-d",$row['user_regdate']) .',' . gmdate("Y-m-d",$row['user_lastvisit']);
			$csv_output .="\n";
		}
		$this->db->sql_freeresult($result);

		header("Content-type: application/vnd.ms-excel");
		header("Content-disposition:	attachment; filename=" . str_replace(" ", "_", $this->config['sitename']) . '_' . $this->user->lang['EMAIL'] . 's_' . date("Y-m-d").".csv");
		print $csv_output;
		exit_handler();
	}

	private function sql_emaillist($group_id = 0)
	{
		if (empty($group_id))
		{
			$sql = 'SELECT *
				FROM ' . USERS_TABLE . '
				WHERE ' . $this->db->sql_in_set('user_type', array(USER_NORMAL, USER_FOUNDER)) . '
				ORDER BY user_id';
		}
		else
		{
			$sql = 'SELECT ug.*, u.username, u.user_email, u.user_lastvisit, u.user_regdate, u.user_colour
				FROM ' . USER_GROUP_TABLE . ' ug
				LEFT JOIN ' . USERS_TABLE . ' u ON ug.user_id = u.user_id
				WHERE ug.group_id = ' . (int) $group_id .	'
					AND ' . $this->db->sql_in_set('u.user_type', array(USER_NORMAL, USER_FOUNDER)) . '
				ORDER BY ug.user_id';
		}
		return $sql;
	}

	/**
	 * function to return groups that are allowed
	 */
	private function get_groups($group_id)
	{
		$ignore_groups = array('BOTS', 'GUESTS');

		$sql = 'SELECT group_name, group_id, group_type
			FROM ' . GROUPS_TABLE . '
			WHERE ' . $this->db->sql_in_set('group_name', $ignore_groups, true) . '
			ORDER BY group_name ASC';
		$result = $this->db->sql_query($sql);

		$selected = ($group_id == 0) ? ' selected="selected"' : '';
		$s_group_options = "<option value='0'$selected>&nbsp;{$this->user->lang['ALL_GROUPS']}&nbsp;</option>";

		while ($row = $this->db->sql_fetchrow($result))
		{
			$selected = ($row['group_id'] == $group_id) ? ' selected="selected"' : '';
			$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $this->user->lang['G_' . $row['group_name']] : $row['group_name'];
			$s_group_options .= "<option value='{$row['group_id']}'$selected>$group_name</option>";
		}
		$this->db->sql_freeresult($result);

		return $s_group_options;
	}
}

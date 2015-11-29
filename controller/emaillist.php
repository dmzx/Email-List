<?php
/**
*
* @package phpBB Extension - Email List
* @copyright (c) 2015 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\emaillist\controller;

class emaillist
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\pagination */
	protected $pagination;

	/**
	* Constructor
	*
	* @param \phpbb\config\config				$config
	* @param \phpbb\controller\helper			$helper
	* @param \phpbb\template\template			$template
	* @param \phpbb\user						$user
	* @param \phpbb\db\driver\driver_interface	$db
	* @param \phpbb\request\request				$request
	* @param \phpbb\pagination					$pagination
	*
	*/

	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\pagination $pagination)

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
		// add lang file
		$this->user->add_lang_ext('dmzx/emaillist', 'common');

		// add a mode for a CSV Listing
		$mode = $this->request->variable('mode', '');
		$start = $this->request->variable('start', 0);

		// Founders only access
		if ($this->user->data['user_type'] != USER_FOUNDER)
		{
			trigger_error('NOT_AUTHORISED');
		}

		if ($mode == 'list')
		{
			$csv_output = trim($this->config['sitename']) . ' ' . $this->user->lang['EMAIL'];
			$csv_output .= "\n";
			$csv_output .= '#,' . $this->user->lang['USERNAME'] . ',' . $this->user->lang['EMAIL_ADDRESS'] . ',' . $this->user->lang['SORT_JOINED'] . ',' . $this->user->lang['LAST_VISIT'];
			$csv_output .= "\n";
			//Pull Users from the database
			$sql = 'SELECT FROM_UNIXTIME(user_regdate) AS regdate, user_id, username, user_email, FROM_UNIXTIME(user_lastvisit) AS lastvisit
				FROM ' . USERS_TABLE . '
				WHERE user_type <> ' .	USER_IGNORE . '
				ORDER BY user_id';
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$csv_output .= $row['user_id'] . ',' . $row['username'] . ',' . $row['user_email'] . ',' . $row['regdate'] .',' . $row['lastvisit'];
				$csv_output .="\n";
			}
			$this->db->sql_freeresult($result);
			header("Content-type: application/vnd.ms-excel");
			header("Content-disposition:	attachment; filename=" . str_replace(" ", "_", $this->config['sitename']) . '_' . $this->user->lang['EMAIL'] . 's_' . date("Y-m-d").".csv");
			print $csv_output;
			exit;
		}

		// How many Users do we have?
		$sql = 'SELECT COUNT(user_id) AS total_users
			FROM ' . USERS_TABLE . '
			WHERE user_type <> ' .	USER_IGNORE;
		$result = $this->db->sql_query($sql);
		$total_users = (int) $this->db->sql_fetchfield('total_users');
		$this->db->sql_freeresult($result);

		$pagination_url = $this->helper->route('dmzx_emaillist_controller');

		// want more to display...change the 20 to a higher number
		$tf = 20;

		//Pull Users from the database
		$sql = 'SELECT *
			FROM ' . USERS_TABLE . '
			WHERE user_type <> ' .	USER_IGNORE . '
			ORDER BY user_id';
		$result = $this->db->sql_query_limit($sql, $tf, $start);

		// Assign specific vars
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('list', array(
				'ID'				=> $row['user_id'],
				'EMAIL'				=> $row['user_email'],
				'REGDATE'			=> $this->user->format_date($row['user_regdate']),
				'LASTVISIT'			=> $this->user->format_date($row['user_lastvisit']),
				'USERNAME_FULL'		=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']),
				'USERNAME'			=> get_username_string('username', $row['user_id'], $row['username'], $row['user_colour']),
				'USER_COLOR'		=> get_username_string('colour', $row['user_id'], $row['username'], $row['user_colour']),
				'U_VIEW_PROFILE'	=> get_username_string('profile', $row['user_id'], $row['username'], $row['user_colour']),
			));
		}
		$this->db->sql_freeresult($result);

		//Start pagination
		$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_users, $tf, $start);

		$this->template->assign_vars(array(
			'U_CSV_LIST'		=> $this->helper->route('dmzx_emaillist_controller', array('mode' => 'list')),
			'TOTAL_USERS'		=> ($total_users == 1) ? $this->user->lang['USER_COUNT'] : sprintf($this->user->lang['USER_COUNTS'], $total_users),
		));

		// Output page
		page_header($this->user->lang['EMAIL_LIST']);

		$this->template->set_filenames(array(
			'body' => 'email_list_body.html')
		);

		page_footer();
	}
}

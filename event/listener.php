<?php
/**
*
* @package phpBB Extension - Email List
* @copyright (c) 2015 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\emaillist\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\controller\helper;
use phpbb\template\template;
use phpbb\user;
use phpbb\files\factory;

class listener implements EventSubscriberInterface
{
	/** @var helper */
	protected $helper;

	/** @var string */
	protected $php_ext;

	/** @var template */
	protected $template;

	/** @var user */
	protected $user;

	/** @var factory */
	protected $files_factory;

	/**
	* Constructor
	*
	* @param helper			$helper
	* @param string		 $php_ext
	* @param template		$template
	* @param user			$user
	* @param factory		$files_factory
	*/
	public function __construct(
		helper $helper,
		$php_ext,
		template $template,
		user $user,
		factory $files_factory = null
	)
	{
		$this->helper 			= $helper;
		$this->php_ext 			= $php_ext;
		$this->template 		= $template;
		$this->user 			= $user;
		$this->files_factory 	= $files_factory;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.viewonline_overwrite_location'	=> 'add_page_viewonline',
			'core.page_header'						=> 'add_page_header_link',
		);
	}

	public function add_page_viewonline($event)
	{
		// add lang file
		$this->user->add_lang_ext('dmzx/emaillist', 'common');

		if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/emaillist') === 0)
		{
			$event['location'] = $this->user->lang('EMAIL_LIST');
			$event['location_url'] = $this->helper->route('dmzx_emaillist_controller');
		}
	}

	public function add_page_header_link($event)
	{
		// Founders only access
		if ($this->user->data['user_type'] != USER_FOUNDER)
		{
			return;
		}
		// add lang file
		$this->user->add_lang_ext('dmzx/emaillist', 'common');

		$this->template->assign_vars(array(
			'U_EMAIL_LIST' 		=> $this->helper->route('dmzx_emaillist_controller'),
			'S_EMAIL_LIST'		=> ($this->user->data['user_type'] == USER_FOUNDER) ? true : false,
			'PHPBB_IS_32'		=> ($this->files_factory !== null) ? true : false,
		));
	}
}

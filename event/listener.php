<?php
/**
*
* @package phpBB Extension - Email List
* @copyright (c) 2015 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\emaillist\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\controller\helper */
	protected $controller_helper;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/**
	* Constructor
	*
	* @param \phpbb\controller\controller_helper	$controller_helper
	* @param \phpbb\template\template				$template
	* @param \phpbb\user							$user
	*/

	public function __construct(\phpbb\controller\helper $controller_helper, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->controller_helper = $controller_helper;
		$this->template = $template;
		$this->user = $user;
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
		global $user, $phpbb_container, $phpEx;
		if (strrpos($event['row']['session_page'], 'app.' . $phpEx . '/emaillist') === 0)
		{
			$event['location'] = $user->lang('EMAIL_LIST');
			$event['location_url'] = $phpbb_container->get('controller.helper')->route('dmzx_emaillist_controller');
		}
	}

	public function add_page_header_link($event)
	{
		// add lang file
		$this->user->add_lang_ext('dmzx/emaillist', 'common');

		$this->template->assign_vars(array(
			'U_EMAIL_LIST' 			=> $this->controller_helper->route('dmzx_emaillist_controller'),
			'S_EMAIL_LIST'			=> ($this->user->data['user_type'] == USER_FOUNDER) ? true : false,
		));
	}
}

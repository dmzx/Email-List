<?php
/**
*
* @package phpBB Extension - Email List
* @copyright (c) 2020 dmzx - https://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace dmzx\emaillist;

use phpbb\extension\base;

class ext extends base
{
	public function is_enableable()
	{
		$config = $this->container->get('config');
		return version_compare($config['version'], '3.2.0', '>=');
	}
}

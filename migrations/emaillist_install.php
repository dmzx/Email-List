<?php
/**
 *
 * @package phpBB Extension - Email List
 * @copyright (c) 2021 dmzx - https://www.dmzx-web.net
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace dmzx\emaillist\migrations;

use phpbb\db\migration\migration;

class emaillist_install extends migration
{
	static public function depends_on()
	{
		return [
			'\phpbb\db\migration\data\v320\v320'
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['emaillist_version', '1.0.0']],
		];
	}
}

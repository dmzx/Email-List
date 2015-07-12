<?php
/**
*
* @package phpBB Extension - Email List
* @copyright (c) 2015 dmzx - http://www.dmzx-web.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'EMAIL_LIST'	=> 'Email List',
	'EXPORT_LIST'	=> 'Export as CSV List',
	'USER_COUNT'	=> 'One user',
	'USER_COUNTS'	=> '%d users',
));
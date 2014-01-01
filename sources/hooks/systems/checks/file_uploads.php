<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core
 */

class Hook_check_file_uploads
{
	/**
	 * Check various input var restrictions.
	 *
	 * @return	array		List of warnings
	 */
	function run()
	{
		$warning=array();
		if (ini_get('file_uploads')=='0')
			$warning[]=do_lang_tempcode('NO_UPLOAD');
		return $warning;
	}
}

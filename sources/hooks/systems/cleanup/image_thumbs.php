<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_cleanup_tools
 */

class Hook_image_thumbs
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		if ((get_option('is_on_gd')=='0') || (!function_exists('imagetypes'))) return NULL;

		$info=array();
		$info['title']=do_lang_tempcode('IMAGE_THUMBNAILS');
		$info['description']=do_lang_tempcode('DESCRIPTION_IMAGE_THUMBNAILS');
		$info['type']='optimise';

		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	Results
	 */
	function run()
	{
		erase_thumb_cache();
		erase_comcode_cache();

		return new ocp_tempcode();
	}

	/**
	 * Create filename-mirrored thumbnails for the given directory stub (mirrors stub/foo with stub_thumbs/foo).
	 *
	 * @param  string		Directory to mirror
	 */
	function directory_thumb_mirror($dir)
	{
		require_code('images');

		$full=get_custom_file_base().'/uploads/'.$dir;
		$dh=@opendir($full);
		if ($dh!==false)
		{
			while (($file=readdir($dh))!==false)
			{
				$target=get_custom_file_base().'/'.$dir.'_thumbs/'.$file;
				if ((!file_exists($target)) && (is_image($full.'/'.$file)))
				{
					require_code('images');
					convert_image($full.'/'.$file,$target,-1,-1,intval(get_option('thumb_width')));
				}
			}
		}
		closedir($dh);
	}

}



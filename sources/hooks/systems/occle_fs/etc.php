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
 * @package		occle
 */

class Hook_occle_fs_etc
{
	/**
	 * Standard modular listing function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return ~array 	The final directory listing (false: failure)
	 */
	function listing($meta_dir,$meta_root_node,&$occle_fs)
	{
		require_all_lang();

		require_code('resource_fs');

		if (count($meta_dir)>0) return false; // Directory doesn't exist
		load_options();

		$query='SELECT param_a,MAX(date_and_time) AS date_and_time FROM '.get_table_prefix().'adminlogs WHERE '.db_string_equal_to('the_type','CONFIGURATION').' GROUP BY param_a';
		$modification_times=collapse_2d_complexity('param_a','date_and_time',$GLOBALS['SITE_DB']->query($query));

		$listing=array();
		$hooks=find_all_hooks('systems','config');
		foreach (array_keys($hooks) as $option)
		{
			$value=get_option($option);
			if (is_null($value)) continue;

			$modification_time=array_key_exists($option,$modification_times)?$modification_times[$option]:NULL;

			$listing[]=array(
				$option,
				OCCLEFS_FILE,
				strlen($value),
				$modification_time,
			);
		}

		require_code('resource_fs');
		$hooks=find_all_hooks('systems','occle_fs_extended_config');
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/occle_fs_extended_config/'.filter_naughty($hook));
			$ob=object_factory('Hook_occle_fs_extended_config__'.$hook);
			$modification_time=$ob->_get_edit_date();

			$listing[]=array(
				'_'.$hook.'s'.'.'.RESOURCEFS_DEFAULT_EXTENSION,
				OCCLEFS_FILE,
				NULL/*don't calculate a filesize*/,
				$modification_time,
			);
		}

		return $listing;
	}

	/**
	 * Standard modular directory creation function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The new directory name
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function make_directory($meta_dir,$meta_root_node,$new_dir_name,&$occle_fs)
	{
		return false;
	}

	/**
	 * Standard modular directory removal function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The directory name
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function remove_directory($meta_dir,$meta_root_node,$dir_name,&$occle_fs)
	{
		return false;
	}

	/**
	 * Standard modular file removal function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The file name
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function remove_file($meta_dir,$meta_root_node,$file_name,&$occle_fs)
	{
		if (count($meta_dir)>0) return false; // Directory doesn't exist

		return false;
	}

	/**
	 * Standard modular file reading function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The file name
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return ~string	The file contents (false: failure)
	 */
	function read_file($meta_dir,$meta_root_node,$file_name,&$occle_fs)
	{
		if (count($meta_dir)>0) return false; // Directory doesn't exist

		require_code('resource_fs');

		$hooks=find_all_hooks('systems','occle_fs_extended_config');
		$extended_config_filename=preg_replace('#^\_(.*)s'.preg_quote('.'.RESOURCEFS_DEFAULT_EXTENSION,'#').'$#','${1}',$file_name);
		if (array_key_exists($extended_config_filename,$hooks))
		{
			require_code('hooks/systems/occle_fs_extended_config/'.filter_naughty($extended_config_filename));
			$ob=object_factory('Hook_occle_fs_extended_config__'.$extended_config_filename);
			return $ob->read_file($meta_dir,$meta_root_node,$file_name,$occle_fs);
		}

		$option=get_option($file_name,true);
		if (is_null($option)) return false;
		return $option;
	}

	/**
	 * Standard modular file writing function for OcCLE FS hooks.
	 *
	 * @param  array		The current meta-directory path
	 * @param  string		The root node of the current meta-directory
	 * @param  string		The file name
	 * @param  string		The new file contents
	 * @param  object		A reference to the OcCLE filesystem object
	 * @return boolean	Success?
	 */
	function write_file($meta_dir,$meta_root_node,$file_name,$contents,&$occle_fs)
	{
		require_code('config2');
		require_code('resource_fs');

		if (count($meta_dir)>0) return false; // Directory doesn't exist

		$hooks=find_all_hooks('systems','occle_fs_extended_config');
		$extended_config_filename=preg_replace('#^\_(.*)s'.preg_quote('.'.RESOURCEFS_DEFAULT_EXTENSION,'#').'$#','${1}',$file_name);
		if (array_key_exists($extended_config_filename,$hooks))
		{
			require_code('hooks/systems/occle_fs_extended_config/'.filter_naughty($extended_config_filename));
			$ob=object_factory('Hook_occle_fs_extended_config__'.$extended_config_filename);
			return $ob->write_file($meta_dir,$meta_root_node,$file_name,$contents,$occle_fs);
		}

		$value=get_option($file_name,true);
		if (is_null($value)) return false; // File doesn't exist

		set_option($file_name,$contents);

		return true;
	}

}


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
 * @package		ocf_forum
 */

require_code('content_fs');

class Hook_occle_fs_forum_groupings extends content_fs_base
{
	var $file_content_type='forum_grouping';

	/**
	 * Whether the filesystem hook is active.
	 *
	 * @return boolean		Whether it is
	 */
	function _is_active()
	{
		return (get_forum_type()=='ocf');
	}

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the content type
	 */
	function _enumerate_file_properties()
	{
		return array(
			'description'=>'LONG_TEXT',
			'expanded_by_default'=>'BINARY',
		);
	}

	/**
	 * Standard modular date fetch function for OcCLE-fs resource hooks. Defined when getting an edit date is not easy.
	 *
	 * @param  array			Content row (not full, but does contain the ID)
	 * @return ?TIME			The edit date or add date, whichever is higher (NULL: could not find one)
	 */
	function _get_file_edit_date($row)
	{
		$query='SELECT MAX(date_and_time) FROM '.get_table_prefix().'adminlogs WHERE '.db_string_equal_to('param_a',strval($row['id'])).' AND  ('.db_string_equal_to('the_type','ADD_FORUM_GROUPING').' OR '.db_string_equal_to('the_type','EDIT_FORUM_GROUPING').')';
		return $GLOBALS['SITE_DB']->query_value_if_there($query);
	}

	/**
	 * Standard modular add function for OcCLE-fs resource hooks. Adds some content with the given label and properties.
	 *
	 * @param  SHORT_TEXT	Filename OR Content label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The content ID (false: error, could not create via these properties / here)
	 */
	function file_add($filename,$path,$properties)
	{
		list($category_content_type,$category)=$this->folder_convert_filename_to_id($path);
		list($properties,$label)=$this->_file_magic_filter($filename,$path,$properties);

		require_code('ocf_forums_action');

		$description=$this->_default_property_str($properties,'description');
		$expanded_by_default=$this->_default_property_int($properties,'expanded_by_default');

		$id=ocf_make_forum_grouping($label,$description,$expanded_by_default);
		return strval($id);
	}

	/**
	 * Standard modular load function for OcCLE-fs resource hooks. Finds the properties for some content.
	 *
	 * @param  SHORT_TEXT	Filename
	 * @param  string			The path (blank: root / not applicable)
	 * @return ~array			Details of the content (false: error)
	 */
	function _file_load($filename,$path)
	{
		list($content_type,$content_id)=$this->file_convert_filename_to_id($filename);

		$rows=$GLOBALS['FORUM_DB']->query_select('f_forum_groupings',array('*'),array('id'=>intval($content_id)),'',1);
		if (!array_key_exists(0,$rows)) return false;
		$row=$rows[0];

		return array(
			'label'=>$row['c_title'],
			'description'=>$row['c_description'],
			'expanded_by_default'=>$row['c_expanded_by_default'],
		);
	}

	/**
	 * Standard modular edit function for OcCLE-fs resource hooks. Edits the content to the given properties.
	 *
	 * @param  ID_TEXT		The filename
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return boolean		Success status
	 */
	function file_edit($filename,$path,$properties)
	{
		list($content_type,$content_id)=$this->file_convert_filename_to_id($filename);

		require_code('ocf_forums_action2');

		$label=$this->_default_property_str($properties,'label');
		$description=$this->_default_property_str($properties,'description');
		$expanded_by_default=$this->_default_property_int($properties,'expanded_by_default');

		ocf_edit_forum_grouping(intval($content_id),$label,$description,$expanded_by_default);

		return true;
	}

	/**
	 * Standard modular delete function for OcCLE-fs resource hooks. Deletes the content.
	 *
	 * @param  ID_TEXT		The filename
	 * @return boolean		Success status
	 */
	function file_delete($filename)
	{
		list($content_type,$content_id)=$this->file_convert_filename_to_id($filename);

		require_code('ocf_forums_action2');
		ocf_delete_forum_grouping(intval($content_id));

		return true;
	}
}

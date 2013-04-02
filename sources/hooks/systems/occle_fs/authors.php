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
 * @package		authors
 */

require_code('resource_fs');

class Hook_occle_fs_authors extends resource_fs_base
{
	var $file_resource_type='author';

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the resource type
	 */
	function _enumerate_file_properties()
	{
		return array(
			'url'=>'URLPATH',
			'member_id'=>'member',
			'description'=>'LONG_TRANS',
			'skills'=>'LONG_TRANS',
			'meta_keywords'=>'LONG_TEXT',
			'meta_description'=>'LONG_TEXT',
		);
	}

	/**
	 * Standard modular date fetch function for OcCLE-fs resource hooks. Defined when getting an edit date is not easy.
	 *
	 * @param  array			Resource row (not full, but does contain the ID)
	 * @return ?TIME			The edit date or add date, whichever is higher (NULL: could not find one)
	 */
	function _get_file_edit_date($row)
	{
		$query='SELECT MAX(date_and_time) FROM '.get_table_prefix().'adminlogs WHERE '.db_string_equal_to('param_a',$row['author']).' AND  ('.db_string_equal_to('the_type','DEFINE_AUTHOR').')';
		return $GLOBALS['SITE_DB']->query_value_if_there($query);
	}

	/**
	 * Standard modular add function for OcCLE-fs resource hooks. Adds some resource with the given label and properties.
	 *
	 * @param  SHORT_TEXT	Filename OR Resource label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The resource ID (false: error, could not create via these properties / here)
	 */
	function file_add($filename,$path,$properties)
	{
		list($properties,$label)=$this->_file_magic_filter($filename,$path,$properties);

		require_code('authors');

		$url=$this->_default_property_str($properties,'url');
		$member_id=$this->_default_property_int_null($properties,'member_id');
		$description=$this->_default_property_str($properties,'description');
		$skills=$this->_default_property_str($properties,'skills');
		$meta_keywords=$this->_default_property_str($properties,'meta_keywords');
		$meta_description=$this->_default_property_str($properties,'meta_description');

		add_author($label,$url,$member_id,$description,$skills,$meta_keywords,$meta_description);

		return $label;
	}

	/**
	 * Standard modular load function for OcCLE-fs resource hooks. Finds the properties for some resource.
	 *
	 * @param  SHORT_TEXT	Filename
	 * @param  string			The path (blank: root / not applicable)
	 * @return ~array			Details of the resource (false: error)
	 */
	function _file_load($filename,$path)
	{
		list($resource_type,$resource_id)=$this->file_convert_filename_to_id($filename);

		$rows=$GLOBALS['SITE_DB']->query_select('authors',array('*'),array('author'=>$resource_id),'',1);
		if (!array_key_exists(0,$rows)) return false;
		$row=$rows[0];

		return array(
			'label'=>$row['author'],
			'url'=>$row['url'],
			'member_id'=>$row['member_id'],
			'description'=>$row['description'],
			'skills'=>$row['skills'],
			'meta_keywords'=>$this->get_meta_keywords('authors',$row['author']),
			'meta_description'=>$this->get_meta_description('authors',$row['author']),
		);
	}

	/**
	 * Standard modular edit function for OcCLE-fs resource hooks. Edits the resource to the given properties.
	 *
	 * @param  ID_TEXT		The filename
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return boolean		Success status
	 */
	function file_edit($filename,$path,$properties)
	{
		list($resource_type,$resource_id)=$this->file_convert_filename_to_id($filename);
		list($properties,)=$this->_file_magic_filter($filename,$path,$properties);

		require_code('authors');

		$label=$this->_default_property_str($properties,'label');
		$url=$this->_default_property_str($properties,'url');
		$member_id=$this->_default_property_int_null($properties,'member_id');
		$description=$this->_default_property_str($properties,'description');
		$skills=$this->_default_property_str($properties,'skills');
		$meta_keywords=$this->_default_property_str($properties,'meta_keywords');
		$meta_description=$this->_default_property_str($properties,'meta_description');

		// Author editing works via re-adding
		if ($label!=$resource_id) delete_author($resource_id); // Delete old one if we renamed
		add_author($label,$url,$member_id,$description,$skills,$meta_keywords,$meta_description);

		return true;
	}

	/**
	 * Standard modular delete function for OcCLE-fs resource hooks. Deletes the resource.
	 *
	 * @param  ID_TEXT		The filename
	 * @return boolean		Success status
	 */
	function file_delete($filename)
	{
		list($resource_type,$resource_id)=$this->file_convert_filename_to_id($filename);

		require_code('authors');
		delete_author($resource_id);

		return true;
	}
}

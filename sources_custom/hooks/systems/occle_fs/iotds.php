<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		iotds
 */

require_code('resource_fs');

class Hook_occle_fs_iotds extends resource_fs_base
{
	var $file_resource_type='iotd';

	/**
	 * Standard modular function for seeing how many resources are. Useful for determining whether to do a full rebuild.
	 *
	 * @param  ID_TEXT		The resource type
	 * @return integer		How many resources there are
	 */
	function get_resources_count($resource_type)
	{
		return $GLOBALS['SITE_DB']->query_select_value('iotd','COUNT(*)');
	}

	/**
	 * Standard modular function for searching for a resource by label.
	 *
	 * @param  ID_TEXT		The resource type
	 * @param  LONG_TEXT		The resource label
	 * @return array			A list of resource IDs
	 */
	function find_resource_by_label($resource_type,$label)
	{
		$_ret=$GLOBALS['SITE_DB']->query_select('iotd',array('id'),array($GLOBALS['SITE_DB']->translate_field_ref('i_title')=>$label));
		$ret=array();
		foreach ($_ret as $r)
		{
			$ret[]=strval($r['id']);
		}
		return $ret;
	}

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the resource type
	 */
	function _enumerate_file_properties()
	{
		return array(
			'url'=>'URLPATH',
			'caption'=>'LONG_TRANS',
			'thumb_url'=>'URLPATH',
			'current'=>'BINARY',
			'allow_rating'=>'BINARY',
			'allow_comments'=>'SHORT_INTEGER',
			'allow_trackbacks'=>'BINARY',
			'notes'=>'LONG_TEXT',
			'used'=>'BINARY',
			'use_time'=>'?TIME',
			'views'=>'INTEGER',
			'submitter'=>'member',
			'add_date'=>'TIME',
			'edit_date'=>'TIME',
		);
	}

	/**
	 * Standard modular add function for resource-fs hooks. Adds some resource with the given label and properties.
	 *
	 * @param  LONG_TEXT		Filename OR Resource label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The resource ID (false: error, could not create via these properties / here)
	 */
	function file_add($filename,$path,$properties)
	{
		list($properties,$label)=$this->_file_magic_filter($filename,$path,$properties);

		require_code('iotds2');

		$url=$this->_default_property_str($properties,'url');
		$caption=$this->_default_property_str($properties,'caption');
		$thumb_url=$this->_default_property_str($properties,'thumb_url');
		$current=$this->_default_property_int($properties,'current');
		$allow_rating=$this->_default_property_int_modeavg($properties,'allow_rating','iotd',1);
		$allow_comments=$this->_default_property_int_modeavg($properties,'allow_comments','iotd',1);
		$allow_trackbacks=$this->_default_property_int_modeavg($properties,'allow_trackbacks','iotd',1);
		$notes=$this->_default_property_str($properties,'notes');
		$time=$this->_default_property_int_null($properties,'add_date');
		$submitter=$this->_default_property_int_null($properties,'submitter');
		$used=$this->_default_property_int($properties,'used');
		$use_time=$this->_default_property_int_null($properties,'use_time');
		$views=$this->_default_property_int($properties,'views');
		$edit_date=$this->_default_property_int_null($properties,'edit_date');
		$id=add_iotd($url,$label,$caption,$thumb_url,$current,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$time,$submitter,$used,$use_time,$views,$edit_date);
		return strval($id);
	}

	/**
	 * Standard modular load function for resource-fs hooks. Finds the properties for some resource.
	 *
	 * @param  SHORT_TEXT	Filename
	 * @param  string			The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
	 * @return ~array			Details of the resource (false: error)
	 */
	function file_load($filename,$path)
	{
		list($resource_type,$resource_id)=$this->file_convert_filename_to_id($filename);

		$rows=$GLOBALS['SITE_DB']->query_select('iotd',array('*'),array('id'=>intval($resource_id)),'',1);
		if (!array_key_exists(0,$rows)) return false;
		$row=$rows[0];

		return array(
			'label'=>$row['i_title'],
			'url'=>$row['url'],
			'caption'=>$row['caption'],
			'thumb_url'=>$row['thumb_url'],
			'current'=>$row['is_current'],
			'allow_rating'=>$row['allow_rating'],
			'allow_comments'=>$row['allow_comments'],
			'allow_trackbacks'=>$row['allow_trackbacks'],
			'notes'=>$row['notes'],
			'used'=>$row['used'],
			'use_time'=>$row['date_and_time'],
			'views'=>$row['iotd_views'],
			'submitter'=>$row['submitter'],
			'add_date'=>$row['add_date'],
			'edit_date'=>$row['edit_date'],
		);
	}

	/**
	 * Standard modular edit function for resource-fs hooks. Edits the resource to the given properties.
	 *
	 * @param  ID_TEXT		The filename
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The resource ID (false: error, could not create via these properties / here)
	 */
	function file_edit($filename,$path,$properties)
	{
		list($resource_type,$resource_id)=$this->file_convert_filename_to_id($filename);
		list($properties,)=$this->_file_magic_filter($filename,$path,$properties);

		require_code('iotds2');

		$label=$this->_default_property_str($properties,'label');
		$url=$this->_default_property_str($properties,'url');
		$caption=$this->_default_property_str($properties,'caption');
		$thumb_url=$this->_default_property_str($properties,'thumb_url');
		$current=$this->_default_property_int($properties,'current');
		$allow_rating=$this->_default_property_int_modeavg($properties,'allow_rating','iotd',1);
		$allow_comments=$this->_default_property_int_modeavg($properties,'allow_comments','iotd',1);
		$allow_trackbacks=$this->_default_property_int_modeavg($properties,'allow_trackbacks','iotd',1);
		$notes=$this->_default_property_str($properties,'notes');
		$add_time=$this->_default_property_int_null($properties,'add_date');
		$submitter=$this->_default_property_int_null($properties,'submitter');
		$used=$this->_default_property_int($properties,'used');
		$use_time=$this->_default_property_int_null($properties,'use_time');
		$views=$this->_default_property_int($properties,'views');
		$edit_time=$this->_default_property_int_null($properties,'edit_date');

		edit_iotd(intval($resource_id),$label,$caption,$thumb_url,$url,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$edit_time,$add_time,$views,$submitter,true);

		return $resource_id;
	}

	/**
	 * Standard modular delete function for resource-fs hooks. Deletes the resource.
	 *
	 * @param  ID_TEXT		The filename
	 * @param  string			The path (blank: root / not applicable)
	 * @return boolean		Success status
	 */
	function file_delete($filename,$path)
	{
		list($resource_type,$resource_id)=$this->file_convert_filename_to_id($filename);

		require_code('iotds2');
		delete_iotd(intval($resource_id));

		return true;
	}
}

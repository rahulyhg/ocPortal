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
 * @package		ecommerce
 */

require_code('resource_fs');

class Hook_occle_fs_usergroup_subscriptions extends resource_fs_base
{
	var $file_resource_type='usergroup_subscription';

	/**
	 * Standard modular function for seeing how many resources are. Useful for determining whether to do a full rebuild.
	 *
	 * @param  ID_TEXT		The resource type
	 * @return integer		How many resources there are
	 */
	function get_resources_count($resource_type)
	{
		return $GLOBALS['FORUM_DB']->query_select_value('f_usergroup_subs','COUNT(*)');
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
		$_ret=$GLOBALS['FORUM_DB']->query_select('f_usergroup_subs',array('id'),array($GLOBALS['FORUM_DB']->translate_field_ref('s_title')=>$label));
		$ret=array();
		foreach ($_ret as $r)
		{
			$ret[]=strval($r['id']);
		}
		return $ret;
	}

	/**
	 * Whether the filesystem hook is active.
	 *
	 * @return boolean		Whether it is
	 */
	function _is_active()
	{
		return (get_forum_type()=='ocf') && (!is_ocf_satellite_site());
	}

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the resource type
	 */
	function _enumerate_file_properties()
	{
		return array(
			'description'=>'LONG_TRANS',
			'cost'=>'SHORT_TEXT',
			'length'=>'INTEGER',
			'length_units'=>'SHORT_TEXT',
			'group_id'=>'GROUP',
			'enabled'=>'BINARY',
			'mail_start'=>'LONG_TRANS',
			'mail_end'=>'LONG_TRANS',
			'mail_uhoh'=>'LONG_TRANS',
			'uses_primary'=>'BINARY',
			'mails'=>'LONG_TEXT',
		);
	}

	/**
	 * Standard modular date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
	 *
	 * @param  array			Resource row (not full, but does contain the ID)
	 * @return ?TIME			The edit date or add date, whichever is higher (NULL: could not find one)
	 */
	function _get_file_edit_date($row)
	{
		$query='SELECT MAX(date_and_time) FROM '.get_table_prefix().'adminlogs WHERE '.db_string_equal_to('param_a',strval($row['id'])).' AND  ('.db_string_equal_to('the_type','ADD_USERGROUP_SUBSCRIPTION').' OR '.db_string_equal_to('the_type','EDIT_USERGROUP_SUBSCRIPTION').')';
		return $GLOBALS['SITE_DB']->query_value_if_there($query);
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

		require_code('ecommerce2');

		$description=$this->_default_property_str($properties,'description');
		$cost=$this->_default_property_int($properties,'cost');
		$length=$this->_default_property_int($properties,'length');
		$length_units=$this->_default_property_str($properties,'length_units');
		$auto_recur=$this->_default_property_int($properties,'auto_recur');
		$group_id=$this->_default_property_int($properties,'group_id');
		$uses_primary=$this->_default_property_int($properties,'uses_primary');
		$enabled=$this->_default_property_int($properties,'enabled');
		$mail_start=$this->_default_property_str($properties,'mail_start');
		$mail_end=$this->_default_property_str($properties,'mail_end');
		$mail_uhoh=$this->_default_property_str($properties,'mail_uhoh');
		$_mails=$this->_default_property_str($properties,'mails');
		$mails=($_mails=='')?array():unserialize($_mails);

		$id=add_usergroup_subscription($label,$description,$cost,$length,$length_units,$auto_recur,$group_id,$uses_primary,$enabled,$mail_start,$mail_end,$mail_uhoh,$mails);
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

		$rows=$GLOBALS['FORUM_DB']->query_select('f_usergroup_subs',array('*'),array('id'=>intval($resource_id)),'',1);
		if (!array_key_exists(0,$rows)) return false;
		$row=$rows[0];

		$dbs_bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;

		$_mails=$GLOBALS['FORUM_DB']->query_select('f_usergroup_sub_mails',array('*'),array('m_usergroup_sub_id'=>intval($resource_id)),'ORDER BY id');
		$mails=array();
		foreach ($_mails as $_mail)
		{
			$mails[]=array(
				'subject'=>get_translated_text($_mail['m_subject'],$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
				'body'=>get_translated_text($_mail['m_body'],$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
				'ref_point'=>$_mail['m_ref_point'],
				'ref_point_offset'=>$_mail['m_ref_point_offset'],
			);
		}

		$GLOBALS['NO_DB_SCOPE_CHECK']=$dbs_bak;

		return array(
			'label'=>$row['s_title'],
			'description'=>$row['s_description'],
			'cost'=>$row['s_cost'],
			'length'=>$row['s_length'],
			'length_units'=>$row['s_length_units'],
			'group_id'=>$row['s_group_id'],
			'enabled'=>$row['s_enabled'],
			'mail_start'=>$row['s_mail_start'],
			'mail_end'=>$row['s_mail_end'],
			'mail_uhoh'=>$row['s_mail_uhoh'],
			'uses_primary'=>$row['s_uses_primary'],
			'mails'=>serialize($mails),
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

		require_code('ecommerce2');

		$label=$this->_default_property_str($properties,'label');
		$description=$this->_default_property_str($properties,'description');
		$cost=$this->_default_property_int($properties,'cost');
		$length=$this->_default_property_int($properties,'length');
		$length_units=$this->_default_property_str($properties,'length_units');
		$auto_recur=$this->_default_property_int($properties,'auto_recur');
		$group_id=$this->_default_property_int($properties,'group_id');
		$uses_primary=$this->_default_property_int($properties,'uses_primary');
		$enabled=$this->_default_property_int($properties,'enabled');
		$mail_start=$this->_default_property_str($properties,'mail_start');
		$mail_end=$this->_default_property_str($properties,'mail_end');
		$mail_uhoh=$this->_default_property_str($properties,'mail_uhoh');
		$_mails=$this->_default_property_str($properties,'mails');
		$mails=($_mails=='')?array():unserialize($_mails);

		edit_usergroup_subscription(intval($resource_id),$label,$description,$cost,$length,$length_units,$auto_recur,$group_id,$uses_primary,$enabled,$mail_start,$mail_end,$mail_uhoh,$mails);

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

		require_code('ecommerce2');
		delete_usergroup_subscription(intval($resource_id));

		return true;
	}
}

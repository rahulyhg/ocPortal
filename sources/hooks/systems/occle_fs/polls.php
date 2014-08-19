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
 * @package		polls
 */

require_code('resource_fs');

class Hook_occle_fs_polls extends resource_fs_base
{
	var $file_resource_type='poll';

	/**
	 * Standard modular function for seeing how many resources are. Useful for determining whether to do a full rebuild.
	 *
	 * @param  ID_TEXT		The resource type
	 * @return integer		How many resources there are
	 */
	function get_resources_count($resource_type)
	{
		return $GLOBALS['SITE_DB']->query_select_value('poll','COUNT(*)');
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
		$_ret=$GLOBALS['SITE_DB']->query_select('poll',array('id'),array($GLOBALS['SITE_DB']->translate_field_ref('question')=>$label));
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
			'answer1'=>'SHORT_TRANS',
			'answer2'=>'SHORT_TRANS',
			'answer3'=>'SHORT_TRANS',
			'answer4'=>'SHORT_TRANS',
			'answer5'=>'SHORT_TRANS',
			'answer6'=>'SHORT_TRANS',
			'answer7'=>'SHORT_TRANS',
			'answer8'=>'SHORT_TRANS',
			'answer9'=>'SHORT_TRANS',
			'answer10'=>'SHORT_TRANS',
			'current'=>'BINARY',
			'allow_rating'=>'BINARY',
			'allow_comments'=>'SHORT_INTEGER',
			'allow_trackbacks'=>'BINARY',
			'notes'=>'LONG_TEXT',
			'use_time'=>'?TIME',
			'votes1'=>'INTEGER',
			'votes2'=>'INTEGER',
			'votes3'=>'INTEGER',
			'votes4'=>'INTEGER',
			'votes5'=>'INTEGER',
			'votes6'=>'INTEGER',
			'votes7'=>'INTEGER',
			'votes8'=>'INTEGER',
			'votes9'=>'INTEGER',
			'votes10'=>'INTEGER',
			'views'=>'INTEGER',
			'submitter'=>'member',
			'add_date'=>'TIME',
			'edit_date'=>'?TIME',
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

		require_code('polls2');

		$a1=$this->_default_property_str($properties,'answer1');
		$a2=$this->_default_property_str($properties,'answer2');
		$a3=$this->_default_property_str($properties,'answer3');
		$a4=$this->_default_property_str($properties,'answer4');
		$a5=$this->_default_property_str($properties,'answer5');
		$a6=$this->_default_property_str($properties,'answer6');
		$a7=$this->_default_property_str($properties,'answer7');
		$a8=$this->_default_property_str($properties,'answer8');
		$a9=$this->_default_property_str($properties,'answer9');
		$a10=$this->_default_property_str($properties,'answer10');
		$num_options=10;
		if ($a10=='') $num_options=9;
		if ($a9=='') $num_options=8;
		if ($a8=='') $num_options=7;
		if ($a7=='') $num_options=6;
		if ($a6=='') $num_options=5;
		if ($a5=='') $num_options=4;
		if ($a4=='') $num_options=3;
		if ($a3=='') $num_options=2;
		if ($a2=='') $num_options=1;
		$current=$this->_default_property_int($properties,'current');
		$allow_rating=$this->_default_property_int_modeavg($properties,'allow_rating','poll',1);
		$allow_comments=$this->_default_property_int_modeavg($properties,'allow_comments','poll',1);
		$allow_trackbacks=$this->_default_property_int_modeavg($properties,'allow_trackbacks','poll',1);
		$notes=$this->_default_property_str($properties,'notes');
		$time=$this->_default_property_int_null($properties,'add_date');
		$submitter=$this->_default_property_int_null($properties,'submitter');
		$use_time=$this->_default_property_int_null($properties,'use_time');
		$v1=$this->_default_property_int($properties,'votes1');
		$v2=$this->_default_property_int($properties,'votes2');
		$v3=$this->_default_property_int($properties,'votes3');
		$v4=$this->_default_property_int($properties,'votes4');
		$v5=$this->_default_property_int($properties,'votes5');
		$v6=$this->_default_property_int($properties,'votes6');
		$v7=$this->_default_property_int($properties,'votes7');
		$v8=$this->_default_property_int($properties,'votes8');
		$v9=$this->_default_property_int($properties,'votes9');
		$v10=$this->_default_property_int($properties,'votes10');
		$views=$this->_default_property_int($properties,'views');
		$edit_date=$this->_default_property_int_null($properties,'edit_date');
		$id=add_poll($label,$a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10,$num_options,$current,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$time,$submitter,$use_time,$v1,$v2,$v3,$v4,$v5,$v6,$v7,$v8,$v9,$v10,$views,$edit_date);
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

		$rows=$GLOBALS['SITE_DB']->query_select('poll',array('*'),array('id'=>intval($resource_id)),'',1);
		if (!array_key_exists(0,$rows)) return false;
		$row=$rows[0];

		return array(
			'label'=>$row['question'],
			'answer1'=>$row['option1'],
			'answer2'=>$row['option2'],
			'answer3'=>$row['option3'],
			'answer4'=>$row['option4'],
			'answer5'=>$row['option5'],
			'answer6'=>$row['option6'],
			'answer7'=>$row['option7'],
			'answer8'=>$row['option8'],
			'answer9'=>$row['option9'],
			'answer10'=>$row['option10'],
			'current'=>$row['is_current'],
			'allow_rating'=>$row['allow_rating'],
			'allow_comments'=>$row['allow_comments'],
			'allow_trackbacks'=>$row['allow_trackbacks'],
			'notes'=>$row['notes'],
			'use_time'=>$row['date_and_time'],
			'votes1'=>$row['votes1'],
			'votes2'=>$row['votes2'],
			'votes3'=>$row['votes3'],
			'votes4'=>$row['votes4'],
			'votes5'=>$row['votes5'],
			'votes6'=>$row['votes6'],
			'votes7'=>$row['votes7'],
			'votes8'=>$row['votes8'],
			'votes9'=>$row['votes9'],
			'votes10'=>$row['votes10'],
			'views'=>$row['poll_views'],
			'submitter'=>$row['submitter'],
			'add_date'=>$row['add_time'],
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

		require_code('polls2');

		$label=$this->_default_property_str($properties,'label');
		$a1=$this->_default_property_str($properties,'answer1');
		$a2=$this->_default_property_str($properties,'answer2');
		$a3=$this->_default_property_str($properties,'answer3');
		$a4=$this->_default_property_str($properties,'answer4');
		$a5=$this->_default_property_str($properties,'answer5');
		$a6=$this->_default_property_str($properties,'answer6');
		$a7=$this->_default_property_str($properties,'answer7');
		$a8=$this->_default_property_str($properties,'answer8');
		$a9=$this->_default_property_str($properties,'answer9');
		$a10=$this->_default_property_str($properties,'answer10');
		$num_options=10;
		if ($a10=='') $num_options=9;
		if ($a9=='') $num_options=8;
		if ($a8=='') $num_options=7;
		if ($a7=='') $num_options=6;
		if ($a6=='') $num_options=5;
		if ($a5=='') $num_options=4;
		if ($a4=='') $num_options=3;
		if ($a3=='') $num_options=2;
		if ($a2=='') $num_options=1;
		$current=$this->_default_property_int($properties,'current');
		$allow_rating=$this->_default_property_int_modeavg($properties,'allow_rating','poll',1);
		$allow_comments=$this->_default_property_int_modeavg($properties,'allow_comments','poll',1);
		$allow_trackbacks=$this->_default_property_int_modeavg($properties,'allow_trackbacks','poll',1);
		$notes=$this->_default_property_str($properties,'notes');
		$add_time=$this->_default_property_int_null($properties,'add_date');
		$submitter=$this->_default_property_int_null($properties,'submitter');
		$use_time=$this->_default_property_int_null($properties,'use_time');
		$v1=$this->_default_property_int($properties,'votes1');
		$v2=$this->_default_property_int($properties,'votes2');
		$v3=$this->_default_property_int($properties,'votes3');
		$v4=$this->_default_property_int($properties,'votes4');
		$v5=$this->_default_property_int($properties,'votes5');
		$v6=$this->_default_property_int($properties,'votes6');
		$v7=$this->_default_property_int($properties,'votes7');
		$v8=$this->_default_property_int($properties,'votes8');
		$v9=$this->_default_property_int($properties,'votes9');
		$v10=$this->_default_property_int($properties,'votes10');
		$views=$this->_default_property_int($properties,'views');
		$edit_time=$this->_default_property_int_null($properties,'edit_date');

		edit_poll(intval($resource_id),$label,$a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10,$num_options,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$edit_time,$add_time,$views,$submitter,true);

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

		require_code('polls2');
		delete_poll(intval($resource_id));

		return true;
	}
}

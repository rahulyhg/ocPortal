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
 * @package		core_fields
 */

class Hook_fields_reference
{
	/**
	 * Find what field types this hook can serve. This method only needs to be defined if it is not serving a single field type with a name corresponding to the hook itself.
	 *
	 * @param  ?ID_TEXT		Only find if we can potential match this field type (NULL: no filter)
	 * @return array			Map of field type to field type title
	 */
	function get_field_types($filter=NULL)
	{
		if (!addon_installed('catalogues')) return array();

		if (($filter!==NULL) && (substr($filter,0,3)!='ck_')) return array(); // To avoid a wasteful query

		require_lang('fields');
		static $cats=NULL;
		if (is_null($cats))
			$cats=$GLOBALS['SITE_DB']->query_select('catalogues',array('c_name','c_title'));
		$ret=array();
		foreach ($cats as $cat)
		{
			$ret['ck_'.$cat['c_name']]=do_lang_tempcode('FIELD_TYPE_reference_x',get_translated_tempcode('catalogues',$cat,'c_title'));
		}
		return $ret;
	}

	// ==============
	// Module: search
	// ==============

	/**
	 * Get special Tempcode for inputting this field.
	 *
	 * @param  array			The row for the field to input
	 * @return ?array			List of specially encoded input detail rows (NULL: nothing special)
	 */
	function get_search_inputter($row)
	{
		return NULL;
	}

	/**
	 * Get special SQL from POSTed parameters for this field.
	 *
	 * @param  array			The row for the field to input
	 * @param  integer		We're processing for the ith row
	 * @return ?array			Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (NULL: nothing special)
	 */
	function inputted_to_sql_for_search($row,$i)
	{
		return exact_match_sql($row,$i);
	}

	// ===================
	// Backend: fields API
	// ===================

	/**
	 * Get some info bits relating to our field type, that helps us look it up / set defaults.
	 *
	 * @param  ?array			The field details (NULL: new field)
	 * @param  ?boolean		Whether a default value cannot be blank (NULL: don't "lock in" a new default value)
	 * @param  ?string		The given default value as a string (NULL: don't "lock in" a new default value)
	 * @return array			Tuple of details (row-type,default-value-to-use,db row-type)
	 */
	function get_field_value_row_bits($field,$required=NULL,$default=NULL)
	{
		return array('short_unescaped',$default,'short');
	}

	/**
	 * Convert a field value to something renderable.
	 *
	 * @param  array			The field details
	 * @param  mixed			The raw value
	 * @return mixed			Rendered field (tempcode or string)
	 */
	function render_field_value($field,$ev)
	{
		if (is_object($ev)) return $ev;

		if ($ev=='') return new ocp_tempcode();

		require_code('content');

		list($title)=content_get_details('catalogue_entry',$ev);

		return hyperlink(build_url(array('page'=>'catalogues','type'=>'entry','id'=>$ev),get_module_zone('catalogues')),$title,false,true);
	}

	// ======================
	// Frontend: fields input
	// ======================

	/**
	 * Get form inputter.
	 *
	 * @param  string			The field name
	 * @param  string			The field description
	 * @param  array			The field details
	 * @param  ?string		The actual current value of the field (NULL: none)
	 * @param  boolean		Whether this is for a new entry
	 * @return ?tempcode		The Tempcode for the input field (NULL: skip the field - it's not input)
	 */
	function get_field_inputter($_cf_name,$_cf_description,$field,$actual_value,$new)
	{
		$options=array();
		if (($field['cf_type']!='reference') && (substr($field['cf_type'],0,3)=='ck_'))
		{
			$options['catalogue_name']=substr($field['cf_type'],3);
		}
		require_code('content');
		list($nice_label)=content_get_details('catalogue_entry',$actual_value);
		return form_input_tree_list($_cf_name,$_cf_description,'field_'.strval($field['id']),NULL,'choose_catalogue_entry',$options,$field['cf_required']==1,$actual_value,false,NULL,false,$nice_label);
	}

	/**
	 * Find the posted value from the get_field_inputter field
	 *
	 * @param  boolean		Whether we were editing (because on edit, it could be a fractional edit)
	 * @param  array			The field details
	 * @param  ?string		Where the files will be uploaded to (NULL: do not store an upload, return NULL if we would need to do so)
	 * @param  ?array			Former value of field (NULL: none)
	 * @return ?string		The value (NULL: could not process)
	 */
	function inputted_to_field_value($editing,$field,$upload_dir='uploads/catalogues',$old_value=NULL)
	{
		$id=$field['id'];
		$tmp_name='field_'.strval($id);
		return post_param($tmp_name,$editing?STRING_MAGIC_NULL:'');
	}
}


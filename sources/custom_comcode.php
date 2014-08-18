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
 * @package		custom_comcode
 */

/**
 * Add a custom Comcode tag.
 *
 * @param  ID_TEXT		Tag name.
 * @param  mixed			Tag title (string, or language ID).
 * @param  mixed			Tag description (string, or language ID).
 * @param  LONG_TEXT		Tag replacement text.
 * @param  LONG_TEXT		Tag example.
 * @param  SHORT_TEXT	Tag parameters.
 * @param  BINARY			Whether the tag is enabled.
 * @param  BINARY			Whether the tag is disabled.
 * @param  BINARY			Whether the tag is a block tag.
 * @param  BINARY			Whether the tag is a textual tag.
 * @param  boolean		Whether to force the name as unique, if there's a conflict
 * @return ID_TEXT		The tag name
 */
function add_custom_comcode_tag($tag,$title,$description,$replace,$example,$parameters,$enabled,$dangerous_tag,$block_tag,$textual_tag,$uniqify=false)
{
	require_code('type_validation');
	if (!is_alphanumeric($tag,true)) warn_exit(do_lang_tempcode('BAD_CODENAME'));

	require_code('comcode_compiler');

	global $VALID_COMCODE_TAGS;
	$test=$GLOBALS['SITE_DB']->query_select_value_if_there('custom_comcode','tag_tag',array('tag_tag'=>$tag));
	if ((array_key_exists($tag,$VALID_COMCODE_TAGS)) || (!is_null($test)))
	{
		if ($uniqify)
		{
			$tag.='_'.uniqid('',true);
		} else
		{
			warn_exit(do_lang_tempcode('ALREADY_EXISTS',escape_html($tag)));
		}
	}

	$GLOBALS['SITE_DB']->query_insert('custom_comcode',array(
		'tag_tag'=>$tag,
		'tag_title'=>is_integer($title)?$title:insert_lang($title,2),
		'tag_description'=>is_integer($description)?$description:insert_lang($description,2),
		'tag_replace'=>$replace,
		'tag_example'=>$example,
		'tag_parameters'=>$parameters,
		'tag_enabled'=>$enabled,
		'tag_dangerous_tag'=>$dangerous_tag,
		'tag_block_tag'=>$block_tag,
		'tag_textual_tag'=>$textual_tag,
	));

	log_it('ADD_CUSTOM_COMCODE_TAG',$tag);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('custom_comcode_tag',$tag,NULL,NULL,true);
	}

	return $tag;
}

/**
 * Edit a custom Comcode tag.
 *
 * @param  ID_TEXT		Current tag name.
 * @param  ID_TEXT		New tag name.
 * @param  SHORT_TEXT	Tag title (string, or language ID).
 * @param  LONG_TEXT		Tag description (string, or language ID).
 * @param  LONG_TEXT		Tag replacement text.
 * @param  LONG_TEXT		Tag example.
 * @param  SHORT_TEXT	Tag parameters.
 * @param  BINARY			Whether the tag is enabled.
 * @param  BINARY			Whether the tag is disabled.
 * @param  BINARY			Whether the tag is a block tag.
 * @param  BINARY			Whether the tag is a textual tag.
 * @param  boolean		Whether to force the name as unique, if there's a conflict
 * @return ID_TEXT		The tag name
 */
function edit_custom_comcode_tag($old_tag,$tag,$title,$description,$replace,$example,$parameters,$enabled,$dangerous_tag,$block_tag,$textual_tag,$uniqify=false)
{
	require_code('type_validation');
	if (!is_alphanumeric($tag,true)) warn_exit(do_lang_tempcode('BAD_CODENAME'));

	require_code('comcode_compiler');

	global $VALID_COMCODE_TAGS;
	$test=$GLOBALS['SITE_DB']->query_select_value_if_there('custom_comcode','tag_tag',array('tag_tag'=>$tag));
	if ($old_tag==$tag) $test=NULL;
	if ((array_key_exists($tag,$VALID_COMCODE_TAGS)) || (!is_null($test)))
	{
		if ($uniqify)
		{
			$tag.='_'.uniqid('',true);
		} else
		{
			warn_exit(do_lang_tempcode('ALREADY_EXISTS',escape_html($tag)));
		}
	}

	$old=$GLOBALS['SITE_DB']->query_select('custom_comcode',array('tag_title','tag_description'),array('tag_tag'=>$old_tag),'',1);
	if (!array_key_exists(0,$old)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$_title=$old[0]['tag_title'];
	$_description=$old[0]['tag_description'];

	$map=array(
		'tag_tag'=>$tag,
		'tag_replace'=>$replace,
		'tag_example'=>$example,
		'tag_parameters'=>$parameters,
		'tag_enabled'=>$enabled,
		'tag_dangerous_tag'=>$dangerous_tag,
		'tag_block_tag'=>$block_tag,
		'tag_textual_tag'=>$textual_tag,
	);
	$map+=lang_remap('tag_title',$_title,$title);
	$map+=lang_remap('tag_description',$_description,$description);
	$GLOBALS['SITE_DB']->query_update('custom_comcode',$map,array('tag_tag'=>$old_tag),'',1);

	log_it('EDIT_CUSTOM_COMCODE_TAG',$tag,$old_tag);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('custom_comcode_tag',$tag);
	}

	return $tag;
}

/**
 * Delete a custom Comcode tag.
 *
 * @param  ID_TEXT		Tag name.
 */
function delete_custom_comcode_tag($tag)
{
	$old=$GLOBALS['SITE_DB']->query_select('custom_comcode',array('tag_title','tag_description'),array('tag_tag'=>$tag),'',1);
	if (!array_key_exists(0,$old)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$_title=$old[0]['tag_title'];
	$_description=$old[0]['tag_description'];
	delete_lang($_title);
	delete_lang($_description);

	$GLOBALS['SITE_DB']->query_delete('custom_comcode',array(
		'tag_tag'=>$tag,
	),'',1);

	log_it('DELETE_CUSTOM_COMCODE_TAG',$tag);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		expunge_resourcefs_moniker('custom_comcode_tag',$tag);
	}
}

<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		import
 */

/**
 * Standard code module initialisation function.
 */
function init__import()
{
	global $REMAP_CACHE;
	$REMAP_CACHE=array();
}

/**
 * Load lots that the importer needs to run.
 */
function load_import_deps()
{
	require_all_lang();
	require_code('config2');
	require_code('ocf_groups');
	require_code('ocf_members');
	require_code('ocf_moderation_action');
	require_code('ocf_posts_action');
	require_code('ocf_polls_action');
	require_code('ocf_members_action');
	require_code('ocf_groups_action');
	require_code('ocf_general_action');
	require_code('ocf_forums_action');
	require_code('ocf_topics_action');
	require_code('ocf_moderation_action2');
	require_code('ocf_posts_action2');
	require_code('ocf_polls_action2');
	require_code('ocf_members_action2');
	require_code('ocf_groups_action2');
	require_code('ocf_general_action2');
	require_code('ocf_forums_action2');
	require_code('ocf_topics_action2');
	require_css('importing');
	require_code('database_action');
}

/**
 * Switch OCF to run over the local site-DB connection. Useful when importing and our forum driver is actually connected to a forum other than OCF.
 */
function ocf_over_local()
{
	$GLOBALS['MSN_DB']=$GLOBALS['FORUM_DB'];
	$GLOBALS['FORUM_DB']=$GLOBALS['SITE_DB'];
}

/**
 * Undo ocf_over_local.
 */
function ocf_over_msn()
{
	$GLOBALS['FORUM_DB']=$GLOBALS['MSN_DB'];
	$GLOBALS['MSN_DB']=NULL;
}

/**
 * Returns the NEW ID of an imported old ID, for the specified importation type. Whether it returns NULL or gives an error message depends on $fail_ok.
 *
 * @param  ID_TEXT		An importation type code, from those ocPortal has defined (E.g. 'download', 'news', ...)
 * @param  string			The source (old, original) ID of the mapping
 * @param  boolean		If it is okay to fail to find a mapping
 * @return ?AUTO_LINK	The new ID (NULL: not found)
 */
function import_id_remap_get($type,$id_old,$fail_ok=false)
{
	global $REMAP_CACHE;
	if ((array_key_exists($type,$REMAP_CACHE)) && (array_key_exists($id_old,$REMAP_CACHE[$type]))) return $REMAP_CACHE[$type][$id_old];

	$value=$GLOBALS['SITE_DB']->query_value_null_ok('import_id_remap','id_new',array('id_session'=>get_session_id(),'id_type'=>$type,'id_old'=>$id_old));
	if (is_null($value))
	{
		if ($fail_ok) return NULL;
		warn_exit(do_lang_tempcode('IMPORT_NOT_IMPORTED',escape_html($type),escape_html($id_old)));
	}
	$REMAP_CACHE[$type][$id_old]=$value;
	return $value;
}

/**
 * Check to see if the given id of the given type has been imported (if it has a mapping).
 *
 * @param  ID_TEXT		An importation type code, from those ocPortal has defined
 * @param  string			The source (old, original) ID of the mapping
 * @return boolean		Whether it has been imported
 */
function import_check_if_imported($type,$id_old)
{
	$test=$GLOBALS['SITE_DB']->query_value_null_ok('import_id_remap','id_new',array('id_session'=>get_session_id(),'id_type'=>$type,'id_old'=>$id_old));
	return !is_null($test);
}

/**
 * Set the NEW ID for an imported old ID, which also tacitly indicates completion of importing an item of some type of content. This mapping (old ID to new ID) may be used later for importing related content that requires the new identifier. import_id_remap_get is the inverse of this function.
 *
 * @param  ID_TEXT		An importation type code, from those ocPortal has defined
 * @param  string			The source (old, original) ID of the mapping
 * @param  AUTO_LINK		The destination (new) ID of the mapping
 */
function import_id_remap_put($type,$id_old,$id_new)
{
	$GLOBALS['SITE_DB']->query_insert('import_id_remap',array('id_session'=>get_session_id(),'id_type'=>$type,'id_old'=>$id_old,'id_new'=>$id_new));
}

/**
 * Add a word to the word-filter.
 *
 * @param  SHORT_TEXT	Word to add to the word-filter
 * @param  SHORT_TEXT	Replacement (blank: block entirely)
 * @param  BINARY			Whether to perform a substring match
 */
function add_wordfilter_word($word,$replacement='',$substr=0)
{
	$test=$GLOBALS['SITE_DB']->query_value_null_ok('wordfilter','word',array('word'=>$word));
	if (is_null($test)) $GLOBALS['SITE_DB']->query_insert('wordfilter',array('word'=>$word,'w_replacement'=>$replacement,'w_substr'=>$substr));
}

/**
 * Find a similar but non conflicting filename to $file in the given directory.
 *
 * @param  PATH			Directory
 * @param  string			Preferred filename
 * @param  boolean		Whether GIF files are made as PNG files
 * @return string			Filename to use
 */
function find_derivative_filename($dir,$file,$shun_gif=false)
{
	if (($shun_gif) && (substr($file,-4)=='.gif')) $file=substr($file,0,strlen($file)-4).'.png';

	$_file=$file;
	$place=get_file_base().'/'.$dir.'/'.$_file;
	$i=2;
	// Hunt with sensible names until we don't get a conflict
	while (file_exists($place))
	{
		$_file=strval($i).$file;
		$place=get_file_base().'/'.$dir.'/'.$_file;
		$i++;
	}
	return $_file;
}

/**
 * Force a page refresh due to maximum execution timeout.
 */
function i_force_refresh()
{
	if (array_key_exists('I_REFRESH_URL',$GLOBALS))
	{
		if ((strpos($GLOBALS['I_REFRESH_URL'],chr(10))!==false) || (strpos($GLOBALS['I_REFRESH_URL'],chr(13))!==false))
			log_hack_attack_and_exit('HEADER_SPLIT_HACK');

		if (!headers_sent())
		{
			header('Location: '.str_replace(chr(13),'',str_replace(chr(10),'',$GLOBALS['I_REFRESH_URL'])));
		} else
		{
			echo '<meta http-equiv="Refresh" content="0; URL='.escape_html($GLOBALS['I_REFRESH_URL']).'" />';
			flush();
		}
		/*$f=fopen(get_file_base().'/test.txt','at');
		fwrite($f,'R');
		fclose($f);*/
		exit();
	}
}

/**
 * Load lots that the importer needs to run.
 */
function post_import_cleanup()
{
	// Quick and simple decacheing. No need to be smart about this.
	delete_value('ocf_member_count');
	delete_value('ocf_topic_count');
	delete_value('ocf_post_count');
}

/**
 * Turn index maintenance off to help speed import, or back on.
 *
 * @param  boolean		Whether index maintenance should be on.
 */
function set_database_index_maintenance($on)
{
	if (strpos(get_db_type(),'mysql')!==false)
	{
		global $NO_DB_SCOPE_CHECK;
		$NO_DB_SCOPE_CHECK=true;

		$tables=$GLOBALS['SITE_DB']->query_select('db_meta',array('DISTINCT m_table'));
		foreach ($tables as $table)
		{
			$tbl=$table['m_table'];
			$GLOBALS['SITE_DB']->query('ALTER TABLE '.$GLOBALS['SITE_DB']->get_table_prefix().$tbl.' '.($on?'ENABLE':'DISABLE').' KEYS');
		}
	}
}

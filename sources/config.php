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
 * @package		core
 */

/**
 * Standard code module initialisation function.
 */
function init__config()
{
	global $VALUES,$IN_MINIKERNEL_VERSION;
	if ($IN_MINIKERNEL_VERSION==0)
	{
		load_options();

		$VALUES=persistant_cache_get('VALUES');
		if (!is_array($VALUES))
		{
			$VALUES=$GLOBALS['SITE_DB']->query_select('values',array('*'));
			$VALUES=list_to_map('the_name',$VALUES);
			persistant_cache_set('VALUES',$VALUES);
		}
	} else $VALUES=array();

	global $GET_OPTION_LOOP;
	$GET_OPTION_LOOP=0;

	global $MULTI_LANG;
	$MULTI_LANG=NULL;

	// Enforce XML db synching
	if ((get_db_type()=='xml') && (!running_script('xml_db_import')) && (is_file(get_file_base().'/data_custom/xml_db_import.php')) && (is_dir(get_file_base().'/.svn')))
	{
		$last_xml_import=get_value('last_xml_import');
		$mod_time=filemtime(get_file_base().'/.svn');
		if ((is_null($last_xml_import)) || (intval($last_xml_import)<$mod_time))
		{
			set_value('last_xml_import',strval(time()));

			header('Location: '.get_base_url().'/data_custom/xml_db_import.php');
			exit();
		}
	}
}

/**
 * Find whether to run in multi-lang mode.
 *
 * @return boolean		Whether to run in multi-lang mode.
 */
function multi_lang()
{
	global $MULTI_LANG;
	if ($MULTI_LANG!==NULL) return $MULTI_LANG;
	$MULTI_LANG=false;
	if (get_option('allow_international',true)!=='1') return false;

	$_dir=opendir(get_file_base().'/lang/');
	$_langs=array();
	while (false!==($file=readdir($_dir)))
	{
		if (($file!=fallback_lang()) && ($file[0]!='.') && ($file[0]!='_') && ($file!='index.html') && ($file!='langs.ini') && ($file!='map.ini'))
		{
			if (is_dir(get_file_base().'/lang/'.$file)) $_langs[$file]='lang';
		}
	}
	closedir($_dir);
	if (!in_safe_mode())
	{
		$_dir=@opendir(get_custom_file_base().'/lang_custom/');
		if ($_dir!==false)
		{
			while (false!==($file=readdir($_dir)))
			{
				if (($file!=fallback_lang()) && ($file[0]!='.') && ($file[0]!='_') && ($file!='index.html') && ($file!='langs.ini') && ($file!='map.ini') && (!isset($_langs[$file])))
				{
					if (is_dir(get_custom_file_base().'/lang_custom/'.$file)) $_langs[$file]='lang_custom';
				}
			}
			closedir($_dir);
		}
		if (get_custom_file_base()!=get_file_base())
		{
			$_dir=opendir(get_file_base().'/lang_custom/');
			while (false!==($file=readdir($_dir)))
			{
				if (($file!=fallback_lang()) && ($file[0]!='.') && ($file[0]!='_') && ($file!='index.html') && ($file!='langs.ini') && ($file!='map.ini') && (!isset($_langs[$file])))
				{
					if (is_dir(get_file_base().'/lang_custom/'.$file)) $_langs[$file]='lang_custom';
				}
			}
			closedir($_dir);
		}
	}	

	foreach ($_langs as $lang=>$dir)
	{
		if (/*optimisation*/is_file((($dir=='lang_custom')?get_custom_file_base():get_file_base()).'/'.$dir.'/'.$lang.'/global.ini'))
		{
			$MULTI_LANG=true;
			break;
		}

		$_dir2=@opendir((($dir=='lang_custom')?get_custom_file_base():get_file_base()).'/'.$dir.'/'.$lang);
		if ($_dir2!==false)
		{
			while (false!==($file2=readdir($_dir2)))
			{
				if ((substr($file2,-4)=='.ini') || (substr($file2,-3)=='.po'))
				{
					$MULTI_LANG=true;
					break;
				}
			}
		}
	}

	return $MULTI_LANG;
}

/**
 * Load all config options.
 */
function load_options()
{
	global $OPTIONS;
	$OPTIONS=function_exists('persistant_cache_get')?persistant_cache_get('OPTIONS'):NULL;
	if (is_array($OPTIONS)) return;
	if (strpos(get_db_type(),'mysql')!==false)
	{
		global $SITE_INFO;
		$OPTIONS=$GLOBALS['SITE_DB']->query_select('config c LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON (c.config_value=t.id AND '.db_string_equal_to('t.language',array_key_exists('default_lang',$SITE_INFO)?$SITE_INFO['default_lang']:'EN').' AND ('.db_string_equal_to('c.the_type','transtext').' OR '.db_string_equal_to('c.the_type','transline').'))',array('c.the_name','c.config_value','c.the_type','c.c_set','t.text_original AS config_value_translated'),array(),'',NULL,NULL,true);
	} else
	{
		$OPTIONS=$GLOBALS['SITE_DB']->query_select('config',array('the_name','config_value','the_type','c_set'),NULL,'',NULL,NULL,true);
	}

	if ($OPTIONS===NULL) critical_error('DATABASE_FAIL');
	$OPTIONS=list_to_map('the_name',$OPTIONS);
	if (function_exists('persistant_cache_set')) persistant_cache_set('OPTIONS',$OPTIONS);
}

/**
 * Find a specified tutorial link identifier.
 *
 * @param  ID_TEXT		The name of the value
 * @return ?SHORT_TEXT	The value (NULL: value not found)
 */
function get_tutorial_link($name)
{
	return $GLOBALS['SITE_DB']->query_value_null_ok('tutorial_links','the_value',array('the_name'=>$name));
}

/**
 * Set the specified value to the specified tutorial link identifier.
 *
 * @param  ID_TEXT		The name of the value
 * @param  SHORT_TEXT	The value
 */
function set_tutorial_link($name,$value)
{
	$GLOBALS['SITE_DB']->query_delete('tutorial_links',array('the_name'=>$name),'',1);
	$GLOBALS['SITE_DB']->query_insert('tutorial_links',array('the_value'=>$value,'the_name'=>$name),false,true); // Allow failure, if there is a race condition
}

/**
 * Find a specified long value. Long values are either really long strings, or just ones you don't want on each page load (i.e. it takes a query to read them, because you don't always need them).
 *
 * @param  ID_TEXT		The name of the value
 * @return ?SHORT_TEXT	The value (NULL: value not found)
 */
function get_long_value($name)
{
	return $GLOBALS['SITE_DB']->query_value_null_ok('long_values','the_value',array('the_name'=>$name));
}

/**
 * Find the specified configuration option if it is younger than a specified time.
 *
 * @param  ID_TEXT		The name of the value
 * @param  TIME			The cutoff time (an absolute time, not a relative "time ago")
 * @return ?SHORT_TEXT	The value (NULL: value newer than not found)
 */
function get_long_value_newer_than($name,$cutoff)
{
	return $GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT the_value FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'long_values WHERE date_and_time>'.strval($cutoff).' AND '.db_string_equal_to('the_name',$name));
}

/**
 * Set the specified situational value to the specified long value. Long values are either really long strings, or just ones you don't want on each page load (i.e. it takes a query to read them, because you don't always need them).
 *
 * @param  ID_TEXT		The name of the value
 * @param  ?SHORT_TEXT	The value (NULL: delete it)
 */
function set_long_value($name,$value)
{
	$GLOBALS['SITE_DB']->query_delete('long_values',array('the_name'=>$name),'',1);
	if ($value!==NULL)
	{
		$GLOBALS['SITE_DB']->query_insert('long_values',array('date_and_time'=>time(),'the_value'=>$value,'the_name'=>$name),false,true);
	}
}

/**
 * Find a specified value.
 *
 * @param  ID_TEXT		The name of the value
 * @param  ?ID_TEXT		Value to return if value not found (NULL: return NULL)
 * @param  boolean		Whether to also check server environmental variables
 * @return ?SHORT_TEXT	The value (NULL: value not found and default is NULL)
 */
function get_value($name,$default=NULL,$env_also=false)
{
	global $IN_MINIKERNEL_VERSION,$VALUES;
	if ($IN_MINIKERNEL_VERSION==1) return $default;

	if (isset($VALUES[$name])) return $VALUES[$name]['the_value'];

	if ($env_also)
	{
		$value=getenv($name);
		if (($value!==false) && ($value!='')) return $value;
	}

	return $default;
}

/**
 * Find the specified configuration option if it is younger than a specified time.
 *
 * @param  ID_TEXT		The name of the value
 * @param  TIME			The cutoff time (an absolute time, not a relative "time ago")
 * @return ?SHORT_TEXT	The value (NULL: value newer than not found)
 */
function get_value_newer_than($name,$cutoff)
{
	$cutoff-=mt_rand(0,200); // Bit of scattering to stop locking issues if lots of requests hit this at once in the middle of a hit burst (whole table is read each page requests, and mysql will lock the table on set_value - causes horrible out-of-control buildups)

	global $VALUES;
	if ((array_key_exists($name,$VALUES)) && ($VALUES[$name]['date_and_time']>$cutoff)) return $VALUES[$name]['the_value'];
	return NULL;
}

/**
 * Set the specified situational value to the specified value.
 *
 * @param  ID_TEXT		The name of the value
 * @param  SHORT_TEXT	The value
 */
function set_value($name,$value)
{
	global $VALUES;
	$existed_before=array_key_exists($name,$VALUES);
	$VALUES[$name]['the_value']=$value;
	$VALUES[$name]['date_and_time']=time();
	if ($existed_before)
	{
		$GLOBALS['SITE_DB']->query_update('values',array('date_and_time'=>time(),'the_value'=>$value),array('the_name'=>$name),'',1,NULL,false,true);
	} else
	{
		$GLOBALS['SITE_DB']->query_insert('values',array('date_and_time'=>time(),'the_value'=>$value,'the_name'=>$name),false,true); // Allow failure, if there is a race condition
	}
	if (function_exists('persistant_cache_set')) persistant_cache_set('VALUES',$VALUES);
}

/**
 * Delete a situational value.
 *
 * @param  ID_TEXT		The name of the value
 */
function delete_value($name)
{
	$GLOBALS['SITE_DB']->query_delete('values',array('the_name'=>$name),'',1);
	if (function_exists('persistant_cache_delete')) persistant_cache_delete('VALUES');
}

/**
 * Find the value of the specified configuration option.
 *
 * @param  ID_TEXT		The name of the option
 * @param  boolean		Where to accept a missing option (and return NULL)
 * @return ?SHORT_TEXT	The value (NULL: either null value, or no option found whilst $missing_ok set)
 */
function get_option($name,$missing_ok=false)
{
	global $OPTIONS;

	if (!isset($OPTIONS[$name]))
	{
		if ($missing_ok) return NULL;
		require_code('config2');
		find_lost_option($name);
	}

	$option=&$OPTIONS[$name];

	// The master of redundant quick exit points. Has to be after the above IF due to weird PHP isset/NULL bug on some 5.1.4 (and possibly others)
	if (isset($option['config_value_translated']))
	{
		if ($option['config_value_translated']=='<null>') return NULL;
		return $option['config_value_translated'];
	}

	// Redundant, quick exit points
	$type=$option['the_type'];
	if (!isset($option['c_set'])) $option['c_set']=($option['config_value']===NULL)?0:1; // for compatibility during upgrades
	if (($option['c_set']==1) && ($type!='transline') && ($type!='transtext'))
	{
		//@print_r($OPTIONS);	exit($name.'='.gettype($option['config_value_translated']));
		$option['config_value_translated']=$option['config_value']; // Allows slightly better code path next time
		if ($option['config_value_translated']===NULL) $option['config_value_translated']='<null>';
		$OPTIONS[$name]=$option;
		if (function_exists('persistant_cache_set')) persistant_cache_set('OPTIONS',$OPTIONS);
		if ($option['config_value']=='<null>') return NULL;
		return $option['config_value'];
	}

	global $GET_OPTION_LOOP;
	$GET_OPTION_LOOP=1;

	// Find default if not set
	if ($option['c_set']==0)
	{
		if (($type=='transline') || ($type=='transtext'))
		{
			if (defined('HIPHOP_PHP'))
			{
				require_code('hooks/systems/config_default/'.$name);
				$hook=object_factory('Hook_config_default_'.$name);
				$option['config_value_translated']=$hook->get_default();
			} else
			{
				if (!isset($option['eval']))
				{
					global $SITE_INFO;
					$OPTIONS=$GLOBALS['SITE_DB']->query_select('config c LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON (c.config_value=t.id AND '.db_string_equal_to('t.language',array_key_exists('default_lang',$SITE_INFO)?$SITE_INFO['default_lang']:'EN').' AND ('.db_string_equal_to('c.the_type','transtext').' OR '.db_string_equal_to('c.the_type','transline').'))',array('c.the_name','c.config_value','c.eval','c.the_type','c.c_set','t.text_original AS config_value_translated'),array(),'');
					$OPTIONS=list_to_map('the_name',$OPTIONS);
					$option=&$OPTIONS[$name];
				}
				$GLOBALS['REQUIRE_LANG_LOOP']=10; // LEGACY Workaround for corrupt webhost installers
				$option['config_value_translated']=eval($option['eval'].';');
				$GLOBALS['REQUIRE_LANG_LOOP']=0; // LEGACY
				if (is_object($option['config_value_translated'])) $option['config_value_translated']=$option['config_value_translated']->evaluate();
				if ((get_value('setup_wizard_completed')==='1') && ($option['config_value_translated']!==NULL)/*Don't save a NULL, means it is unreferencable yet rather than an actual value*/)
				{
					require_code('config2');
					set_option($name,$option['config_value_translated']);
				}
			}
			if (is_object($option['config_value_translated'])) $option['config_value_translated']=$option['config_value_translated']->evaluate();
			$GET_OPTION_LOOP=0;
			return $option['config_value_translated'];
		}
//		if ((!function_exists('do_lang')) && (strpos($option['eval'],'do_lang')!==false)) @debug_print_backtrace();
		if (defined('HIPHOP_PHP'))
		{
			require_code('hooks/systems/config_default/'.$name);
			$hook=object_factory('Hook_config_default_'.$name);
			$option['config_value']=$hook->get_default();
		} else
		{
			if (!isset($option['eval']))
			{
				global $SITE_INFO;
				$OPTIONS=$GLOBALS['SITE_DB']->query_select('config c LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON (c.config_value=t.id AND '.db_string_equal_to('t.language',array_key_exists('default_lang',$SITE_INFO)?$SITE_INFO['default_lang']:'EN').' AND ('.db_string_equal_to('c.the_type','transtext').' OR '.db_string_equal_to('c.the_type','transline').'))',array('c.the_name','c.config_value','c.eval','c.the_type','c.c_set','t.text_original AS config_value_translated'),array(),'');
				$OPTIONS=list_to_map('the_name',$OPTIONS);
				$option=&$OPTIONS[$name];
			}
			require_code('lang');
			$GLOBALS['REQUIRE_LANG_LOOP']=10; // LEGACY Workaround for corrupt webhost installers
			$option['config_value']=eval($option['eval'].';');
			$GLOBALS['REQUIRE_LANG_LOOP']=0; // LEGACY
			if ((get_value('setup_wizard_completed')==='1') && (isset($option['config_value_translated']))/*Don't save a NULL, means it is unreferencable yet rather than an actual value*/)
			{
				require_code('config2');
				set_option($name,$option['config_value']);
			}
		}
		if (is_object($option['config_value'])) $option['config_value']=$option['config_value']->evaluate(); elseif (is_integer($option['config_value'])) $option['config_value']=strval($option['config_value']);

		$GET_OPTION_LOOP=0;
		$option['c_set']=1;
		return $option['config_value'];
	}

	// Translations if needed
	if (($type=='transline') || ($type=='transtext'))
	{
		if (!isset($option['config_value_translated']))
		{
			$option['config_value_translated']=get_translated_text(intval($option['config_value']));
			$OPTIONS[$name]=$option;
			persistant_cache_set('OPTIONS',$OPTIONS);
		}
		// Answer
		$GET_OPTION_LOOP=0;
		return $option['config_value_translated'];
	}

	// Answer
	$GET_OPTION_LOOP=0;
	return $option['config_value'];
}

/**
 * Increment the specified stored value, by the specified amount.
 *
 * @param  ID_TEXT		The codename for the stat
 * @param  integer		What to increment the statistic by
 */
function update_stat($stat,$increment)
{
	if (running_script('stress_test_loader')) return;

	$current=get_value($stat);
	if (is_null($current)) $current='0';
	$new=intval($current)+$increment;
	set_value($stat,strval($new));
}

/**
 * Very simple function to invert the meaning of an old hidden option. We often use this when we've promoted a hidden option into a new proper option but inverted the meaning in the process - we use this in the default value generation code, as an in-line aid to preserve existing hidden option settings.
 *
 * @param  ID_TEXT		The old value
 * @set 0 1
 * @return ID_TEXT		The inverted value
 */
function invert_value($old)
{
	if ($old=='1') return '0';
	return '1';
}



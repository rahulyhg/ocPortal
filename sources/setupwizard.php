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
 * @package		setupwizard
 */

/**
 * Get Comcode for the pages in the zone.
 *
 * @param  array			List of blocks in the install profile
 * @param  array			Block options in the install profile
 * @param  boolean		Whether we have zone collapsing on
 * @param  ID_TEXT		ID of the install profile
 * @return array			Structure of pages
 */
function _get_zone_pages($installprofileblocks,$block_options,$collapse_zones,$installprofile)
{
	$page_structure=array();

	// Find blocks used, from environment
	$zone_blocks=array();
	$zone_blocks['site']=array();
	foreach ($_POST as $key=>$value)
	{
		if (substr($key,0,6)=='block_')
		{
			$block=substr($key,6);
			if (substr($block,0,5)=='SITE_')
			{
				$zone_blocks['site'][substr($block,5)]=$value;
			}
		}
	}

	// Order them according to profile
	foreach ($installprofileblocks as $set)
	{
		foreach ($set as $block)
		{
			if (isset($zone_blocks['site'][$block]))
			{
				$value=$zone_blocks['site'][$block];
				unset($zone_blocks['site'][$block]);
				$zone_blocks['site']+=array($block=>$value);
			}
		}
	}

	// Work out all Comcode
	foreach ($zone_blocks as $zone=>$blocks)
	{
		if ($collapse_zones)
		{
			if ($zone=='') continue;
			if ($zone=='site') $zone='';
		}

		$page_structure[$zone]=array();

		// Generally work out what Comcode goes where
		$cells='';
		$main='';
		$left='';
		$right='';
		$cell_count=0;
		foreach ($blocks as $block=>$val)
		{
			$params='';
			if (!is_null($block_options))
			{
				if (array_key_exists($block,$block_options))
				{
					foreach ($block_options[$block] as $block_option=>$block_option_value)
					{
						$params.=' '.$block_option.'="'.comcode_escape($block_option_value).'"';
					}
				}
			}
			$block_comcode="[block".$params."]".$block."[/block]";
			if ($val=='PANEL_LEFT')
				$left.=$block_comcode."\n";
			if ($val=='PANEL_RIGHT')
				$right.=$block_comcode."\n";
			if ($val=='YES')
				$main.=$block_comcode."\n\n";
			if ($val=='YES_CELL')
			{
				if ($cell_count%2==0)
				{
					if ($cell_count!=0) $cells.='<br />';
					$cells.="<div class=\"float_surrounder\">";
				}
				$cells.="<div class=\"fp_col_block\" style=\"float: ".(($cell_count%2==0)?'left':'right')."\">".$block_comcode."</div>\n";
				if ($cell_count%2==1) $cells.="</div>";
				$cell_count++;
			}
		}
		if ($cells!='') // Odd number of cells chosen, close off at odd point
		{
			if ($cell_count%2==1) $cells.="</div>";
		}

		// Start page
		$comcode='';
		$comcode.="[title=\"1\"]Welcome to {\$SITE_NAME*}[/title]\n\n[block=\"3\"]main_greeting[/block]\n\n";
		if ($cells!='')
		{
			$comcode.="[semihtml]\n";
			$comcode.=$cells;
			$comcode.="\n[/semihtml]\n\n";
		}
		$main.=chr(10).chr(10)."[block]main_comcode_page_children[/block]";
		$comcode.=$main;
		$page_structure[$zone]['start']=$comcode;

		// Left panel
		$comcode='';
		require_lang('menus');
		if ($installprofile=='')
		{
			if (($zone=='') && (!$collapse_zones))
				$comcode.="[block=\"root_website\" type=\"tree\" caption=\"".do_lang('SITE')."\"]side_stored_menu[/block]\n";
			else
				$comcode.="[block=\"main_features\" type=\"tree\" caption=\"".do_lang('FEATURES')."\"]side_stored_menu[/block]\n[block=\"main_content\" type=\"tree\" caption=\"".do_lang('CONTENT')."\"]side_stored_menu[/block]\n[block=\"main_community\" type=\"tree\" caption=\"".do_lang('FORUM_SLASH_COMMUNITY')."\"]side_stored_menu[/block]\n[block=\"main_website\" type=\"tree\" caption=\"".do_lang('SITE')."\"]side_stored_menu[/block]\n[block=\"cedi_features\" type=\"tree\" silent_failure=\"1\" caption=\"CEDI\"]side_stored_menu[/block]\n";
		} elseif ($left!='')
		{
			if ($GLOBALS['SITE_DB']->query_value('menu_items','COUNT(*)',array('i_menu'=>'site'))>1)
				$comcode.="[block=\"site\" type=\"tree\" caption=\"".do_lang('PAGES')."\"]side_stored_menu[/block]\n";
		}
		$comcode.=$left;
		if ($installprofile=='')
		{
			$comcode.="[block]side_personal_stats[/block]";
		}
		if (post_param_integer('include_ocp_advert',0)==1)
		{
			$comcode.='[center][url="http://ocportal.com/?from=logo"][img="Powered by ocPortal"]http://ocportal.com/uploads/website_specific/ocportal.com/logos/a.png[/img][/url][/center]{$SET,interlock,0}';
		}
		$page_structure[$zone]['left']=$comcode;

		// Right panel
		$comcode='';
		if (($left=='') && ($installprofile!=''))
		{
			if ($GLOBALS['SITE_DB']->query_value('menu_items','COUNT(*)',array('i_menu'=>'site'))>1)
				$comcode.="[block=\"site\" type=\"tree\" caption=\"".do_lang('PAGES')."\"]side_stored_menu[/block]\n";
		}
		$comcode.=$right;
		$page_structure[$zone]['right']=$comcode;
	}

	return $page_structure;
}

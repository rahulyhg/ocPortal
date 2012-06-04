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
 * Script to make a nice textual image.
 */
function gd_text_script()
{
	if (!function_exists('imagefontwidth')) return;

	$text=get_param('text');
	if (get_magic_quotes_gpc()) $text=stripslashes($text);

	$font_size=array_key_exists('size',$_GET)?intval($_GET['size']):8;

	$font=get_param('font',get_file_base().'/data/fonts/'.filter_naughty(get_param('font','FreeMonoBoldOblique')).'.ttf');

	if ((!function_exists('imagettftext')) || (!array_key_exists('FreeType Support',gd_info())) || (@imagettfbbox(26.0,0.0,get_file_base().'/data/fonts/Vera.ttf','test')===false) || (strlen($text)==0))
	{
		$pfont=4;
		$height=intval(imagefontwidth($pfont)*strlen($text)*1.05);
		$width=imagefontheight($pfont);
		$baseline_offset=0;
	} else
	{
		$scale=4;
		list(,,$height,,,,,$width)=imagettfbbox(floatval($font_size*$scale),0.0,$font,$text);
		$baseline_offset=8*intval(ceil(floatval($font_size)/8.0));
		$width=max($width,-$width);
		$width+=$baseline_offset;
		$height+=2*$scale; // This is just due to inaccuracy in imagettfbbox

		list(,,$real_height,,,,,$real_width)=imagettfbbox(floatval($font_size),0.0,$font,$text);
		$real_width=max($real_width,-$real_width);
		$real_width+=$baseline_offset/$scale;
		$real_height+=2;
	}
	if ($width==0) $width=1;
	if ($height==0) $height=1;
	$trans_color=array_key_exists('color',$_GET)?$_GET['color']:'FF00FF';
	$img=imagecreatetruecolor($width,$height+$baseline_offset);
	imagealphablending($img,false);
	$black_color=array_key_exists('fgcolor',$_GET)?$_GET['fgcolor']:'000000';
	$black=imagecolorallocate($img,hexdec(substr($black_color,0,2)),hexdec(substr($black_color,2,2)),hexdec(substr($black_color,4,2)));
	if ((!function_exists('imagettftext')) || (!array_key_exists('FreeType Support',gd_info())) || (@imagettfbbox(26.0,0.0,get_file_base().'/data/fonts/Vera.ttf','test')===false) || (strlen($text)==0))
	{
		$trans=imagecolorallocate($img,hexdec(substr($trans_color,0,2)),hexdec(substr($trans_color,2,2)),hexdec(substr($trans_color,4,2)));
		imagefill($img,0,0,$trans);
		imagecolortransparent($img,$trans);
		imagestringup($img,$pfont,0,$height-1-intval($height*0.02),$text,$black);
	} else
	{
		if (function_exists('imagecolorallocatealpha'))
		{
			$trans=imagecolorallocatealpha($img,hexdec(substr($trans_color,0,2)),hexdec(substr($trans_color,2,2)),hexdec(substr($trans_color,4,2)),127);
		} else
		{
			$trans=imagecolorallocate($img,hexdec(substr($trans_color,0,2)),hexdec(substr($trans_color,2,2)),hexdec(substr($trans_color,4,2)));
		}
		imagefilledrectangle($img,0,0,$width,$height,$trans);
		if (@$_GET['angle']!=90)
		{
			require_code('character_sets');
			$text=utf8tohtml(convert_to_internal_encoding($text,strtolower(get_param('charset',get_charset())),'utf-8'));
			if (strpos($text,'&#')===false)
			{
				$previous=mixed();
				$nxpos=0;
				for ($i=0;$i<strlen($text);$i++)
				{
					if (!is_null($previous)) // check for existing previous character
					{
						list(,,$rx1,,$rx2)=imagettfbbox(floatval($font_size*$scale),0.0,$font,$previous);
						$nxpos+=max($rx1,$rx2)+3;
					}
					imagettftext($img,floatval($font_size*$scale),270.0,$baseline_offset,$nxpos,$black,$font,$text[$i]);
					$previous=$text[$i];
				}
			} else
			{
				imagettftext($img,floatval($font_size*$scale),270.0,4,0,$black,$font,$text);
			}
		} else
		{
			imagettftext($img,floatval($font_size*$scale),90.0,$width-$baseline_offset,$height,$black,$font,$text);
		}
		$dest_img=imagecreatetruecolor($real_width+intval(ceil(floatval($baseline_offset)/floatval($scale))),$real_height);
		imagealphablending($dest_img,false);
		imagecopyresampled($dest_img,$img,0,0,0,0,$real_width+intval(ceil(floatval($baseline_offset)/floatval($scale))),$real_height,$width,$height); // Sizes down, for simple antialiasing-like effect
		imagedestroy($img);
		$img=$dest_img;
		if (function_exists('imagesavealpha')) imagesavealpha($img,true);
	}

	header('Content-Type: image/png');
	imagepng($img);
	imagedestroy($img);
}

/**
 * Script to track clicks to external sites.
 */
function simple_tracker_script()
{
	$url=get_param('url');
	if (strpos($url,'://')===false) $url=base64_decode($url);

	$GLOBALS['SITE_DB']->query_insert('link_tracker',array(
		'c_date_and_time'=>time(),
		'c_member_id'=>get_member(),
		'c_ip_address'=>get_ip_address(),
		'c_url'=>$url,
	));

	header('Location: '.$url);
}

/**
 * Script to show previews of content being added/edited.
 */
function preview_script()
{
	require_code('preview');
	list($output,$validation,$keyword_density,$spelling)=build_preview(true);

	$output=do_template('PREVIEW_SCRIPT',array('_GUID'=>'97bd8909e8b9983a0bbf7ab68fab92f3','OUTPUT'=>$output->evaluate(),'VALIDATION'=>$validation,'KEYWORD_DENSITY'=>$keyword_density,'SPELLING'=>$spelling,'HIDDEN'=>build_keep_post_fields()));

	$tpl=do_template('STANDALONE_HTML_WRAP',array('TITLE'=>do_lang_tempcode('PREVIEW'),'FRAME'=>true,'TARGET'=>'_top','CONTENT'=>$output));
	$tpl->handle_symbol_preprocessing();
	$tpl->evaluate_echo();
}

/**
 * Script to perform ocPortal CRON jobs called by the real CRON.
 *
 * @param  PATH  	File path of the cron_bridge.php script
 */
function cron_bridge_script($caller)
{
	if (function_exists('set_time_limit')) @set_time_limit(1000); // May get overridden lower later on

	if (get_param_integer('querymode',0)==1)
	{
		header('Content-Type: text/plain');
		@ini_set('ocproducts.xss_detect','0');
		require_code('files2');
		$php_path=find_php_path();
		echo $php_path.' -C -q --no-header '.$caller;
		exit();
	}

	global $CURRENT_SHARE_USER,$SITE_INFO;
	if ((is_null($CURRENT_SHARE_USER)) && (array_key_exists('custom_share_domain',$SITE_INFO)))
	{
		require_code('files');

		foreach ($SITE_INFO as $key=>$val)
		{
			if (substr($key,0,12)=='custom_user_')
			{
				$url=preg_replace('#://[\w\.]+#','://'.substr($key,12).'.'.$SITE_INFO['custom_share_domain'],get_base_url()).'/data/cron_bridge.php';
				http_download_file($url);
			}
		}
	}

	decache('main_staff_checklist');

	$limit_hook=get_param('limit_hook','');

	set_value('last_cron',strval(time()));
	$cron_hooks=find_all_hooks('systems','cron');
	foreach (array_keys($cron_hooks) as $hook)
	{
		if (($limit_hook!='') && ($limit_hook!=$hook)) continue;

		require_code('hooks/systems/cron/'.$hook);
		$object=object_factory('Hook_cron_'.$hook,true);
		if (is_null($object)) continue;
		$object->run();
	}

	if (!headers_sent()) header('Content-type: text/plain');
}

/**
 * Script to handle iframe.
 */
function iframe_script()
{
	$zone=get_param('zone');
	$page=get_param('page');

	$zones=$GLOBALS['SITE_DB']->query_select('zones',array('*'),array('zone_name'=>$zone),'',1);
	if (!array_key_exists(0,$zones)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

	if ($zones[0]['zone_require_session']==1) header('X-Frame-Options: SAMEORIGIN'); // Clickjacking protection
	if (($zones[0]['zone_name']!='') && (get_option('windows_auth_is_enabled',true)!='1') && ((get_session_id()==-1) || ($GLOBALS['SESSION_CONFIRMED']==0)) && (!is_guest()) && ($zones[0]['zone_require_session']==1))
		access_denied('ZONE_ACCESS_SESSION');

	if (!has_actual_page_access(get_member(),$page,$zone))
		access_denied('ZONE_ACCESS');

	// Closed site
	$site_closed=get_option('site_closed');
	if (($site_closed=='1') && (!has_specific_permission(get_member(),'access_closed_site')) && (!$GLOBALS['IS_ACTUALLY_ADMIN']))
	{
		header('Content-Type: text/plain');
		@exit(get_option('closed'));
	}

	global $EXTRA_HEAD;
	if (!isset($EXTRA_HEAD)) $EXTRA_HEAD=new ocp_tempcode();
	$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	$output=request_page($page,true);

	global $ATTACHED_MESSAGES;
	$output->handle_symbol_preprocessing();
	$tpl=do_template('STANDALONE_HTML_WRAP',array('OPENS_BELOW'=>get_param_integer('opens_below',0)==1,'FRAME'=>true,'TARGET'=>'_top','CONTENT'=>$output));
	$tpl->handle_symbol_preprocessing();
	$tpl->evaluate_echo();
}

/**
 * Redirect the browser to where a pagelink specifies.
 */
function pagelink_redirect_script()
{
	$pagelink=get_param('id');
	$tpl=symbol_tempcode('PAGE_LINK',array($pagelink));

	$x=$tpl->evaluate();

	if ((strpos($x,chr(10))!==false) || (strpos($x,chr(13))!==false))
		log_hack_attack_and_exit('HEADER_SPLIT_HACK');

	header('Location: '.$x);
}

/**
 * Outputs the page link chooser popup.
 */
function page_link_chooser_script()
{
	// Check we are allowed here
	if (!has_zone_access(get_member(),'adminzone'))
		access_denied('ZONE_ACCESS');

	require_lang('menus');

	require_javascript('javascript_ajax');
	require_javascript('javascript_tree_list');
	require_javascript('javascript_more');

	global $EXTRA_HEAD;
	if (!isset($EXTRA_HEAD)) $EXTRA_HEAD=new ocp_tempcode();
	$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	// Display
	$content=do_template('PAGE_LINK_CHOOSER',array('_GUID'=>'235d969528d7b81aeb17e042a17f5537','NAME'=>'tree_list'));
	$echo=do_template('STANDALONE_HTML_WRAP',array('TITLE'=>do_lang_tempcode('CHOOSE'),'CONTENT'=>$content));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}

/**
 * Outputs the staff tips iframe.
 *
 * @param  boolean			Whether to get the output instead of outputting it directly
 * @return ?object			Output (NULL: outputted it already)
 */
function staff_tips_script($ret=false)
{
	// Check we are allowed here
	if (!has_zone_access(get_member(),'adminzone'))
		access_denied('ZONE_ACCESS');

	require_css('adminzone_frontpage');

	// Anything to dismiss?
	$dismiss=get_param('dismiss','');
	if ($dismiss!='')
	{
		$GLOBALS['SITE_DB']->query_delete('staff_tips_dismissed',array('t_tip'=>$dismiss,'t_member'=>get_member()),'',1);
		$GLOBALS['SITE_DB']->query_insert('staff_tips_dismissed',array('t_tip'=>$dismiss,'t_member'=>get_member()));
	}

	// What tips have been permanently dismissed by the current member?
	$read=collapse_1d_complexity('t_tip',$GLOBALS['SITE_DB']->query_select('staff_tips_dismissed',array('t_tip'),array('t_member'=>get_member())));

	// Load up tips by searching for the correctly named language files; also choose level
	require_lang('tips');
	$tips=array();
	$level=0;
	$letters=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
	for ($i=0;$i<5;$i++)
	{
		$tips[$i]=array();
		foreach ($letters as $j)
		{
			$tip_id=strval($i).$j;
			if (!in_array($tip_id,$read))
			{
				$lang2=do_lang('TIP_'.$tip_id,NULL,NULL,NULL,NULL,false);
				if (!is_null($lang2))
				{
					$lang=do_lang_tempcode('TIP_'.$tip_id);
					$tips[$i][$tip_id]=$lang;
				}
			}
		}
		if (count($tips[$level])==0) $level=$i+1;
	}

	// Choose a tip from the level we're on
	if (!array_key_exists($level,$tips))
	{
		$tip=do_lang_tempcode('ALL_TIPS_READ');
		$level=5;
		$tip_code='';
		$count=0;
	} else
	{
		$tip_pool=array_values($tips[$level]);
		$count=count($tip_pool);
		$choose_id=mt_rand(0,$count-1);
		$tip=$tip_pool[$choose_id];
		$tip_keys=array_keys($tips[$level]);
		$tip_code=$tip_keys[$choose_id];
	}

	$content=do_template('BLOCK_MAIN_STAFF_TIPS',array('_GUID'=>'c2cffc480b7bd9beef7f78a8ee7b7359','TIP'=>$tip,'TIP_CODE'=>$tip_code,'LEVEL'=>integer_format($level),'COUNT'=>integer_format($count)));

	if ($ret) return $content;

	global $EXTRA_HEAD;
	if (!isset($EXTRA_HEAD)) $EXTRA_HEAD=new ocp_tempcode();
	$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	// Display
	$echo=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'3b5596a12c46295081f09ebe5349a479','FRAME'=>true,'TITLE'=>do_lang_tempcode('TIPS'),'CONTENT'=>$content));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
	return NULL;
}

/**
 * Shows an HTML page for making block Comcode.
 */
function block_helper_script()
{
	require_lang('comcode');
	require_lang('blocks');
	require_code('zones2');
	require_code('zones3');

	check_specific_permission('comcode_dangerous');

	$title=get_screen_title('BLOCK_HELPER');

	require_code('form_templates');
	require_all_lang();

	$type_wanted=get_param('block_type','main');

	$type=get_param('type','step1');
	$content=new ocp_tempcode();
	if ($type=='step1') // Ask for block
	{
		// Find what addons all our block files are in, and icons if possible
		$hooks=find_all_hooks('systems','addon_registry');
		$hook_keys=array_keys($hooks);
		$hook_files=array();
		foreach ($hook_keys as $hook)
		{
			$path=get_custom_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($hook).'.php';
			if (!file_exists($path))
			{
				$path=get_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($hook).'.php';
			}
			$hook_files[$hook]=file_get_contents($path);
		}
		unset($hook_keys);
		$addon_icons=array();
		$addons_blocks=array();
		foreach ($hook_files as $addon_name=>$hook_file)
		{
			$matches=array();
			if (preg_match('#function get_file_list\(\)\s*\{([^\}]*)\}#',$hook_file,$matches)!=0)
			{
				if (!defined('HIPHOP_PHP'))
				{
					$addon_files=eval($matches[1]);
				} else
				{
					require_code('hooks/systems/addon_registry/'.$addon_name);
					$hook_ob=object_factory('Hook_addon_registry_'.$addon_name);
					$addon_files=$hook_ob->get_file_list();
				}
				foreach ($addon_files as $file)
				{
					if ((substr($file,0,31)=='themes/default/images/bigicons/') && (!array_key_exists($addon_name,$addon_icons)))
					{
						$addon_icons[$addon_name]=find_theme_image('bigicons/'.basename($file,'.png'),false,true);
					}
					if ((substr($file,0,21)=='sources_custom/blocks/') || (substr($file,0,15)=='sources/blocks/'))
					{
						if ($addon_name=='staff_messaging') $addon_name='core_feedback_features';

						$addons_blocks[basename($file,'.php')]=$addon_name;
					}
				}
			}
		}

		// Find where blocks have been used
		$block_usage=array();
		$zones=find_all_zones(false,true);
		foreach ($zones as $_zone)
		{
			$zone=$_zone[0];
			$pages=find_all_pages_wrap($zone,true);
			foreach ($pages as $filename=>$type)
			{
				if (substr(strtolower($filename),-4)=='.txt')
				{
					$matches=array();
					$contents=file_get_contents(zone_black_magic_filterer(((substr($type,0,15)=='comcode_custom/')?get_custom_file_base():get_file_base()).'/'.(($zone=='')?'':($zone.'/')).'pages/'.$type.'/'.$filename));
					//$fallback=get_file_base().'/'.(($zone=='')?'':($zone.'/')).'pages/comcode/'.fallback_lang().'/'.$filename;
					//if (file_exists($fallback)) $contents.=file_get_contents($fallback);
					$num_matches=preg_match_all('#\[block[^\]]*\](.*)\[/block\]#U',$contents,$matches);
					for ($i=0;$i<$num_matches;$i++)
					{
						$block_used=$matches[1][$i];
						if (!array_key_exists($block_used,$block_usage)) $block_usage[$block_used]=array();
						$block_usage[$block_used][]=$zone.':'.basename($filename,'.txt');
					}
				}
			}
		}

		// Show block list
		$links=new ocp_tempcode();
		$blocks=find_all_blocks();
		$dh=@opendir(get_file_base().'/sources_custom/miniblocks');
		if ($dh!==false)
		{
			while (($file=readdir($dh))!==false)
				if ((substr($file,-4)=='.php') && (preg_match('#^[\w\-]*$#',substr($file,0,strlen($file)-4))!=0))
					$blocks[substr($file,0,strlen($file)-4)]='sources_custom';
			closedir($dh);
		}
		$block_types=array();
		$block_types_icon=array();
		$keep=symbol_tempcode('KEEP');
		foreach (array_keys($blocks) as $block)
		{
			if (array_key_exists($block,$addons_blocks))
			{
				$addon_name=$addons_blocks[$block];
				$addon_icon=array_key_exists($addon_name,$addon_icons)?$addon_icons[$addon_name]:NULL;
				$addon_name=preg_replace('#^core\_#','',$addon_name);
			} else
			{
				$addon_name=NULL;
				$addon_icon=NULL;
			}
			$this_block_type=(is_null($addon_name) || (strpos($addon_name,'block')!==false) || ($addon_name=='core'))?substr($block,0,(strpos($block,'_')===false)?strlen($block):strpos($block,'_')):$addon_name;
			if (!array_key_exists($this_block_type,$block_types)) $block_types[$this_block_type]=new ocp_tempcode();
			if (!is_null($addon_icon)) $block_types_icon[$this_block_type]=$addon_icon;

			$block_description=do_lang('BLOCK_'.$block.'_DESCRIPTION',NULL,NULL,NULL,NULL,false);
			$block_use=do_lang('BLOCK_'.$block.'_USE',NULL,NULL,NULL,NULL,false);
			if (is_null($block_description)) $block_description='';
			if (is_null($block_use)) $block_use='';
			$descriptiont=($block_description=='' && $block_use=='')?new ocp_tempcode():do_lang_tempcode('BLOCK_HELPER_1X',$block_description,$block_use);

			$url=find_script('block_helper').'?type=step2&block='.urlencode($block).'&field_name='.get_param('field_name').$keep->evaluate();
			if (get_param('utheme','')!='') $url.='&utheme='.get_param('utheme');
			$url.='&block_type='.$type_wanted;
			$link_caption=do_lang_tempcode('NICE_BLOCK_NAME',escape_html(cleanup_block_name($block)),$block);
			$usage=array_key_exists($block,$block_usage)?$block_usage[$block]:array();

			$block_types[$this_block_type]->attach(do_template('BLOCK_HELPER_BLOCK_CHOICE',array('_GUID'=>'079e9b37fc142d292d4a64940243178a','USAGE'=>$usage,'DESCRIPTION'=>$descriptiont,'URL'=>$url,'LINK_CAPTION'=>$link_caption)));
		}
		/*if (array_key_exists($type_wanted,$block_types)) We don't do this now, as we structure by addon name
		{
			$x=$block_types[$type_wanted];
			unset($block_types[$type_wanted]);
			$block_types=array_merge(array($type_wanted=>$x),$block_types);
		}*/
		ksort($block_types); // We sort now instead
		$move_after=$block_types['adminzone_frontpage'];
		unset($block_types['adminzone_frontpage']);
		$block_types['adminzone_frontpage']=$move_after;
		foreach ($block_types as $block_type=>$_links)
		{
			switch ($block_type)
			{
				case 'side':
				case 'main':
				case 'bottom':
					$type_title=do_lang_tempcode('BLOCKS_TYPE_'.$block_type);
					$img=NULL;
					break;
				default:
					$type_title=do_lang_tempcode('BLOCKS_TYPE_ADDON',escape_html(cleanup_block_name($block_type)));
					$img=array_key_exists($block_type,$block_types_icon)?$block_types_icon[$block_type]:NULL;
					break;
			}
			$links->attach(do_template('BLOCK_HELPER_BLOCK_GROUP',array('_GUID'=>'975a881f5dbd054ced9d2e3b35ed59bf','IMG'=>$img,'TITLE'=>$type_title,'LINKS'=>$_links)));
		}
		$content=do_template('BLOCK_HELPER_START',array('_GUID'=>'1d58238a6d00eb7f79d5a4f0e85fb1a4','GET'=>true,'TITLE'=>$title,'LINKS'=>$links));
	}
	elseif ($type=='step2') // Ask for block fields
	{
		require_code('comcode_text');
		$defaults=parse_single_comcode_tag(get_param('parse_defaults','',true),'block');

		$block=trim(get_param('block'));
		$title=get_screen_title('_BLOCK_HELPER',true,array(escape_html($block)));
		$fields=new ocp_tempcode();
		$parameters=get_block_parameters($block);
		$parameters[]='failsafe';
		$parameters[]='cache';
		$parameters[]='quick_cache';
		if (is_null($parameters)) $parameters=array();
		$advanced_ind=do_lang('BLOCK_IND_ADVANCED');
		$param_classes=array('normal'=>array(),'advanced'=>array());
		foreach ($parameters as $parameter)
		{
			$param_class='normal';
			if (($parameter=='cache') || ($parameter=='quick_cache') || ($parameter=='failsafe') || (strpos(do_lang('BLOCK_'.$block.'_PARAM_'.$parameter),$advanced_ind)!==false))
				$param_class='advanced';
			$param_classes[$param_class][]=$parameter;
		}
		foreach ($param_classes as $param_class=>$parameters)
		{
			if (count($parameters)==0)
			{
				if ($param_class=='normal')
				{
					$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>false,'TITLE'=>do_lang_tempcode('PARAMETERS'),'HELP'=>protect_from_escaping(paragraph(do_lang_tempcode('BLOCK_HELPER_NO_PARAMETERS'),'','nothing_here')))));
				}

				continue;
			}

			if ($param_class=='advanced')
			{
				$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('ADVANCED'))));
			}

			foreach ($parameters as $parameter)
			{
				$matches=array();
				switch ($parameter)
				{
					case 'quick_cache':
					case 'cache':
					case 'failsafe':
						$description=do_lang('BLOCK_PARAM_'.$parameter);
						break;
					default:
						$description=do_lang('BLOCK_'.$block.'_PARAM_'.$parameter);
						break;
				}
				$description=str_replace(do_lang('BLOCK_IND_STRIPPABLE_1'),'',$description);
				$description=trim(str_replace(do_lang('BLOCK_IND_ADVANCED'),'',$description));

				// Work out default value for field
				$default='';
				if (preg_match('#'.do_lang('BLOCK_IND_DEFAULT').': ["\']([^"]*)["\']#Ui',$description,$matches)!=0)
				{
					$default=$matches[1];
					$has_default=true;
					$description=preg_replace('#\s*'.do_lang('BLOCK_IND_DEFAULT').': ["\']([^"]*)["\'](?-U)\.?(?U)#Ui','',$description);
				} else $has_default=false;

				if (isset($defaults[$parameter]))
				{
					$default=$defaults[$parameter];
					$has_default=true;
				}

				// Show field
				if ($block.':'.$parameter=='side_stored_menu:type') // special case for menus
				{
					$matches=array();
					$dh=opendir(get_file_base().'/themes/default/templates/');
					$options=array();
					while (($file=readdir($dh))!==false)
						if (preg_match('^MENU\_([a-z]+)\.tpl$^',$file,$matches)!=0)
							$options[]=$matches[1];
					closedir($dh);
					$dh=opendir(get_custom_file_base().'/themes/default/templates_custom/');
					while (($file=readdir($dh))!==false)
						if ((preg_match('^MENU\_([a-z]+)\.tpl$^',$file,$matches)!=0) && (!file_exists(get_file_base().'/themes/default/templates/'.$file)))
							$options[]=$matches[1];
					closedir($dh);
					sort($options);
					$list=new ocp_tempcode();
					foreach ($options as $option)
						$list->attach(form_input_list_entry($option,$has_default && $option==$default));
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif ($block.':'.$parameter=='side_stored_menu:param') // special case for menus
				{
					$list=new ocp_tempcode();
					$rows=$GLOBALS['SITE_DB']->query_select('menu_items',array('DISTINCT i_menu'),NULL,'ORDER BY i_menu');
					foreach ($rows as $row)
					{
						$list->attach(form_input_list_entry($row['i_menu'],$has_default && $row['i_menu']==$default));
					}
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif ($block.':'.$parameter=='side_shoutbox:param') // special case for chat rooms
				{
					$list=new ocp_tempcode();
					$rows=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('id','room_name'),array('is_im'=>0),'',100/*In case insane number*/);
					foreach ($rows as $row)
					{
						$list->attach(form_input_list_entry(strval($row['id']),$has_default && strval($row['id'])==$default,$row['room_name']));
					}
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif ($block.':'.$parameter=='main_poll:param') // special case for polls
				{
					$list=new ocp_tempcode();
					$rows=$GLOBALS['SITE_DB']->query_select('poll',array('id','question'),NULL,'ORDER BY id DESC',100/*In case insane number*/);
					$list->attach(form_input_list_entry('',false,do_lang('NA')));
					foreach ($rows as $row)
					{
						$list->attach(form_input_list_entry(strval($row['id']),$has_default && strval($row['id'])==$default,get_translated_text($row['question'])));
					}
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif ($block.':'.$parameter=='main_awards:param') // special case for menus
				{
					$list=new ocp_tempcode();
					$rows=$GLOBALS['SITE_DB']->query_select('award_types',array('id','a_title'));
					foreach ($rows as $row)
					{
						$list->attach(form_input_list_entry(strval($row['id']),$has_default && strval($row['id'])==$default,get_translated_text($row['a_title'])));
					}
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif (($parameter=='zone') || (($parameter=='param') && ($block=='main_as_zone_access'))) // Zone list
				{
					$list=new ocp_tempcode();
					$list->attach(form_input_list_entry('_SEARCH',($default=='')));
					$list->attach(nice_get_zones(($default=='')?NULL:$default));
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif ((($parameter=='forum') || (($parameter=='param') && (in_array($block,array('main_forum_topics'))))) && (get_forum_type()=='ocf')) // OCF forum list
				{
					require_code('ocf_forums');
					require_code('ocf_forums2');
					if (!addon_installed('ocf_forum')) warn_exit(do_lang_tempcode('NO_FORUM_INSTALLED'));
					$list=ocf_get_forum_tree_secure(NULL,NULL,true,explode(',',$default));
					$fields->attach(form_input_multi_list(titleify($parameter),escape_html($description),$parameter,$list));
				}
				elseif (($parameter=='param') && (in_array($block,array('side_root_galleries','main_gallery_tease','main_gallery_embed','main_image_fader')))) // gallery list
				{
					require_code('galleries');
					$list=nice_get_gallery_tree($default);
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif (($parameter=='param') && (in_array($block,array('main_download_category')))) // download category list
				{
					require_code('downloads');
					$list=nice_get_download_category_tree(($default=='')?NULL:intval($default));
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif ((($parameter=='param') && (in_array($block,array('main_contact_catalogues')))) || (($parameter=='catalogue') && (in_array($block,array('main_recent_cc_entries'))))) // catalogue list
				{
					require_code('catalogues');
					$list=nice_get_catalogues($default,false);
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif (($parameter=='param') && (in_array($block,array('main_cc_embed'))) && ($GLOBALS['SITE_DB']->query_value('catalogue_categories','COUNT(*)')<500)) // catalogue category
				{
					$list=new ocp_tempcode();
					$structured_list=new ocp_tempcode();
					$categories=$GLOBALS['SITE_DB']->query_select('catalogue_categories',array('id','cc_title','c_name'),array('cc_parent_id'=>NULL),'ORDER BY c_name,id',100);
					$last_cat=mixed();
					foreach ($categories as $cat)
					{
						if ((is_null($last_cat)) || ($cat['c_name']!=$last_cat))
						{
							$structured_list->attach(form_input_list_group($cat['c_name'],$list));
							$list=new ocp_tempcode();
							$last_cat=$cat['c_name'];
						}
						$list->attach(form_input_list_entry(strval($cat['id']),$has_default && strval($cat['id'])==$default,get_translated_text($cat['cc_title'])));
					}
					$structured_list->attach(form_input_list_group($cat['c_name'],$list));
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$structured_list,NULL,false,false));
				}
				elseif (($parameter=='param') && (in_array($block,array('main_banner_wave','main_topsites')))) // banner type list
				{
					require_code('banners');
					$list=nice_get_banner_types($default);
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif (($parameter=='param') && (in_array($block,array('main_newsletter_signup')))) // newsletter list
				{
					$list=new ocp_tempcode();
					$rows=$GLOBALS['SITE_DB']->query_select('newsletters',array('id','title'));
					foreach ($rows as $newsletter)
						$list->attach(form_input_list_entry(strval($newsletter['id']),$has_default && strval($newsletter['id'])==$default,get_translated_text($newsletter['title'])));
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif (($parameter=='filter') && (in_array($block,array('bottom_news','main_news','side_news','side_news_archive')))) // news category list
				{
					require_code('news');
					$list=nice_get_news_categories(($default=='')?-1:intval($default));
					$fields->attach(form_input_multi_list(titleify($parameter),escape_html($description),$parameter,$list));
				}
				elseif ($parameter=='font') // font choice
				{
					$fonts=array();
					$dh=opendir(get_file_base().'/data/fonts');
					while (($f=readdir($dh)))
					{
						if (substr($f,-4)=='.ttf') $fonts[]=substr($f,0,strlen($f)-4);
					}
					closedir($dh);
					$dh=opendir(get_custom_file_base().'/data_custom/fonts');
					while (($f=readdir($dh)))
					{
						if (substr($f,-4)=='.ttf') $fonts[]=substr($f,0,strlen($f)-4);
					}
					closedir($dh);
					$fonts=array_unique($fonts);
					sort($fonts);
					$list=new ocp_tempcode();
					foreach ($fonts as $font)
					{
						$list->attach(form_input_list_entry($font,$font==$default));
					}
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif (preg_match('#'.do_lang('BLOCK_IND_EITHER').' (.+)#i',$description,$matches)!=0) // list
				{
					$description=preg_replace('# \('.do_lang('BLOCK_IND_EITHER').'.*\)#U','',$description);

					$list=new ocp_tempcode();
					$matches2=array();
					$num_matches=preg_match_all('#\'([^\']*)\'="([^"]*)"#',$matches[1],$matches2);
					if ($num_matches!=0)
					{
						for ($i=0;$i<$num_matches;$i++)
							$list->attach(form_input_list_entry($matches2[1][$i],$matches2[1][$i]==$default,$matches2[2][$i]));
					} else
					{
						$num_matches=preg_match_all('#\'([^\']*)\'#',$matches[1],$matches2);
						for ($i=0;$i<$num_matches;$i++)
							$list->attach(form_input_list_entry($matches2[1][$i],$matches2[1][$i]==$default));
					}
					$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
				}
				elseif (preg_match('#\('.do_lang('BLOCK_IND_HOOKTYPE').': \'([^\'/]*)/([^\'/]*)\'\)#i',$description,$matches)!=0) // hook list
				{
					$description=preg_replace('#\s*\('.do_lang('BLOCK_IND_HOOKTYPE').': \'([^\'/]*)/([^\'/]*)\'\)#i','',$description);

					$list=new ocp_tempcode();
					$hooks=find_all_hooks($matches[1],$matches[2]);
					ksort($hooks);
					if (($default=='') && ($has_default))
						$list->attach(form_input_list_entry('',true));
					foreach (array_keys($hooks) as $hook)
					{
						if ($block=='side_tag_cloud') // HACKHACK: When we unify our names, fix this
						{
							if (substr($hook,-1)=='y') $hook.=','.substr($hook,0,strlen($hook)-1).'ies';
							elseif ((substr($hook,-1)!='s') && ($hook!='quiz')) $hook.=','.$hook.'s';
						}
						$list->attach(form_input_list_entry($hook,$hook==$default));
					}
					if ((($block=='main_search') && ($parameter=='limit_to')) || ($block=='side_tag_cloud'))
					{
						$fields->attach(form_input_multi_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,0));
					} else
					{
						$fields->attach(form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false));
					}
				}
				elseif ((($default=='0') || ($default=='1') || (strpos($description,'\'0\'')!==false) || (strpos($description,'\'1\'')!==false)) && (do_lang('BLOCK_IND_WHETHER')!='') && (strpos(strtolower($description),do_lang('BLOCK_IND_WHETHER'))!==false)) // checkbox
				{
					$fields->attach(form_input_tick(titleify($parameter),escape_html($description),$parameter,$default=='1'));
				} elseif ((do_lang('BLOCK_IND_NUMERIC')!='') && (strpos($description,do_lang('BLOCK_IND_NUMERIC'))!==false)) // numeric
				{
					$fields->attach(form_input_integer(titleify($parameter),escape_html($description),$parameter,($default=='')?NULL:intval($default),false));
				} else // normal
				{
					$fields->attach(form_input_line(titleify($parameter),escape_html($description),$parameter,$default,false));
				}
			}
		}
		$keep=symbol_tempcode('KEEP');
		$post_url=find_script('block_helper').'?type=step3&field_name='.get_param('field_name').$keep->evaluate();
		if (get_param('utheme','')!='') $post_url.='&utheme='.get_param('utheme');
		$post_url.='&block_type='.$type_wanted;
		if (get_param('save_to_id','')!='')
		{
			$post_url.='&save_to_id='.urlencode(get_param('save_to_id'));
			$submit_name=do_lang_tempcode('SAVE');

			// Allow remove option
			$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>false,'TITLE'=>do_lang_tempcode('ACTIONS'),'HELP'=>'')));
			$fields->attach(form_input_tick(do_lang_tempcode('REMOVE'),'','_delete',false));
		} else
		{
			$submit_name=do_lang_tempcode('USE');
		}
		$text=do_lang_tempcode('BLOCK_HELPER_2',escape_html(cleanup_block_name($block)),escape_html(do_lang('BLOCK_'.$block.'_DESCRIPTION')),escape_html(do_lang('BLOCK_'.$block.'_USE')));
		$hidden=form_input_hidden('block',$block);
		$content=do_template('FORM_SCREEN',array('_GUID'=>'62f8688bf0ae4223a2ba1f76fef3b0b4','TITLE'=>$title,'TARGET'=>'_self','SKIP_VALIDATION'=>true,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'HIDDEN'=>$hidden,'PREVIEW'=>true,'THEME'=>$GLOBALS['FORUM_DRIVER']->get_theme()));

		if ($fields->is_empty()) $type='step3';
	}
	if ($type=='step3') // Close off, and copy in Comcode to browser
	{
		require_javascript('javascript_posting');
		require_javascript('javascript_editing');

		$field_name=get_param('field_name');

		$bparameters='';
		$bparameters_xml='';
		$bparameters_tempcode='';
		$block=trim(either_param('block'));
		$parameters=get_block_parameters($block);
		$parameters[]='failsafe';
		$parameters[]='cache';
		$parameters[]='quick_cache';
		if (in_array('param',$parameters))
		{
			$_parameters=array('param');
			unset($parameters[array_search('param',$parameters)]);
			$parameters=array_merge($_parameters,$parameters);
		}
		foreach ($parameters as $parameter)
		{
			$value=post_param($parameter,NULL);
			if (is_null($value))
			{
				if (post_param_integer('tick_on_form__'.$value,0)!=1) continue;
				$value='0';
			}
			if (($value!='') && (($parameter!='failsafe') || ($value=='1')) && (($parameter!='cache') || ($value=='0')) && (($parameter!='quick_cache') || ($value=='1')))
			{
				if ($parameter=='param')
				{
					$bparameters.='="'.str_replace('"','\"',$value).'"';
				} else
				{
					$bparameters.=' '.$parameter.'="'.str_replace('"','\"',$value).'"';
				}
				$bparameters_xml='<blockParam key="'.escape_html($parameter).'" val="'.escape_html($value).'" />';
				$bparameters_tempcode.=','.$parameter.'='.str_replace(',','\,',$value);
			}
		}

		$comcode='[block'.$bparameters.']'.$block.'[/block]';
		$comcode_xml='<block>'.$bparameters_xml.$block.'</block>';
		$tempcode='{$BLOCK,block='.$block.$bparameters_tempcode.'}';
		if ($type_wanted=='template') $comcode=$tempcode; // This is what will be written in

		$comcode_semihtml=comcode_to_tempcode($comcode,NULL,false,60,NULL,NULL,true,false,false);

		$content=do_template('BLOCK_HELPER_DONE',array('_GUID'=>'575d6c8120d6001c8156560be518f296','TITLE'=>$title,'FIELD_NAME'=>$field_name,'BLOCK'=>$block,'COMCODE_XML'=>$comcode_xml,'COMCODE'=>$comcode,'COMCODE_SEMIHTML'=>$comcode_semihtml));
	}

	global $EXTRA_HEAD;
	if (!isset($EXTRA_HEAD)) $EXTRA_HEAD=new ocp_tempcode();
	$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	$echo=do_template('STANDALONE_HTML_WRAP',array('TITLE'=>do_lang_tempcode('BLOCK_HELPER'),'POPUP'=>true,'CONTENT'=>$content));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}

/**
 * Shows an HTML page of all emoticons clickably.
 */
function emoticons_script()
{
	if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF'));

	require_css('ocf');

	require_lang('ocf');
	require_javascript('javascript_editing');

	$extra=has_specific_permission(get_member(),'use_special_emoticons')?'':' AND e_is_special=0';
	$rows=$GLOBALS['FORUM_DB']->query('SELECT * FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_emoticons WHERE e_relevance_level<3'.$extra);
	$content=new ocp_tempcode();
	$cols=8;
	$current_row=new ocp_tempcode();
	foreach ($rows as $i=>$myrow)
	{
		if (($i%$cols==0) && ($i!=0))
		{
			$content->attach(do_template('OCF_EMOTICON_ROW',array('_GUID'=>'283bff0bb281039b94ff2d4dcaf79172','CELLS'=>$current_row)));
			$current_row=new ocp_tempcode();
		}

		$code_esc=$myrow['e_code'];
		$current_row->attach(do_template('OCF_EMOTICON_CELL',array('_GUID'=>'ddb838e6fa296df41299c8758db92f8d','FIELD_NAME'=>get_param('field_name','post'),'CODE_ESC'=>$code_esc,'THEME_IMG_CODE'=>$myrow['e_theme_img_code'],'CODE'=>$myrow['e_code'])));
	}
	if (!$current_row->is_empty())
		$content->attach(do_template('OCF_EMOTICON_ROW',array('_GUID'=>'d13e74f7febc560dc5fc241dc7914a03','CELLS'=>$current_row)));

	$content=do_template('OCF_EMOTICON_TABLE',array('_GUID'=>'d3dd9bbfacede738e2aff4712b86944b','ROWS'=>$content));

	global $EXTRA_HEAD;
	if (!isset($EXTRA_HEAD)) $EXTRA_HEAD=new ocp_tempcode();
	$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	$echo=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'8acac778b145bfe7b063317fbcae7fde','TITLE'=>do_lang_tempcode('EMOTICONS_POPUP'),'POPUP'=>true,'CONTENT'=>$content));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}

/**
 * Allows conversion of a URL to a thumbnail via a simple script.
 */
function thumb_script()
{
	$url_full=get_param('url');
	if (strpos($url_full,'://')===false) $url_full=base64_decode($url_full);

	require_code('images');

	$new_name=url_to_filename($url_full);
	if (!is_saveable_image($new_name)) $new_name.='.png';
	if (is_null($new_name)) warn_exit(do_lang_tempcode('URL_THUMB_TOO_LONG'));
	$file_thumb=get_custom_file_base().'/uploads/auto_thumbs/'.$new_name;
	if (!file_exists($file_thumb))
	{
		convert_image($url_full,$file_thumb,-1,-1,intval(get_option('thumb_width')),false);
	}
	$url_thumb=get_custom_base_url().'/uploads/auto_thumbs/'.rawurlencode($new_name);

	if ((strpos($url_thumb,chr(10))!==false) || (strpos($url_thumb,chr(13))!==false))
		log_hack_attack_and_exit('HEADER_SPLIT_HACK');
	header('Location: '.$url_thumb);
}

/**
 * Outputs a modal question dialog.
 */
function question_ui_script()
{
	@ini_set('ocproducts.xss_detect','0');
	$GLOBALS['SCREEN_TEMPLATE_CALLED']='';

	$title=get_param('window_title',false,true);
	$_message=nl2br(escape_html(get_param('message',false,true)));
	if (function_exists('ocp_mark_as_escaped')) ocp_mark_as_escaped($_message);
	$button_set=explode(',',get_param('button_set',false,true));
	$_image_set=get_param('image_set',false,true);
	$image_set=($_image_set=='')?array():explode(',',$_image_set);
	$message=do_template('QUESTION_UI_BUTTONS',array('_GUID'=>'0c5a1efcf065e4281670426c8fbb2769','TITLE'=>$title,'IMAGES'=>$image_set,'BUTTONS'=>$button_set,'MESSAGE'=>$_message));

	global $EXTRA_HEAD;
	if (!isset($EXTRA_HEAD)) $EXTRA_HEAD=new ocp_tempcode();
	$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	$echo=do_template('STANDALONE_HTML_WRAP',array('TITLE'=>escape_html($title),'POPUP'=>true,'CONTENT'=>$message));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}


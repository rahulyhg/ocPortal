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
 * Module page class.
 */
class Module_admin_version
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=16;
		$info['locked']=true;
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('url_id_monikers');
		$GLOBALS['SITE_DB']->drop_if_exists('cache');
		$GLOBALS['SITE_DB']->drop_if_exists('cache_on');
		$GLOBALS['SITE_DB']->drop_if_exists('security_images');
		$GLOBALS['SITE_DB']->drop_if_exists('rating');
		$GLOBALS['SITE_DB']->drop_if_exists('member_tracking');
		$GLOBALS['SITE_DB']->drop_if_exists('trackbacks');
		$GLOBALS['SITE_DB']->drop_if_exists('menu_items');
		$GLOBALS['SITE_DB']->drop_if_exists('long_values');
		$GLOBALS['SITE_DB']->drop_if_exists('tutorial_links');
		$GLOBALS['SITE_DB']->drop_if_exists('translate_history');
		$GLOBALS['SITE_DB']->drop_if_exists('edit_pings');
		$GLOBALS['SITE_DB']->drop_if_exists('validated_once');
		$GLOBALS['SITE_DB']->drop_if_exists('msp');
		$GLOBALS['SITE_DB']->drop_if_exists('member_zone_access');
		$GLOBALS['SITE_DB']->drop_if_exists('member_page_access');
		$GLOBALS['SITE_DB']->drop_if_exists('member_category_access');
		$GLOBALS['SITE_DB']->drop_if_exists('tracking');
		$GLOBALS['SITE_DB']->drop_if_exists('sms_log');
		$GLOBALS['SITE_DB']->drop_if_exists('confirmed_mobiles');
		$GLOBALS['SITE_DB']->drop_if_exists('autosave');
		$GLOBALS['SITE_DB']->drop_if_exists('messages_to_render');
		$GLOBALS['SITE_DB']->drop_if_exists('url_title_cache');
		$GLOBALS['SITE_DB']->drop_if_exists('review_supplement');
		$GLOBALS['SITE_DB']->drop_if_exists('logged_mail_messages');
		$GLOBALS['SITE_DB']->drop_if_exists('link_tracker');
		$GLOBALS['SITE_DB']->drop_if_exists('incoming_uploads');
		$GLOBALS['SITE_DB']->drop_if_exists('f_group_member_timeouts');
		$GLOBALS['SITE_DB']->drop_if_exists('temp_block_permissions');
		$GLOBALS['SITE_DB']->drop_if_exists('cron_caching_requests');
		$GLOBALS['SITE_DB']->drop_if_exists('notifications_enabled');
		$GLOBALS['SITE_DB']->drop_if_exists('digestives_tin');
		$GLOBALS['SITE_DB']->drop_if_exists('digestives_consumed');
		delete_specific_permission('reuse_others_attachments');
		delete_specific_permission('use_sms');
		delete_specific_permission('sms_higher_limit');
		delete_specific_permission('sms_higher_trigger_limit');
		delete_specific_permission('assume_any_member');
		delete_config_option('url_monikers_enabled');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		// A lot of "peripheral architectural" tables are defined here. Central ones are defined in the installer -- as they need to be installed before any module.
		// This is always the first module to be installed.

		if (($upgrade_from<3) && (!is_null($upgrade_from))) // These are new in 3 of this module, and thus are for upgrades
		{
			$GLOBALS['SITE_DB']->create_table('seo_meta',array(
				'id'=>'*AUTO',
				'meta_for_type'=>'ID_TEXT',
				'meta_for_id'=>'ID_TEXT',
				'meta_keywords'=>'LONG_TRANS',
				'meta_description'=>'LONG_TRANS'
			));
			$GLOBALS['SITE_DB']->create_index('seo_meta','alt_key',array('meta_for_type','meta_for_id'));
			$GLOBALS['SITE_DB']->create_index('seo_meta','ftjoin_keywords',array('meta_keywords'));
		}
		if (($upgrade_from<4) && (!is_null($upgrade_from)))
		{
			// The sessions table isn't defined in this module... this is a throwback from before upgrader.php was fully developed
			$GLOBALS['SITE_DB']->add_table_field('sessions','session_confirmed','BINARY',0);
			$GLOBALS['SITE_DB']->add_table_field('sessions','cache_username','SHORT_TEXT');

			$GLOBALS['SITE_DB']->create_table('https_pages',array(
				'https_page_name'=>'*ID_TEXT'
			));

			$GLOBALS['SITE_DB']->create_table('attachments',array(
				'id'=>'*AUTO',
				'a_member_id'=>'USER',
				'a_file_size'=>'?INTEGER', // NULL means non-local. Doesn't count to quota
				'a_url'=>'URLPATH',
				'a_description'=>'SHORT_TEXT',
				'a_thumb_url'=>'SHORT_TEXT',
				'a_original_filename'=>'SHORT_TEXT',
				'a_num_downloads'=>'INTEGER',
				'a_last_downloaded_time'=>'?INTEGER',
				'a_add_time'=>'INTEGER'
			));
			$GLOBALS['SITE_DB']->create_index('attachments','a_add_time',array('a_member_id','a_add_time'));

			$GLOBALS['SITE_DB']->create_table('attachment_refs',array(
				'id'=>'*AUTO',
				'r_referer_type'=>'ID_TEXT',
				'r_referer_id'=>'ID_TEXT',
				'a_id'=>'INTEGER'
			));
		}

		if (($upgrade_from<4) || (is_null($upgrade_from))) // These are for fresh installs and upgrades
		{
			add_specific_permission('_COMCODE','reuse_others_attachments',true);
		}

		if (($upgrade_from<5) || (is_null($upgrade_from))) // These are for fresh installs and upgrades
		{
			$GLOBALS['SITE_DB']->create_table('menu_items',array(
				'id'=>'*AUTO',
				'i_menu'=>'ID_TEXT', // Foreign key in the future - currently it just binds together
				'i_order'=>'INTEGER',
				'i_parent'=>'?AUTO_LINK',
				'i_caption'=>'SHORT_TRANS', // Comcode
				'i_caption_long'=>'SHORT_TRANS', // Comcode
				'i_url'=>'SHORT_TEXT', // Supports zone:page followed by many :attribute=value
				'i_check_permissions'=>'BINARY',
				'i_expanded'=>'BINARY',
				'i_new_window'=>'BINARY',
				'i_page_only'=>'ID_TEXT', // Only show up if the page is this (allows page specific menus)
				'i_theme_img_code'=>'ID_TEXT',
			));
			$GLOBALS['SITE_DB']->create_index('menu_items','menu_extraction',array('i_menu'));

			$GLOBALS['SITE_DB']->create_table('trackbacks',array(
				'id'=>'*AUTO',
				'trackback_for_type'=>'ID_TEXT',
				'trackback_for_id'=>'ID_TEXT',
				'trackback_ip'=>'IP',
				'trackback_time'=>'TIME',
				'trackback_url'=>'SHORT_TEXT',
				'trackback_title'=>'SHORT_TEXT',
				'trackback_excerpt'=>'LONG_TEXT',
				'trackback_name'=>'SHORT_TEXT'
			));
			$GLOBALS['SITE_DB']->create_index('trackbacks','trackback_for_type',array('trackback_for_type'));
			$GLOBALS['SITE_DB']->create_index('trackbacks','trackback_for_id',array('trackback_for_id'));
			$GLOBALS['SITE_DB']->create_index('trackbacks','trackback_time',array('trackback_time'));

			$GLOBALS['SITE_DB']->create_table('security_images',array(
				'si_session_id'=>'*INTEGER',
				'si_time'=>'TIME',
				'si_code'=>'INTEGER'
			));
			$GLOBALS['SITE_DB']->create_index('security_images','si_time',array('si_time'));

			$GLOBALS['SITE_DB']->create_table('member_tracking',array(
				'mt_member_id'=>'*USER',
				'mt_cache_username'=>'ID_TEXT',
				'mt_time'=>'*TIME',
				'mt_page'=>'*ID_TEXT',
				'mt_type'=>'*ID_TEXT',
				'mt_id'=>'*ID_TEXT'
			));
			$GLOBALS['SITE_DB']->create_index('member_tracking','mt_page',array('mt_page'));
			$GLOBALS['SITE_DB']->create_index('member_tracking','mt_id',array('mt_page','mt_id','mt_type'));

			$GLOBALS['SITE_DB']->create_table('cache_on',array(
				'cached_for'=>'*ID_TEXT',
				'cache_on'=>'LONG_TEXT',
				'cache_ttl'=>'INTEGER',
			));
		}

		if (($upgrade_from<6) && (!is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->add_table_field('config','shared_hosting_restricted','BINARY',0);
			$GLOBALS['SITE_DB']->add_table_field('zones','zone_title','SHORT_TRANS','');
			$rows=$GLOBALS['SITE_DB']->query_select('zones',array('zone_name','zone_title'));
			foreach ($rows as $row)
			{
				$zone=$row['zone_name'];
				$st=$row['zone_title'];

				$_zone=ucfirst($zone);
				switch ($zone)
				{
					case 'docs':
						$_zone=do_lang('GUIDES');
						break;
					case 'forum':
						$_zone=do_lang('SECTION_FORUMS');
						break;
					case '':
						$_zone=do_lang('_WELCOME');
						break;
					case 'site':
						$_zone=do_lang('SITE');
						break;
					case 'cms':
						$_zone=do_lang('CMS');
						break;
					case 'collaboration':
						$_zone=do_lang('collaboration');
						break;
					case 'adminzone':
						$_zone=do_lang('ADMIN_ZONE');
						break;
				}
				$_lang2=do_lang('ZONE_'.$zone,NULL,NULL,NULL,NULL,false);
				if (!is_null($_lang2)) $_zone=$_lang2;

				lang_remap($st,$_zone);
			}
		}

		if (($upgrade_from<7) && (!is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->add_table_field('gsp','the_page','ID_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('gsp','module_the_name','ID_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('gsp','category_name','ID_TEXT');
			$GLOBALS['SITE_DB']->change_primary_key('gsp',array('group_id','specific_permission','the_page','module_the_name','category_name'));
			$GLOBALS['SITE_DB']->add_table_field('gsp','the_value','BINARY');
			$GLOBALS['SITE_DB']->query_update('gsp',array('the_value'=>1));
			$GLOBALS['SITE_DB']->add_table_field('sessions','session_invisible','BINARY');
			$GLOBALS['SITE_DB']->add_table_field('sessions','the_zone','ID_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('sessions','the_page','ID_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('sessions','the_type','ID_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('sessions','the_id','ID_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('sessions','the_title','SHORT_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('menu_items','i_caption_long','SHORT_TRANS');
			$GLOBALS['SITE_DB']->add_table_field('attachments','a_description','SHORT_TEXT');
			$GLOBALS['SITE_DB']->alter_table_field('attachments','a_url','URLPATH');
			$GLOBALS['SITE_DB']->query('UPDATE '.$GLOBALS['SITE_DB']->get_table_prefix().'menu_items SET i_url=replace(i_url,\':type=gui\',\':type=misc\')');
			$GLOBALS['SITE_DB']->query('UPDATE '.$GLOBALS['SITE_DB']->get_table_prefix().'menu_items SET i_url=replace(i_url,\':type=choose\',\':type=misc\')');
			$GLOBALS['SITE_DB']->query_update('menu_items',array('i_url'=>'_SEARCH:seedy_page:type=misc'),array('i_url'=>'_SEARCH:seedy_page:type=page'),'',1);
			$GLOBALS['SITE_DB']->query_update('menu_items',array('i_url'=>'_SEARCH:staff:type=misc'),array('i_url'=>'_SEARCH:staff:type=directory'),'',1);
			$GLOBALS['SITE_DB']->query_update('menu_items',array('i_url'=>'_SEARCH:points:type=misc'),array('i_url'=>'_SEARCH:points:type=search'),'',1);
			$GLOBALS['SITE_DB']->query_update('menu_items',array('i_url'=>'_SEARCH:members:type=misc'),array('i_url'=>'_SEARCH:members:type=directory'),'',1);
			$GLOBALS['SITE_DB']->query_update('menu_items',array('i_url'=>'_SEARCH:galleries:type=misc'),array('i_url'=>'_SEARCH:galleries:type=list'),'',1);
			$GLOBALS['SITE_DB']->query_update('menu_items',array('i_url'=>'_SEARCH:downloads:type=misc'),array('i_url'=>'_SEARCH:downloads:type=cat'),'',1);
			$GLOBALS['SITE_DB']->query_update('menu_items',array('i_url'=>'_SEARCH:catalogues:type=misc'),array('i_url'=>'_SEARCH:catalogues:type=list'),'',1);
			$GLOBALS['SITE_DB']->query_update('menu_items',array('i_url'=>'_SEARCH:calendar:type=misc'),array('i_url'=>'_SEARCH:calendar:type=calendar'),'',1);
			$GLOBALS['SITE_DB']->query_update('menu_items',array('i_url'=>'_SEARCH:news:type=misc'),array('i_url'=>'_SEARCH:news:type=list'),'',1);
		}

		if (($upgrade_from<7) || (is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->create_table('validated_once',array(
				'hash'=>'*MD5'
			));

			$GLOBALS['SITE_DB']->create_table('edit_pings',array(
				'id'=>'*AUTO',
				'the_page'=>'ID_TEXT',
				'the_type'=>'ID_TEXT',
				'the_id'=>'ID_TEXT',
				'the_time'=>'TIME',
				'the_member'=>'USER'
			));

			$GLOBALS['SITE_DB']->create_table('translate_history',array(
				'id'=>'*AUTO',
				'lang_id'=>'AUTO_LINK',
				'language'=>'*LANGUAGE_NAME',
				'text_original'=>'LONG_TEXT',
				'broken'=>'BINARY',
				'action_member'=>'USER',
				'action_time'=>'TIME'
			));

			$GLOBALS['SITE_DB']->create_table('long_values',array(
				'the_name'=>'*ID_TEXT',
				'the_value'=>'LONG_TEXT',
				'date_and_time'=>'TIME',
			));

			$GLOBALS['SITE_DB']->create_table('tutorial_links',array(
				'the_name'=>'*ID_TEXT',
				'the_value'=>'LONG_TEXT',
			));
		}

		if (($upgrade_from<9) && (!is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->add_table_field('zones','zone_displayed_in_menu','BINARY',1);
			$GLOBALS['SITE_DB']->add_table_field('config','c_set','BINARY',1);
			$options=$GLOBALS['SITE_DB']->query_select('config',array('the_name'),array('config_value'=>NULL));
			foreach ($options as $o)
			{
				$GLOBALS['SITE_DB']->query_update('config',array('config_value'=>'','c_set'=>0),array('the_name'=>$o['the_name']),'',1);
			}
			$GLOBALS['SITE_DB']->add_table_field('config','c_data','SHORT_TEXT');

			$GLOBALS['SITE_DB']->add_table_field('menu_items','i_theme_img_code','ID_TEXT');
		}

		if (($upgrade_from<8) || (is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->create_table('msp',array(
				'active_until'=>'*TIME',
				'member_id'=>'*INTEGER',
				'specific_permission'=>'*ID_TEXT',
				'the_page'=>'*ID_TEXT',
				'module_the_name'=>'*ID_TEXT',
				'category_name'=>'*ID_TEXT',
				'the_value'=>'BINARY'
			));
			$GLOBALS['SITE_DB']->create_index('msp','mspname',array('specific_permission','the_page','module_the_name','category_name'));
			$GLOBALS['SITE_DB']->create_index('msp','mspmember_id',array('member_id'));

			$GLOBALS['SITE_DB']->create_table('member_zone_access',array(
				'active_until'=>'*TIME',
				'zone_name'=>'*ID_TEXT',
				'member_id'=>'*USER'
			));
			$GLOBALS['SITE_DB']->create_index('member_zone_access','mzazone_name',array('zone_name'));
			$GLOBALS['SITE_DB']->create_index('member_zone_access','mzamember_id',array('member_id'));

			$GLOBALS['SITE_DB']->create_table('member_page_access',array(
				'active_until'=>'*TIME',
				'page_name'=>'*ID_TEXT',
				'zone_name'=>'*ID_TEXT',
				'member_id'=>'*USER'
			));
			$GLOBALS['SITE_DB']->create_index('member_page_access','mzaname',array('page_name','zone_name'));
			$GLOBALS['SITE_DB']->create_index('member_page_access','mzamember_id',array('member_id'));

			$GLOBALS['SITE_DB']->create_table('member_category_access',array(
				'active_until'=>'*TIME',
				'module_the_name'=>'*ID_TEXT',
				'category_name'=>'*ID_TEXT',
				'member_id'=>'*USER'
			));
			$GLOBALS['SITE_DB']->create_index('member_category_access','mcaname',array('module_the_name','category_name'));
			$GLOBALS['SITE_DB']->create_index('member_category_access','mcamember_id',array('member_id'));
		}

		if (($upgrade_from<9) || (is_null($upgrade_from)))
		{
			/*$GLOBALS['SITE_DB']->create_table('confirmed_mobiles',array(
				'm_phone_number'=>'*SHORT_TEXT',
				'm_member_id'=>'USER',
				'm_time'=>'TIME',
				'm_confirm_code'=>'IP'
			));*/
			/*$GLOBALS['SITE_DB']->create_index('confirmed_mobiles','confirmed_numbers',array('m_confirm_code'));*/
			add_specific_permission('STAFF_ACTIONS','assume_any_member',false,true);

			$GLOBALS['SITE_DB']->create_table('autosave',array(
				'id'=>'*AUTO',
				'a_member_id'=>'USER',
				'a_key'=>'LONG_TEXT',
				'a_value'=>'LONG_TEXT',
				'a_time'=>'TIME',
			));
			$GLOBALS['SITE_DB']->create_index('autosave','myautosaves',array('a_member_id'));

			$GLOBALS['SITE_DB']->create_table('messages_to_render',array(
				'id'=>'*AUTO',
				'r_session_id'=>'AUTO_LINK',
				'r_message'=>'LONG_TEXT',
				'r_type'=>'ID_TEXT',
				'r_time'=>'TIME',
			));
			$GLOBALS['SITE_DB']->create_index('messages_to_render','forsession',array('r_session_id'));

			$GLOBALS['SITE_DB']->create_table('url_title_cache',array(
				'id'=>'*AUTO',
				't_url'=>'URLPATH',
				't_title'=>'SHORT_TEXT',
			));
		}

		if (($upgrade_from<10) && (!is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'db_meta SET m_type=\'SHORT_INTEGER\' WHERE m_name LIKE \''.db_encode_like('%allow\_comments').'\'');
		}

		if (($upgrade_from<10) || (is_null($upgrade_from)))
		{
			add_config_option('URL_MONIKERS_ENABLED','url_monikers_enabled','tick','return \'1\';','SITE','ADVANCED');

			$GLOBALS['SITE_DB']->create_table('url_id_monikers',array(
				'id'=>'*AUTO',
				'm_resource_page'=>'ID_TEXT',
				'm_resource_type'=>'ID_TEXT',
				'm_resource_id'=>'ID_TEXT',
				'm_moniker'=>'SHORT_TEXT',
				'm_deprecated'=>'BINARY'
			));
			$GLOBALS['SITE_DB']->create_index('url_id_monikers','uim_pagelink',array('m_resource_page','m_resource_type','m_resource_id'));
			$GLOBALS['SITE_DB']->create_index('url_id_monikers','uim_moniker',array('m_moniker'));

			$GLOBALS['SITE_DB']->create_table('review_supplement',array(
				'r_post_id'=>'*AUTO_LINK',
				'r_rating_type'=>'*ID_TEXT',
				'r_rating'=>'SHORT_INTEGER',
				'r_topic_id'=>'AUTO_LINK',
				'r_rating_for_id'=>'ID_TEXT',
				'r_rating_for_type'=>'ID_TEXT',
			));
			$GLOBALS['SITE_DB']->create_index('review_supplement','rating_for_id',array('r_rating_for_id'));

			// TODO: Move these into sms addon_registry hook, once these hooks support installation
			$GLOBALS['SITE_DB']->create_table('sms_log',array(
				'id'=>'*AUTO',
				's_member_id'=>'USER',
				's_time'=>'TIME',
				's_trigger_ip'=>'IP'
			));
			$GLOBALS['SITE_DB']->create_index('sms_log','sms_log_for',array('s_member_id','s_time'));
			$GLOBALS['SITE_DB']->create_index('sms_log','sms_trigger_ip',array('s_trigger_ip'));
			require_lang('ocf');
			$GLOBALS['FORUM_DRIVER']->install_create_custom_field('mobile_phone_number',20,1,0,1,0,do_lang('SPECIAL_CPF__ocp_mobile_phone_number_DESCRIPTION'),'short_text');
			add_specific_permission('GENERAL_SETTINGS','use_sms',false);
			add_specific_permission('GENERAL_SETTINGS','sms_higher_limit',false);
			add_specific_permission('GENERAL_SETTINGS','sms_higher_trigger_limit',false);

			$GLOBALS['SITE_DB']->create_table('logged_mail_messages',array(
				'id'=>'*AUTO',
				'm_subject'=>'LONG_TEXT', // Whilst data for a subject would be tied to SHORT_TEXT, a language string could bump it up higher
				'm_message'=>'LONG_TEXT',
				'm_to_email'=>'LONG_TEXT',
				'm_to_name'=>'LONG_TEXT',
				'm_from_email'=>'SHORT_TEXT',
				'm_from_name'=>'SHORT_TEXT',
				'm_priority'=>'SHORT_INTEGER',
				'm_attachments'=>'LONG_TEXT',
				'm_no_cc'=>'BINARY',
				'm_as'=>'USER',
				'm_as_admin'=>'BINARY',
				'm_in_html'=>'BINARY',
				'm_date_and_time'=>'TIME',
				'm_member_id'=>'USER',
				'm_url'=>'LONG_TEXT',
				'm_queued'=>'BINARY',
				'm_template'=>'ID_TEXT',
			));
			$GLOBALS['SITE_DB']->create_index('logged_mail_messages','recentmessages',array('m_date_and_time'));
			$GLOBALS['SITE_DB']->create_index('logged_mail_messages','queued',array('m_queued'));

			$GLOBALS['SITE_DB']->create_table('link_tracker',array(
				'id'=>'*AUTO',
				'c_date_and_time'=>'TIME',
				'c_member_id'=>'USER',
				'c_ip_address'=>'IP',
				'c_url'=>'URLPATH',
			));
			$GLOBALS['SITE_DB']->create_index('url_title_cache','t_url',array('t_url'));

			$GLOBALS['SITE_DB']->create_table('incoming_uploads',array(
				'id'=>'*AUTO',
				'i_submitter'=>'USER',
				'i_date_and_time'=>'TIME',
				'i_orig_filename'=>'URLPATH',
				'i_save_url'=>'SHORT_TEXT'
			));
		}

		if (($upgrade_from<11) && (!is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'comcode_pages SET p_submitter=2 WHERE p_submitter='.strval($GLOBALS['FORUM_DRIVER']->get_guest_id()));
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<12))
		{
			$GLOBALS['SITE_DB']->drop_if_exists('cache');
			$GLOBALS['SITE_DB']->create_table('cache',array(
				'cached_for'=>'*ID_TEXT',
				'identifier'=>'*MINIID_TEXT',
				'the_value'=>'LONG_TEXT',
				'date_and_time'=>'TIME',
				'the_theme'=>'*ID_TEXT',
				'lang'=>'*LANGUAGE_NAME',
				'langs_required'=>'LONG_TEXT'
			));
			$GLOBALS['SITE_DB']->create_index('cache','cached_ford',array('date_and_time'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_fore',array('cached_for'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_fore2',array('cached_for','identifier'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_forf',array('lang'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_forg',array('identifier'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_forh',array('the_theme'));
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<13))
		{
			if (!$GLOBALS['SITE_DB']->table_exists('f_group_member_timeouts'))
			{
				$GLOBALS['SITE_DB']->create_table('f_group_member_timeouts',array(
					'member_id'=>'*USER',
					'group_id'=>'*GROUP',
					'timeout'=>'TIME',
				));
			}
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<13))
		{
			if (substr(get_db_type(),0,5)=='mysql')
			{
				$GLOBALS['SITE_DB']->create_index('translate','equiv_lang',array('text_original(4)'));
				$GLOBALS['SITE_DB']->create_index('translate','decache',array('text_parsed(2)'));
			}
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from>=10) && ($upgrade_from<14))
		{
			$GLOBALS['SITE_DB']->drop_if_exists('tracking');
			$GLOBALS['SITE_DB']->add_table_field('logged_mail_messages','m_template','ID_TEXT');
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from==14))
		{
			$GLOBALS['SITE_DB']->alter_table_field('digestives_tin','d_from_member_id','?USER');
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<14))
		{
			$GLOBALS['SITE_DB']->create_table('temp_block_permissions',array(
				'id'=>'*AUTO',
				'p_session_id'=>'AUTO_LINK',
				'p_block_constraints'=>'LONG_TEXT',
				'p_time'=>'TIME',
			));

			$GLOBALS['SITE_DB']->create_table('cron_caching_requests',array(
				'id'=>'*AUTO',
				'c_codename'=>'ID_TEXT',
				'c_map'=>'LONG_TEXT',
				'c_timezone'=>'ID_TEXT',
				'c_is_bot'=>'BINARY',
				'c_store_as_tempcode'=>'BINARY',
				'c_lang'=>'LANGUAGE_NAME',
				'c_theme'=>'ID_TEXT',
			));
			$GLOBALS['SITE_DB']->create_index('cron_caching_requests','c_compound',array('c_codename','c_theme','c_lang','c_timezone'));
			$GLOBALS['SITE_DB']->create_index('cron_caching_requests','c_is_bot',array('c_is_bot'));
			$GLOBALS['SITE_DB']->create_index('cron_caching_requests','c_store_as_tempcode',array('c_store_as_tempcode'));

			$GLOBALS['SITE_DB']->create_table('notifications_enabled',array(
				'id'=>'*AUTO',
				'l_member_id'=>'USER',
				'l_notification_code'=>'ID_TEXT',
				'l_code_category'=>'SHORT_TEXT',
				'l_setting'=>'INTEGER',
			));
			$GLOBALS['SITE_DB']->create_index('notifications_enabled','l_member_id',array('l_member_id','l_notification_code'));
			$GLOBALS['SITE_DB']->create_index('notifications_enabled','l_code_category',array('l_code_category'));

			$GLOBALS['SITE_DB']->create_table('digestives_tin',array( // Notifications queued up ready for the regular digest email
				'id'=>'*AUTO',
				'd_subject'=>'LONG_TEXT',
				'd_message'=>'LONG_TEXT',
				'd_from_member_id'=>'?USER',
				'd_to_member_id'=>'USER',
				'd_priority'=>'SHORT_INTEGER',
				'd_no_cc'=>'BINARY',
				'd_date_and_time'=>'TIME',
				'd_notification_code'=>'ID_TEXT',
				'd_code_category'=>'SHORT_TEXT',
				'd_frequency'=>'INTEGER', // e.g. A_DAILY_EMAIL_DIGEST
			));
			$GLOBALS['SITE_DB']->create_index('digestives_tin','d_date_and_time',array('d_date_and_time'));
			$GLOBALS['SITE_DB']->create_index('digestives_tin','d_frequency',array('d_frequency'));
			$GLOBALS['SITE_DB']->create_index('digestives_tin','d_to_member_id',array('d_to_member_id'));
			$GLOBALS['SITE_DB']->create_table('digestives_consumed',array(
				'c_member_id'=>'*USER',
				'c_frequency'=>'*INTEGER', // e.g. A_DAILY_EMAIL_DIGEST
				'c_time'=>'TIME',
			));
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from==15))
		{
			$GLOBALS['SITE_DB']->delete_index_if_exists('cron_caching_requests','c_in_panel');
			$GLOBALS['SITE_DB']->delete_index_if_exists('cron_caching_requests','c_interlock');
			$GLOBALS['SITE_DB']->delete_table_field('cron_caching_requests','c_interlock');
			$GLOBALS['SITE_DB']->delete_table_field('cron_caching_requests','c_in_panel');

			$GLOBALS['SITE_DB']->delete_index_if_exists('rating','rating_for_id');
			$GLOBALS['SITE_DB']->create_index('rating','rating_for_id',array('rating_for_id'));
		}

		if (is_null($upgrade_from)) // These are only for fresh installs
		{
			$GLOBALS['SITE_DB']->create_table('rating',array(
				'id'=>'*AUTO',
				'rating_for_type'=>'ID_TEXT',
				'rating_for_id'=>'ID_TEXT',
				'rating_member'=>'USER',
				'rating_ip'=>'IP',
				'rating_time'=>'TIME',
				'rating'=>'SHORT_INTEGER'
			));
			$GLOBALS['SITE_DB']->create_index('rating','alt_key',array('rating_for_type','rating_for_id'));
			$GLOBALS['SITE_DB']->create_index('rating','rating_for_id',array('rating_for_id'));
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array();
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		// This used to be a real module, before ocPortal was free
		return new ocp_tempcode();
	}

}



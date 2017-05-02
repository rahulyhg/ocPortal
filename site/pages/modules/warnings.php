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
 * @package		ocf_warnings
 */

require_code('aed_module');

/**
 * Module page class.
 */
class Module_warnings extends standard_aed_module
{
	var $lang_type='WARNING';
	var $select_name='SUBMITTER';
	var $select_name_description='DESCRIPTION_SUBMITTER';
	var $redirect_type='!';
	var $menu_label='MODULE_TRANS_NAME_warnings';
	var $table='f_warnings';
	var $orderer='w_time';
	var $orderer_is_multi_lang=false;
	var $title_is_multi_lang=true;

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return is_guest()?array():(parent::get_entry_points());
	}

	/**
	 * Standard aed_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		require_lang('ocf_warnings');

		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_code('ocf_moderation_action');
		require_code('ocf_moderation_action2');

		if (!ocf_may_warn_members())
			access_denied('PRIVILEGE','warn_members');

		if ($type=='history') return $this->history();
		if ($type=='undo_charge') return $this->undo_charge();
		if ($type=='undo_probation') return $this->undo_probation();
		if ($type=='undo_banned_ip') return $this->undo_banned_ip();
		if ($type=='undo_banned_member') return $this->undo_banned_member();
		if ($type=='undo_silence_from_topic') return $this->undo_silence_from_topic();
		if ($type=='undo_silence_from_forum') return $this->undo_silence_from_forum();

		return new ocp_tempcode();
	}

	/**
	 * View the warning/punishment history for a member.
	 *
	 * @return tempcode		The output of the run
	 */
	function history()
	{
		$title=get_screen_title('PUNITIVE_HISTORY');

		require_code('templates_results_table');

		$member_id=get_param_integer('id');

		$rows=$GLOBALS['FORUM_DB']->query_select('f_warnings',array('*'),array('w_member_id'=>$member_id));
		if (count($rows)==0) inform_exit(do_lang_tempcode('NO_ENTRIES'));
		$max_rows=count($rows);

		$out=new ocp_tempcode();
		$f=array(do_lang_tempcode('SLASH_OR',do_lang_tempcode('DATE'),do_lang_tempcode('BY')),do_lang('WHETHER_MAKE_WARNING'),do_lang('CHANGED_USERGROUP'),do_lang('PUNISHMENT_UNDOING'));
		$fields_title=results_field_title($f,array());
		foreach ($rows as $row)
		{
			$date=hyperlink(build_url(array('page'=>'_SELF','type'=>'_ed','id'=>$row['id'],'redirect'=>get_self_url(true)),'_SELF'),get_timezoned_date($row['w_time']),false,true,$row['w_explanation']);
			$by=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['w_by']);
			$date_by=new ocp_tempcode();
			$date_by->attach(do_lang_tempcode('SLASH_OR',$date,$by));

			$is_warning=escape_html($row['w_is_warning']?do_lang_tempcode('YES'):do_lang_tempcode('NO'));

			$changed_usergroup_from=escape_html((is_null($row['p_changed_usergroup_from'])?do_lang_tempcode('NO'):do_lang_tempcode('YES')));
			$charged_points=($row['p_charged_points']==0)?new ocp_tempcode():div(hyperlink(build_url(array('page'=>'_SELF','type'=>'undo_charge'),'_SELF'),do_lang_tempcode('RESTORE_POINTS',integer_format($row['p_charged_points'])),false,true,'',NULL,form_input_hidden('id',strval($row['id']))),'dsgsgdfgddgdf');
			$undoing=new ocp_tempcode();
			if ($row['p_probation']==0)
			{
				$_undoing_link=new ocp_tempcode();
			} else
			{
				$_undoing_url=build_url(array('page'=>'_SELF','type'=>'undo_probation'),'_SELF');
				$_undoing_link=div(hyperlink($_undoing_url,do_lang_tempcode('REMOVE_PROBATION_DAYS',integer_format($row['p_probation'])),false,false,'',NULL,form_input_hidden('id',strval($row['id']))),'46t54yhrtghdfhdhdfg');
			}
			$undoing->attach($_undoing_link);
			if (addon_installed('points')) $undoing->attach($charged_points);
			if ($row['p_banned_ip']!='')
				$undoing->attach(div(hyperlink(build_url(array('page'=>'_SELF','type'=>'undo_banned_ip'),'_SELF'),do_lang_tempcode('UNBAN_IP'),false,true,'',NULL,form_input_hidden('id',strval($row['id']))),'4teryeryrydfhyhrgf'));
			if ($row['p_banned_member']==1)
				$undoing->attach(div(hyperlink(build_url(array('page'=>'_SELF','type'=>'undo_banned_member'),'_SELF'),do_lang_tempcode('UNBAN_MEMBER'),false,true,'',NULL,form_input_hidden('id',strval($row['id']))),'56ytryrtyhrtyrt'));
			if (!is_null($row['p_silence_from_topic']))
				$undoing->attach(div(hyperlink(build_url(array('page'=>'_SELF','type'=>'undo_silence_from_topic'),'_SELF'),do_lang_tempcode('UNSILENCE_TOPIC'),false,true,'',NULL,form_input_hidden('id',strval($row['id']))),'rgergdfhfhg'));
			if (!is_null($row['p_silence_from_forum']))
				$undoing->attach(div(hyperlink(build_url(array('page'=>'_SELF','type'=>'undo_silence_from_forum'),'_SELF'),do_lang_tempcode('UNSILENCE_FORUM'),false,true,'',NULL,form_input_hidden('id',strval($row['id']))),'ghgfhfghggf'));
			if ($undoing->is_empty()) $undoing=do_lang_tempcode('NA_EM');

			$g=array($date_by,$is_warning,$changed_usergroup_from,$undoing);
			$out->attach(results_entry($g));
		}
		$results_table=results_table(do_lang_tempcode('PUNITIVE_HISTORY'),0,'start',1000000,'max',$max_rows,$fields_title,$out,NULL,NULL,NULL,NULL,paragraph(do_lang_tempcode('PUNITIVE_HISTORY_TEXT'),'4t4ygyerhrth4'));

		$add_warning_url=build_url(array('page'=>'_SELF','type'=>'ad','id'=>$member_id,'redirect'=>get_self_url(true)),'_SELF');
		$view_profile_url=$GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,false,true);
		$edit_profile_url=build_url(array('page'=>'members','type'=>'view','id'=>$member_id),get_module_zone('members'),NULL,false,false,false,'tab__edit');

		return do_template('OCF_WARNING_HISTORY_SCREEN',array('_GUID'=>'4444beed9305f0460a6c00e6c87d4208','TITLE'=>$title,'MEMBER_ID'=>strval($member_id),'EDIT_PROFILE_URL'=>$edit_profile_url,'VIEW_PROFILE_URL'=>$view_profile_url,'ADD_WARNING_URL'=>$add_warning_url,'RESULTS_TABLE'=>$results_table));
	}

	/**
	 * Actualiser to undo a certain type of punitive action.
	 *
	 * @return tempcode		Result (redirect page)
	 */
	function undo_charge()
	{
		$title=get_screen_title('UNDO_CHARGE');

		$id=post_param_integer('id');
		$member_id=$GLOBALS['FORUM_DB']->query_value('f_warnings','w_member_id',array('id'=>$id));
		$charged_points=$GLOBALS['FORUM_DB']->query_value('f_warnings','p_charged_points',array('id'=>$id));
		require_code('points2');
		charge_member($member_id,-$charged_points,do_lang('UNDO_CHARGE_FOR',strval($id)));
		$GLOBALS['FORUM_DB']->query_update('f_warnings',array('p_charged_points'=>0),array('id'=>$id),'',1);

		log_it('UNDO_CHARGE',strval($id),$GLOBALS['FORUM_DRIVER']->get_username($member_id));

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'history','id'=>$member_id),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Actualiser to undo a certain type of punitive action.
	 *
	 * @return tempcode		Result (redirect page)
	 */
	function undo_probation()
	{
		$title=get_screen_title('UNDO_PROBATION');

		$id=post_param_integer('id');
		$member_id=$GLOBALS['FORUM_DB']->query_value('f_warnings','w_member_id',array('id'=>$id));
		$probation=$GLOBALS['FORUM_DB']->query_value('f_warnings','p_probation',array('id'=>$id));
		$on_probation_until=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_on_probation_until');
		if (!is_null($on_probation_until))
			$GLOBALS['FORUM_DB']->query_update('f_members',array('m_on_probation_until'=>$on_probation_until-$probation*60*60*24),array('id'=>$member_id),'',1);
		$GLOBALS['FORUM_DB']->query_update('f_warnings',array('p_probation'=>0),array('id'=>$id),'',1);

		log_it('UNDO_PROBATION',strval($id),$GLOBALS['FORUM_DRIVER']->get_username($member_id));

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'history','id'=>$member_id),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Actualiser to undo a certain type of punitive action.
	 *
	 * @return tempcode		Result (redirect page)
	 */
	function undo_banned_ip()
	{
		$title=get_screen_title('UNBAN_IP');

		require_code('failure');

		$id=post_param_integer('id');
		$member_id=$GLOBALS['FORUM_DB']->query_value('f_warnings','w_member_id',array('id'=>$id));
		$banned_ip=$GLOBALS['FORUM_DB']->query_value('f_warnings','p_banned_ip',array('id'=>$id));
		remove_ip_ban($banned_ip);
		$GLOBALS['FORUM_DB']->query_update('f_warnings',array('p_banned_ip'=>''),array('id'=>$id),'',1);

		log_it('UNBAN_IP',strval($id),$banned_ip);

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'history','id'=>$member_id),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Actualiser to undo a certain type of punitive action.
	 *
	 * @return tempcode		Result (redirect page)
	 */
	function undo_banned_member()
	{
		$title=get_screen_title('UNBAN_MEMBER');

		$id=post_param_integer('id');
		$member_id=$GLOBALS['FORUM_DB']->query_value('f_warnings','w_member_id',array('id'=>$id));
		$banned_member=$GLOBALS['FORUM_DB']->query_value('f_warnings','p_banned_member',array('id'=>$id));
		$GLOBALS['FORUM_DB']->query_update('f_members',array('m_is_perm_banned'=>0),array('id'=>$member_id),'',1);
		$GLOBALS['FORUM_DB']->query_update('f_warnings',array('p_banned_member'=>0),array('id'=>$id),'',1);

		log_it('UNBAN_MEMBER',strval($id),$GLOBALS['FORUM_DRIVER']->get_username($member_id));

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'history','id'=>$member_id),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Actualiser to undo a certain type of punitive action.
	 *
	 * @return tempcode		Result (redirect page)
	 */
	function undo_silence_from_topic()
	{
		$title=get_screen_title('UNSILENCE_TOPIC');

		$id=post_param_integer('id');
		$member_id=$GLOBALS['FORUM_DB']->query_value('f_warnings','w_member_id',array('id'=>$id));
		$silence_from_topic=$GLOBALS['FORUM_DB']->query_value('f_warnings','p_silence_from_topic',array('id'=>$id));
		$GLOBALS['SITE_DB']->query_delete('msp',array(
			'member_id'=>$member_id,
			'specific_permission'=>'submit_lowrange_content',
			'the_page'=>'',
			'module_the_name'=>'topics',
			'category_name'=>strval($silence_from_topic),
		));
		$GLOBALS['FORUM_DB']->query_update('f_warnings',array('p_silence_from_topic'=>NULL),array('id'=>$id),'',1);

		log_it('UNSILENCE_TOPIC',strval($id));

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'history','id'=>$member_id),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Actualiser to undo a certain type of punitive action.
	 *
	 * @return tempcode		Result (redirect page)
	 */
	function undo_silence_from_forum()
	{
		$title=get_screen_title('UNSILENCE_FORUM');

		$id=post_param_integer('id');
		$member_id=$GLOBALS['FORUM_DB']->query_value('f_warnings','w_member_id',array('id'=>$id));
		$silence_from_forum=$GLOBALS['FORUM_DB']->query_value('f_warnings','p_silence_from_forum',array('id'=>$id));
		$GLOBALS['SITE_DB']->query_delete('msp',array(
			'member_id'=>$member_id,
			'specific_permission'=>'submit_lowrange_content',
			'the_page'=>'',
			'module_the_name'=>'forums',
			'category_name'=>strval($silence_from_forum),
		));
		$GLOBALS['FORUM_DB']->query_update('f_warnings',array('p_silence_from_forum'=>NULL),array('id'=>$id),'',1);

		log_it('UNSILENCE_FORUM',strval($id));

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'history','id'=>$member_id),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Get tempcode for a warning adding/editing form.
	 *
	 * @param  boolean		Whether it is a new warning/punishment record
	 * @param  LONG_TEXT		The explanation for the warning/punishment record
	 * @param  BINARY			Whether to make this a formal warning
	 * @param  ?MEMBER		The member the warning is for (NULL: get from environment)
	 * @return array			A pair: the tempcode for the visible fields, and the tempcode for the hidden fields
	 */
	function get_form_fields($new=true,$explanation='',$is_warning=0,$member_id=NULL)
	{
		if (is_null($member_id)) $member_id=get_param_integer('id',get_member());

		$hidden=new ocp_tempcode();
		$fields=new ocp_tempcode();

		require_code('form_templates');

		// Information about their history, and the rules - to educate the warner/punisher
		if ($new)
		{
			$post_id=get_param_integer('post_id',NULL);

			$hidden->attach(form_input_hidden('member_id',strval($member_id)));

			$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id);
			$num_warnings=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_cache_warnings');
			$_rules_url=build_url(array('page'=>'rules'),'_SEARCH');
			$rules_url=$_rules_url->evaluate();
			$_history_url=build_url(array('page'=>'_SELF','type'=>'history','id'=>$member_id),'_SELF');
			$history_url=$_history_url->evaluate();
			$profile_url=$GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,false,true);
			if (is_object($profile_url)) $profile_url=$profile_url->evaluate();
			$this->add_text=do_lang_tempcode('HAS_ALREADY_X_WARNINGS',escape_html($username),integer_format($num_warnings),array(escape_html(get_site_name()),escape_html($rules_url),escape_html($history_url),escape_html($profile_url)));
		}

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('MODULE_TRANS_NAME_warnings'))));
		$fields->attach(form_input_tick(do_lang_tempcode('WHETHER_MAKE_WARNING'),do_lang_tempcode('DESCRIPTION_WHETHER_MAKE_WARNING'),'is_warning',$is_warning==1));

		// Punitive actions
		if ($new)
		{
			$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('PUNITIVE_ACTIONS'))));

			if (!is_null($post_id))
			{
				$topic_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_posts','p_topic_id',array('id'=>$post_id));
				if (!is_null($topic_id))
				{
					$forum_id=$GLOBALS['FORUM_DB']->query_value('f_topics','t_forum_id',array('id'=>$topic_id));
					$hidden->attach(form_input_hidden('topic_id',strval($topic_id)));
					$hidden->attach(form_input_hidden('forum_id',strval($forum_id)));
					$silence_topic_time=NULL;//time()+60*60*24*7;
					$silence_forum_time=NULL;//time()+60*60*24*7;
					$active_until=$GLOBALS['SITE_DB']->query_value_null_ok('msp','active_until',array(
						'member_id'=>$member_id,
						'specific_permission'=>'submit_lowrange_content',
						'the_page'=>'',
						'module_the_name'=>'topics',
						'category_name'=>strval($topic_id),
					));
					if (!is_null($active_until)) $silence_topic_time=$active_until;
					$active_until=$GLOBALS['SITE_DB']->query_value_null_ok('msp','active_until',array(
						'member_id'=>$member_id,
						'specific_permission'=>'submit_lowrange_content',
						'the_page'=>'',
						'module_the_name'=>'forums',
						'category_name'=>strval($forum_id),
					));
					if (!is_null($active_until)) $silence_forum_time=$active_until;
					$fields->attach(form_input_date(do_lang_tempcode('SILENCE_FROM_TOPIC'),do_lang_tempcode('DESCRIPTION_SILENCE_FROM_TOPIC'),'silence_from_topic',true,true,true,$silence_topic_time,2));
					$fields->attach(form_input_date(do_lang_tempcode('SILENCE_FROM_FORUM'),do_lang_tempcode('DESCRIPTION_SILENCE_FROM_FORUM'),'silence_from_forum',true,true,true,$silence_forum_time,2));
				}
			}

			if (has_specific_permission(get_member(),'probate_members'))
			{
				$fields->attach(form_input_integer(do_lang_tempcode('EXTEND_PROBATION'),do_lang_tempcode('DESCRIPTION_EXTEND_PROBATION'),'probation',0,true));
			}
			if (addon_installed('securitylogging'))
			{
				if (has_actual_page_access(get_member(),'admin_ipban'))
				{
					$fields->attach(form_input_tick(do_lang_tempcode('WHETHER_BANNED_IP'),do_lang_tempcode('DESCRIPTION_WHETHER_BANNED_IP'),'banned_ip',false));
				}
			}
			$stopforumspam_api_key=get_option('stopforumspam_api_key');
			if (is_null($stopforumspam_api_key)) $stopforumspam_api_key='';
			$tornevall_api_username=get_option('tornevall_api_username');
			if (is_null($tornevall_api_username)) $tornevall_api_username='';
			if ($stopforumspam_api_key.$tornevall_api_username!='')
			{
				require_lang('security');
				$fields->attach(form_input_tick(do_lang_tempcode('SYNDICATE_TO_STOPFORUMSPAM'),do_lang_tempcode('DESCRIPTION_SYNDICATE_TO_STOPFORUMSPAM'),'stopforumspam',false));
			}
			if (addon_installed('points'))
			{
				if (has_actual_page_access(get_member(),'admin_points'))
				{
					require_code('points');
					$num_points_currently=available_points($member_id);
					$fields->attach(form_input_integer(do_lang_tempcode('CHARGED_POINTS'),do_lang_tempcode('DESCRIPTION_CHARGED_POINTS',escape_html(integer_format($num_points_currently))),'charged_points',0,true));
				}
			}
			if (has_specific_permission(get_member(),'member_maintenance'))
			{
				$fields->attach(form_input_tick(do_lang_tempcode('BANNED_MEMBER'),do_lang_tempcode('DESCRIPTION_BANNED_MEMBER'),'banned_member',false));

				$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('id','g_name'),array('g_is_private_club'=>0));
				$groups=new ocp_tempcode();
				$groups->attach(form_input_list_entry('-1',false,do_lang_tempcode('NA_EM')));
				foreach ($rows as $group)
				{
					if ($group['id']!=db_get_first_id())
					{
						$groups->attach(form_input_list_entry(strval($group['id']),false,get_translated_text($group['g_name'],$GLOBALS['FORUM_DB'])));
					}
				}
				$fields->attach(form_input_list(do_lang_tempcode('CHANGE_USERGROUP_TO'),do_lang_tempcode('DESCRIPTION_CHANGE_USERGROUP_TO'),'changed_usergroup_from',$groups));
			}
		}

		// Explanatory text
		$keep=symbol_tempcode('KEEP');
		$load_url=find_script('warnings').'?type=load'.$keep->evaluate();
		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('EXPLANATORY_TEXT'),'HELP'=>do_lang_tempcode('LOAD_SAVED_WARNING',escape_html($load_url)))));
		$fields->attach(form_input_line_comcode(do_lang_tempcode('EXPLANATION'),do_lang_tempcode('DESCRIPTION_EXPLANATION'),'explanation',$explanation,true));
		if ($new)
		{
			$message='';
			if (!is_null($post_id))
			{
				$_postdetails_text=$GLOBALS['FORUM_DB']->query_value_null_ok('f_posts','p_post',array('id'=>$post_id));
				if (!is_null($_postdetails_text))
				{
					$message='[quote="'.$username.'"]'.chr(10).get_translated_text($_postdetails_text,$GLOBALS['FORUM_DB']).chr(10).'[/quote]';
				}
			}
			$fields->attach(form_input_text_comcode(do_lang_tempcode('MESSAGE'),do_lang_tempcode('DESCRIPTION_PP_MESSAGE'),'message',$message,false));

			$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('ACTIONS'))));
			$fields->attach(form_input_line(do_lang_tempcode('SAVE_WARNING_DETAILS'),do_lang_tempcode('DESCRIPTION_SAVE_WARNING_DETAILS'),'save','',false));
		}

		return array($fields,$hidden);
	}

	/**
	 * Standard aed_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return array			A quartet: The choose table, Whether re-ordering is supported from this screen, Search URL, Archive URL.
	 */
	function nice_get_choose_table($url_map)
	{
		require_code('templates_results_table');

		$current_ordering=get_param('sort','w_time DESC',true);
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		$sortables=array(
			'w_time'=>do_lang_tempcode('DATE'),
		);
		if (addon_installed('points'))
			$sortables['p_charged_points']=do_lang_tempcode('POINTS');
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';

		$fh=array(
			do_lang_tempcode('USERNAME'),
			do_lang_tempcode('BY'),
			do_lang_tempcode('DATE'),
		);
		if (addon_installed('points'))
			$fh[]=do_lang_tempcode('POINTS');
		$fh[]=do_lang_tempcode('ACTIONS');

		$header_row=results_field_title($fh,$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		require_code('form_templates');
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering);
		foreach ($rows as $row)
		{
			$edit_link=build_url($url_map+array('id'=>$row['id']),'_SELF');

			$username=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['w_member_id']);
			$by=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['w_by']);

			$map=array(
				protect_from_escaping($username),
				protect_from_escaping($by),
				get_timezoned_date($row['w_time']),
			);

			if (addon_installed('points'))
				$map[]=integer_format($row['p_charged_points']);

			$map[]=protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.strval($row['id'])));

			$fields->attach(results_entry($map,true));
		}

		$search_url=NULL;
		$archive_url=NULL;

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order),false,$search_url,$archive_url);
	}

	/**
	 * Standard aed_module list function.
	 *
	 * @return tempcode		The selection list
	 */
	function nice_get_entries()
	{
		$_m=$GLOBALS['FORUM_DB']->query_select('f_warnings',array('*'),NULL,'ORDER BY w_time DESC');
		$entries=new ocp_tempcode();
		foreach ($_m as $m)
		{
			$entries->attach(form_input_list_entry(strval($m['id']),false,$GLOBALS['FORUM_DRIVER']->get_username($m['w_member_id']).' ('.get_timezoned_date($m['w_time']).')'));
		}

		return $entries;
	}

	/**
	 * Standard aed_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return tempcode		The edit form
	 */
	function fill_in_edit_form($id)
	{
		$warning=$GLOBALS['FORUM_DB']->query_select('f_warnings',array('w_explanation','w_by','w_member_id','w_is_warning'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$warning)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$fields=$this->get_form_fields(false,$warning[0]['w_explanation'],$warning[0]['w_is_warning'],$warning[0]['w_member_id']);

		return $fields;
	}

	/**
	 * Standard aed_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		$explanation=post_param('explanation');
		$member_id=post_param_integer('member_id');
		$message=post_param('message','');
		$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id);
		if (is_null($username)) warn_exit(do_lang_tempcode('_USER_NO_EXIST',escape_html($username)));

		$save=post_param('save');
		if ($save!='')
		{
			$GLOBALS['FORUM_DB']->query_delete('f_saved_warnings',array('s_title'=>$save),'',1);
			$GLOBALS['FORUM_DB']->query_insert('f_saved_warnings',array(
				's_title'=>$save,
				's_explanation'=>$explanation,
				's_message'=>$message,
			));
		}

		// Send PT
		if ($message!='')
		{
			require_code('ocf_topics_action');
			require_code('ocf_topics_action2');
			require_code('ocf_posts_action');
			require_code('ocf_posts_action2');

			$_title=do_lang('NEW_WARNING_TO_YOU');

			$pt_topic_id=ocf_make_topic(NULL,'','',1,1,0,0,0,get_member(),$member_id);
			$post_id=ocf_make_post($pt_topic_id,$_title,$message,0,true,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,false);

			send_pt_notification($post_id,$_title,$pt_topic_id,$member_id);
		}

		// Topic silencing
		$silence_from_topic=post_param_integer('topic_id',NULL);
		if (!is_null($silence_from_topic))
		{
			$_silence_from_topic=get_input_date('silence_from_topic');
			$GLOBALS['SITE_DB']->query_delete('msp',array(
				'member_id'=>$member_id,
				'specific_permission'=>'submit_lowrange_content',
				'the_page'=>'',
				'module_the_name'=>'topics',
				'category_name'=>strval($silence_from_topic),
			));
		} else $_silence_from_topic=NULL;
		if (!is_null($_silence_from_topic))
		{
			$GLOBALS['SITE_DB']->query_insert('msp',array(
				'active_until'=>$_silence_from_topic,
				'member_id'=>$member_id,
				'specific_permission'=>'submit_lowrange_content',
				'the_page'=>'',
				'module_the_name'=>'topics',
				'category_name'=>strval($silence_from_topic),
				'the_value'=>'0'
			));
		} else $silence_from_topic=NULL;

		// Forum silencing
		$silence_from_forum=post_param_integer('forum_id',NULL);
		if (!is_null($silence_from_forum))
		{
			$GLOBALS['SITE_DB']->query_delete('msp',array(
				'member_id'=>$member_id,
				'specific_permission'=>'submit_lowrange_content',
				'the_page'=>'',
				'module_the_name'=>'forums',
				'category_name'=>strval($silence_from_forum),
			));
			$GLOBALS['SITE_DB']->query_delete('msp',array(
				'member_id'=>$member_id,
				'specific_permission'=>'submit_midrange_content',
				'the_page'=>'',
				'module_the_name'=>'forums',
				'category_name'=>strval($silence_from_forum),
			));
			$_silence_from_forum=get_input_date('silence_from_forum');
		} else $_silence_from_forum=NULL;
		if (!is_null($_silence_from_forum))
		{
			$GLOBALS['SITE_DB']->query_insert('msp',array(
				'active_until'=>$_silence_from_forum,
				'member_id'=>$member_id,
				'specific_permission'=>'submit_lowrange_content',
				'the_page'=>'',
				'module_the_name'=>'forums',
				'category_name'=>strval($silence_from_forum),
				'the_value'=>'0'
			));
			$GLOBALS['SITE_DB']->query_insert('msp',array(
				'active_until'=>$_silence_from_forum,
				'member_id'=>$member_id,
				'specific_permission'=>'submit_midrange_content',
				'the_page'=>'',
				'module_the_name'=>'forums',
				'category_name'=>strval($silence_from_forum),
				'the_value'=>'0'
			));
		} else $silence_from_forum=NULL;

		// Probation
		$probation=post_param_integer('probation',0);
		if (has_specific_permission(get_member(),'probate_members'))
		{
			if ($probation!=0)
			{
				$on_probation_until=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_on_probation_until');
				if ((is_null($on_probation_until)) || ($on_probation_until<time())) $on_probation_until=time();
				$on_probation_until+=$probation*60*60*24;
				$GLOBALS['FORUM_DB']->query_update('f_members',array('m_on_probation_until'=>$on_probation_until),array('id'=>$member_id),'',1);
			}
		}

		// Ban member
		if (has_specific_permission(get_member(),'member_maintenance'))
		{
			$banned_member=post_param_integer('banned_member',0);
			if ($banned_member==1)
			{
				$GLOBALS['FORUM_DB']->query_update('f_members',array('m_is_perm_banned'=>1),array('id'=>$member_id),'',1);
			}
		} else $banned_member=0;

		// IP ban
		$banned_ip='';
		if (addon_installed('securitylogging'))
		{
			if (has_actual_page_access(get_member(),'admin_ipban'))
			{
				$_banned_ip=post_param_integer('banned_ip',0);
				if ($_banned_ip==1)
				{
					$banned_ip=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_ip_address');
					require_code('failure');
					add_ip_ban($banned_ip);
				}
			}
		}

		// Stop Forum Spam report
		$stopforumspam=post_param_integer('stopforumspam',0);
		if ($stopforumspam==1)
		{
			$banned_ip=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_ip_address');
			require_code('failure');
			syndicate_spammer_report($banned_ip,$GLOBALS['FORUM_DRIVER']->get_username($member_id),$GLOBALS['FORUM_DRIVER']->get_member_email_address($member_id),$explanation,true);
		}

		// Change group
		$changed_usergroup_from=NULL;
		if (has_specific_permission(get_member(),'member_maintenance'))
		{
			$__changed_usergroup_from=post_param('changed_usergroup_from');
			if ($__changed_usergroup_from=='')
			{
				$_changed_usergroup_from=NULL;
			} else
			{
				$_changed_usergroup_from=intval($__changed_usergroup_from);
			}
			if ((!is_null($_changed_usergroup_from)) && ($_changed_usergroup_from!=-1))
			{
				$changed_usergroup_from=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_primary_group');
				$GLOBALS['FORUM_DB']->query_update('f_members',array('m_primary_group'=>$_changed_usergroup_from),array('id'=>$member_id),'',1);
			}
		}

		// Prepare to charge points (used in ocf_make_warning)
		$charged_points=post_param_integer('charged_points',0);

		// Make the warning
		$warning_id=ocf_make_warning($member_id,$explanation,NULL,NULL,post_param_integer('is_warning',0),$silence_from_topic,$silence_from_forum,$probation,$banned_ip,$charged_points,$banned_member,$changed_usergroup_from);

		// Charge points
		if (addon_installed('points'))
		{
			if (has_actual_page_access(get_member(),'admin_points'))
			{
				if ($charged_points!=0)
				{
					require_code('points2');
					charge_member($member_id,$charged_points,do_lang('FOR_PUNISHMENT',strval($warning_id)));
				}
			}
		}

		if (get_param('redirect','')=='')
		{
			require_code('site2');
			assign_refresh($GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,true,true),0.0);
		}

		return strval($warning_id);
	}

	/**
	 * Standard aed_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 */
	function edit_actualisation($id)
	{
		$member_id=ocf_edit_warning(intval($id),post_param('explanation'),post_param_integer('is_warning',0));

		if (get_param('redirect','')=='')
		{
			require_code('site2');
			assign_refresh($GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,true,true),0.0);
		}
	}

	/**
	 * Standard aed_module submitter getter.
	 *
	 * @param  ID_TEXT		The entry for which the submitter is sought
	 * @return array			The submitter, and the time of submission (null submission time implies no known submission time)
	 */
	function get_submitter($id)
	{
		$rows=$GLOBALS['FORUM_DB']->query_select('f_warnings',array('w_by','w_time'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$rows)) return array(NULL,NULL);
		return array($rows[0]['w_by'],$rows[0]['w_time']);
	}

	/**
	 * Standard aed_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		$member_id=ocf_delete_warning(intval($id));

		if (get_param('redirect','')=='')
		{
			require_code('site2');
			assign_refresh($GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,true,true),0.0);
		}
	}
}



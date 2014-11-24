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
 * @package		core_ocf
 */

class Hook_search_ocf_members
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		if (get_forum_type()!='ocf') return NULL;
		if (($GLOBALS['FORUM_DB']->query_value('f_members','COUNT(*)')<=3) && (get_param('id','')!='ocf_members') && (get_param_integer('search_ocf_members',0)!=1)) return NULL;

		require_lang('ocf');

		$info=array();
		$info['lang']=do_lang_tempcode('MEMBERS');
		$info['default']=false;
		$info['special_on']=array();
		$info['special_off']=array();
		$info['user_label']=do_lang_tempcode('USERNAME');
		$info['days_label']=do_lang_tempcode('JOINED_AGO');

		$extra_sort_fields=array();
		if (has_specific_permission(get_member(),'view_profiles'))
		{
			require_code('ocf_members');
			$rows=ocf_get_all_custom_fields_match(NULL,has_specific_permission(get_member(),'view_any_profile_field')?NULL:1,has_specific_permission(get_member(),'view_any_profile_field')?NULL:1);
			foreach ($rows as $row)
			{
				$extra_sort_fields['field_'.strval($row['id'])]=$row['trans_name'];
			}
		}
		$info['extra_sort_fields']=$extra_sort_fields;

		return $info;
	}

	/**
	 * Get a list of extra fields to ask for.
	 *
	 * @return array			A list of maps specifying extra fields
	 */
	function get_fields()
	{
		require_code('ocf_members');

		$indexes=collapse_2d_complexity('i_fields','i_name',$GLOBALS['FORUM_DB']->query_select('db_meta_indices',array('i_fields','i_name'),array('i_table'=>'f_member_custom_fields')));

		$fields=array();
		if (has_specific_permission(get_member(),'view_profiles'))
		{
			$rows=ocf_get_all_custom_fields_match(NULL,has_specific_permission(get_member(),'view_any_profile_field')?NULL:1,has_specific_permission(get_member(),'view_any_profile_field')?NULL:1);
			require_code('fields');
			foreach ($rows as $row)
			{
				if (!array_key_exists('field_'.strval($row['id']),$indexes)) continue;

				$ob=get_fields_hook($row['cf_type']);
				$temp=$ob->get_search_inputter($row);
				if (is_null($temp))
				{
					$type='_TEXT';
					$special=make_string_tempcode(get_param('option_'.strval($row['id']),''));
					$display=$row['trans_name'];
					$fields[]=array('NAME'=>strval($row['id']),'DISPLAY'=>$display,'TYPE'=>$type,'SPECIAL'=>$special);
				} else $fields=array_merge($fields,$temp);
			}

			$age_range=get_param('option__age_range',get_param('option__age_range_from','').'-'.get_param('option__age_range_to',''));
			$fields[]=array('NAME'=>'_age_range','DISPLAY'=>do_lang_tempcode('AGE_RANGE'),'TYPE'=>'_TEXT','SPECIAL'=>$age_range);
		}

		$map=has_specific_permission(get_member(),'see_hidden_groups')?array():array('g_hidden'=>0);
		$group_count=$GLOBALS['FORUM_DB']->query_value('f_groups','COUNT(*)');
		if ($group_count>300) $map['g_is_private_club']=0;
		if ($map==array()) $map=NULL;
		$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('id','g_name'),$map,'ORDER BY g_order');
		$groups=form_input_list_entry('',true,'---');
		$default_group=get_param('option__user_group','');
		$group_titles=array();
		foreach ($rows as $row)
		{
			$row['text_original']=get_translated_text($row['g_name'],$GLOBALS['FORUM_DB']);

			if ($row['id']==db_get_first_id()) continue;
			$groups->attach(form_input_list_entry(strval($row['id']),strval($row['id'])==$default_group,$row['text_original']));
			$group_titles[$row['id']]=$row['text_original'];
		}
		if (strpos($default_group,',')!==false)
		{
			$bits=explode(',',$default_group);
			$combination=new ocp_tempcode();
			foreach ($bits as $bit)
			{
				if (!$combination->is_empty()) $combination->attach(do_lang_tempcode('LIST_SEP'));
				$combination->attach(escape_html(@$group_titles[intval($bit)]));
			}
			$groups->attach(form_input_list_entry(strval($default_group),true,do_lang_tempcode('USERGROUP_SEARCH_COMBO',escape_html($combination))));
		}
		$fields[]=array('NAME'=>'_user_group','DISPLAY'=>do_lang_tempcode('GROUP'),'TYPE'=>'_LIST','SPECIAL'=>$groups);
		if (has_specific_permission(get_member(),'see_hidden_groups'))
//      $fields[]=array('NAME'=>'_photo_thumb_url','DISPLAY'=>do_lang('PHOTO'),'TYPE'=>'','SPECIAL'=>'','CHECKED'=>false);
		{
			//$fields[]=array('NAME'=>'_emails_only','DISPLAY'=>do_lang_tempcode('EMAILS_ONLY'),'TYPE'=>'_TICK','SPECIAL'=>'');	CSV export better now
		}

		return $fields;
	}

	/**
	 * Standard modular run function for search results.
	 *
	 * @param  string			Search string
	 * @param  boolean		Whether to only do a META (tags) search
	 * @param  ID_TEXT		Order direction
	 * @param  integer		Start position in total results
	 * @param  integer		Maximum results to return in total
	 * @param  boolean		Whether only to search titles (as opposed to both titles and content)
	 * @param  string			Where clause that selects the content according to the main search string (SQL query fragment) (blank: full-text search)
	 * @param  SHORT_TEXT	Username/Author to match for
	 * @param  ?MEMBER		Member-ID to match for (NULL: unknown)
	 * @param  TIME			Cutoff date
	 * @param  string			The sort type (gets remapped to a field in this function)
	 * @set    title add_date
	 * @param  integer		Limit to this number of results
	 * @param  string			What kind of boolean search to do
	 * @set    or and
	 * @param  string			Where constraints known by the main search code (SQL query fragment)
	 * @param  string			Comma-separated list of categories to search under
	 * @param  boolean		Whether it is a boolean search
	 * @return array			List of maps (template, orderer)
	 */
	function run($content,$only_search_meta,$direction,$max,$start,$only_titles,$content_where,$author,$author_id,$cutoff,$sort,$limit_to,$boolean_operator,$where_clause,$search_under,$boolean_search)
	{
		unset($limit_to);

		if (get_forum_type()!='ocf') return array();
		require_code('ocf_members');

		$remapped_orderer='';
		switch ($sort)
		{
			case 'title':
				$remapped_orderer='m_username';
				break;

			case 'add_date':
				$remapped_orderer='m_join_time';
				break;

			case 'relevance':
			case 'rating':
				break;

			default:
				$remapped_orderer=$sort;
				break;
		}

		require_lang('ocf');

		$indexes=collapse_2d_complexity('i_fields','i_name',$GLOBALS['FORUM_DB']->query_select('db_meta_indices',array('i_fields','i_name'),array('i_table'=>'f_member_custom_fields')));

		// Calculate our where clause (search)
		if ($author!='')
		{
			$where_clause.=' AND ';
			$where_clause.=db_string_equal_to('m_username',$author);
		}
		if (!is_null($cutoff))
		{
			$where_clause.=' AND ';
			$where_clause.='m_join_time>'.strval($cutoff);
		}
		$raw_fields=array('m_username');
		$trans_fields=array();
		$rows=ocf_get_all_custom_fields_match(NULL,has_specific_permission(get_member(),'view_any_profile_field')?NULL:1,has_specific_permission(get_member(),'view_any_profile_field')?NULL:1);
		$table='';
		require_code('fields');
		$non_trans_fields=0;
		foreach ($rows as $i=>$row)
		{
			$ob=get_fields_hook($row['cf_type']);
			list(,,$storage_type)=$ob->get_field_value_row_bits($row);
			if (strpos($storage_type,'_trans')===false)
			{
				$non_trans_fields++;
			}
		}
		$index_issue=(get_param_integer('force_like',0)==0) && ($non_trans_fields>16);
		foreach ($rows as $i=>$row)
		{
			if (!array_key_exists('field_'.strval($row['id']),$indexes)) continue;

			$ob=get_fields_hook($row['cf_type']);
			list(,,$storage_type)=$ob->get_field_value_row_bits($row);

			$param=get_param('option_'.strval($row['id']),'');
			if ($param!='')
			{
				$where_clause.=' AND ';

				if ((db_has_full_text($GLOBALS['SITE_DB']->connection_read)) && (method_exists($GLOBALS['SITE_DB']->static_ob,'db_has_full_text_boolean')) && ($GLOBALS['SITE_DB']->static_ob->db_has_full_text_boolean()) && (!is_under_radar($param)))
				{
					$temp=db_full_text_assemble('"'.$param.'"',true);
				} else
				{
					$temp=db_like_assemble($param);
				}
				if (($row['cf_type']=='short_trans') || ($row['cf_type']=='long_trans'))
				{
					$where_clause.=preg_replace('#\?#','t'.strval(count($trans_fields)+1).'.text_original',$temp);
				} else
				{
					if ($index_issue) // MySQL limit for fulltext index querying
					{
						$temp=db_like_assemble($param);
					}
					$where_clause.=preg_replace('#\?#','field_'.strval($row['id']),$temp);
				}
			}
			if (strpos($storage_type,'_trans')===false)
			{
				if (!$index_issue) // MySQL limit for fulltext index querying
					$raw_fields[]='field_'.strval($row['id']);
			} else
			{
				$trans_fields[]='field_'.strval($row['id']);
			}
		}
		$age_range=get_param('option__age_range',get_param('option__age_range_from','').'-'.get_param('option__age_range_to',''));
		if (($age_range!='') && ($age_range!='-'))
		{
			$bits=explode('-',$age_range);
			if (count($bits)==2)
			{
				$lower=strval(intval(date('Y',utctime_to_usertime()))-intval($bits[0]));
				$upper=strval(intval(date('Y',utctime_to_usertime()))-intval($bits[1]));

				$where_clause.=' AND ';
				$where_clause.='(m_dob_year<'.$lower.' OR m_dob_year='.$lower.' AND (m_dob_month<'.date('m').' OR m_dob_month='.date('m').' AND m_dob_day<='.date('d').'))';
				$where_clause.=' AND ';
				$where_clause.='(m_dob_year>'.$upper.' OR m_dob_year='.$upper.' AND (m_dob_month>'.date('m').' OR m_dob_month='.date('m').' AND m_dob_day>='.date('d').'))';
			}
			if (either_param_integer('option__photo_thumb_url',0)==1)
			{
				$where_clause.=' AND ';
				$where_clause.=db_string_not_equal_to('m_photo_thumb_url','');
			}
		}
		$user_group=get_param('option__user_group','');
		if ($user_group!='')
		{
			$bits=explode(',',$user_group);
			$where_clause.=' AND ';
			$group_where_clause='';
			foreach ($bits as $i=>$bit)
			{
				$group=intval($bit);
				$table.=' LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_group_members g'.strval($i).' ON (g'.strval($i).'.gm_group_id='.strval($group).' AND g'.strval($i).'.gm_member_id=r.id)';
				if ($group_where_clause!='') $group_where_clause.=' OR ';
				$group_where_clause.='g'.strval($i).'.gm_validated=1 OR m_primary_group='.strval($group);
			}
			$where_clause.='('.$group_where_clause.')';
		}

		if (!has_specific_permission(get_member(),'see_unvalidated'))
		{
			$where_clause.=' AND ';
			$where_clause.='m_validated=1';
		}

		// Calculate and perform query
		$rows=get_search_rows(NULL,NULL,$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,'f_members r JOIN '.get_table_prefix().'f_member_custom_fields a ON r.id=a.mf_member_id'.$table,array('!','m_signature')+$trans_fields,$where_clause,$content_where,$remapped_orderer,'r.*,a.*,r.id AS id',$raw_fields);

		$out=array();
		foreach ($rows as $i=>$row)
		{
			/*if ($user_group!='')
			{
				$bits=explode(',',$user_group);
				$ok=false;
				$groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($row['id']);
				foreach ($bits as $bit)
				{
					if (in_array($user_group,$groups)) $ok=true;
				}
				if (!$ok) continue;
			}*/

			if (!is_guest($row['id']))
			{
				$out[$i]['data']=$row;
				if (($remapped_orderer!='') && (array_key_exists($remapped_orderer,$row))) $out[$i]['orderer']=$row[$remapped_orderer]; elseif (substr($remapped_orderer,0,7)=='_rating') $out[$i]['orderer']=$row['compound_rating'];
			} else $out[$i]['data']=NULL;
			unset($rows[$i]);
		}

		return $out;
	}

	/**
	 * Standard modular run function for rendering a search result.
	 *
	 * @param  array		The data row stored when we retrieved the result
	 * @return tempcode	The output
	 */
	function render($row)
	{
		require_code('ocf_members');
		if (get_param_integer('option__emails_only',0)==1)
		{
			$link=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['id'],false,$row['m_username']);
			$link2=($row['m_email_address']=='')?new ocp_tempcode():hyperlink('mailto: '.$row['m_email_address'],$row['m_email_address'],false,true);
			return paragraph($link->evaluate().' &lt;'.$link2->evaluate().'&gt;','e3f;l23kf;l320932kl');
		}
		require_code('ocf_members2');
		$GLOBALS['OCF_DRIVER']->MEMBER_ROWS_CACHED[$row['id']]=$row;
		$box=render_member_box($row['id']);
		return $box;
	}

	/**
	 * Standard modular run function for rendering a search result.
	 *
	 * @param  array		The data row stored when we retrieved the result
	 * @return array		The output
	 */
	function render_tabular($row)
	{
		$member_id=$row['id'];
		$preview=true;

		if (get_param_integer('option__emails_only',0)==1)
		{
			$link=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['id'],false,$row['m_username']);
			$link2=($row['m_email_address']=='')?new ocp_tempcode():hyperlink('mailto: '.$row['m_email_address'],$row['m_email_address'],false,true);
			return array(do_lang('USERNAME')=>$link->evaluate(),do_lang('EMAIL_ADDRESS')=>$link2->evaluate());
		}

		if (get_forum_type()!='ocf')
		{
			return array();
		}
		$username=$GLOBALS['OCF_DRIVER']->get_username($member_id);
		if (is_null($username))
		{
			return array();
		}
		require_lang('ocf');
		require_code('ocf_groups');

		$_lines=array();
		$primary_group=ocf_get_member_primary_group($member_id);
		if (is_null($primary_group))
		{
			return array();
		}
		//if (has_specific_permission(get_member(),'view_profiles'))
		{
			if ((get_option('show_gallery_counts')=='1') && (addon_installed('galleries')))
			{
				$num_galleries=$GLOBALS['SITE_DB']->query('SELECT COUNT(*) AS cnt FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'galleries WHERE name LIKE \''.db_encode_like('member_'.strval($member_id).'_%').'\'');
			}
			$_lines+=array(
								do_lang('USERNAME')=>hyperlink($GLOBALS['OCF_DRIVER']->member_profile_url($member_id,false,true),$username,false,true),
								do_lang('JOIN_DATE')=>escape_html(get_timezoned_date($GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_join_time'),false)),
						);
			if ((get_option('show_gallery_counts')=='1') && (addon_installed('galleries')))
			{
				if ($num_galleries[0]['cnt']>1)
				{
					require_lang('galleries');
					$_lines[do_lang('GALLERIES')]=escape_html(integer_format($num_galleries[0]['cnt']));
				}
			}
			$day=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_dob_day');
			$month=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_dob_month');
			$year=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_dob_year');
			if (($GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_reveal_age')==1) && (!is_null($day)))
			{
				if (@strftime('%Y',@mktime(0,0,0,1,1,1963))!='1963') $dob=strval($year).'-'.str_pad(strval($month),2,'0',STR_PAD_LEFT).'-'.str_pad(strval($day),2,'0',STR_PAD_LEFT); else $dob=get_timezoned_date(mktime(12,0,0,$month,$day,$year),false,true);
				$_lines[do_lang('DATE_OF_BIRTH')]=escape_html($dob);
			}
			$fields=ocf_get_all_custom_fields_match_member(
				$member_id,
				((get_member()!=$member_id) && (!has_specific_permission(get_member(),'view_any_profile_field')))?1:NULL, // public view
				((get_member()==$member_id) && (!has_specific_permission(get_member(),'view_any_profile_field')))?1:NULL, // owner view
				NULL, // owner set
				0, // encrypted
				NULL, // required
				$preview?NULL:1, // show in posts
				$preview?1:NULL // show in post previews
			);
			foreach ($fields as $key=>$val)
			{
				if ($val['RAW']!='') $_lines[$key]=$val['RENDERED'];
			}
			if ((!$preview) && (addon_installed('ocf_contactmember')) && (has_actual_page_access(get_member(),'contactmember')))
			{
				$redirect=get_self_url(true);
				$email_member_url=build_url(array('page'=>'contactmember','redirect'=>$redirect,'id'=>$member_id),get_module_zone('contactmember'));

				$_lines[do_lang('ACTIONS')]=hyperlink($email_member_url,do_lang_tempcode('_EMAIL_MEMBER'));
			}
		}

		return $_lines;
	}

}



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
 * @package		news
 */

require_code('aed_module');

/**
 * Module page class.
 */
class Module_cms_blogs extends standard_aed_module
{
	var $lang_type='NEWS_BLOG';
	var $select_name='TITLE';
	var $code_require='news';
	var $permissions_require='mid';
	var $permissions_cat_require='news';
	var $permissions_cat_name='main_news_category';
	var $user_facing=true;
	var $seo_type='news';
	var $award_type='news';
	var $possibly_some_kind_of_upload=true;
	var $upload='image';
	var $menu_label='BLOGS';
	var $table='news';
	var $orderer='title';
	var $title_is_multi_lang=true;
	var $permission_page_name='cms_news';

	var $donext_type=NULL;

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array_merge(array('misc'=>'MANAGE_BLOGS','import_wordpress'=>'IMPORT_WORDPRESS'),parent::get_entry_points());
	}

	/**
	 * Standard aed_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/news';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_news';

		$this->posting_form_title=do_lang_tempcode('BLOG_NEWS_ARTICLE');

		if (is_guest()) access_denied('NOT_AS_GUEST');

		require_css('news');
		require_lang('news');

		// Decide what to do
		if ($type=='misc') return $this->misc();

		if ($type=='import_wordpress') return $this->import_wordpress();

		if ($type=='_import_wordpress') return $this->_import_wordpress();

		return new ocp_tempcode();
	}

	/**
	 * The do-next manager for before content management.
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		require_code('templates_donext');
		return do_next_manager(get_screen_title('MANAGE_BLOGS'),comcode_lang_string('DOC_BLOGS'),
					array(
						/*	 type							  page	 params													 zone	  */
						has_specific_permission(get_member(),'submit_midrange_content','cms_news')?array('add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_NEWS_BLOG')):NULL,
						has_specific_permission(get_member(),'edit_own_midrange_content','cms_news')?array('edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_NEWS_BLOG')):NULL,
						has_specific_permission(get_member(),'mass_import','cms_news')?array('import',array('_SELF',array('type'=>'import_wordpress'),'_SELF'),do_lang('IMPORT_WORDPRESS')):NULL,
					),
					do_lang('MANAGE_BLOGS')
		);
	}

	/**
	 * Standard aed_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return ?array			A quartet: The choose table, Whether re-ordering is supported from this screen, Search URL, Archive URL (NULL: nothing to select).
	 */
	function nice_get_choose_table($url_map)
	{
		require_code('templates_results_table');

		$current_ordering=get_param('sort','date_and_time DESC');
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		$sortables=array(
			'title'=>do_lang_tempcode('TITLE'),
			'date_and_time'=>do_lang_tempcode('ADDED'),
			'news_views'=>do_lang_tempcode('COUNT_VIEWS'),
		);
		if (addon_installed('unvalidated'))
			$sortables['validated']=do_lang_tempcode('VALIDATED');
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='sort';

		$fh=array();
		$fh[]=do_lang_tempcode('TITLE');
		$fh[]=do_lang_tempcode('ADDED');
		$fh[]=do_lang_tempcode('COUNT_VIEWS');
		if (addon_installed('unvalidated'))
			$fh[]=do_lang_tempcode('VALIDATED');
		$fh[]=do_lang_tempcode('ACTIONS');
		$header_row=results_field_title($fh,$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		require_code('form_templates');
		$only_owned=has_specific_permission(get_member(),'edit_midrange_content','cms_news')?NULL:get_member();
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering,is_null($only_owned)?NULL:array('submitter'=>$only_owned),false,' JOIN '.get_table_prefix().'news_categories c ON c.id=r.news_category AND nc_owner IS NOT NULL');
		if (count($rows)==0) return NULL;
		foreach ($rows as $row)
		{
			$edit_link=build_url($url_map+array('id'=>$row['id']),'_SELF');

			$fr=array();
			$fr[]=protect_from_escaping(hyperlink(build_url(array('page'=>'news','type'=>'view','id'=>$row['id']),get_module_zone('news')),get_translated_text($row['title'])));
			$fr[]=get_timezoned_date($row['date_and_time']);
			$fr[]=integer_format($row['news_views']);
			if (addon_installed('unvalidated'))
				$fr[]=($row['validated']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO');
			$fr[]=protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.strval($row['id'])));

			$fields->attach(results_entry($fr,true));
		}

		$search_url=build_url(array('page'=>'search','id'=>'news'),get_module_zone('search'));
		$archive_url=build_url(array('page'=>'news'),get_module_zone('news'));

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',either_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order),false,$search_url,$archive_url);
	}

	/**
	 * Standard aed_module list function.
	 *
	 * @return tempcode		The selection list
	 */
	function nice_get_entries()
	{
		$only_owned=has_specific_permission(get_member(),'edit_midrange_content','cms_news')?NULL:get_member();
		return nice_get_news(NULL,$only_owned,false,true);
	}

	/**
	 * Get tempcode for a news adding/editing form.
	 *
	 * @param  ?AUTO_LINK		The primary category for the news (NULL: personal)
	 * @param  ?array				A list of categories the news is in (NULL: not known)
	 * @param  SHORT_TEXT		The news title
	 * @param  LONG_TEXT			The news summary
	 * @param  SHORT_TEXT		The name of the author
	 * @param  BINARY				Whether the news is validated
	 * @param  ?BINARY			Whether rating is allowed (NULL: decide statistically, based on existing choices)
	 * @param  ?SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style) (NULL: decide statistically, based on existing choices)
	 * @param  ?BINARY			Whether trackbacks are allowed (NULL: decide statistically, based on existing choices)
	 * @param  BINARY				Whether to show the "send trackback" field
	 * @param  LONG_TEXT			Notes for the video
	 * @param  URLPATH			URL to the image for the news entry (blank: use cat image)
	 * @param  ?array				Scheduled go-live time (NULL: N/A)
	 * @return array				A tuple of lots of info (fields, hidden fields, trailing fields)
	 */
	function get_form_fields($main_news_category=NULL,$news_category=NULL,$title='',$news='',$author='',$validated=1,$allow_rating=NULL,$allow_comments=NULL,$allow_trackbacks=NULL,$send_trackbacks=1,$notes='',$image='',$scheduled=NULL)
	{
		list($allow_rating,$allow_comments,$allow_trackbacks)=$this->choose_feedback_fields_statistically($allow_rating,$allow_comments,$allow_trackbacks);

		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='validated';

		if ($title=='')
		{
			$title=get_param('title',$title);
			$author=get_param('author',$author);
			$notes=get_param('notes',$notes);

			if (is_null($main_news_category))
			{
				global $NON_CANONICAL_PARAMS;
				$NON_CANONICAL_PARAMS[]='cat';

				$param_cat=get_param('cat','');
				if ($param_cat=='')
				{
					$main_news_category=NULL;
					$news_category=array();
				} elseif (strpos($param_cat,',')===false)
				{
					$main_news_category=intval($param_cat);
					$news_category=array();
				} else
				{
					require_code('ocfiltering');
					$_param_cat=explode(',',$param_cat);
					$_main_news_category=array_shift($_param_cat);
					$param_cat=implode(',',$_param_cat);
					$main_news_category=($_main_news_category=='')?NULL:intval($_main_news_category);
					$news_category=ocfilter_to_idlist_using_db($param_cat,'id','news_categories','news_categories',NULL,'id','id');
				}

				$author=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
			}
		}

		$cats1=nice_get_news_categories($main_news_category,false,true,false,true);
		$cats2=nice_get_news_categories((is_null($news_category) || (count($news_category)==0))?array(get_param_integer('cat',NULL)):$news_category,false,true,true,false);

		$fields=new ocp_tempcode();
		$fields2=new ocp_tempcode();
		$hidden=new ocp_tempcode();
		require_code('form_templates');
		$fields->attach(form_input_line_comcode(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_TITLE'),'title',$title,true));
		if ($validated==0)
		{
			$validated=get_param_integer('validated',0);
			if ($validated==1) attach_message(do_lang_tempcode('WILL_BE_VALIDATED_WHEN_SAVING'));
		}
		if (has_some_cat_specific_permission(get_member(),'bypass_validation_'.$this->permissions_require.'range_content','cms_news',$this->permissions_cat_require))
			if (addon_installed('unvalidated'))
				$fields2->attach(form_input_tick(do_lang_tempcode('VALIDATED'),do_lang_tempcode('DESCRIPTION_VALIDATED'),'validated',$validated==1));
		if ($cats1->is_empty()) warn_exit(do_lang_tempcode('NO_CATEGORIES'));
		if (addon_installed('authors'))
		{
			$hidden->attach(form_input_hidden('author',$author));
		}
		$fields2->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>$image=='' && ($title==''/*=new entry and selected news cats was from URL*/ || is_null($news_category) || $news_category==array()),'TITLE'=>do_lang_tempcode('ADVANCED'))));
		$fields2->attach(form_input_text_comcode(do_lang_tempcode('BLOG_NEWS_SUMMARY'),do_lang_tempcode('DESCRIPTION_NEWS_SUMMARY'),'news',$news,false));
		if (get_value('disable_secondary_news')!=='1')
		{
			$fields2->attach(form_input_list(do_lang_tempcode('MAIN_CATEGORY'),do_lang_tempcode('DESCRIPTION_MAIN_CATEGORY'),'main_news_category',$cats1));
		} else
		{
			$fields2->attach(form_input_hidden('main_news_category',is_null($main_news_category)?'personal':strval($main_news_category)));
		}
		if (get_value('disable_secondary_news')!=='1')
			$fields2->attach(form_input_multi_list(do_lang_tempcode('SECONDARY_CATEGORIES'),do_lang_tempcode('DESCRIPTION_SECONDARY_CATEGORIES'),'news_category',$cats2));
		$fields2->attach(form_input_upload(do_lang_tempcode('IMAGE'),do_lang_tempcode('DESCRIPTION_NEWS_IMAGE_OVERRIDE'),'file',false,$image,NULL,true,str_replace(' ','',get_option('valid_images'))));
		//handle_max_file_size($hidden,'image'); Attachments will add this
		if ((addon_installed('calendar')) && (has_specific_permission(get_member(),'scheduled_publication_times')))
			$fields2->attach(form_input_date__scheduler(do_lang_tempcode('PUBLICATION_TIME'),do_lang_tempcode('DESCRIPTION_PUBLICATION_TIME'),'schedule',true,true,true,$scheduled,intval(date('Y'))-1970+2,1970));

		require_code('feedback2');
		$fields2->attach(feedback_fields($allow_rating==1,$allow_comments==1,$allow_trackbacks==1,$send_trackbacks==1,$notes,$allow_comments==2));

		$fields2->attach(get_syndication_option_fields());

		return array($fields,$hidden,NULL,NULL,NULL,NULL,make_string_tempcode($fields2->evaluate())/*XHTMLXHTML*/);
	}

	/**
	 * Standard aed_module submitter getter.
	 *
	 * @param  ID_TEXT		The entry for which the submitter is sought
	 * @return array			The submitter, and the time of submission (null submission time implies no known submission time)
	 */
	function get_submitter($id)
	{
		$rows=$GLOBALS['SITE_DB']->query_select('news',array('submitter','date_and_time'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$rows)) return array(NULL,NULL);
		return array($rows[0]['submitter'],$rows[0]['date_and_time']);
	}

	/**
	 * Standard aed_module cat getter.
	 *
	 * @param  AUTO_LINK		The entry for which the cat is sought
	 * @return string			The cat
	 */
	function get_cat($id)
	{
		$temp=$GLOBALS['SITE_DB']->query_value_null_ok('news','news_category',array('id'=>$id));
		if (is_null($temp)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		return strval($temp);
	}

	/**
	 * Standard aed_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return array			A tuple of lots of info
	 */
	function fill_in_edit_form($_id)
	{
		$id=intval($_id);

		require_lang('menus');
		require_lang('zones');
		$GLOBALS['HELPER_PANEL_TEXT']=comcode_lang_string('DOC_WRITING');
		$GLOBALS['HELPER_PANEL_PIC']='';

		$rows=$GLOBALS['SITE_DB']->query_select('news',array('*'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$rows))
		{
			warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		}
		$myrow=$rows[0];

		$cat=$myrow['news_category'];

		$categories=array();
		$category_query=$GLOBALS['SITE_DB']->query_select('news_category_entries',array('news_entry_category'),array('news_entry'=>$id));

		foreach ($category_query as $value) $categories[]=$value['news_entry_category'];

		$scheduled=mixed();

		if (addon_installed('calendar'))
		{
			$schedule_code=':$GLOBALS[\'SITE_DB\']->query_update(\'news\',array(\'date_and_time\'=>$GLOBALS[\'event_timestamp\'],\'validated\'=>1),array(\'id\'=>'.strval($id).'),\'\',1);';
			$past_event=$GLOBALS['SITE_DB']->query_select('calendar_events e LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON e.e_content=t.id',array('e_start_day','e_start_month','e_start_year','e_start_hour','e_start_minute'),array('text_original'=>$schedule_code),'',1);
			$scheduled=array_key_exists(0,$past_event)?array($past_event[0]['e_start_minute'],$past_event[0]['e_start_hour'],$past_event[0]['e_start_month'],$past_event[0]['e_start_day'],$past_event[0]['e_start_year']):NULL;
			if ((!is_null($scheduled)) && ($scheduled<time())) $scheduled=NULL;
		} else
		{
			$scheduled=NULL;
		}

		list($fields,$hidden,,,,,$fields2)=$this->get_form_fields($cat,$categories,get_translated_text($myrow['title']),get_translated_text($myrow['news']),$myrow['author'],$myrow['validated'],$myrow['allow_rating'],$myrow['allow_comments'],$myrow['allow_trackbacks'],0,$myrow['notes'],$myrow['news_image'],$scheduled);

		return array($fields,$hidden,new ocp_tempcode(),'',false,get_translated_text($myrow['news_article']),$fields2,get_translated_tempcode($myrow,'news_article'));
	}

	/**
	 * Standard aed_module add actualiser.
	 *
	 * @return ID_TEXT		The ID of the entry added
	 */
	function add_actualisation()
	{
		$author=post_param('author',$GLOBALS['FORUM_DRIVER']->get_username(get_member()));
		$news=post_param('news');
		$title=post_param('title');
		$validated=post_param_integer('validated',0);
		$news_article=post_param('post');
		if (post_param('main_news_category')!='personal') $main_news_category=post_param_integer('main_news_category');
		else $main_news_category=NULL;

		$news_category=array();
		if (array_key_exists('news_category',$_POST))
		{
			foreach ($_POST['news_category'] as $val)
			{
				$news_category[]=($val=='personal')?NULL:intval($val);
			}
		}

		$allow_rating=post_param_integer('allow_rating',0);
		$allow_comments=post_param_integer('allow_comments',0);
		$allow_trackbacks=post_param_integer('allow_trackbacks',0);
		require_code('feedback2');
		send_trackbacks(post_param('send_trackbacks',''),$title,$news);
		$notes=post_param('notes','');

		$urls=get_url('','file','uploads/grepimages',0,OCP_UPLOAD_IMAGE);
		$url=$urls[0];
		if (($url!='') && (function_exists('imagecreatefromstring')) && (get_value('resize_rep_images')!=='0'))
			convert_image(get_custom_base_url().'/'.$url,get_custom_file_base().'/uploads/grepimages/'.basename(rawurldecode($url)),-1,-1,intval(get_option('thumb_width')),true,NULL,false,true);

		$schedule=get_input_date('schedule');
		$add_time=is_null($schedule)?time():$schedule;
		if ((addon_installed('calendar')) && (has_specific_permission(get_member(),'scheduled_publication_times')) && (!is_null($schedule)) && ($schedule>time()))
		{
			$validated=0;
		} else $schedule=NULL;

		if (!is_null($main_news_category))
		{
			$owner=$GLOBALS['SITE_DB']->query_value('news_categories','nc_owner',array('id'=>intval($main_news_category)));
			if ((!is_null($owner)) && ($owner!=get_member())) check_specific_permission('can_submit_to_others_categories',array('news',$main_news_category),NULL,'cms_news');
		}

		$time=$add_time;
		$id=add_news($title,$news,$author,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$news_article,$main_news_category,$news_category,$time,NULL,0,NULL,NULL,$url);

		$main_news_category=$GLOBALS['SITE_DB']->query_value('news','news_category',array('id'=>$id));
		$this->donext_type=$main_news_category;

		if (($validated==1) || (!addon_installed('unvalidated')))
		{
			$is_blog=true;

			if (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'news'))
				syndicate_described_activity($is_blog?'news:ACTIVITY_ADD_NEWS_BLOG':'news:ACTIVITY_ADD_NEWS',$title,'','','_SEARCH:news:view:'.strval($id),'','','news',1,NULL,true);
		}

		if (!is_null($schedule))
		{
			require_code('calendar');
			$schedule_code=':$GLOBALS[\'SITE_DB\']->query_update(\'news\',array(\'date_and_time\'=>$GLOBALS[\'event_timestamp\'],\'validated\'=>1),array(\'id\'=>'.strval($id).'),\'\',1);';
			$start_year=intval(date('Y',$schedule));
			$start_month=intval(date('m',$schedule));
			$start_day=intval(date('d',$schedule));
			$start_hour=intval(date('H',$schedule));
			$start_minute=intval(date('i',$schedule));
			require_code('calendar2');
			$event_id=add_calendar_event(db_get_first_id(),'',NULL,0,do_lang('PUBLISH_NEWS',$title),$schedule_code,3,0,$start_year,$start_month,$start_day,'day_of_month',$start_hour,$start_minute);
			regenerate_event_reminder_jobs($event_id,true);
		}

		return strval($id);
	}

	/**
	 * Standard aed_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 */
	function edit_actualisation($_id)
	{
		$id=intval($_id);

		$validated=post_param_integer('validated',fractional_edit()?INTEGER_MAGIC_NULL:0);

		$news_article=post_param('post',STRING_MAGIC_NULL);
		if (post_param('main_news_category')!='personal') $main_news_category=post_param_integer('main_news_category',INTEGER_MAGIC_NULL);
		else warn_exit(do_lang_tempcode('INTERNAL_ERROR'));

		$news_category=array();
		if (array_key_exists('news_category',$_POST))
		{
			foreach ($_POST['news_category'] as $val)
			{
				$news_category[]=intval($val);
			}
		}

		$allow_rating=post_param_integer('allow_rating',fractional_edit()?INTEGER_MAGIC_NULL:0);
		$allow_comments=post_param_integer('allow_comments',fractional_edit()?INTEGER_MAGIC_NULL:0);
		$allow_trackbacks=post_param_integer('allow_trackbacks',fractional_edit()?INTEGER_MAGIC_NULL:0);
		$notes=post_param('notes',STRING_MAGIC_NULL);

		$this->donext_type=$main_news_category;

		if (!fractional_edit())
		{
			$urls=get_url('','file','uploads/grepimages',0,OCP_UPLOAD_IMAGE);
			$url=$urls[0];
			if (($url!='') && (function_exists('imagecreatefromstring')) && (get_value('resize_rep_images')!=='0'))
				convert_image(get_custom_base_url().'/'.$url,get_custom_file_base().'/uploads/grepimages/'.basename(rawurldecode($url)),-1,-1,intval(get_option('thumb_width')),true,NULL,false,true);
			if (($url=='') && (post_param_integer('file_unlink',0)!=1)) $url=NULL;
		} else
		{
			$url=STRING_MAGIC_NULL;
		}

		$owner=$GLOBALS['SITE_DB']->query_value_null_ok('news_categories','nc_owner',array('id'=>$main_news_category)); // null_ok in case somehow category setting corrupted
		if ((!is_null($owner)) && ($owner!=get_member())) check_specific_permission('can_submit_to_others_categories',array('news',$main_news_category),NULL,'cms_news');

		$schedule=get_input_date('schedule');
		$add_time=is_null($schedule)?mixed():$schedule;

		if ((addon_installed('calendar')) && (has_specific_permission(get_member(),'scheduled_publication_times')))
		{
			require_code('calendar2');
			$schedule_code=':$GLOBALS[\'SITE_DB\']->query_update(\'news\',array(\'date_and_time\'=>$GLOBALS[\'event_timestamp\'],\'validated\'=>1),array(\'id\'=>'.strval($id).'),\'\',1);';
			$past_event=$GLOBALS['SITE_DB']->query_value_null_ok('calendar_events e LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON e.e_content=t.id','e.id',array('text_original'=>$schedule_code));
			require_code('calendar');
			if (!is_null($past_event))
			{
				delete_calendar_event($past_event);
			}

			if ((!is_null($schedule)) && ($schedule>time()))
			{
				$validated=0;

				$start_year=intval(date('Y',$schedule));
				$start_month=intval(date('m',$schedule));
				$start_day=intval(date('d',$schedule));
				$start_hour=intval(date('H',$schedule));
				$start_minute=intval(date('i',$schedule));
				$event_id=add_calendar_event(db_get_first_id(),'none',NULL,0,do_lang('PUBLISH_NEWS',0,post_param('title')),$schedule_code,3,0,$start_year,$start_month,$start_day,'day_of_month',$start_hour,$start_minute);
				regenerate_event_reminder_jobs($event_id,true);
			}
		}

		$title=post_param('title',STRING_MAGIC_NULL);

		if (($validated==1) && ($main_news_category!=INTEGER_MAGIC_NULL) && ($GLOBALS['SITE_DB']->query_value('news','validated',array('id'=>intval($id)))==0)) // Just became validated, syndicate as just added
		{
			$is_blog=true;

			$submitter=$GLOBALS['SITE_DB']->query_value('news','submitter',array('id'=>$id));
			$activity_title=($is_blog?'news:ACTIVITY_ADD_NEWS_BLOG':'news:ACTIVITY_ADD_NEWS');
			$activity_title_validate=($is_blog?'news:ACTIVITY_VALIDATE_NEWS_BLOG':'news:ACTIVITY_VALIDATE_NEWS');

			if (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'news')) // NB: no category permission check, as syndication choice was explicit, and news categorisation is a bit more complex
				syndicate_described_activity(($submitter!=get_member())?$activity_title_validate:$activity_title,$title,'','','_SEARCH:news:view:'.strval($id),'','','news',1,NULL/*$submitter*/,true);
		}

		edit_news(intval($id),$title,post_param('news',STRING_MAGIC_NULL),post_param('author',STRING_MAGIC_NULL),$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$news_article,$main_news_category,$news_category,post_param('meta_keywords',STRING_MAGIC_NULL),post_param('meta_description',STRING_MAGIC_NULL),$url,$add_time);
	}

	/**
	 * Standard aed_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($_id)
	{
		$id=intval($_id);

		delete_news($id);
	}

	/**
	 * The do-next manager for after news content management.
	 *
	 * @param  tempcode		The title (output of get_screen_title)
	 * @param  tempcode		Some description to show, saying what happened
	 * @param  ?AUTO_LINK	The ID of whatever was just handled (NULL: N/A)
	 * @return tempcode		The UI
	 */
	function do_next_manager($title,$description,$id=NULL)
	{
		breadcrumb_set_self(do_lang_tempcode('DONE'));

		$cat=$this->donext_type;

		require_code('templates_donext');

		return do_next_manager($title,$description,
					NULL,
					NULL,
					/*		TYPED-ORDERED LIST OF 'LINKS'		*/
					/*	 page	 params				  zone	  */
					array('_SELF',array('type'=>'ad','cat'=>$cat),'_SELF'),							// Add one
					(is_null($id) || (!has_specific_permission(get_member(),'edit_own_midrange_content','cms_news',array('news',$cat))))?NULL:array('_SELF',array('type'=>'_ed','id'=>$id),'_SELF'),							 // Edit this
					has_specific_permission(get_member(),'edit_own_midrange_content','cms_news')?array('_SELF',array('type'=>'ed'),'_SELF'):NULL,											// Edit one
					is_null($id)?NULL:array('news',array('type'=>'view','id'=>$id,'blog'=>1),get_module_zone('news')),							// View this
					array('news',array('type'=>'misc','blog'=>1),get_module_zone('news')),									 // View archive
					NULL,	  // Add to category
					has_specific_permission(get_member(),'submit_cat_midrange_content','cms_news')?array('cms_news',array('type'=>'ac'),'_SELF'):NULL,					  // Add one category
					has_specific_permission(get_member(),'edit_own_cat_midrange_content','cms_news')?array('cms_news',array('type'=>'ec'),'_SELF'):NULL,					  // Edit one category
					is_null($cat)?NULL:has_specific_permission(get_member(),'edit_own_cat_midrange_content','cms_news')?array('cms_news',array('type'=>'_ec','id'=>$cat),'_SELF'):NULL,			 // Edit this category
					NULL																						 // View this category
		);
	}

	/**
	 * The UI to import news
	 *
	 * @return tempcode		The UI
	 */
	function import_wordpress()
	{
		check_specific_permission('mass_import',NULL,NULL,'cms_news');

		$lang=post_param('lang',user_lang());
		$title=get_screen_title('IMPORT_WORDPRESS');
		$submit_name=do_lang_tempcode('IMPORT_WORDPRESS');

		require_code('form_templates');

		/* RSS method */

		require_code('news2');
		$fields=import_rss_fields(true);

		$hidden=form_input_hidden('lang',$lang);

		$xml_post_url=build_url(array('page'=>'_SELF','type'=>'_import_wordpress','method'=>'xml'),'_SELF');

		$xml_upload_form=do_template('FORM',array('TABINDEX'=>strval(get_form_field_tabindex()),'TEXT'=>'','HIDDEN'=>$hidden,'FIELDS'=>$fields,'SUBMIT_NAME'=>$submit_name,'URL'=>$xml_post_url));

		/* Database method */

		$fields=new ocp_tempcode();

		$fields->attach(form_input_line(do_lang_tempcode('WORDPRESS_HOST_NAME'),do_lang_tempcode('DESCRIPTION_WORDPRESS_HOST_NAME'),'wp_host','localhost',false));
		$fields->attach(form_input_line(do_lang_tempcode('WORDPRESS_DB_NAME'),do_lang_tempcode('DESCRIPTION_WORDPRESS_DB_NAME'),'wp_db','wordpress',false));
		$fields->attach(form_input_line(do_lang_tempcode('WORDPRESS_TABLE_PREFIX'),do_lang_tempcode('DESCRIPTION_WORDPRESS_TABLE_PREFIX'),'wp_table_prefix','wp',false));
		$fields->attach(form_input_line(do_lang_tempcode('WORDPRESS_DB_USERNAME'),do_lang_tempcode('DESCRIPTION_WORDPRESS_DB_USERNAME'),'wp_db_user','root',false));
		$fields->attach(form_input_password(do_lang_tempcode('WORDPRESS_DB_PASSWORD'),do_lang_tempcode('DESCRIPTION_WORDPRESS_DB_PASSWORD'),'wp_db_password',false));

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>false,'TITLE'=>do_lang_tempcode('ADVANCED'))));

		$fields->attach(form_input_tick(do_lang_tempcode('IMPORT_WORDPRESS_USERS'),do_lang_tempcode('DESCRIPTION_IMPORT_WORDPRESS_USER'),'wp_import_wordpress_users',true));
		$fields->attach(form_input_tick(do_lang_tempcode('IMPORT_BLOG_COMMENTS'),do_lang_tempcode('DESCRIPTION_IMPORT_BLOG_COMMENTS'),'wp_import_blog_comments',true));
		if (addon_installed('unvalidated'))
			$fields->attach(form_input_tick(do_lang_tempcode('AUTO_VALIDATE_ALL_POSTS'),do_lang_tempcode('DESCRIPTION_VALIDATE_ALL_POSTS'),'wp_auto_validate',true));
		if ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()))
			$fields->attach(form_input_tick(do_lang_tempcode('ADD_TO_OWN_ACCOUNT'),do_lang_tempcode('DESCRIPTION_ADD_TO_OWN_ACCOUNT'),'wp_to_own_account',false));
		$fields->attach(form_input_tick(do_lang_tempcode('IMPORT_TO_BLOG'),do_lang_tempcode('DESCRIPTION_IMPORT_TO_BLOG'),'wp_import_to_blog',true));
		if (has_specific_permission(get_member(),'draw_to_server'))
			$fields->attach(form_input_tick(do_lang_tempcode('DOWNLOAD_IMAGES'),do_lang_tempcode('DESCRIPTION_DOWNLOAD_IMAGES'),'wp_download_images',true));

		$hidden=new ocp_tempcode();
		$hidden->attach(form_input_hidden('lang',$lang));
		handle_max_file_size($hidden);

		$javascript='';

		$db_post_url=build_url(array('page'=>'_SELF','type'=>'_import_wordpress','method'=>'db'),'_SELF');

		$db_import_form=do_template('FORM',array('TABINDEX'=>strval(get_form_field_tabindex()),'TEXT'=>'','HIDDEN'=>$hidden,'FIELDS'=>$fields,'SUBMIT_NAME'=>$submit_name,'URL'=>$db_post_url,'JAVASCRIPT'=>$javascript));

		/* Render */

		return do_template('NEWS_WORDPRESS_IMPORT_SCREEN',array('TITLE'=>get_screen_title('IMPORT_WORDPRESS'),'XML_UPLOAD_FORM'=>$xml_upload_form,'DB_IMPORT_FORM'=>$db_import_form));
	}

	/**
	 * The actualiser to import a wordpress blog
	 *
	 * @return tempcode		The UI
	 */
	function _import_wordpress()
	{
		check_specific_permission('mass_import',NULL,NULL,'cms_news');

		$title=get_screen_title('IMPORT_WORDPRESS');

		require_code('news2');

		// Wordpress posts, XML file importing method
		if ((get_param('method')=='xml'))
		{
			import_rss();
		}
		elseif (get_param('method')=='db')	// Importing directly from wordpress DB
		{
			import_wordpress_db();
		}

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('MANAGE_BLOGS')),array('_SELF:_SELF:import_wordpress',do_lang_tempcode('IMPORT_WORDPRESS'))));
		breadcrumb_set_self(do_lang_tempcode('DONE'));

		return inform_screen($title,do_lang_tempcode('IMPORT_WORDPRESS_DONE'));
	}

}

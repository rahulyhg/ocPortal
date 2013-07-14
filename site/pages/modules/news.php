<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

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

/**
 * Module page class.
 */
class Module_news
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
		$info['version']=6;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('news');
		$GLOBALS['SITE_DB']->drop_table_if_exists('news_categories');
		$GLOBALS['SITE_DB']->drop_table_if_exists('news_rss_cloud');
		$GLOBALS['SITE_DB']->drop_table_if_exists('news_category_entries');

		$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'news'));

		$GLOBALS['SITE_DB']->query_delete('trackbacks',array('trackback_for_type'=>'news'));
		$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_type'=>'news'));

		delete_attachments('news');

		delete_menu_item_simple('_SEARCH:cms_news:type=ad');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('news',array(
				'id'=>'*AUTO',
				'date_and_time'=>'TIME',
				'title'=>'SHORT_TRANS',	// Comcode
				'news'=>'LONG_TRANS',	// Comcode
				'news_article'=>'LONG_TRANS',	// Comcode
				'allow_rating'=>'BINARY',
				'allow_comments'=>'SHORT_INTEGER',
				'allow_trackbacks'=>'BINARY',
				'notes'=>'LONG_TEXT',
				'author'=>'ID_TEXT',
				'submitter'=>'MEMBER',
				'validated'=>'BINARY',
				'edit_date'=>'?TIME',
				'news_category'=>'AUTO_LINK',
				'news_views'=>'INTEGER',
				'news_image'=>'URLPATH'
			));
			$GLOBALS['SITE_DB']->create_index('news','news_views',array('news_views'));
			$GLOBALS['SITE_DB']->create_index('news','findnewscat',array('news_category'));
			$GLOBALS['SITE_DB']->create_index('news','newsauthor',array('author'));
			$GLOBALS['SITE_DB']->create_index('news','nes',array('submitter'));
			$GLOBALS['SITE_DB']->create_index('news','headlines',array('date_and_time','id'));
			$GLOBALS['SITE_DB']->create_index('news','nvalidated',array('validated'));

			$GLOBALS['SITE_DB']->create_table('news_categories',array(
				'id'=>'*AUTO',
				'nc_title'=>'SHORT_TRANS',
				'nc_owner'=>'?MEMBER',
				'nc_img'=>'ID_TEXT',
				'notes'=>'LONG_TEXT'
			));
			$GLOBALS['SITE_DB']->create_index('news_categories','ncs',array('nc_owner'));

			$default_categories=array('general','technology','difficulties','community','entertainment','business','art');
			require_lang('news');
			foreach ($default_categories as $category)
			{
				$GLOBALS['SITE_DB']->query_insert('news_categories',array('notes'=>'','nc_img'=>'newscats/'.$category,'nc_owner'=>NULL,'nc_title'=>lang_code_to_default_content('NC_'.$category)));
			}

			$GLOBALS['SITE_DB']->create_table('news_rss_cloud',array(
				'id'=>'*AUTO',
				'rem_procedure'=>'ID_TEXT',
				'rem_port'=>'INTEGER',
				'rem_path'=>'SHORT_TEXT',
				'rem_protocol'=>'ID_TEXT',
				'rem_ip'=>'IP',
				'watching_channel'=>'URLPATH',
				'register_time'=>'TIME'
			));

			$GLOBALS['SITE_DB']->create_table('news_category_entries',array(
				'news_entry'=>'*AUTO_LINK',
				'news_entry_category'=>'*AUTO_LINK'
			));
			$GLOBALS['SITE_DB']->create_index('news_category_entries','news_entry_category',array('news_entry_category'));

			$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
			$categories=$GLOBALS['SITE_DB']->query_select('news_categories',array('id'));
			foreach ($categories as $_id)
			{
				foreach (array_keys($groups) as $group_id)
					$GLOBALS['SITE_DB']->query_insert('group_category_access',array('module_the_name'=>'news','category_name'=>strval($_id['id']),'group_id'=>$group_id));
			}

			$GLOBALS['SITE_DB']->create_index('news','ftjoin_ititle',array('title'));
			$GLOBALS['SITE_DB']->create_index('news','ftjoin_nnews',array('news'));
			$GLOBALS['SITE_DB']->create_index('news','ftjoin_nnewsa',array('news_article'));
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'NEWS_ARCHIVE','cat_select'=>'NEWS_CATEGORIES','blog_select'=>'BLOGS','select'=>'JUST_NEWS_CATEGORIES');
	}

	/**
	 * Standard modular page-link finder function (does not return the main entry-points that are not inside the tree).
	 *
	 * @param  ?integer  The number of tree levels to computer (NULL: no limit)
	 * @param  boolean	Whether to not return stuff that does not support permissions (unless it is underneath something that does).
	 * @param  ?string	Position to start at in the tree. Does not need to be respected. (NULL: from root)
	 * @param  boolean	Whether to avoid returning categories.
	 * @return ?array	 	A tuple: 1) full tree structure [made up of (pagelink, permission-module, permissions-id, title, children, ?entry point for the children, ?children permission module, ?whether there are children) OR a list of maps from a get_* function] 2) permissions-page 3) optional base entry-point for the tree 4) optional permission-module 5) optional permissions-id (NULL: disabled).
	 */
	function get_page_links($max_depth=NULL,$require_permission_support=false,$start_at=NULL,$dont_care_about_categories=false)
	{
		$permission_page='cms_news';
		$tree=array();
		$rows=$dont_care_about_categories?array():$GLOBALS['SITE_DB']->query_select('news_categories c LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND c.nc_title=t.id',array('c.nc_title','c.id','text_original'),array('nc_owner'=>NULL),'ORDER BY text_original ASC');
		if (($max_depth>0) || (is_null($max_depth)))
		{
			foreach ($rows as $row)
			{
				if (is_null($row['text_original'])) $row['text_original']=get_translated_text($row['nc_title']);

				$page_link_under='_SELF:_SELF:type=misc:id='.strval($row['id']);
				if (!is_null($start_at))
				{
					if (strpos($start_at,':blog=0')!==false) $page_link_under.=':blog=0';
					if (strpos($start_at,':blog=1')!==false) $page_link_under.=':blog=1';
				}
				$tree[]=array($page_link_under,'news',$row['id'],$row['text_original'],array());
			}
		}
		return array($tree,$permission_page);
	}

	/**
	 * Standard modular new-style deep page-link finder function (does not return the main entry-points).
	 *
	 * @param  string  	Callback function to send discovered page-links to.
	 * @param  MEMBER		The member we are finding stuff for (we only find what the member can view).
	 * @param  integer	Code for how deep we are tunnelling down, in terms of whether we are getting entries as well as categories.
	 * @param  string		Stub used to create page-links. This is passed in because we don't want to assume a zone or page name within this function.
	 * @param  ?string	Where we're looking under (NULL: root of tree). We typically will NOT show a root node as there's often already an entry-point representing it.
	 * @param  integer	Our recursion depth (used to calculate importance of page-link, used for instance by Google sitemap). Deeper is typically less important.
	 * @param  ?array		Non-standard for API [extra parameter tacked on] (NULL: yet unknown). Contents of database table for performance.
	 * @param  ?array		Non-standard for API [extra parameter tacked on] (NULL: yet unknown). Contents of database table for performance.
	 */
	function get_sitemap_pagelinks($callback,$member_id,$depth,$pagelink_stub,$parent_pagelink=NULL,$recurse_level=0,$category_data=NULL,$entry_data=NULL)
	{
		// This is where we start
		if (is_null($parent_pagelink))
		{
			$parent_pagelink=$pagelink_stub.':misc'; // This is the entry-point we're under

			// Subcategories
			$start=0;
			do
			{
				$category_data=$GLOBALS['SITE_DB']->query_select('news_categories c LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=c.nc_title',array('c.nc_title','c.id','t.text_original AS title'),NULL,'',300,$start);
				foreach ($category_data as $row)
				{
					if (is_null($row['title'])) $row['title']=get_translated_text($row['nc_title']);

					$pagelink=$pagelink_stub.'misc:'.strval($row['id']);
					if (__CLASS__!='')
					{
						$this->get_sitemap_pagelinks($callback,$member_id,$depth,$pagelink_stub,$pagelink,$recurse_level+1,$category_data,$entry_data); // Recurse
					} else
					{
						call_user_func_array(__FUNCTION__,array($callback,$member_id,$depth,$pagelink_stub,$pagelink,$recurse_level+1,$category_data,$entry_data)); // Recurse
					}
					if (has_category_access($member_id,'news',strval($row['id'])))
					{
						call_user_func_array($callback,array($pagelink,$parent_pagelink,NULL,NULL,max(0.7-$recurse_level*0.1,0.3),$row['title'])); // Callback
					} else // Not accessible: we need to copy the node through, but we will flag it 'Unknown' and say it's not accessible.
					{
						call_user_func_array($callback,array($pagelink,$parent_pagelink,NULL,NULL,max(0.7-$recurse_level*0.1,0.3),do_lang('UNKNOWN'),false)); // Callback
					}
				}
				$start+=300;
			}
			while (array_key_exists(0,$category_data));
		} else
		{
			list(,$parent_attributes,)=page_link_decode($parent_pagelink);

			// Entries
			if (($depth>=DEPTH__ENTRIES) && (has_category_access($member_id,'news',$parent_attributes['id'])))
			{
				$start=0;
				do
				{
					$privacy_join='';
					$privacy_where='';
					if (addon_installed('content_privacy'))
					{
						require_code('content_privacy');
						list($privacy_join,$privacy_where)=get_privacy_where_clause('news','d');
					}
					$where='1=1'.$privacy_where;
					$entry_data=$GLOBALS['SITE_DB']->query('SELECT d.title,d.id,t.text_original AS title,news_category AS category_id,date_and_time AS add_date,edit_date FROM '.get_table_prefix().'news d'.$privacy_join.' LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=d.title WHERE '.$where,500,$start);

					foreach ($entry_data as $row)
					{
						if (strval($row['category_id'])==$parent_attributes['id'])
						{
							if (is_null($row['title'])) $row['title']=get_translated_text($row['title']);

							$pagelink=$pagelink_stub.'view:'.strval($row['id']);
							call_user_func_array($callback,array($pagelink,$parent_pagelink,$row['add_date'],$row['edit_date'],0.2,$row['title'])); // Callback
						}
					}

					$start+=500;
				}
				while (array_key_exists(0,$entry_data));
			}
		}
	}

	/**
	 * Convert a page link to a category ID and category permission module type.
	 *
	 * @param  string	The page link
	 * @return array	The pair
	 */
	function extract_page_link_permissions($page_link)
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*):type=misc:id=(.*)$#',$page_link,$matches);
		return array($matches[3],'news');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_lang('news');
		require_code('feedback');
		require_code('news');
		require_css('news');

		inform_non_canonical_parameter('filter');
		inform_non_canonical_parameter('filter_and');
		inform_non_canonical_parameter('blog');

		$type=get_param('type','misc');

		if ($type=='view') return $this->view_news();
		if ($type=='misc') return $this->news_archive();
		if ($type=='cat_select') return $this->news_cat_select(0);
		if ($type=='blog_select') return $this->news_cat_select(1);
		if ($type=='select') return $this->news_cat_select(NULL);

		return new ocp_tempcode();
	}

	/**
	 * The UI to select a news category to view news within.
	 *
	 * @param  ?integer		What to show (NULL: news and blogs, 0: news, 1: blogs)
	 * @return tempcode		The UI
	 */
	function news_cat_select($blogs)
	{
		$title=get_screen_title(($blogs===1)?'BLOGS':(($blogs===0)?'JUST_NEWS_CATEGORIES':'NEWS_CATEGORIES'));

		$start=get_param_integer('news_categories_start',0);
		$max=get_param_integer('news_categories_max',intval(get_option('news_categories_per_page')));

		require_code('ocfiltering');
		$filter=get_param('filter','*');
		$q_filter=ocfilter_to_sqlfragment($filter,'r.news_category','news_categories',NULL,'r.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)

		if (is_null($blogs))
		{
			$map=array();
			$categories=$GLOBALS['SITE_DB']->query_select('news_categories c LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=c.nc_title',array('c.*','text_original'),$map,'ORDER BY nc_owner',$max,$start); // Ordered to show non-blogs first (nc_owner=NULL)
			$max_rows=$GLOBALS['SITE_DB']->query_select_value('news_categories','COUNT(*)',$map);
		} elseif ($blogs==1)
		{
			$categories=$GLOBALS['SITE_DB']->query('SELECT c.*,text_original FROM '.get_table_prefix().'news_categories c LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=c.nc_title WHERE nc_owner IS NOT NULL ORDER BY nc_owner DESC',$max,$start); // Ordered to show newest blogs first
			$max_rows=$GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM '.get_table_prefix().'news_categories WHERE nc_owner IS NOT NULL');
		} else
		{
			$map=array('nc_owner'=>NULL);
			$categories=$GLOBALS['SITE_DB']->query_select('news_categories c LEFT JOIN '.get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND t.id=c.nc_title',array('c.*','text_original'),$map,'ORDER BY text_original ASC',$max,$start); // Ordered by title (can do efficiently as limited numbers of non-blogs)
			$max_rows=$GLOBALS['SITE_DB']->query_select_value('news_categories','COUNT(*)',$map);
		}
		if ($max_rows==count($categories)) // We'll implement better title-based sorting only if we can show all rows on one screen, otherwise too slow
		{
			sort_maps_by($categories,'text_original');
		}
		$content=new ocp_tempcode();
		$join=' LEFT JOIN '.get_table_prefix().'news_category_entries d ON d.news_entry=r.id';
		if ($blogs===1)
		{
			$q_filter.=' AND c.nc_owner IS NOT NULL';

			$join.=' LEFT JOIN '.get_table_prefix().'news_categories c ON c.id=r.news_category';
		}
		elseif ($blogs===0)
		{
			$q_filter.=' AND c.nc_owner IS NULL AND c.id IS NOT NULL';

			$join.=' LEFT JOIN '.get_table_prefix().'news_categories c ON c.id=r.news_category';
		}
		$_content=array();
		foreach ($categories as $category)
		{
			if (has_category_access(get_member(),'news',strval($category['id'])))
			{
				$query='SELECT COUNT(*) FROM '.get_table_prefix().'news r'.$join.' WHERE '.(((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated')))?'validated=1 AND ':'').' (news_entry_category='.strval($category['id']).' OR news_category='.strval($category['id']).') AND '.$q_filter.' ORDER BY date_and_time DESC';
				$count=$GLOBALS['SITE_DB']->query_value_if_there($query);
				if ($count>0)
				{
					$_content[]=render_news_category_box($category,'_SELF',false,true,$blogs);
				}
			}
		}
		foreach ($_content as $c) // To allow code overrides to easily shuffle it
		{
			$content->attach($c);
		}
		if ($content->is_empty()) inform_exit(do_lang_tempcode('NO_ENTRIES'));

		if ((($blogs!==1) || (has_privilege(get_member(),'have_personal_category','cms_news'))) && (has_actual_page_access(NULL,($blogs===1)?'cms_blogs':'cms_news',NULL,NULL)) && (has_submit_permission('high',get_member(),get_ip_address(),'cms_news')))
		{
			$map=array('page'=>($blogs===1)?'cms_blogs':'cms_news','type'=>'ad');
			if (is_numeric($filter)) $map['cat']=$filter;
			$submit_url=build_url($map,get_module_zone('cms_news'));
		} else $submit_url=new ocp_tempcode();

		require_code('templates_pagination');
		$pagination=pagination(do_lang_tempcode('NEWS_CATEGORIES'),$start,'news_categories_start',$max,'news_categories_max',$max_rows);

		$tpl=do_template('PAGINATION_SCREEN',array('_GUID'=>'c61c945e0453c2145a819ca60e8faf09','TITLE'=>$title,'SUBMIT_URL'=>$submit_url,'CONTENT'=>$content,'PAGINATION'=>$pagination));

		require_code('templates_internalise_screen');
		return internalise_own_screen($tpl);
	}

	/**
	 * The UI to view the news archive.
	 *
	 * @return tempcode		The UI
	 */
	function news_archive()
	{
		$blog=get_param_integer('blog',NULL);

		$filter=get_param('id',get_param('filter','*'));
		$filter_and=get_param('filter_and','*');
		$ocselect=either_param('active_filter','');

		// Title
		if ($blog===1)
		{
			$title=get_screen_title('BLOG_NEWS_ARCHIVE');
		} else
		{
			if (is_numeric($filter))
			{
				$news_cat_title=$GLOBALS['SITE_DB']->query_select('news_categories',array('nc_title'),array('id'=>intval($filter)),'',1);
				if (array_key_exists(0,$news_cat_title))
				{
					$news_cat_title[0]['text_original']=get_translated_text($news_cat_title[0]['nc_title']);
					$title=get_screen_title(make_fractionable_editable('news_category',$filter,$news_cat_title[0]['text_original']),false);
				} else
				{
					$title=get_screen_title('NEWS_ARCHIVE');
				}
			} else
			{
				$title=get_screen_title('NEWS_ARCHIVE');
			}
		}

		// Breadcrumbs
		if ($blog===1)
		{
			$first_bc=array('_SELF:_SELF:blog_select',do_lang_tempcode('BLOGS'));
		}
		elseif ($blog===0)
		{
			$first_bc=array('_SELF:_SELF:cat_select',do_lang_tempcode('JUST_NEWS_CATEGORIES'));
		} else
		{
			$first_bc=array('_SELF:_SELF:select',do_lang_tempcode('NEWS_CATEGORIES'));
		}
		breadcrumb_set_parents(array($first_bc));

		// Get category contents
		$inline=get_param_integer('inline',0)==1;
		$content=do_block('main_news',array('param'=>'0','title'=>'','filter'=>$filter,'filter_and'=>$filter_and,'blogs'=>is_null($blog)?'-1':strval($blog),'member_based'=>($blog===1)?'1':'0','zone'=>'_SELF','days'=>'0','fallback_full'=>$inline?'0':'10','fallback_archive'=>$inline?get_option('news_entries_per_page'):'0','no_links'=>'1','pagination'=>'1','attach_to_url_filter'=>'1','ocselect'=>$ocselect,'block_id'=>'module'));

		// Management links
		if ((($blog!==1) || (has_privilege(get_member(),'have_personal_category','cms_news'))) && (has_actual_page_access(NULL,($blog===1)?'cms_blogs':'cms_news',NULL,NULL)) && (has_submit_permission('high',get_member(),get_ip_address(),'cms_news')))
		{
			$map=array('page'=>($blog===1)?'cms_blogs':'cms_news','type'=>'ad');
			if (is_numeric($filter)) $map['cat']=$filter;
			$submit_url=build_url($map,get_module_zone('cms_news'));
		} else $submit_url=new ocp_tempcode();

		// Render
		return do_template('NEWS_ARCHIVE_SCREEN',array('_GUID'=>'228918169ab1db445ee0c2d71f85983c','CAT'=>is_numeric($filter)?$filter:NULL,'SUBMIT_URL'=>$submit_url,'BLOG'=>$blog===1,'TITLE'=>$title,'CONTENT'=>$content));
	}

	/**
	 * The UI to view a news entry.
	 *
	 * @return tempcode		The UI
	 */
	function view_news()
	{
		$id=get_param_integer('id');

		if (addon_installed('content_privacy'))
		{
			require_code('content_privacy');
			check_privacy('news',strval($id));
		}

		$blog=get_param_integer('blog',NULL);

		$filter=get_param('filter','*');
		$filter_and=get_param('filter_and','*');

		// Load from database
		$rows=$GLOBALS['SITE_DB']->query_select('news',array('*'),array('id'=>$id),'',1);
		if (!array_key_exists(0,$rows))
		{
			return warn_screen(get_screen_title('NEWS'),do_lang_tempcode('MISSING_RESOURCE'));
		}
		$myrow=$rows[0];

		// Breadcrumbs
		if ($blog===1)
		{
			$first_bc=array('_SELF:_SELF:blog_select',do_lang_tempcode('BLOGS'));
		}
		elseif ($blog===0)
		{
			$first_bc=array('_SELF:_SELF:cat_select',do_lang_tempcode('JUST_NEWS_CATEGORIES'));
		} else
		{
			$first_bc=array('_SELF:_SELF:select',do_lang_tempcode('NEWS_CATEGORIES'));
		}
		if ($blog===1)
		{
			$parent_title=do_lang_tempcode('BLOG_NEWS_ARCHIVE');
		} else
		{
			if (is_numeric($filter))
			{
				$news_cat_title=$GLOBALS['SITE_DB']->query_select('news_categories',array('nc_title'),array('id'=>intval($filter)),'',1);
				if (array_key_exists(0,$news_cat_title))
				{
					$news_cat_title[0]['text_original']=get_translated_text($news_cat_title[0]['nc_title']);
					$parent_title=make_string_tempcode(escape_html($news_cat_title[0]['text_original']));
				} else
				{
					$parent_title=do_lang_tempcode('NEWS_ARCHIVE');
				}
			} else
			{
				$parent_title=do_lang_tempcode('NEWS_ARCHIVE');
			}
		}
		breadcrumb_set_parents(array($first_bc,array('_SELF:_SELF:misc'.(($blog===1)?':blog=1':(($blog===0)?':blog=0':'')).(($filter=='*')?'':(is_numeric($filter)?(':id='.$filter):(':filter='.$filter))).(($filter_and=='*')?'':(':filter_and='.$filter_and)).propagate_ocselect_pagelink(),$parent_title)));
		breadcrumb_set_self(get_translated_tempcode($myrow['title']));

		// Permissions
		if (!has_category_access(get_member(),'news',strval($myrow['news_category']))) access_denied('CATEGORY_ACCESS');

		// Title
		if ((get_value('no_awards_in_titles')!=='1') && (addon_installed('awards')))
		{
			require_code('awards');
			$awards=find_awards_for('news',strval($id));
		} else $awards=array();
		$_title=get_translated_tempcode($myrow['title']);
		$title_to_use=do_lang_tempcode(($blog===1)?'BLOG__NEWS':'_NEWS',make_fractionable_editable('news',$id,$_title));
		$title=get_screen_title($title_to_use,false,NULL,NULL,$awards);

		// SEO
		seo_meta_load_for('news',strval($id),do_lang(($blog===1)?'BLOG__NEWS':'_NEWS',get_translated_text($myrow['title'])));

		// Rating and comments
		$self_url_map=array('page'=>'_SELF','type'=>'view','id'=>$id);
		/*if ($filter!='*') $self_url_map['filter']=$filter;		Potentially makes URL too long for content topic to store, and we probably don't want to store this assumptive context anyway
		if (($filter_and!='*') && ($filter_and!='')) $self_url_map['filter_and']=$filter_and;*/
		if (!is_null($blog)) $self_url_map['blog']=$blog;
		list($rating_details,$comment_details,$trackback_details)=embed_feedback_systems(
			get_page_name(),
			strval($id),
			$myrow['allow_rating'],
			$myrow['allow_comments'],
			$myrow['allow_trackbacks'],
			$myrow['validated'],
			$myrow['submitter'],
			build_url($self_url_map,'_SELF',NULL,false,false,true),
			get_translated_text($myrow['title']),
			get_value('comment_forum__news')
		);

		// Load details
		$date=get_timezoned_date($myrow['date_and_time']);
		$author_url=addon_installed('authors')?build_url(array('page'=>'authors','type'=>'misc','id'=>$myrow['author']),get_module_zone('authors')):new ocp_tempcode();
		$author=$myrow['author'];
		$news_full=get_translated_tempcode($myrow['news_article']);
		$news_full_plain=get_translated_text($myrow['news_article']);
		if ($news_full->is_empty())
		{
			$news_full=get_translated_tempcode($myrow['news']);
			$news_full_plain=get_translated_text($myrow['news']);
		}

		// Validation
		if (($myrow['validated']==0) && (addon_installed('unvalidated')))
		{
			if (!has_privilege(get_member(),'jump_to_unvalidated'))
				access_denied('PRIVILEGE','jump_to_unvalidated');

			$warning_details=do_template('WARNING_BOX',array('_GUID'=>'5fd82328dc2ac9695dc25646237065b0','WARNING'=>do_lang_tempcode((get_param_integer('redirected',0)==1)?'UNVALIDATED_TEXT_NON_DIRECT':'UNVALIDATED_TEXT')));
		} else $warning_details=new ocp_tempcode();

		// Views
		if ((get_db_type()!='xml') && (get_value('no_view_counts')!=='1'))
		{
			$myrow['news_views']++;
			if (!$GLOBALS['SITE_DB']->table_is_locked('news'))
				$GLOBALS['SITE_DB']->query_update('news',array('news_views'=>$myrow['news_views']),array('id'=>$id),'',1,NULL,false,true);
		}

		// Management links
		if ((has_actual_page_access(NULL,($blog===1)?'cms_blogs':'cms_news',NULL,NULL)) && (has_edit_permission('high',get_member(),$myrow['submitter'],($blog===1)?'cms_blogs':'cms_news',array('news',$myrow['news_category']))))
		{
			$edit_url=build_url(array('page'=>($blog===1)?'cms_blogs':'cms_news','type'=>'_ed','id'=>$id),get_module_zone(($blog===1)?'cms_blogs':'cms_news'));
		} else $edit_url=new ocp_tempcode();
		$tmp=array('page'=>'_SELF','type'=>'misc');
		if ($filter!='*') $tmp[is_numeric($filter)?'id':'filter']=$filter;
		if (($filter_and!='*') && ($filter_and!='')) $tmp['filter_and']=$filter_and;
		if (!is_null($blog)) $tmp['blog']=$blog;
		$archive_url=build_url($tmp+propagate_ocselect(),'_SELF');
		if ((($blog!==1) || (has_privilege(get_member(),'have_personal_category','cms_news'))) && (has_actual_page_access(NULL,($blog===1)?'cms_blogs':'cms_news',NULL,NULL)) && (has_submit_permission('high',get_member(),get_ip_address(),'cms_news',array('news',$myrow['news_category']))))
		{
			$map=array('page'=>($blog===1)?'cms_blogs':'cms_news','type'=>'ad');
			if (is_numeric($filter)) $map['cat']=$filter;
			$submit_url=build_url($map,get_module_zone('cms_news'));
		} else $submit_url=new ocp_tempcode();

		// Category membership
		$news_cats=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'news_categories WHERE nc_owner IS NULL OR id='.strval($myrow['news_category']));
		$news_cats=list_to_map('id',$news_cats);
		$img=($news_cats[$myrow['news_category']]['nc_img']=='')?'':find_theme_image($news_cats[$myrow['news_category']]['nc_img']);
		if (is_null($img)) $img='';
		if ($myrow['news_image']!='')
		{
			$img=$myrow['news_image'];
			if (url_is_local($img)) $img=get_base_url().'/'.$img;
		}
		$category=get_translated_text($news_cats[$myrow['news_category']]['nc_title']);
		$categories=array(strval($myrow['news_category'])=>$category);
		$all_categories_for_this=$GLOBALS['SITE_DB']->query_select('news_category_entries',array('*'),array('news_entry'=>$id));
		$NEWS_CATS_CACHE=array();
		foreach ($all_categories_for_this as $category_for_this)
		{
			if (!array_key_exists($category_for_this['news_entry_category'],$news_cats))
			{
				$_news_cats=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('id'=>$category_for_this['news_entry_category']),'',1);
				if (array_key_exists(0,$_news_cats))
					$NEWS_CATS_CACHE[$category_for_this['news_entry_category']]=$_news_cats[0];
			}

			if (array_key_exists($category_for_this['news_entry_category'],$news_cats))
				$categories[strval($category_for_this['news_entry_category'])]=get_translated_text($news_cats[$category_for_this['news_entry_category']]['nc_title']);
		}

		// Newsletter tie-in
		$newsletter_url=new ocp_tempcode();
		if (addon_installed('newsletter'))
		{
			require_lang('newsletter');
			if (has_actual_page_access(get_member(),'admin_newsletter'))
			{
				$newsletter_url=build_url(array('page'=>'admin_newsletter','type'=>'new','from_news'=>$id),get_module_zone('admin_newsletter'));
			}
		}

		// Meta data
		set_extra_request_metadata(array(
			'created'=>date('Y-m-d',$myrow['date_and_time']),
			'creator'=>$myrow['author'],
			'publisher'=>$GLOBALS['FORUM_DRIVER']->get_username($myrow['submitter']),
			'modified'=>is_null($myrow['edit_date'])?'':date('Y-m-d',$myrow['edit_date']),
			'type'=>'News article',
			'title'=>get_translated_text($myrow['title']),
			'identifier'=>'_SEARCH:news:view:'.strval($id),
			'image'=>$img,
			'description'=>strip_comcode(get_translated_text($myrow['news'])),
			'category'=>$category,
		));

		// Render
		return do_template('NEWS_ENTRY_SCREEN',array(
			'_GUID'=>'7686b23934e22c493d4ac10ba6c475c4',
			'TITLE'=>$title,
			'ID'=>strval($id),
			'CATEGORY_ID'=>strval($myrow['news_category']),
			'BLOG'=>$blog===1,
			'_TITLE'=>$_title,
			'TAGS'=>get_loaded_tags('news'),
			'CATEGORIES'=>$categories,
			'NEWSLETTER_URL'=>$newsletter_url,
			'ADD_DATE_RAW'=>strval($myrow['date_and_time']),
			'EDIT_DATE_RAW'=>is_null($myrow['edit_date'])?'':strval($myrow['edit_date']),
			'SUBMITTER'=>strval($myrow['submitter']),
			'CATEGORY'=>$category,
			'IMG'=>$img,
			'VIEWS'=>integer_format($myrow['news_views']),
			'COMMENT_DETAILS'=>$comment_details,
			'RATING_DETAILS'=>$rating_details,
			'TRACKBACK_DETAILS'=>$trackback_details,
			'DATE'=>$date,
			'AUTHOR'=>$author,
			'AUTHOR_URL'=>$author_url,
			'NEWS_FULL'=>$news_full,
			'NEWS_FULL_PLAIN'=>$news_full_plain,
			'EDIT_URL'=>$edit_url,
			'ARCHIVE_URL'=>$archive_url,
			'SUBMIT_URL'=>$submit_url,
			'WARNING_DETAILS'=>$warning_details,
		));
	}

}


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
 * @package		news
 */

class Block_main_news
{
    /**
	 * Find details of the block.
	 *
	 * @return ?array	Map of block info (NULL: block is disabled).
	 */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array('param','member_based','filter','filter_and','multiplier','fallback_full','fallback_archive','blogs','historic','zone','title','show_in_full','no_links','attach_to_url_filter','render_if_empty','ocselect','start','pagination','as_guest');
        return $info;
    }

    /**
	 * Find cacheing details for the block.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: block is disabled).
	 */
    public function cacheing_environment()
    {
        $info = array();
        $info['cache_on'] = '((addon_installed(\'content_privacy\')) && (!(array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false)))?NULL:(preg_match(\'#<\w+>#\',(array_key_exists(\'ocselect\',$map)?$map[\'ocselect\']:\'\'))!=0)?NULL:array(((array_key_exists(\'pagination\',$map)?$map[\'pagination\']:\'0\')==\'1\'),array_key_exists(\'title\',$map)?escape_html($map[\'title\']):\'(default title)\',array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false,get_param_integer($block_id.\'_start\',array_key_exists(\'start\',$map)?intval($map[\'start\']):0),array_key_exists(\'ocselect\',$map)?$map[\'ocselect\']:\'\',array_key_exists(\'show_in_full\',$map)?$map[\'show_in_full\']:\'0\',array_key_exists(\'render_if_empty\',$map)?$map[\'render_if_empty\']:\'0\',((array_key_exists(\'attach_to_url_filter\',$map)?$map[\'attach_to_url_filter\']:\'0\')==\'1\'),array_key_exists(\'no_links\',$map)?$map[\'no_links\']:0,array_key_exists(\'title\',$map)?$map[\'title\']:\'\',array_key_exists(\'member_based\',$map)?$map[\'member_based\']:\'0\',array_key_exists(\'blogs\',$map)?$map[\'blogs\']:\'-1\',array_key_exists(\'historic\',$map)?$map[\'historic\']:\'\',$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),array_key_exists(\'param\',$map)?intval($map[\'param\']):14,array_key_exists(\'multiplier\',$map)?floatval($map[\'multiplier\']):0.5,array_key_exists(\'fallback_full\',$map)?intval($map[\'fallback_full\']):3,array_key_exists(\'fallback_archive\',$map)?intval($map[\'fallback_archive\']):6,array_key_exists(\'filter\',$map)?$map[\'filter\']:get_param(\'news_filter\',\'\'),array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'news\'),array_key_exists(\'filter_and\',$map)?$map[\'filter_and\']:\'\')';
        $info['ttl'] = (get_value('no_block_timeout') === '1')?60*60*24*365*5/*5 year timeout*/:60;
        return $info;
    }

    /**
	 * Execute the block.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
    public function run($map)
    {
        require_lang('news');
        require_lang('ocf');
        require_css('news');

        // Read in parameters
        $days = isset($map['param'])?intval($map['param']):14;
        $multiplier = isset($map['multiplier'])?floatval($map['multiplier']):0.5;
        $fallback_full = isset($map['fallback_full'])?intval($map['fallback_full']):3;
        $fallback_archive = isset($map['fallback_archive'])?intval($map['fallback_archive']):6;
        $zone = isset($map['zone'])?$map['zone']:get_module_zone('news');
        $historic = isset($map['historic'])?$map['historic']:'';
        $filter_and = isset($map['filter_and'])?$map['filter_and']:'';
        $blogs = isset($map['blogs'])?intval($map['blogs']):-1;
        $member_based = (isset($map['member_based'])) && ($map['member_based'] == '1');
        $attach_to_url_filter = ((isset($map['attach_to_url_filter'])?$map['attach_to_url_filter']:'0') == '1');
        $ocselect = isset($map['ocselect'])?$map['ocselect']:'';

        // Pagination
        $block_id = get_block_id($map);
        $start = get_param_integer($block_id . '_start',isset($map['start'])?intval($map['start']):0);
        if ($start != 0) {
            $days = 0;
        }
        $do_pagination = ((isset($map['pagination'])?$map['pagination']:'0') == '1');

        // Read in news categories ahead, for performance
        global $NEWS_CATS_CACHE;
        if (!isset($NEWS_CATS_CACHE)) {
            $NEWS_CATS_CACHE = $GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('nc_owner' => NULL));
            $NEWS_CATS_CACHE = list_to_map('id',$NEWS_CATS_CACHE);
        }

        // Work out how many days to show
        $days_full = floatval($days)*$multiplier;
        $days_outline = floatval($days)-$days_full;

        // News query
        require_code('ocfiltering');
        $filter = isset($map['filter'])?$map['filter']:get_param('news_filter','*');
        if ($filter == '*') {
            $q_filter = '1=1';
        } else {
            $filters_1 = ocfilter_to_sqlfragment($filter,'r.news_category','news_categories',null,'r.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
            $filters_2 = ocfilter_to_sqlfragment($filter,'d.news_entry_category','news_categories',null,'d.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
            $q_filter = '(' . $filters_1 . ' OR ' . $filters_2 . ')';
        }
        if ($blogs === 0) {
            if ($q_filter != '') {
                $q_filter .= ' AND ';
            }
            $q_filter .= 'nc_owner IS NULL';
        } elseif ($blogs === 1) {
            if ($q_filter != '') {
                $q_filter .= ' AND ';
            }
            $q_filter .= '(nc_owner IS NOT NULL)';
        }
        if ($blogs != -1) {
            $join = ' LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news_categories c ON c.id=r.news_category';
        } else {
            $join = '';
        }

        if ($filter_and != '') {
            $filters_and_1 = ocfilter_to_sqlfragment($filter_and,'r.news_category','news_categories',null,'r.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
            $filters_and_2 = ocfilter_to_sqlfragment($filter_and,'d.news_entry_category','news_categories',null,'d.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
            $q_filter .= ' AND (' . $filters_and_1 . ' OR ' . $filters_and_2 . ')';
        }

        // ocSelect
        if ($ocselect != '') {
            require_code('ocselect');
            $content_type = 'news';
            list($ocselect_extra_select,$ocselect_extra_join,$ocselect_extra_where) = ocselect_to_sql($GLOBALS['SITE_DB'],parse_ocselect($ocselect),$content_type,'');
            $extra_select_sql = implode('',$ocselect_extra_select);
            $join .= implode('',$ocselect_extra_join);
            $q_filter .= $ocselect_extra_where;
        } else {
            $extra_select_sql = '';
        }

        if (addon_installed('content_privacy')) {
            require_code('content_privacy');
            $as_guest = array_key_exists('as_guest',$map)?($map['as_guest'] == '1'):false;
            $viewing_member_id = $as_guest?$GLOBALS['FORUM_DRIVER']->get_guest_id():mixed();
            list($privacy_join,$privacy_where) = get_privacy_where_clause('news','r',$viewing_member_id);
            $join .= $privacy_join;
            $q_filter .= $privacy_where;
        }

        // Read in rows
        $max_rows = $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(DISTINCT r.id) FROM ' . get_table_prefix() . 'news r LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news_category_entries d ON d.news_entry=r.id' . $join . ' WHERE ' . $q_filter . ((!has_privilege(get_member(),'see_unvalidated'))?' AND validated=1':''),false,true);
        if ($historic == '') {
            $rows = ($days_full == 0.0)?array():$GLOBALS['SITE_DB']->query('SELECT *,r.id AS p_id' . $extra_select_sql . ' FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news r LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news_category_entries d ON d.news_entry=r.id' . $join . ' WHERE ' . $q_filter . ((!has_privilege(get_member(),'see_unvalidated'))?' AND validated=1':'') . ' AND date_and_time>=' . strval(time()-60*60*24*intval($days_full)) . (can_arbitrary_groupby()?' GROUP BY r.id':'') . ' ORDER BY r.date_and_time DESC',min($fallback_full+$fallback_archive,30)/*reasonable limit*/,null,false,false,array('title' => 'SHORT_TRANS','news' => 'LONG_TRANS','news_article' => 'LONG_TRANS'));
            if (!isset($rows[0])) { // Nothing recent, so we work to get at least something
                $rows = ($fallback_full == 0)?array():$GLOBALS['SITE_DB']->query('SELECT *,r.id AS p_id' . $extra_select_sql . ' FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news r LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news_category_entries d ON r.id=d.news_entry' . $join . ' WHERE ' . $q_filter . ((!has_privilege(get_member(),'see_unvalidated'))?' AND validated=1':'') . (can_arbitrary_groupby()?' GROUP BY r.id':'') . ' ORDER BY r.date_and_time DESC',$fallback_full,$start,false,true,array('title' => 'SHORT_TRANS','news' => 'LONG_TRANS','news_article' => 'LONG_TRANS'));
                $rows2 = ($fallback_archive == 0)?array():$GLOBALS['SITE_DB']->query('SELECT *,r.id AS p_id' . $extra_select_sql . ' FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news r LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news_category_entries d ON r.id=d.news_entry' . $join . ' WHERE ' . $q_filter . ((!has_privilege(get_member(),'see_unvalidated'))?' AND validated=1':'') . (can_arbitrary_groupby()?' GROUP BY r.id':'') . ' ORDER BY r.date_and_time DESC',$fallback_archive,$fallback_full+$start,false,true,array('title' => 'SHORT_TRANS','news' => 'LONG_TRANS','news_article' => 'LONG_TRANS'));
            } else {
                $rows2 = $GLOBALS['SITE_DB']->query('SELECT *,r.id AS p_id' . $extra_select_sql . ' FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news r LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news_category_entries d ON r.id=d.news_entry' . $join . ' WHERE ' . $q_filter . ((!has_privilege(get_member(),'see_unvalidated'))?' AND validated=1':'') . ' AND date_and_time>=' . strval(time()-60*60*24*intval($days_full+$days_outline)) . ' AND date_and_time<' . strval(time()-60*60*24*intval($days_full)) . (can_arbitrary_groupby()?' GROUP BY r.id':'') . ' ORDER BY r.date_and_time DESC',max($fallback_full+$fallback_archive,30)/*reasonable limit*/,null,false,false,array('title' => 'SHORT_TRANS','news' => 'LONG_TRANS','news_article' => 'LONG_TRANS'));
            }
        } else {
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }
            $start = 0;
            do {
                $_rows = $GLOBALS['SITE_DB']->query('SELECT *,r.id AS p_id' . $extra_select_sql . ' FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news r LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news_category_entries d ON r.id=d.news_entry' . $join . ' WHERE ' . $q_filter . ((!has_privilege(get_member(),'see_unvalidated'))?' AND validated=1':'') . (can_arbitrary_groupby()?' GROUP BY r.id':'') . ' ORDER BY r.date_and_time DESC',200,$start,null,false,true);
                $rows = array();
                $rows2 = array();
                foreach ($_rows as $row) {
                    $ok = false;
                    switch ($historic) {
                        case 'month':
                            if ((date('m',utctime_to_usertime($row['date_and_time'])) == date('m',utctime_to_usertime())) && (date('Y',utctime_to_usertime($row['date_and_time'])) != date('Y',utctime_to_usertime()))) {
                                $ok = true;
                            }
                            break;

                        case 'week':
                            if ((date('W',utctime_to_usertime($row['date_and_time'])) == date('W',utctime_to_usertime())) && (date('Y',utctime_to_usertime($row['date_and_time'])) != date('Y',utctime_to_usertime()))) {
                                $ok = true;
                            }
                            break;

                        case 'day':
                            if ((date('d',utctime_to_usertime($row['date_and_time'])) == date('d',utctime_to_usertime())) && (date('m',utctime_to_usertime($row['date_and_time'])) == date('m',utctime_to_usertime())) && (date('Y',utctime_to_usertime($row['date_and_time'])) != date('Y',utctime_to_usertime()))) {
                                $ok = true;
                            }
                            break;
                    }
                    if ($ok) {
                        if (count($rows)<$fallback_full) {
                            $rows[] = $row;
                        } elseif (count($rows2)<$fallback_archive) {
                            $rows2[] = $row;
                        } else {
                            break 2;
                        }
                    }
                }
                $start += 200;
            } while (count($_rows) == 200);
            unset($_rows);
        }
        $rows = remove_duplicate_rows($rows,'p_id');

        // Shared calculations
        $show_in_full = (isset($map['show_in_full'])) && ($map['show_in_full'] == '1');
        $show_author = (addon_installed('authors')) && (!$member_based);
        $prop_url = array();
        if ($attach_to_url_filter) {
            $prop_url += propagate_ocselect();
        }
        if ($filter != '*') {
            $prop_url['filter'] = $filter;
        }
        if (($filter_and != '*') && ($filter_and != '')) {
            $prop_url['filter_and'] = $filter_and;
        }
        if ($blogs != -1) {
            $prop_url['blog'] = $blogs;
        }
        $allow_comments_shared = (get_option('is_on_comments') == '1') && (!has_no_forum());
        $base_url = get_base_url();

        // Render loop
        $news_text = new ocp_tempcode();
        foreach ($rows as $i => $myrow) {
            if (has_category_access(get_member(),'news',strval($myrow['news_category']))) {
                $just_news_row = db_map_restrict($myrow,array('id','title','news','news_article'));

                // Basic details
                $id = $myrow['p_id'];
                $date = get_timezoned_date($myrow['date_and_time']);
                $news_title = get_translated_tempcode('news',$just_news_row,'title');
                $news_title_plain = get_translated_text($myrow['title']);

                // Author
                $author_url = new ocp_tempcode();
                if ($show_author) {
                    $url_map = array('page' => 'authors','type' => 'misc','id' => $myrow['author']);
                    if ($attach_to_url_filter) {
                        $url_map += propagate_ocselect();
                    }
                    $author_url = build_url($url_map,get_module_zone('authors'));
                }
                $author = $myrow['author'];

                // Text
                if ($show_in_full) {
                    $news = get_translated_tempcode('news',$just_news_row,'news_article');
                    $truncate = false;
                    if ($news->is_empty()) {
                        $news = get_translated_tempcode('news',$just_news_row,'news');
                    }
                } else {
                    $news = get_translated_tempcode('news',$just_news_row,'news');
                    if ($news->is_empty()) {
                        $news = get_translated_tempcode('news',$just_news_row,'news_article');
                        $truncate = true;
                    } else {
                        $truncate = false;
                    }
                }

                // URL
                $tmp = array('page' => 'news','type' => 'view','id' => $id)+$prop_url;
                $full_url = build_url($tmp,$zone);

                // Category
                if (!isset($NEWS_CATS_CACHE[$myrow['news_category']])) {
                    $_news_cats = $GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('id' => $myrow['news_category']),'',1);
                    if (isset($_news_cats[0])) {
                        $NEWS_CATS_CACHE[$myrow['news_category']] = $_news_cats[0];
                    } else {
                        $myrow['news_category'] = db_get_first_id();
                    }
                }
                $news_cat_row = $NEWS_CATS_CACHE[$myrow['news_category']];

                $category = get_translated_text($news_cat_row['nc_title']);
                if ($myrow['news_image'] != '') {
                    $img_raw = $myrow['news_image'];
                    if (url_is_local($img_raw)) {
                        $img_raw = $base_url . '/' . $img_raw;
                    }
                    require_code('images');
                    $img = do_image_thumb($img_raw,$category,false);
                } else {
                    $img_raw = ($news_cat_row['nc_img'] == '')?'':find_theme_image($news_cat_row['nc_img']);
                    if (is_null($img_raw)) {
                        $img_raw = '';
                    }
                    $img = $img_raw;
                }

                // SEO
                $seo_bits = (get_value('no_tags') === '1')?array('',''):seo_meta_get_for('news',strval($id));

                // Render
                $map2 = array(
                    'GIVE_CONTEXT' => false,
                    'TAGS' => get_loaded_tags('news',explode(',',$seo_bits[0])),
                    'ID' => strval($id),
                    'TRUNCATE' => $truncate,
                    'BLOG' => $blogs === 1,
                    'SUBMITTER' => strval($myrow['submitter']),
                    'CATEGORY' => $category,
                    'IMG' => $img,
                    '_IMG' => $img_raw,
                    'DATE' => $date,
                    'DATE_RAW' => strval($myrow['date_and_time']),
                    'NEWS_TITLE' => $news_title,
                    'NEWS_TITLE_PLAIN' => $news_title_plain,
                    'AUTHOR' => $author,
                    'AUTHOR_URL' => $author_url,
                    'NEWS' => $news,
                    'FULL_URL' => $full_url,
                );
                if (($allow_comments_shared) && ($myrow['allow_comments'] >= 1)) {
                    $map2['COMMENT_COUNT'] = '1';
                }
                $news_text->attach(do_template('NEWS_BOX',$map2));
            }
        }
        $news_text2 = new ocp_tempcode();
        foreach ($rows2 as $j => $myrow) {
            if (has_category_access(get_member(),'news',strval($myrow['news_category']))) {
                $just_news_row = db_map_restrict($myrow,array('id','title','news','news_article'));

                // Basic details
                $date = get_timezoned_date($myrow['date_and_time']);

                // URL
                $tmp = array('page' => 'news','type' => 'view','id' => $myrow['p_id'])+$prop_url;
                $url = build_url($tmp,$zone);

                // Title
                $title = get_translated_tempcode('news',$just_news_row,'title');
                $title_plain = get_translated_text($myrow['title']);

                // Render
                $seo_bits = (get_value('no_tags') === '1')?array('',''):seo_meta_get_for('news',strval($myrow['p_id']));
                $map2 = array('_GUID' => 'd81bda3a0912a1e708af6bb1f503b296','TAGS' => get_loaded_tags('news',explode(',',$seo_bits[0])),'BLOG' => $blogs === 1,'ID' => strval($myrow['p_id']),'SUBMITTER' => strval($myrow['submitter']),'DATE' => $date,'DATE_RAW' => strval($myrow['date_and_time']),'URL' => $url,'NEWS_TITLE_PLAIN' => $title_plain,'NEWS_TITLE' => $title);
                if (($allow_comments_shared) && ($myrow['allow_comments'] >= 1)) {
                    $map2['COMMENT_COUNT'] = '1';
                }
                $news_text2->attach(do_template('NEWS_BRIEF',$map2));
            }
        }

        // Work out management URLs
        $tmp = array('page' => 'news','type' => 'misc');
        if ($filter != '*') {
            $tmp[is_numeric($filter)?'id':'filter'] = $filter;
        }
        if (($filter_and != '*') && ($filter_and != '')) {
            $tmp['filter_and'] = $filter_and;
        }
        if ($blogs != -1) {
            $tmp['blog'] = $blogs;
        }
        $archive_url = build_url($tmp,$zone);
        $_is_on_rss = get_option('is_rss_advertised',true);
        $is_on_rss = is_null($_is_on_rss)?0:intval($_is_on_rss); // Set to zero if we don't want to show RSS links
        $submit_url = new ocp_tempcode();
        $management_page = ($blogs === 1)?'cms_blogs':'cms_news';
        if ((($blogs !== 1) || (has_privilege(get_member(),'have_personal_category','cms_news'))) && (has_actual_page_access(null,$management_page,null,null)) && (has_submit_permission('high',get_member(),get_ip_address(),$management_page))) {
            $map2 = array('page' => $management_page,'type' => 'ad','redirect' => SELF_REDIRECT);
            if (is_numeric($filter)) {
                $map2['cat'] = $filter; // select news cat by default, if we are only showing one news cat in this block
            } elseif ($filter != '*') {
                $pos_a = strpos($filter,',');
                $pos_b = strpos($filter,'-');
                if ($pos_a !== false) {
                    $first_cat = substr($filter,0,$pos_a);
                } elseif ($pos_b !== false) {
                    $first_cat = substr($filter,0,$pos_b);
                } else {
                    $first_cat = '';
                }
                if (is_numeric($first_cat)) {
                    $map2['cat'] = $first_cat;
                }
            }
            $submit_url = build_url($map2,get_module_zone($management_page));
        }

        // Block title
        $_title = isset($map['title'])?protect_from_escaping(escape_html($map['title'])):do_lang_tempcode(($blogs == 1)?'BLOGS_POSTS':'NEWS');

        // Feed URLs
        $atom_url = new ocp_tempcode();
        $rss_url = new ocp_tempcode();
        if ($is_on_rss == 1) {
            $atom_url = make_string_tempcode(find_script('backend') . '?type=atom&mode=news&filter=' . $filter);
            $atom_url->attach(symbol_tempcode('KEEP'));
            $rss_url = make_string_tempcode(find_script('backend') . '?type=rss2&mode=news&filter=' . $filter);
            $rss_url->attach(symbol_tempcode('KEEP'));
        }

        // Wipe out management/feed URLs if no links was requested
        if ((isset($map['no_links'])) && ($map['no_links'] == '1')) {
            $submit_url = new ocp_tempcode();
            $archive_url = new ocp_tempcode();
            $atom_url = new ocp_tempcode();
            $rss_url = new ocp_tempcode();
        }

        if ((count($rows) == 0) && (count($rows2) == 0)) {
            if ((!isset($map['render_if_empty'])) || ($map['render_if_empty'] == '0')) {
                return do_template('BLOCK_NO_ENTRIES',array(
                    '_GUID' => '9d7065af4dd4026ffb34243fd931f99d',
                    'HIGH' => false,
                    'TITLE' => $_title,
                    'MESSAGE' => do_lang_tempcode(($blogs == 1)?'BLOG_NO_NEWS':'NO_NEWS'),
                    'ADD_NAME' => do_lang_tempcode(($blogs == 1)?'ADD_NEWS_BLOG':'ADD_NEWS'),
                    'SUBMIT_URL' => $submit_url,
                ));
            }
        }

        // Pagination
        $pagination = mixed();
        if ($do_pagination) {
            require_code('templates_pagination');
            $pagination = pagination(do_lang_tempcode('NEWS'),$start,$block_id . '_start',$fallback_full+$fallback_archive,$block_id . '_max',$max_rows);
        }

        return do_template('BLOCK_MAIN_NEWS',array(
            '_GUID' => '01f5fbd2b0c7c8f249023ecb4254366e',
            'BLOCK_PARAMS' => block_params_arr_to_str($map),
            'BLOG' => $blogs === 1,
            'TITLE' => $_title,
            'CONTENT' => $news_text,
            'BRIEF' => $news_text2,
            'FILTER' => $filter,
            'ARCHIVE_URL' => $archive_url,
            'SUBMIT_URL' => $submit_url,
            'RSS_URL' => $rss_url,
            'ATOM_URL' => $atom_url,
            'PAGINATION' => $pagination,

            'START' => strval($start),
            'MAX' => strval($fallback_full+$fallback_archive),
            'START_PARAM' => $block_id . '_start',
            'MAX_PARAM' => $block_id . '_max',
        ));
    }
}

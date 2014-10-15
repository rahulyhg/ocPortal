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
 * @package		ocf_forum
 */

class Hook_search_ocf_within_topic
{
    /**
	 * Find details for this search hook.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @return ?array		Map of search hook details (NULL: hook is disabled).
	 */
    public function info($check_permissions = true)
    {
        if (get_forum_type() != 'ocf') {
            return NULL;
        }

        if ($check_permissions) {
            if (!has_actual_page_access(get_member(),'topicview')) {
                return NULL;
            }
        }

        if (get_param('search_under','',true) == '') {
            return NULL;
        }

        require_lang('ocf');

        $info = array();
        $info['lang'] = do_lang_tempcode('POSTS_WITHIN_TOPIC');
        $info['default'] = false;
        $info['special_on'] = array();
        $info['special_off'] = array();
        $info['category'] = 'p_topic_id';
        $info['integer_category'] = true;
        $info['advanced_only'] = true;

        $info['permissions'] = array(
            array(
                'type' => 'zone',
                'zone_name' => get_module_zone('topicview'),
            ),
            array(
                'type' => 'page',
                'zone_name' => get_module_zone('topicview'),
                'page_name' => 'topicview',
            ),
        );

        return $info;
    }

    /**
	 * Get details for an ajax-tree-list of entries for the content covered by this search hook.
	 *
	 * @return array			A pair: the hook, and the options
	 */
    public function ajax_tree()
    {
        return array('choose_topic',array('compound_list' => false));
    }

    /**
	 * Run function for search results.
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
    public function run($content,$only_search_meta,$direction,$max,$start,$only_titles,$content_where,$author,$author_id,$cutoff,$sort,$limit_to,$boolean_operator,$where_clause,$search_under,$boolean_search)
    {
        if (get_forum_type() != 'ocf') {
            return array();
        }
        require_code('ocf_forums');
        require_code('ocf_posts');
        require_css('ocf');

        $remapped_orderer = '';
        switch ($sort) {
            case 'title':
                $remapped_orderer = 'p_title';
                break;

            case 'add_date':
                $remapped_orderer = 'p_time';
                break;
        }

        require_lang('ocf');

        // Calculate our where clause (search)
        $sq = build_search_submitter_clauses('p_poster',$author_id,$author);
        if (is_null($sq)) {
            return array();
        } else {
            $where_clause .= $sq;
        }
        if (!is_null($cutoff)) {
            $where_clause .= ' AND ';
            $where_clause .= 'p_time>' . strval($cutoff);
        }

        if ((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated'))) {
            $where_clause .= ' AND ';
            $where_clause .= 'p_validated=1';
        }
        $where_clause .= ' AND ';
        $where_clause .= 't_forum_id=p_cache_forum_id AND t_forum_id IS NOT NULL AND p_intended_solely_for IS NULL';

        // Calculate and perform query
        $rows = get_search_rows(null,null,$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,'f_posts r JOIN ' . get_table_prefix() . 'f_topics s ON r.p_topic_id=s.id',array('!' => '!','r.p_post' => 'LONG_TRANS__COMCODE'),$where_clause,$content_where,$remapped_orderer,'r.*,t_forum_id',array('r.p_title'),'forums','t_forum_id');

        $out = array();
        foreach ($rows as $i => $row) {
            $out[$i]['data'] = $row;
            unset($rows[$i]);
            if (($remapped_orderer != '') && (array_key_exists($remapped_orderer,$row))) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            } elseif (strpos($remapped_orderer,'_rating:') !== false) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            }
        }

        return $out;
    }

    /**
	 * Run function for rendering a search result.
	 *
	 * @param  array		The data row stored when we retrieved the result
	 * @return tempcode	The output
	 */
    public function render($row)
    {
        require_code('ocf_posts2');
        return render_post_box($row,false,false);
    }
}

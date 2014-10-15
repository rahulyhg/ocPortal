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
 * @package		ocf_clubs
 */

class Hook_search_ocf_clubs
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
            if (!has_actual_page_access(get_member(),'groups')) {
                return NULL;
            }
        }

        if ($GLOBALS['FORUM_DB']->query_select_value('f_groups','COUNT(*)',array('g_is_private_club' => 1)) == 0) {
            return NULL;
        }

        require_lang('ocf');

        $info = array();
        $info['lang'] = do_lang_tempcode('CLUBS');
        $info['default'] = false;

        $info['permissions'] = array(
            array(
                'type' => 'zone',
                'zone_name' => get_module_zone('groups'),
            ),
            array(
                'type' => 'page',
                'zone_name' => get_module_zone('groups'),
                'page_name' => 'groups',
            ),
        );

        return $info;
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

        $remapped_orderer = '';
        switch ($sort) {
            case 'title':
                $remapped_orderer = 'g_name';
                break;
        }

        require_lang('ocf');

        // Calculate our where clause (search)
        $sq = build_search_submitter_clauses('g_group_leader',$author_id,$author);
        if (is_null($sq)) {
            return array();
        } else {
            $where_clause .= $sq;
        }

        $where_clause .= ' AND ';
        $where_clause .= 'g_hidden=0 AND g_is_private_club=1';

        // Calculate and perform query
        $rows = get_search_rows(null,null,$content,$boolean_search,$boolean_operator,$only_search_meta,$direction,$max,$start,$only_titles,'f_groups r',array('!' => '!','r.g_name' => 'SHORT_TRANS','r.g_title' => 'SHORT_TRANS'),$where_clause,$content_where,$remapped_orderer,'r.*');

        $out = array();
        foreach ($rows as $i => $row) {
            $out[$i]['data'] = $row;
            unset($rows[$i]);
            if (($remapped_orderer != '') && (array_key_exists($remapped_orderer,$row))) {
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
        $leader = $GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['g_group_leader']);

        require_code('ocf_groups');
        $group_name = ocf_get_group_name($row['id']);

        require_code('ocf_groups2');
        $num_members = ocf_get_group_members_raw_count($row['id'],false,false,true,false);

        $title = do_lang('CONTENT_IS_OF_TYPE',do_lang('CLUB'),$group_name);

        $summary = do_lang_tempcode(($row['g_open_membership'] == 1)?'CLUB_WITH_MEMBERS_OPEN':'CLUB_WITH_MEMBERS_APPROVAL',escape_html($group_name),escape_html(integer_format($num_members)),$leader);

        $url = build_url(array('page' => 'groups','type' => 'view','id' => $row['id']),get_module_zone('groups'));

        return do_template('SIMPLE_PREVIEW_BOX',array(
            '_GUID' => '2f7814a2e1f868d2ac73fba69f3aeee1',
            'ID' => strval($row['id']),
            'TITLE' => $title,
            'SUMMARY' => $summary,
            'URL' => $url,
        ));
    }
}

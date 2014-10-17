<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    news
 */
class Hook_rss_news
{
    /**
     * Run function for RSS hooks.
     *
     * @param  string                   A list of categories we accept from
     * @param  TIME                     Cutoff time, before which we do not show results from
     * @param  string                   Prefix that represents the template set we use
     * @set    RSS_ ATOM_
     * @param  string                   The standard format of date to use for the syndication type represented in the prefix
     * @param  integer                  The maximum number of entries to return, ordering by date
     * @return ?array                   A pair: The main syndication section, and a title (NULL: error)
     */
    public function run($_filters, $cutoff, $prefix, $date_string, $max)
    {
        if (!addon_installed('news')) {
            return null;
        }

        if (!has_actual_page_access(get_member(), 'news')) {
            return null;
        }

        $filters_1 = ocfilter_to_sqlfragment($_filters, 'p.news_category', 'news_categories', null, 'p.news_category', 'id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
        $filters_2 = ocfilter_to_sqlfragment($_filters, 'd.news_entry_category', 'news_categories', null, 'd.news_category', 'id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
        $filters = '(' . $filters_1 . ' OR ' . $filters_2 . ')';

        $privacy_join = '';
        $privacy_where = '';
        if (addon_installed('content_privacy')) {
            require_code('content_privacy');
            list($privacy_join, $privacy_where) = get_privacy_where_clause('news', 'p');
        }

        $rows = $GLOBALS['SITE_DB']->query('SELECT * FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'news p LEFT JOIN ' . get_table_prefix() . 'news_category_entries d ON d.news_entry=p.id' . $privacy_join . ' WHERE date_and_time>' . strval($cutoff) . (((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) ? ' AND validated=1 ' : '') . ' AND ' . $filters . (can_arbitrary_groupby() ? ' GROUP BY p.id' : '') . $privacy_where . ' ORDER BY date_and_time DESC', $max);
        $rows = remove_duplicate_rows($rows, 'id');
        $_categories = $GLOBALS['SITE_DB']->query_select('news_categories', array('id', 'nc_title'), array('nc_owner' => null));
        foreach ($_categories as $i => $_category) {
            $_categories[$i]['_title'] = get_translated_text($_category['nc_title']);
        }
        $categories = collapse_2d_complexity('id', '_title', $_categories);

        $content = new ocp_tempcode();
        foreach ($rows as $row) {
            if (has_category_access(get_member(), 'news', strval($row['news_category']))) {
                $id = strval($row['id']);
                $author = $row['author'];

                $news_date = date($date_string, $row['date_and_time']);
                $edit_date = is_null($row['edit_date']) ? '' : date($date_string, $row['edit_date']);

                $just_news_row = db_map_restrict($row, array('id', 'title', 'news', 'news_article'));

                $_title = get_translated_tempcode('news', $just_news_row, 'title');
                $news_title = xmlentities($_title->evaluate());
                $_summary = get_translated_tempcode('news', $just_news_row, 'news');
                if ($_summary->is_empty()) {
                    $_summary = get_translated_tempcode('news', $just_news_row, 'news_article');
                }
                $summary = xmlentities($_summary->evaluate());

                if (!is_null($row['news_article'])) {
                    $_news = get_translated_tempcode('news', $just_news_row, 'news_article');
                    if ($_news->is_empty()) {
                        $news = '';
                    } else {
                        $news = xmlentities($_news->evaluate());
                    }
                } else {
                    $news = '';
                }

                if (!array_key_exists($row['news_category'], $categories)) {
                    $categories[$row['news_category']] = get_translated_text($GLOBALS['SITE_DB']->query_select_value('news_categories', 'nc_title', array('id' => $row['news_category'])));
                }
                $category = $categories[$row['news_category']];
                $category_raw = strval($row['news_category']);

                $view_url = build_url(array('page' => 'news', 'type' => 'view', 'id' => $row['id']), get_module_zone('news'), null, false, false, true);

                if (($prefix == 'RSS_') && (get_option('is_on_comments') == '1') && ($row['allow_comments'] >= 1)) {
                    $if_comments = do_template('RSS_ENTRY_COMMENTS', array('_GUID' => 'b4f25f5cf68304f8d402bb06851489d6', 'COMMENT_URL' => $view_url, 'ID' => strval($row['id'])));
                } else {
                    $if_comments = new ocp_tempcode();
                }

                $content->attach(do_template($prefix . 'ENTRY', array('VIEW_URL' => $view_url, 'SUMMARY' => $summary, 'EDIT_DATE' => $edit_date, 'IF_COMMENTS' => $if_comments, 'TITLE' => $news_title, 'CATEGORY_RAW' => $category_raw, 'CATEGORY' => $category, 'AUTHOR' => $author, 'ID' => $id, 'NEWS' => $news, 'DATE' => $news_date)));
            }
        }

        return array($content, do_lang('NEWS'));
    }
}

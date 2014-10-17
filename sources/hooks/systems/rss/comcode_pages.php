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
 * @package    core_comcode_pages
 */
class Hook_rss_comcode_pages
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
        $filters = explode(',', $_filters);

        $content = new ocp_tempcode();
        $_rows = $GLOBALS['SITE_DB']->query_select('cached_comcode_pages', array('the_page', 'the_zone'));
        $rows = array();
        foreach ($_rows as $row) {
            $rows[$row['the_zone'] . ':' . $row['the_page']] = $row;
        }
        $_rows2 = $GLOBALS['SITE_DB']->query_select('seo_meta', array('*'), array('meta_for_type' => 'comcode_page'));
        $rows2 = array();
        foreach ($_rows2 as $row) {
            $rows2[$row['meta_for_id']] = $row;
        }
        $_rows3 = $GLOBALS['SITE_DB']->query_select('comcode_pages');
        $rows3 = array();
        foreach ($_rows3 as $row) {
            $rows3[$row['the_zone'] . ':' . $row['the_page']] = $row;
        }
        $zones = find_all_zones();
        foreach ($zones as $zone) {
            if (!has_zone_access(get_member(), $zone)) {
                continue;
            }

            if ($filters != array('')) {
                $ok = false;
                foreach ($filters as $filter) {
                    if ($zone == $filter) {
                        $ok = true;
                    }
                }
                if (!$ok) {
                    continue;
                }
            }

            $pages = find_all_pages($zone, 'comcode_custom/' . get_site_default_lang(), 'txt', false, $cutoff);
            foreach (array_keys($pages) as $i => $page) {
                if ($i == $max) {
                    break;
                }

                if (substr($page, 0, 6) == 'panel_') {
                    continue;
                }
                if (!has_page_access(get_member(), $page, $zone)) {
                    continue;
                }

                $id = $zone . ':' . $page;

                $path = get_custom_file_base() . '/' . $zone . '/pages/comcode_custom/' . get_site_default_lang() . '/' . $page . '.txt';
                $news_date = date($date_string, filectime($path));
                $edit_date = date($date_string, filemtime($path));
                if ($news_date == $edit_date) {
                    $edit_date = '';
                }

                $summary = '';
                $news = '';
                $author = '';
                $news_title = $page;
                if (array_key_exists($id, $rows)) {
                    $_news_title = get_translated_text($rows[$id]['cc_page_title'], null, null, true);
                    if (is_null($_news_title)) {
                        $_news_title = '';
                    }
                    $news_title = xmlentities($_news_title);
                }
                if (array_key_exists($id, $rows2)) {
                    $summary = xmlentities(get_translated_text($rows2[$id]['meta_description']));
                }
                if (array_key_exists($id, $rows3)) {
                    $author = $GLOBALS['FORUM_DRIVER']->get_username($rows3[$id]['p_submitter']);
                    $news_date = date($date_string, $rows3[$id]['p_add_date']);
                    $edit_date = date($date_string, $rows3[$id]['p_edit_date']);
                    if ($news_date == $edit_date) {
                        $edit_date = '';
                    }
                }
                if (is_null($author)) {
                    $author = '';
                }

                $category = '';
                $category_raw = '';

                $view_url = build_url(array('page' => $page), $zone, null, false, false, true);

                $if_comments = new ocp_tempcode();

                $content->attach(do_template($prefix . 'ENTRY', array('VIEW_URL' => $view_url, 'SUMMARY' => $summary, 'EDIT_DATE' => $edit_date, 'IF_COMMENTS' => $if_comments, 'TITLE' => $news_title, 'CATEGORY_RAW' => $category_raw, 'CATEGORY' => $category, 'AUTHOR' => $author, 'ID' => $id, 'NEWS' => $news, 'DATE' => $news_date)));
            }
        }

        require_lang('zones');
        return array($content, do_lang('COMCODE_PAGES'));
    }
}

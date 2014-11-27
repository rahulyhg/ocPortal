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
 * @package    galleries
 */

/**
 * Hook class.
 */
class Hook_whatsnew_galleries
{
    /**
     * Find selectable (filterable) categories.
     *
     * @param  TIME                     $updated_since The time that there must be entries found newer than
     * @return ?array                   Tuple of result details: HTML list of all types that can be choosed, title for selection list (null: disabled)
     */
    public function choose_categories($updated_since)
    {
        if (!addon_installed('galleries')) {
            return null;
        }

        require_lang('galleries');

        require_code('galleries');
        $cats = create_selection_list_gallery_tree(null, null, false, false, true, false, null, false, false, $updated_since);
        return array($cats, do_lang('GALLERIES'));
    }

    /**
     * Run function for newsletter hooks.
     *
     * @param  TIME                     $cutoff_time The time that the entries found must be newer than
     * @param  LANGUAGE_NAME            $lang The language the entries found must be in
     * @param  string                   $filter Category filter to apply
     * @return array                    Tuple of result details
     */
    public function run($cutoff_time, $lang, $filter)
    {
        if (!addon_installed('galleries')) {
            return array();
        }

        require_lang('galleries');

        $max = intval(get_option('max_newsletter_whatsnew'));

        $new = new Tempcode();

        $count = $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'galleries WHERE name NOT LIKE \'' . db_encode_like('download\_%') . '\'');
        if ($count < 500) {
            $_galleries = $GLOBALS['SITE_DB']->query('SELECT name,fullname FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'galleries WHERE name NOT LIKE \'' . db_encode_like('download\_%') . '\'');
            foreach ($_galleries as $i => $_gallery) {
                $_galleries[$i]['_fullname'] = get_translated_text($_gallery['fullname']);
            }
            $galleries = collapse_2d_complexity('name', '_fullname', $_galleries);
        } else {
            $galleries = array();
        }

        require_code('ocfiltering');
        $or_list = ocfilter_to_sqlfragment($filter, 'cat', null, null, null, null, false);

        $privacy_join = '';
        $privacy_where = '';
        if (addon_installed('content_privacy')) {
            require_code('content_privacy');
            list($privacy_join, $privacy_where) = get_privacy_where_clause('video', 'r', $GLOBALS['FORUM_DRIVER']->get_guest_id());
        }

        $rows = $GLOBALS['SITE_DB']->query('SELECT * FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'videos r' . $privacy_join . ' WHERE add_date>' . strval($cutoff_time) . ' AND validated=1 AND (' . $or_list . ')' . $privacy_where . ' ORDER BY add_date DESC', $max/*reasonable limit*/);

        if (count($rows) == $max) {
            return array();
        }

        foreach ($rows as $row) {
            $id = $row['id'];
            $_url = build_url(array('page' => 'galleries', 'type' => 'video', 'id' => $row['id']), get_module_zone('galleries'), null, false, false, true);
            $url = $_url->evaluate();
            if (!array_key_exists($row['cat'], $galleries)) {
                $galleries[$row['cat']] = get_translated_text($GLOBALS['SITE_DB']->query_select_value('galleries', 'fullname', array('name' => $row['cat'])));
            }
            $name = $galleries[$row['cat']];
            $_name = get_translated_text($row['title']);
            if ($_name != '') {
                $name = $_name;
            }
            $description = get_translated_text($row['description'], null, $lang);
            $member_id = (is_guest($row['submitter'])) ? null : strval($row['submitter']);
            $thumbnail = $row['thumb_url'];
            if ($thumbnail != '') {
                if (url_is_local($thumbnail)) {
                    $thumbnail = get_custom_base_url() . '/' . $thumbnail;
                }
            } else {
                $thumbnail = mixed();
            }
            $new->attach(do_template('NEWSLETTER_WHATSNEW_RESOURCE_FCOMCODE', array('_GUID' => 'dfe5850aa67c0cd00ff7d465248b87a5', 'MEMBER_ID' => $member_id, 'URL' => $url, 'NAME' => $name, 'DESCRIPTION' => $description, 'THUMBNAIL' => $thumbnail, 'CONTENT_TYPE' => 'video', 'CONTENT_ID' => strval($id))), null, false, null, '.txt', 'text');
        }

        return array($new, do_lang('GALLERIES', '', '', '', $lang));
    }
}

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

/**
 * Hook class.
 */
class Hook_members_news
{
    /**
     * Find member-related links to inject.
     *
     * @param  MEMBER                   $member_id The ID of the member we are getting link hooks for
     * @return array                    List of lists of tuples for results (by link section). Each tuple is: type,title,url
     */
    public function run($member_id)
    {
        if (!addon_installed('news')) {
            return array();
        }

        $nc_id = $GLOBALS['SITE_DB']->query_select_value_if_there('news_categories', 'id', array('nc_owner' => $member_id));
        if (!is_null($nc_id)) {
            require_lang('news');
            $modules = array();
            if (has_actual_page_access(get_member(), 'news', get_page_zone('news'))) {
                $modules[] = array('content', do_lang_tempcode('BLOG_ARCHIVE'), build_url(array('page' => 'news', 'type' => 'browse', 'id' => $nc_id, 'blog' => 1), get_module_zone('news')), 'tabs/member_account/blog');
            }
            return $modules;
        }
        return array();
    }
}

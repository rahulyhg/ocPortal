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
 * Block class.
 */
class Block_side_news_categories
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (null: block is disabled).
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
        $info['parameters'] = array();
        return $info;
    }

    /**
     * Find cacheing details for the block.
     *
     * @return ?array                   Map of cache details (cache_on and ttl) (null: block is disabled).
     */
    public function cacheing_environment()
    {
        $info = array();
        $info['cache_on'] = 'array($GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true))';
        $info['ttl'] = (get_value('no_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 24;
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    $map A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        require_lang('news');

        $cnt = $GLOBALS['SITE_DB']->query_select_value('news_categories', 'COUNT(*)', array('nc_owner' => null));
        if (($cnt > 100) && (db_has_subqueries($GLOBALS['SITE_DB']->connection_read))) {
            $categories = $GLOBALS['SITE_DB']->query('SELECT c.* FROM ' . get_table_prefix() . 'news_categories c WHERE nc_owner IS NULL AND EXISTS (SELECT * FROM ' . get_table_prefix() . 'news n WHERE n.news_category=c.id AND n.validated=1)');
        } else {
            $categories = $GLOBALS['SITE_DB']->query_select('news_categories', array('*'), array('nc_owner' => null));
        }
        $content = new Tempcode();
        $categories2 = array();
        foreach ($categories as $category) {
            if (has_category_access(get_member(), 'news', strval($category['id']))) {
                $join = ' LEFT JOIN ' . get_table_prefix() . 'news_category_entries d ON d.news_entry=p.id';
                $count = $GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM ' . get_table_prefix() . 'news p' . $join . ' WHERE validated=1 AND (news_entry_category=' . strval($category['id']) . ' OR news_category=' . strval($category['id']) . ') ORDER BY date_and_time DESC');
                if ($count > 0) {
                    $category['_nc_title'] = get_translated_text($category['nc_title']);
                    $categories2[] = $category;
                }
            }
        }
        if (count($categories2) == 0) {
            foreach ($categories as $category) {
                if (has_category_access(get_member(), 'news', strval($category['id']))) {
                    $category['_nc_title'] = get_translated_text($category['nc_title']);
                    $categories2[] = $category;
                }
            }
        }
        sort_maps_by($categories2, '_nc_title');
        foreach ($categories2 as $category) {
            $url = build_url(array('page' => 'news', 'type' => 'browse', 'id' => $category['id']), get_module_zone('news'));
            $name = $category['_nc_title'];
            $content->attach(do_template('BLOCK_SIDE_NEWS_CATEGORIES_CATEGORY', array('_GUID' => 'fee49cac370ec00fc59d2e9c66b6255a', 'URL' => $url, 'NAME' => $name, 'COUNT' => integer_format($count))));
        }
        return do_template('BLOCK_SIDE_NEWS_CATEGORIES', array('_GUID' => 'b47a0047247096373e5aa626348c4ebb', 'CONTENT' => $content, 'PRE' => '', 'POST' => ''));
    }
}

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
 * @package		forum_blocks
 */

class Block_bottom_forum_news
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
        $info['parameters'] = array('date_key','param','forum');
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
        $info['cache_on'] = 'array(array_key_exists(\'param\',$map)?$map[\'param\']:6,array_key_exists(\'forum\',$map)?$map[\'forum\']:\'Announcements\',array_key_exists(\'date_key\',$map)?$map[\'date_key\']:\'firsttime\')';
        $info['ttl'] = (get_value('no_block_timeout') === '1')?60*60*24*365*5/*5 year timeout*/:15;
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
        if (has_no_forum()) {
            return new ocp_tempcode();
        }

        $limit = array_key_exists('param',$map)?intval($map['param']):6;
        $forum_name = array_key_exists('forum',$map)?$map['forum']:do_lang('NEWS');

        $date_key = array_key_exists('date_key',$map)?$map['date_key']:'firsttime';

        $forum_ids = array();
        $forum_names = explode(',',$forum_name);
        foreach ($forum_names as $forum_name) {
            $forum_name = trim($forum_name);

            $forum_id = is_numeric($forum_name)?intval($forum_name):$GLOBALS['FORUM_DRIVER']->forum_id_from_name($forum_name);
            if ($forum_name == '<announce>') {
                $forum_id = null;
                $forum_ids[$forum_id] = $forum_name;
            } else {
                $forum_id = is_numeric($forum_name)?intval($forum_name):$GLOBALS['FORUM_DRIVER']->forum_id_from_name($forum_name);
            }
            if (!is_null($forum_id)) {
                $forum_ids[$forum_id] = $forum_name;
            }
        }

        if (!has_no_forum()) {
            $max_rows = 0;
            $topics = $GLOBALS['FORUM_DRIVER']->show_forum_topics($forum_ids,$limit,0,$max_rows,'',false,$date_key);

            $out = new ocp_tempcode();
            $_postdetailss = array();
            if (!is_null($topics)) {
                sort_maps_by($topics,$date_key);
                $topics = array_reverse($topics,false);

                foreach ($topics as $topic) {
                    $topic_url = $GLOBALS['FORUM_DRIVER']->topic_url($topic['id'],$forum_name,true);
                    $title = $topic['title'];
                    $date = get_timezoned_date($topic[$date_key],false);

                    $_postdetailss[] = array('DATE' => $date,'FULL_URL' => $topic_url,'NEWS_TITLE' => escape_html($title));
                }
            }

            return do_template('BLOCK_BOTTOM_NEWS',array('_GUID' => '04d5390309dcba1f17391e9928da0d56','POSTS' => $_postdetailss));
        } else {
            return new ocp_tempcode();
        }
    }
}

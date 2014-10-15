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
 * @package		chat
 */

class Hook_rss_chat
{
    /**
	 * Run function for RSS hooks.
	 *
	 * @param  string			A list of categories we accept from
	 * @param  TIME			Cutoff time, before which we do not show results from
	 * @param  string			Prefix that represents the template set we use
	 * @set    RSS_ ATOM_
	 * @param  string			The standard format of date to use for the syndication type represented in the prefix
	 * @param  integer		The maximum number of entries to return, ordering by date
	 * @return ?array			A pair: The main syndication section, and a title (NULL: error)
	 */
    public function run($_filters,$cutoff,$prefix,$date_string,$max)
    {
        if (!addon_installed('chat')) {
            return NULL;
        }

        if (!has_actual_page_access(get_member(),'chat')) {
            return NULL;
        }

        $filters = ocfilter_to_sqlfragment($_filters,'room_id','chat_rooms',null,'room_id','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)

        require_code('chat');

        $rows = $GLOBALS['SITE_DB']->query('SELECT m.* FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'chat_messages m LEFT JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'chat_rooms r ON r.id=m.room_id WHERE r.is_im=0 AND date_and_time>' . strval(time()-$cutoff) . ' AND ' . $filters . ' ORDER BY date_and_time DESC',$max);
        $count = $GLOBALS['SITE_DB']->query_select_value('chat_rooms','COUNT(*)',array('is_im' => 0));
        $categories = array();
        if ($count<100) {
            $_categories = $GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('is_im' => 0));
            foreach ($_categories as $category) {
                $categories[$category['id']] = $category;
            }
        }

        $content = new ocp_tempcode();
        foreach ($rows as $row) {
            if ((!array_key_exists($row['room_id'],$categories)) && ($count >= 100)) {
                $_categories = $GLOBALS['SITE_DB']->query_select('chat_rooms',array('*'),array('id' => $row['room_id']),'',1);
                if (array_key_exists(0,$_categories)) {
                    $categories[$row['room_id']] = $_categories[0];
                }
            }

            if (!array_key_exists($row['room_id'],$categories)) {
                continue;
            } // Message is in deleted room (although should not exist in DB anymore!)

            if (check_chatroom_access($categories[$row['room_id']],true)) {
                $id = strval($row['id']);
                $author = $GLOBALS['FORUM_DRIVER']->get_username($row['member_id']);
                if (is_null($author)) {
                    $author = '';
                }

                $news_date = date($date_string,$row['date_and_time']);
                $edit_date = '';

                $just_message_row = db_map_restrict($row,array('id','the_message'));

                $_title = get_translated_tempcode('chat_messages',$just_message_row,'the_message');
                $news_title = xmlentities($_title->evaluate());
                $summary = '';

                $news = '';

                $category = $categories[$row['room_id']]['room_name'];
                $category_raw = strval($row['room_id']);

                $view_url = build_url(array('page' => 'chat','type' => 'room','id' => $row['room_id']),get_module_zone('chat'),null,false,false,true);

                $if_comments = new ocp_tempcode();

                $content->attach(do_template($prefix . 'ENTRY',array('VIEW_URL' => $view_url,'SUMMARY' => $summary,'EDIT_DATE' => $edit_date,'IF_COMMENTS' => $if_comments,'TITLE' => $news_title,'CATEGORY_RAW' => $category_raw,'CATEGORY' => $category,'AUTHOR' => $author,'ID' => $id,'NEWS' => $news,'DATE' => $news_date)));
            }
        }

        require_lang('chat');
        return array($content,do_lang('MESSAGES'));
    }
}

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
 * @package    staff_messaging
 */
class Hook_checklist_messaging
{
    /**
     * Find items to include on the staff checklist.
     *
     * @return array                    An array of tuples: The task row to show, the number of seconds until it is due (or NULL if not on a timer), the number of things to sort out (or NULL if not on a queue), The name of the config option that controls the schedule (or NULL if no option).
     */
    public function run()
    {
        if (!addon_installed('staff_messaging')) {
            return array();
        }

        require_lang('messaging');

        $outstanding = 0;

        $forum = get_option('messaging_forum_name');

        $max_rows = 0;
        $rows = $GLOBALS['FORUM_DRIVER']->show_forum_topics(get_option('messaging_forum_name'), 100, 0, $max_rows);
        if (!is_null($rows)) {
            foreach ($rows as $i => $row) {
                $looking_at = $row['title'];
                if ($row['description'] != '') {
                    $looking_at = $row['description'];
                }
                $id = substr($looking_at, strrpos($looking_at, '_') + 1);
                $message_type = substr($looking_at, strpos($looking_at, '#') + 1, strrpos($looking_at, '_') - strpos($looking_at, '#') - 1);
                if ($message_type == '') {
                    continue;
                }

                $outstanding++;

                $count = 0;
                $_comments = $GLOBALS['FORUM_DRIVER']->get_forum_topic_posts($GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier($forum, $message_type . '_' . $id), $count, 100, 0, false);
                if ((is_array($_comments)) && (array_key_exists(0, $_comments))) {
                    $message_title = $_comments[0]['title'];
                    $message = $_comments[0]['message'];

                    foreach ($_comments as $i2 => $comment) {
                        if (is_object($comment['message'])) {
                            $comment['message'] = $comment['message']->evaluate();
                        }
                        if (substr($comment['message'], 0, strlen(do_lang('AUTO_SPACER_STUB'))) == do_lang('AUTO_SPACER_STUB')) {
                            $matches = array();
                            if (preg_match('#' . str_replace('\\{1\\}', '(.+)', preg_quote(do_lang('AUTO_SPACER_TAKE_RESPONSIBILITY'))) . '#', $comment['message'], $matches) != 0) {
                                $outstanding--;
                                continue 2;
                            }
                        }
                    }
                }
            }
        }

        if ($outstanding > 0) {
            $status = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_0', array('_GUID' => 'x578142633c6f3d37776e82a869deb91'));
        } else {
            $status = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_1', array('_GUID' => 'u578142633c6f3d37776e82a869deb91'));
        }

        $url = build_url(array('page' => 'admin_messaging', 'type' => 'misc'), get_module_zone('admin_messaging'));

        $tpl = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM', array('_GUID' => '10cf866e2ea104ac41685a8756e182f8', 'URL' => $url, 'STATUS' => $status, 'TASK' => do_lang_tempcode('CONTACT_US_MESSAGING'), 'INFO' => do_lang_tempcode('NUM_QUEUE', escape_html(integer_format($outstanding)))));
        return array(array($tpl, null, $outstanding, null));
    }
}

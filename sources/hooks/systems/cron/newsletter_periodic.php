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
 * @package    newsletter
 */
class Hook_cron_newsletter_periodic
{
    /**
     * Run function for CRON hooks. Searches for tasks to perform.
     */
    public function run()
    {
        // This hook looks for a 'periodic newsletter', which is a 'new content'
        // newsletter that should be sent out automatically.

        // Grab the details of this periodic newsletter
        $periodic_rows = $GLOBALS['SITE_DB']->query_select('newsletter_periodic', array('*'));
        foreach ($periodic_rows as $periodic_row) {
            $last_sent = $this->newsletter_periodic_handle($periodic_row);
            if (!is_null($last_sent)) { // was sent, so update with new time
                $GLOBALS['SITE_DB']->query_update('newsletter_periodic', array('np_last_sent' => $last_sent), array('id' => $periodic_row['id']), '', 1);
                break; // Limited to 1 because we use global variables to store what we're sending, so can only do one per request
            }
        }
    }

    /**
     * Send a periodic newsletter.
     *
     * @param  array                    Details of periodic newsletter
     * @return ?TIME                    Time was sent (NULL: not sent)
     */
    public function newsletter_periodic_handle($periodic_row)
    {
        // If we're here then we have a periodic newsletter along with details of
        // what we should put in it, who it should go to and when it should be
        // sent.

        // We check here to see if we're scheduled to be sent out
        $last_sent = $periodic_row['np_last_sent'];

        // At the moment we only support weekly or biweekly or monthly intervals. Thus we can
        // say for sure that if the last issue was sent in the past 4 days, we
        // don't need to run. This is useful because it stops the code sending out
        // multiple issues all day, and because we may as well extend it a few
        // days either side so that we bail out more quickly.
        if (abs(time() - $last_sent) < 60 * 60 * 24 * 4) {
            return null;
        }

        if ($periodic_row['np_frequency'] == 'monthly') {
            // Find out what day of the month it is
            $today = date('j');

            // Are we meant to be sending out an issue today?
            if (intval($today) != $periodic_row['np_day']) {
                return null;
            }        // No, we're not
        } elseif ($periodic_row['np_frequency'] == 'weekly' || $periodic_row['np_frequency'] == 'biweekly') {
            // Find out what day of the week it is
            $weekdays = array('Error', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            $send_day = $weekdays[$periodic_row['np_day']];
            $today = date('l');

            // Are we meant to be sending out an issue today?
            if ($today != $send_day) {
                return null;
            }        // No, we're not
            if (($periodic_row['np_frequency'] == 'biweekly') && (abs(time() - $last_sent) < 60 * 60 * 24 * 14)) {
                return null;
            }
        } else {
            // If we don't know when to send it then we bail out
            return null;
        }

        // If we're here then we need to create and send out a newsletter.

        // We include everything since the last "What's New" newsletter,
        // irregardless of whether it was automatically or manually generated.
        $cutoff_time = $periodic_row['np_last_sent'];

        require_lang('newsletter');
        $lang = $periodic_row['np_lang'];

        $in_full = ($periodic_row['np_in_full'] == 1);

        // We need to build the content, based on the chosen categories. This code
        // is lifted straight out of admin_newsletter.php

        // Generate Comcode for content selected, drawing on hooks
        $automatic = array();
        $i = 0;
        $contentarr = explode("\n", $periodic_row['np_message']);
        $_hooks = find_all_hooks('modules', 'admin_newsletter');
        foreach (array_keys($_hooks) as $hook) {
            require_code('hooks/modules/admin_newsletter/' . filter_naughty_harsh($hook));
            $object = object_factory('Hook_whats_news_' . filter_naughty_harsh($hook), true);
            if (is_null($object)) {
                continue;
            }
            $found_one_match = false;
            $last_find_id = mixed();
            $last_cat_id = mixed();
            $filter = '';
            foreach ($contentarr as $find_id => $line) {
                $matches = array();
                if (preg_match('#\[' . preg_quote($hook, '#') . '/(.*)\]#', $line, $matches) != 0) {
                    $found_one_match = true;

                    if ((!is_null($last_find_id)) && (($find_id != $last_find_id + 1))) {
                        $last_cat_id = intval($matches[1]);

                        $temp = $object->run(intval($cutoff_time), $lang, $filter, $in_full);
                        if ((is_null($temp)) || (count($temp) == 0)) {
                            continue;
                        }
                        if (!$temp[0]->is_empty()) {
                            $automatic[$last_find_id] = do_template('NEWSLETTER_AUTOMATE_SECTION_FCOMCODE', array('_GUID' => '88e90623e3ae6c58222010a8a1d50965', 'I' => strval($i + 1), 'TITLE' => $temp[1], 'CONTENT' => $temp[0]));
                            $i++;
                        }

                        $filter = $matches[1];
                    } else {
                        if ($filter != '') {
                            $filter .= ',';
                        }
                        $filter .= $matches[1];
                    }

                    $last_find_id = $find_id;
                }
            }
            if (!$found_one_match) {
                $found = false;
                foreach ($contentarr as $find_id => $line) {
                    if (strpos($line, '[' . $hook . ']') !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    continue;
                }

                $temp = $object->run(intval($cutoff_time), $lang, $filter, $in_full);
                if ((is_null($temp)) || (count($temp) == 0)) {
                    continue;
                }
                if (!$temp[0]->is_empty()) {
                    $automatic[$find_id] = do_template('NEWSLETTER_AUTOMATE_SECTION_FCOMCODE', array('_GUID' => '0a24698f1072c0e3e7d6f5ec65825ab6', 'I' => strval($i + 1), 'TITLE' => $temp[1], 'CONTENT' => $temp[0]));
                    $i++;
                }
            } elseif ($filter != '') {
                $temp = $object->run(intval($cutoff_time), $lang, $filter, $in_full);
                if ((is_null($temp)) || (count($temp) == 0)) {
                    continue;
                }
                if (!$temp[0]->is_empty()) {
                    $automatic[$last_find_id] = do_template('NEWSLETTER_AUTOMATE_SECTION_FCOMCODE', array('_GUID' => 'a20519cc04ba5e98c1c6fc05a39d86a5', 'I' => strval($i + 1), 'TITLE' => $temp[1], 'CONTENT' => $temp[0]));
                    $i++;
                }
            }
        }
        ksort($automatic);
        $_automatic = new ocp_tempcode();
        if (count($automatic) == 0) {
            return null;
        } // Nothing new
        foreach ($automatic as $tp) {
            $_automatic->attach($tp);
        }
        $completed = do_template('NEWSLETTER_AUTOMATED_FCOMCODE', array('_GUID' => 'b8897fb43a341f6b7058ea6125630f5a', 'CONTENT' => $_automatic));

        // Now we have the contents of our newsletter, we can send it to all of
        // those listed in the newsletter_periodic row
        $message = $completed->evaluate($lang);
        $subject = $periodic_row['np_subject'] . '-' . get_timezoned_date(time(), false, false, false, true);

        $time = time();

        require_code('newsletter');
        actual_send_newsletter($message, $subject, $lang, unserialize($periodic_row['np_send_details']), $periodic_row['np_html_only'], $periodic_row['np_from_email'], $periodic_row['np_from_name'], $periodic_row['np_priority'], $periodic_row['np_csv_data'], $periodic_row['np_template']);

        return $time;
    }
}

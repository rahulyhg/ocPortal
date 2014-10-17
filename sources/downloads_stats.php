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
 * @package    downloads
 */

/**
 * Get the currently used download bandwidth.
 *
 * @return integer                      The currently used download bandwidth (forever)
 */
function get_download_bandwidth()
{
    $value = intval(get_value_newer_than('download_bandwidth', time() - 60 * 60 * 24));

    if ($value == 0) {
        $total = $GLOBALS['SITE_DB']->query_select_value_if_there('download_downloads', 'COUNT(*)', array('validated' => 1));
        if ($total > 200) { // Fast but won't work on some databases
            $value = $GLOBALS['SITE_DB']->query_select_value_if_there('download_downloads', 'SUM(file_size*num_downloads)', array('validated' => 1));
        } else {
            $value = 0;

            $rows = $GLOBALS['SITE_DB']->query_select('download_downloads', array('url', 'num_downloads'), array('validated' => 1));
            foreach ($rows as $myrow) {
                if (url_is_local($myrow['url'])) {
                    $file = get_custom_file_base() . '/' . rawurldecode($myrow['url']);
                    if (file_exists($file)) {
                        $value += filesize($file) * $myrow['num_downloads'];
                    }
                }
            }
            if (!$GLOBALS['SITE_DB']->table_is_locked('values')) {
                set_value('download_bandwidth', strval($value));
            }
        }
    }

    return $value;
}

/**
 * Get the total size of all the currently available downloads in a formatted string.
 *
 * @return string                       The total size of all the currently available downloads
 */
function get_download_archive_size()
{
    $value = intval(get_value_newer_than('archive_size', time() - 60 * 60 * 24));
    if ($value == 0) {
        $value = $GLOBALS['SITE_DB']->query_select_value_if_there('download_downloads', 'SUM(file_size)', array('validated' => 1));
        if (!(intval($value) > 0)) {
            $value = 0;
        }
        if (!$GLOBALS['SITE_DB']->table_is_locked('values')) {
            set_value('archive_size', strval($value));
        }
    }

    return clean_file_size($value);
}

/**
 * Get the total number of downloads available.
 *
 * @return integer                      The total number of downloads available
 */
function get_num_archive_downloads()
{
    $value = intval(get_value_newer_than('num_archive_downloads', time() - 60 * 60 * 24));

    if ($value == 0) {
        $value = $GLOBALS['SITE_DB']->query_select_value_if_there('download_downloads', 'COUNT(*)', array('validated' => 1));
        if (!(intval($value) > 0)) {
            $value = 0;
        }
        if (!$GLOBALS['SITE_DB']->table_is_locked('values')) {
            set_value('num_archive_downloads', strval($value));
        }
    }

    return $value;
}

/**
 * Get the total number of files downloaded since installation.
 *
 * @return integer                      The total number of files downloaded since installation
 */
function get_num_downloads_downloaded()
{
    $value = intval(get_value_newer_than('num_downloads_downloaded', time() - 60 * 60 * 24));

    if ($value == 0) {
        $value = $GLOBALS['SITE_DB']->query_select_value_if_there('download_downloads', 'SUM(num_downloads)', array('validated' => 1));
        if (!(intval($value) > 0)) {
            $value = 0;
        }
        if (!$GLOBALS['SITE_DB']->table_is_locked('values')) {
            set_value('num_downloads_downloaded', strval($value));
        }
    }

    return $value;
}

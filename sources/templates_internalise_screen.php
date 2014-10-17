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
 * @package    core_abstract_interfaces
 */

/**
 * Put the contents of a screen inside an AJAX updatable area. This is typically used when a page is being used to traverse a result-set that spans multiple screens.
 *
 * @param  tempcode                     The screen content
 * @param  ?integer                     The time between refreshes (NULL: do not refresh)
 * @param  ?mixed                       Data. A refresh will only happen if an AJAX-check indicates this data has changed (NULL: no check)
 * @return tempcode                     The screen output, wrapped with some AJAX code
 */
function internalise_own_screen($screen_content, $refresh_time = null, $refresh_if_changed = null)
{
    if (!has_js()) {
        return $screen_content;
    } // We need JS to make this a seamless process
    if (!is_null(get_bot_type())) {
        return $screen_content;
    }

    require_javascript('javascript_ajax');
    require_javascript('javascript_internalised_ajax_screen');

    $params = '';
    foreach ($_GET as $key => $param) {
        if (!is_string($param)) {
            continue;
        }
        if (($key == 'ajax') || ($key == 'zone') || ($key == 'utheme')) {
            continue;
        }
        if ((substr($key, 0, 5) == 'keep_') && (skippable_keep($key, $param))) {
            continue;
        }
        if (get_magic_quotes_gpc()) {
            $param = stripslashes($param);
        }
        $params .= (($params == '') ? '?' : '&') . $key . '=' . urlencode($param);
    }
    $params .= (($params == '') ? '?' : '&') . 'ajax=1';
    if (get_param('utheme', '') != '') {
        $params .= '&utheme=' . urlencode(get_param('utheme', $GLOBALS['FORUM_DRIVER']->get_theme()));
    }
    $params .= '&zone=' . urlencode(get_zone_name());

    $url = find_script('iframe') . $params;

    if (!is_null($refresh_if_changed)) {
        require_javascript('javascript_sound');
        $change_detection_url = find_script('change_detection') . $params;
    } else {
        $refresh_if_changed = '';
        $change_detection_url = '';
    }

    return do_template('INTERNALISED_AJAX_SCREEN', array(
        '_GUID' => '06554eb227428fd5c648dee3c5b38185',
        'SCREEN_CONTENT' => $screen_content,
        'REFRESH_IF_CHANGED' => md5(serialize($refresh_if_changed)),
        'CHANGE_DETECTION_URL' => $change_detection_url,
        'URL' => $url,
        'REFRESH_TIME' => is_null($refresh_time) ? '' : strval($refresh_time),
    ));
}

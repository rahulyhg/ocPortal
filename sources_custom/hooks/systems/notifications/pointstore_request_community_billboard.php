<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		community_billboard
 */

class Hook_Notification_pointstore_request_community_billboard extends Hook_Notification__Staff
{
    /**
	 * Find the initial setting that members have for a notification code (only applies to the member_could_potentially_enable members).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @return integer		Initial setting
	 */
    public function get_initial_setting($notification_code,$category = null)
    {
        return A_NA;
    }

    /**
	 * Get a list of all the notification codes this hook can handle.
	 * (Addons can define hooks that handle whole sets of codes, so hooks are written so they can take wide authority)
	 *
	 * @return array			List of codes (mapping between code names, and a pair: section and labelling for those codes)
	 */
    public function list_handled_codes()
    {
        $list = array();
        $list['pointstore_request_community_billboard'] = array(do_lang('pointstore:POINTSTORE'),do_lang('pointstore:NOTIFICATION_TYPE_pointstore_request_community_billboard'));
        return $list;
    }
}

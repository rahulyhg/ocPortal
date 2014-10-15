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
 * @package		ecommerce
 */

class Hook_Notification_ip_address_sharing extends Hook_Notification__Staff
{
    /**
	 * Get a list of all the notification codes this hook can handle.
	 * (Addons can define hooks that handle whole sets of codes, so hooks are written so they can take wide authority)
	 *
	 * @return array			List of codes (mapping between code names, and a pair: section and labelling for those codes)
	 */
    public function list_handled_codes()
    {
        $limit = get_option('max_ip_addresses_per_subscriber');
        if ($limit == '') {
            return array();
        }

        if (get_forum_type() != 'ocf') {
            return array();
        }
        if (!addon_installed('stats')) {
            return array();
        }
        if (is_ocf_satellite_site()) {
            return array();
        }
        if (!db_has_subqueries($GLOBALS['SITE_DB']->connection_write)) {
            return array();
        }

        $list = array();
        $list['ip_address_sharing'] = array(do_lang('MEMBERS'),do_lang('ecommerce:NOTIFICATION_TYPE_ip_address_sharing'));
        return $list;
    }
}

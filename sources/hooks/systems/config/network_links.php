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
 * @package		msn
 */

class Hook_config_network_links
{
    /**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
    public function get_details()
    {
        return array(
            'human_name' => 'NETWORK_LINKS',
            'type' => 'line',
            'category' => 'SITE',
            'group' => 'MULTI_SITE_NETWORKING',
            'explanation' => 'CONFIG_OPTION_network_links',
            'shared_hosting_restricted' => '1',
            'list_options' => '',

            'addon' => 'msn',
        );
    }

    /**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
    public function get_default()
    {
        return get_base_url() . '/netlink.php';
    }
}

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
 * @package		ldap
 */

class Hook_config_ldap_group_class
{
    /**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
    public function get_details()
    {
        return array(
            'human_name' => 'LDAP_GROUP_CLASS',
            'type' => 'line',
            'category' => 'USERS',
            'group' => 'LDAP',
            'explanation' => 'CONFIG_OPTION_ldap_group_class',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 5,

            'addon' => 'ldap',
        );
    }

    /**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
    public function get_default()
    {
        return (get_option('ldap_is_windows') == '1')?'group':'posixGroup';
    }
}

<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		facebook
 */

class Hook_config_facebook_auto_syndicate
{
    /**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
    public function get_details()
    {
        return array(
            'human_name' => 'FACEBOOK_AUTO_SYNDICATE',
            'type' => 'tick',
            'category' => 'USERS',
            'group' => 'FACEBOOK_SYNDICATION',
            'explanation' => 'CONFIG_OPTION_facebook_auto_syndicate',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 5,

            'addon' => 'facebook',
        );
    }

    /**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
    public function get_default()
    {
        return '0';
    }
}

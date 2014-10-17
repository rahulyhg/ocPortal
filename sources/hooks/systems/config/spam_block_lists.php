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
 * @package    core_configuration
 */
class Hook_config_spam_block_lists
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (NULL: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'SPAM_BLOCK_LISTS',
            'type' => 'line',
            'category' => 'SECURITY',
            'group' => 'SPAMMER_DETECTION',
            'explanation' => 'CONFIG_OPTION_spam_block_lists',
            'shared_hosting_restricted' => '0',
            'list_options' => '',
            'order_in_category_group' => 4,

            'addon' => 'core_configuration',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (NULL: option is disabled)
     */
    public function get_default()
    {
        return ''; // Not listing "*.opm.tornevall.org" by default, because it keeps IPs for over 365 days, which is okay for blocking e-mail servers/proxies, but not for normal dynamic web IPs
    }
}

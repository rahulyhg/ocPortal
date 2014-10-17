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
 * @package    ocf_forum
 */
class Hook_config_is_on_topic_emoticons
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (NULL: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'IS_ON_TOPIC_EMOTICONS',
            'type' => 'tick',
            'category' => 'FORUMS',
            'group' => 'FORUM_TOPICS',
            'explanation' => 'CONFIG_OPTION_is_on_topic_emoticons',
            'shared_hosting_restricted' => '0',
            'list_options' => '',

            'addon' => 'ocf_forum',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (NULL: option is disabled)
     */
    public function get_default()
    {
        return '1';
    }
}

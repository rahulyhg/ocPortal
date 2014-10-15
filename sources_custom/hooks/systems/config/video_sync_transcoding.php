<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		gallery_syndication
 */

class Hook_config_video_sync_transcoding
{
    /**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
    public function get_details()
    {
        return array(
            'human_name' => 'VIDEO_SYNC_TRANSCODING',
            'type' => 'special',
            'category' => 'GALLERY',
            'group' => 'GALLERY_SYNDICATION',
            'explanation' => 'CONFIG_OPTION_video_sync_transcoding',
            'shared_hosting_restricted' => '0',
            'list_options' => '',

            'addon' => 'gallery_syndication',
        );
    }

    /**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
    public function get_default()
    {
        require_lang('gallery_syndication');
        return do_lang('SYND_LOCAL',null,null,null,fallback_lang());
    }

    /**
	 * Field inputter (because the_type=special).
	 *
	 * @param  ID_TEXT		The config option name
	 * @param  array			The config row
	 * @param  tempcode		The field title
	 * @param  tempcode		The field description
	 * @return tempcode		The inputter
	 */
    public function field_inputter($name,$myrow,$human_name,$explanation)
    {
        $list = '';
        $list .= static_evaluate_tempcode(form_input_list_entry(do_lang('OTHER',null,null,null,fallback_lang())));

        $hooks = find_all_hooks('modules','video_syndication');
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/modules/video_syndication/' . filter_naughty($hook));
            $ob = object_factory('video_syndication_' . filter_naughty($hook));
            $label = $ob->get_service_title();

            $list .= static_evaluate_tempcode(form_input_list_entry($hook,$hook == get_option($name),$label));
        }

        return form_input_list($human_name,$explanation,'video_sync_transcoding',make_string_tempcode($list));
    }
}

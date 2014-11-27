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
 * @package    custom_comcode
 */

/**
 * Hook class.
 */
class Hook_main_custom_gfx_text_overlay
{
    /**
     * Standard graphic generator function. Creates custom graphics from parameters.
     *
     * @param  array                    $map Map of hook parameters (relayed from block parameters map).
     * @param  object                    &$block The block itself (contains utility methods).
     * @return tempcode                 HTML to output.
     */
    public function run($map, &$block)
    {
        if ((!function_exists('imagettftext')) || (!array_key_exists('FreeType Support', gd_info())) || (@imagettfbbox(26.0, 0.0, get_file_base() . '/data/fonts/Vera.ttf', 'test') === false)) {
            return do_lang_tempcode('REQUIRES_TTF');
        }

        if (!array_key_exists('img', $map)) {
            $map['img'] = 'button1';
        }
        $img_path = find_theme_image($map['img'], true, true);
        if ($img_path == '') {
            return do_lang_tempcode('NO_SUCH_THEME_IMAGE', $map['img']);
        }

        $cache_id = 'text_overlay_' . md5(serialize($map));
        $url = $block->_do_image($cache_id, $map, $img_path);
        if (is_object($url)) {
            return $url;
        }

        $ret = '<img class="gfx_text_overlay" alt="' . str_replace("\n", ' ', escape_html($map['data'])) . '" src="' . escape_html($url) . '" />';

        if (function_exists('ocp_mark_as_escaped')) {
            ocp_mark_as_escaped($ret);
        }
        return make_string_tempcode($ret);
    }
}

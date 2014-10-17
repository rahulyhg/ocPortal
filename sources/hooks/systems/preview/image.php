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
 * @package    galleries
 */
class Hook_Preview_image
{
    /**
     * Find whether this preview hook applies.
     *
     * @return array                    Triplet: Whether it applies, the attachment ID type, whether the forum DB is used [optional]
     */
    public function applies()
    {
        $applies = (get_param('page', '') == 'cms_galleries') && ((get_param('type') == '_ed') || (get_param('type') == 'ad'));
        return array($applies, null, false);
    }

    /**
     * Run function for preview hooks.
     *
     * @return array                    A pair: The preview, the updated post Comcode
     */
    public function run()
    {
        require_code('uploads');

        $cat = post_param('cat');

        $urls = get_url('url', 'file', 'uploads/galleries', 0, OCP_UPLOAD_IMAGE, true, '', 'file2');
        if ($urls[0] == '') {
            if (!is_null(post_param_integer('id', null))) {
                $rows = $GLOBALS['SITE_DB']->query_select('images', array('url', 'thumb_url'), array('id' => post_param_integer('id')), '', 1);
                $urls = $rows[0];

                $url = $urls['url'];
                $thumb_url = $urls['thumb_url'];
            } else {
                warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN_UPLOAD'));
            }
        } else {
            $url = $urls[0];
            $thumb_url = $urls[1];
        }

        require_code('images');
        $thumb = do_image_thumb(url_is_local($thumb_url) ? (get_custom_base_url() . '/' . $thumb_url) : $thumb_url, post_param('description'), true);
        $preview = hyperlink(url_is_local($url) ? (get_custom_base_url() . '/' . $url) : $url, $thumb);

        return array($preview, null);
    }
}

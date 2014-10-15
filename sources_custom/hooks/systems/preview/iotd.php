<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		iotds
 */

class Hook_Preview_iotd
{
    /**
	 * Find whether this preview hook applies.
	 *
	 * @return array			Triplet: Whether it applies, the attachment ID type, whether the forum DB is used [optional]
	 */
    public function applies()
    {
        require_code('uploads');
        $applies = (get_param('page','') == 'cms_iotds') && ((get_param('type') == '_ed') || (get_param('type') == 'ad')) && ((is_plupload()) || (count($_FILES) != 0));
        return array($applies,null,false);
    }

    /**
	 * Run function for preview hooks.
	 *
	 * @return array			A pair: The preview, the updated post Comcode
	 */
    public function run()
    {
        require_code('uploads');
        require_code('images');

        $urls = get_url('','file','uploads/iotds',0,OCP_UPLOAD_IMAGE,true,'','file2');
        if ($urls[0] == '') {
            if (!is_null(post_param_integer('id',null))) {
                $rows = $GLOBALS['SITE_DB']->query_select('iotds',array('url','thumb_url'),array('id' => post_param_integer('id')),'',1);
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

        $thumb_url = url_is_local($thumb_url)?(get_custom_base_url() . '/' . $thumb_url):$thumb_url;
        $url = url_is_local($url)?(get_custom_base_url() . '/' . $url):$url;
        $thumb = do_image_thumb($thumb_url,'');

        $choose_url = mixed();
        $delete_url = mixed();
        $edit_url = mixed();

        $title = comcode_to_tempcode(post_param('title',''));
        $caption = comcode_to_tempcode(post_param('caption',''));
        $date = get_timezoned_date(time());

        $submitter = get_member();
        $username = $GLOBALS['FORUM_DRIVER']->get_username($submitter);

        $view_url = mixed();

        $preview = do_template('IOTD_BOX',array(
            '_GUID' => 'a6479902d2cd7b4119be7159147e0a0b',
            'VIEWS' => '',
            'THUMB' => $thumb,
            'DATE' => $date,
            'DATE_RAW' => strval(time()),
            'IS_CURRENT' => 0,
            'THUMB_URL' => $thumb_url,
            'VIEW_URL' => $view_url,
            'ID' => '',
            'EDIT_URL' => $edit_url,
            'DELETE_URL' => $delete_url,
            'CHOOSE_URL' => $choose_url,
            'TITLE' => $title,
            'CAPTION' => $caption,
            'SUBMITTER' => strval($submitter),
            'USERNAME' => $username,
            'GIVE_CONTEXT' => true,
        ));

        return array($preview,null);
    }
}

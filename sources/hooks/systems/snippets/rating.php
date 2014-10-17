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
 * @package    core_feedback_features
 */
class Hook_rating
{
    /**
     * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
     *
     * @return tempcode                 The snippet
     */
    public function run()
    {
        if (get_option('is_on_rating') == '0') {
            return do_lang_tempcode('INTERNAL_ERROR');
        }

        // Has there actually been any rating?
        if ((strtoupper(ocp_srv('REQUEST_METHOD')) == 'POST') || (ocp_srv('HTTP_REFERER') == '')) { // Code branch if this is a post request. Allow rating to not be given (= unrate). Has to check is post request to stop CSRF
            $rating = either_param_integer('rating', null);
        } else {
            $rating = post_param_integer('rating'); // Will fail
        }
        $content_type = get_param('content_type');
        $type = get_param('type', '');
        $content_id = get_param('id');

        $content_url = get_param('content_url', '', true);
        $content_title = get_param('content_title', '', true);

        require_code('feedback');
        actualise_specific_rating($rating, get_page_name(), get_member(), $content_type, $type, $content_id, $content_url, $content_title);

        actualise_give_rating_points();

        $template = get_param('template', null);
        if ($template !== '') {
            if (is_null($template)) {
                $template = 'RATING_BOX';
            }
            return display_rating($content_url, $content_title, $content_type, $content_id, $template);
        }

        return do_lang_tempcode('THANKYOU_FOR_RATING_SHORT');
    }
}

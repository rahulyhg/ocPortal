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
 * @package    core_comcode_pages
 */
class Hook_Preview_comcode_page
{
    /**
     * Find whether this preview hook applies.
     *
     * @return array                    Quartet: Whether it applies, the attachment ID type, whether the forum DB is used [optional], list of fields to limit to [optional]
     */
    public function applies()
    {
        $applies = (get_param('page', '') == 'cms_comcode_pages');
        return array($applies, 'comcode_page', false, array('post'));
    }

    /**
     * Run function for preview hooks.
     *
     * @return array                    A pair: The preview, the updated post Comcode
     */
    public function run()
    {
        $codename = post_param('file');
        $zone = post_param('zone');

        $original_comcode = post_param('post');

        $posting_ref_id = post_param_integer('posting_ref_id', mt_rand(0, 100000));
        $post_bits = do_comcode_attachments($original_comcode, 'comcode_page', strval(-$posting_ref_id), true, $GLOBALS['SITE_DB']);
        $post_comcode = $post_bits['comcode'];
        $post_html = $post_bits['tempcode'];

        $output = do_template('COMCODE_PAGE_SCREEN', array(
            'IS_PANEL' => (substr($codename, 0, 6) == 'panel_'),
            'BEING_INCLUDED' => false,
            'SUBMITTER' => strval(get_member()),
            'TAGS' => '',
            'WARNING_DETAILS' => '',
            'EDIT_DATE_RAW' => strval(time()),
            'SHOW_AS_EDIT' => (get_param_integer('show_as_edit', 0) == 1),
            'CONTENT' => $post_html,
            'EDIT_URL' => '',
            'ADD_CHILD_URL' => '',
            'NAME' => $codename,
            'ZONE' => $zone,
        ));

        return array($output, $post_comcode);
    }
}

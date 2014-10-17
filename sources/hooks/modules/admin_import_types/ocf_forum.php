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
class Hook_admin_import_types_ocf_forum
{
    /**
     * Get a map of valid import types.
     *
     * @return array                    A map from codename to the language string that names them to the user.
     */
    public function run()
    {
        return array(
            'ocf_post_history' => 'POST_HISTORY',
            'ocf_post_templates' => 'POST_TEMPLATES',
            'ocf_announcements' => 'ANNOUNCEMENTS',
            'ocf_forum_groupings' => 'MODULE_TRANS_NAME_admin_ocf_forum_groupings',
            'ocf_forums' => 'SECTION_FORUMS',
            'ocf_topics' => 'FORUM_TOPICS',
            'ocf_polls_and_votes' => 'TOPIC_POLLS',
            'ocf_posts' => 'FORUM_POSTS',
            'ocf_post_files' => 'POST_FILES',
            'ocf_multi_moderations' => 'MULTI_MODERATIONS',
            'ocf_private_topics' => 'PRIVATE_TOPICS',
            'ocf_saved_warnings' => 'SAVED_WARNINGS',
        );
    }
}

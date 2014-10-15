<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocf_forum
 */

class Hook_content_meta_aware_topic
{
    /**
	 * Get content type details. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @param  ?ID_TEXT	The zone to link through to (NULL: autodetect).
	 * @return ?array		Map of award content-type info (NULL: disabled).
	 */
    public function info($zone = null)
    {
        if (get_forum_type() != 'ocf') {
            return NULL;
        }

        return array(
            'supports_custom_fields' => true,

            'content_type_label' => 'ocf:FORUM_TOPIC',

            'connection' => $GLOBALS['FORUM_DB'],
            'table' => 'f_topics',
            'id_field' => 'id',
            'id_field_numeric' => true,
            'parent_category_field' => 't_forum_id',
            'parent_category_meta_aware_type' => 'forum',
            'is_category' => true,
            'is_entry' => true,
            'category_field' => 't_forum_id', // For category permissions
            'category_type' => 'forums', // For category permissions
            'parent_spec__table_name' => 'f_forums',
            'parent_spec__parent_name' => 'f_parent_forum',
            'parent_spec__field_name' => 'id',
            'category_is_string' => false,

            'title_field' => 't_cache_first_title',
            'title_field_dereference' => false,
            /*'title_field__resource_fs'=>'t_cache_first_title',
			'title_field_dereference__resource_fs'=>false,*/
            'title_field__resource_fs' => 't_description',
            'title_field_dereference__resource_fs' => false,
            'description_field' => 't_description',
            'thumb_field' => 't_emoticon',
            'thumb_field_is_theme_image' => true,

            'view_page_link_pattern' => '_SEARCH:topicview:misc:_WILD',
            'edit_page_link_pattern' => '_SEARCH:topics:edit_topic:_WILD',
            'view_category_page_link_pattern' => '_SEARCH:forumview:misc:_WILD',
            'add_url' => '',
            'archive_url' => ((!is_null($zone))?$zone:get_module_zone('forumview')) . ':forumview',

            'support_url_monikers' => true,

            'views_field' => 't_num_views',
            'submitter_field' => 't_cache_first_member_id',
            'add_time_field' => 't_cache_first_time',
            'edit_time_field' => 't_cache_last_time',
            'date_field' => 't_cache_first_time',
            'validated_field' => 't_validated',

            'seo_type_code' => 'topic',

            'feedback_type_code' => NULL,

            'permissions_type_code' => 'forums', // NULL if has no permissions

            'search_hook' => 'ocf_posts',

            'addon_name' => 'ocf_forum',

            'cms_page' => 'topics',
            'module' => 'topicview',

            'occle_filesystem_hook' => 'forums',
            'occle_filesystem__is_folder' => true,

            'rss_hook' => 'ocf_forumview',

            'actionlog_regexp' => '\w+_TOPIC',
        );
    }

    /**
	 * Run function for content hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @param  boolean	Whether to include context (i.e. say WHAT this is, not just show the actual content)
	 * @param  boolean	Whether to include breadcrumbs (if there are any)
	 * @param  ?ID_TEXT	Virtual root to use (NULL: none)
	 * @param  boolean	Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
	 * @param  ID_TEXT	Overridden GUID to send to templates (blank: none)
	 * @return tempcode	Results
	 */
    public function run($row,$zone,$give_context = true,$include_breadcrumbs = true,$root = null,$attach_to_url_filter = false,$guid = '')
    {
        require_code('ocf_topics');

        return render_topic_box($row,$zone,$give_context,$include_breadcrumbs,is_null($root)?null:intval($root),$guid);
    }
}

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
 * @package    core_menus
 */

/**
 * Hook class.
 */
class Hook_resource_meta_aware_menu_item
{
    /**
     * Get content type details. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
     *
     * @param  ?ID_TEXT                 $zone The zone to link through to (null: autodetect).
     * @return ?array                   Map of award content-type info (null: disabled).
     */
    public function info($zone = null)
    {
        return array(
            'supports_custom_fields' => false,

            'content_type_label' => 'MENU_ITEMS',

            'connection' => $GLOBALS['SITE_DB'],
            'table' => 'menu_items',
            'id_field' => 'id',
            'id_field_numeric' => true,
            'parent_category_field' => 'i_menu',
            'parent_category_meta_aware_type' => null,
            'is_category' => false,
            'is_entry' => true,
            'category_field' => null, // For category permissions
            'category_type' => null, // For category permissions
            'parent_spec__table_name' => null,
            'parent_spec__parent_name' => null,
            'parent_spec__field_name' => null,
            'category_is_string' => true,

            'title_field' => 'i_caption',
            'title_field_dereference' => true,

            'view_page_link_pattern' => null,
            'edit_page_link_pattern' => null,
            'view_category_page_link_pattern' => null,
            'add_url' => null,
            'archive_url' => null,

            'support_url_monikers' => false,

            'views_field' => null,
            'submitter_field' => null,
            'add_time_field' => null,
            'edit_time_field' => null,
            'date_field' => null,
            'validated_field' => null,

            'seo_type_code' => null,

            'feedback_type_code' => null,

            'permissions_type_code' => null, // NULL if has no permissions

            'search_hook' => null,

            'addon_name' => 'core_menus',

            'cms_page' => 'admin_menus',
            'module' => null,

            'occle_filesystem_hook' => 'menus',
            'occle_filesystem__is_folder' => false,

            'rss_hook' => null,

            'actionlog_regexp' => '\w+_MENU_ITEM',
        );
    }
}

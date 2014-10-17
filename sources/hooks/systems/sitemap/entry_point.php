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
 * @package    core
 */
class Hook_sitemap_entry_point extends Hook_sitemap_base
{
    /**
     * Find if a page-link will be covered by this node.
     *
     * @param  ID_TEXT                  The page-link.
     * @return integer                  A SITEMAP_NODE_* constant.
     */
    public function handles_page_link($page_link)
    {
        if (preg_match('#^cms:cms_catalogues:add_catalogue:#', $page_link)) {
            return SITEMAP_NODE_HANDLED;
        }

        $matches = array();
        if (preg_match('#^([^:]*):([^:]*):([^:]*)$#', $page_link, $matches) != 0) {
            $zone = $matches[1];
            $page = $matches[2];
            $type = $matches[3];

            $details = $this->_request_page_details($page, $zone);

            if ($details !== false) {
                $path = end($details);
                if (is_file(get_file_base() . '/' . str_replace('/modules_custom/', '/modules/', $path))) {
                    $path = str_replace('/modules_custom/', '/modules/', $path);
                }

                if ($details[0] == 'MODULES' || $details[0] == 'MODULES_CUSTOM') {
                    $functions = extract_module_functions(get_file_base() . '/' . $path, array('get_entry_points'), array(/*$check_perms=*/
                            true,/*$member_id=*/
                            null,/*$support_crosslinks=*/
                            true,/*$be_deferential=*/
                            true));
                    if (!is_null($functions[0])) {
                        if (is_file(get_file_base() . '/' . str_replace('/modules_custom/', '/modules/', $path))) {
                            $path = str_replace('/modules_custom/', '/modules/', $path);
                            $functions = extract_module_functions(get_file_base() . '/' . $path, array('get_entry_points', 'get_wrapper_icon'), array(/*$check_perms=*/
                                    true,/*$member_id=*/
                                    null,/*$support_crosslinks=*/
                                    true,/*$be_deferential=*/
                                    true));
                        }
                    }
                    if (!is_null($functions[0])) {
                        $entry_points = is_array($functions[0]) ? call_user_func_array($functions[0][0], $functions[0][1]) : eval($functions[0]);

                        if ($entry_points !== null) {
                            if (isset($entry_points['misc'])) {
                                unset($entry_points['misc']);
                            } else {
                                array_shift($entry_points);
                            }
                        }

                        if (isset($entry_points[$type])) {
                            return SITEMAP_NODE_HANDLED;
                        }
                    }
                }
            }
        }
        return SITEMAP_NODE_NOT_HANDLED;
    }

    /**
     * Find details of a position in the Sitemap.
     *
     * @param  ID_TEXT                  The page-link we are finding.
     * @param  ?string                  Callback function to send discovered page-links to (NULL: return).
     * @param  ?array                   List of node types we will return/recurse-through (NULL: no limit)
     * @param  ?integer                 Maximum number of children before we cut off all children (NULL: no limit).
     * @param  ?integer                 How deep to go from the Sitemap root (NULL: no limit).
     * @param  integer                  Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
     * @param  boolean                  Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
     * @param  ID_TEXT                  The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
     * @param  boolean                  Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
     * @param  boolean                  Whether to consider secondary categorisations for content that primarily exists elsewhere.
     * @param  boolean                  Whether to filter out non-validated content.
     * @param  integer                  A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
     * @param  ?array                   Database row (NULL: lookup).
     * @param  boolean                  Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
     * @return ?array                   Node structure (NULL: working via callback / error).
     */
    public function get_node($page_link, $callback = null, $valid_node_types = null, $child_cutoff = null, $max_recurse_depth = null, $recurse_level = 0, $require_permission_support = false, $zone = '_SEARCH', $use_page_groupings = false, $consider_secondary_categories = false, $consider_validation = false, $meta_gather = 0, $row = null, $return_anyway = false)
    {
        $matches = array();
        preg_match('#^([^:]*):([^:]*)(:([^:]*)(:.*|$))?#', $page_link, $matches);
        $page = $matches[2];
        if (!isset($matches[3])) {
            $matches[3] = '';
        }
        if (!isset($matches[4])) {
            $matches[4] = '';
        }
        if (!isset($matches[5])) {
            $matches[5] = '';
        }
        $type = $matches[4];
        if ($type == '') {
            $type = 'misc';
        }
        $id = mixed();
        if ($matches[5] != '') {
            $_id = substr($matches[5], 1);
            if (strpos($_id, '=') === false) {
                $id = $_id;
            }
        }

        $orig_page_link = $page_link;
        $this->_make_zone_concrete($zone, $page_link);

        $details = $this->_request_page_details($page, $zone);

        $path = end($details);

        if (($type == 'add_catalogue') && ($matches[5] != '') && ($matches[5][1] == '_')) {
            require_code('fields');
            $entry_points = manage_custom_fields_entry_points(substr($matches[5], 2));
            $entry_point = $entry_points[$orig_page_link];
        } else {
            $functions = extract_module_functions(get_file_base() . '/' . $path, array('get_entry_points'), array(/*$check_perms=*/
                    true,/*$member_id=*/
                    null,/*$support_crosslinks=*/
                    true,/*$be_deferential=*/
                    false));
            if (is_null($functions[0])) {
                if (is_file(get_file_base() . '/' . str_replace('/modules_custom/', '/modules/', $path))) {
                    $path = str_replace('/modules_custom/', '/modules/', $path);
                    $functions = extract_module_functions(get_file_base() . '/' . $path, array('get_entry_points', 'get_wrapper_icon'), array(/*$check_perms=*/
                            true,/*$member_id=*/
                            null,/*$support_crosslinks=*/
                            true,/*$be_deferential=*/
                            false));
                }
            }

            $entry_points = is_array($functions[0]) ? call_user_func_array($functions[0][0], $functions[0][1]) : eval($functions[0]);

            if ((($matches[5] == '') || ($page == 'cms_catalogues' && $matches[5] != ''/*masquerades as direct content types but fulfilled as normal entry points*/)) && (isset($entry_points[$type]))) {
                $entry_point = $entry_points[$type];
            } elseif (($matches[5] == '') && ((isset($entry_points['!'])) && ($type == 'misc'))) {
                $entry_point = $entry_points['!'];
            } else {
                if (isset($entry_points[$orig_page_link])) {
                    $entry_point = $entry_points[$orig_page_link];
                } else {
                    $entry_point = array(null, null);

                    // Not actually an entry-point, so maybe something else handles it directly?
                    // Technically this would be better code to have in page_grouping.php, but we don't want to do a scan for entry-points that are easy to find.
                    $hooks = find_all_hooks('systems', 'sitemap');
                    foreach (array_keys($hooks) as $_hook) {
                        require_code('hooks/systems/sitemap/' . $_hook);
                        $ob = object_factory('Hook_sitemap_' . $_hook);
                        if ($ob->is_active()) {
                            $is_handled = $ob->handles_page_link($page_link);
                            if ($is_handled == SITEMAP_NODE_HANDLED) {
                                return $ob->get_node($page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather, null, $return_anyway);
                            }
                        }
                    }
                }
            }
        }

        $icon = mixed();
        $_title = $entry_point[0];
        $icon = $entry_point[1];
        if (is_null($_title)) {
            $title = new ocp_tempcode();
        } elseif (is_object($_title)) {
            $title = $_title;
        } else {
            $title = (preg_match('#^[A-Z\_]+$#', $_title) == 0) ? make_string_tempcode($_title) : do_lang_tempcode($_title);
        }

        $struct = array(
            'title' => $title,
            'content_type' => 'page',
            'content_id' => $zone,
            'modifiers' => array(),
            'only_on_page' => '',
            'page_link' => $page_link,
            'url' => null,
            'extra_meta' => array(
                'description' => null,
                'image' => ($icon === null) ? null : find_theme_image('icons/24x24/' . $icon),
                'image_2x' => ($icon === null) ? null : find_theme_image('icons/48x48/' . $icon),
                'add_date' => (($meta_gather & SITEMAP_GATHER_TIMES) != 0) ? filectime(get_file_base() . '/' . $path) : null,
                'edit_date' => (($meta_gather & SITEMAP_GATHER_TIMES) != 0) ? filemtime(get_file_base() . '/' . $path) : null,
                'submitter' => null,
                'views' => null,
                'rating' => null,
                'meta_keywords' => null,
                'meta_description' => null,
                'categories' => null,
                'validated' => null,
                'db_row' => null,
            ),
            'permissions' => array(
                array(
                    'type' => 'zone',
                    'zone_name' => $zone,
                    'is_owned_at_this_level' => false,
                ),
                array(
                    'type' => 'page',
                    'zone_name' => $zone,
                    'page_name' => $page,
                    'is_owned_at_this_level' => false,
                ),
            ),
            'children' => null,
            'has_possible_children' => false,

            // These are likely to be changed in individual hooks
            'sitemap_priority' => SITEMAP_IMPORTANCE_MEDIUM,
            'sitemap_refreshfreq' => 'monthly',

            'privilege_page' => null,
        );

        $row_x = $this->_load_row_from_page_groupings(null, $zone, $page, $type, $id);
        if ($row_x != array()) {
            if ($_title !== null) {
                $row_x[0] = null;
            } // We have a better title
            if ($icon !== null) {
                $row_x[1] = null;
            } // We have a better icon
            $this->_ameliorate_with_row($struct, $row_x, $meta_gather);
        }

        if (!$this->_check_node_permissions($struct)) {
            return null;
        }

        // Look for virtual nodes to put under this
        if ($type != 'misc') {
            $hooks = find_all_hooks('systems', 'sitemap');
            foreach (array_keys($hooks) as $_hook) {
                require_code('hooks/systems/sitemap/' . $_hook);
                $ob = object_factory('Hook_sitemap_' . $_hook);
                if ($ob->is_active()) {
                    $is_handled = $ob->handles_page_link($page_link);
                    if ($is_handled == SITEMAP_NODE_HANDLED_VIRTUALLY) {
                        $struct['has_possible_children'] = true;

                        if (($max_recurse_depth === null) || ($recurse_level < $max_recurse_depth)) {
                            $children = array();

                            $virtual_child_nodes = $ob->get_virtual_nodes($page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather, true);
                            if (is_null($virtual_child_nodes)) {
                                $virtual_child_nodes = array();
                            }
                            foreach ($virtual_child_nodes as $child_node) {
                                if ($callback === null) {
                                    $children[$child_node['page_link']] = $child_node;
                                }
                            }

                            $struct['children'] = $children;
                        }
                    }
                }
            }
        }

        if ($callback !== null) {
            call_user_func($callback, $struct);
        }

        return ($callback === null || $return_anyway) ? $struct : null;
    }
}

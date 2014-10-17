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
class Hook_sitemap_zone extends Hook_sitemap_base
{
    /**
     * Get the permission page that nodes matching $page_link in this hook are tied to.
     * The permission page is where privileges may be overridden against.
     *
     * @param  string                   The page-link
     * @return ?ID_TEXT                 The permission page (NULL: none)
     */
    public function get_privilege_page($page_link)
    {
        return 'cms_comcode_pages';
    }

    /**
     * Find if a page-link will be covered by this node.
     *
     * @param  ID_TEXT                  The page-link.
     * @return integer                  A SITEMAP_NODE_* constant.
     */
    public function handles_page_link($page_link)
    {
        if (get_option('collapse_user_zones') == '0') {
            if ($page_link == ':') {
                return SITEMAP_NODE_NOT_HANDLED;
            }
        }

        if (preg_match('#^([^:]*):$#', $page_link) != 0) {
            return SITEMAP_NODE_HANDLED;
        }
        return SITEMAP_NODE_NOT_HANDLED;
    }

    /**
     * Convert a page-link to a category ID and category permission module type.
     *
     * @param  ID_TEXT                  The page-link
     * @return ?array                   The pair (NULL: permission modules not handled)
     */
    public function extract_child_page_link_permission_pair($page_link)
    {
        $matches = array();
        preg_match('#^([^:]*):$#', $page_link, $matches);
        $zone = $matches[1];

        return array($zone, 'zone_page');
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
        preg_match('#^([^:]*):#', $page_link, $matches);
        $zone = $matches[1]; // overrides $zone which we must replace

        if (!isset($row)) {
            $rows = $GLOBALS['SITE_DB']->query_select('zones', array('zone_title', 'zone_default_page'), array('zone_name' => $zone), '', 1);
            $row = array($zone, get_translated_text($rows[0]['zone_title']), $rows[0]['zone_default_page']);
        }
        $title = $row[1];
        $zone_default_page = $row[2];

        $path = get_custom_file_base() . '/' . $zone . '/index.php';
        if (!is_file($path)) {
            $path = get_file_base() . '/' . $zone . '/index.php';
        }

        $icon = mixed();
        switch ($zone) {
            case '':
            case 'site':
                $icon = 'menu/start';
                break;
            case 'collaboration':
                $icon = 'menu/collaboration';
                break;
            case 'adminzone':
                $icon = 'menu/adminzone/adminzone';
                break;
            case 'cms':
                $icon = 'menu/cms/cms';
                if ($use_page_groupings) {
                    $title = do_lang('CONTENT');
                }
                break;
            case 'forum':
                $icon = 'menu/social/forum/forums';
                break;
            case 'docs':
                $icon = 'menu/pages/help';
                break;
        }

        $struct = array(
            'title' => make_string_tempcode($title),
            'content_type' => 'zone',
            'content_id' => $zone,
            'modifiers' => array(),
            'only_on_page' => '',
            'page_link' => $page_link,
            'url' => null,
            'extra_meta' => array(
                'description' => null,
                'image' => ($icon === null) ? null : find_theme_image('icons/24x24/' . $icon),
                'image_2x' => ($icon === null) ? null : find_theme_image('icons/48x48/' . $icon),
                'add_date' => (($meta_gather & SITEMAP_GATHER_TIMES) != 0) ? filectime($path) : null,
                'edit_date' => (($meta_gather & SITEMAP_GATHER_TIMES) != 0) ? filemtime($path) : null,
                'submitter' => null,
                'views' => null,
                'rating' => null,
                'meta_keywords' => null,
                'meta_description' => null,
                'categories' => null,
                'validated' => null,
                'db_row' => (($meta_gather & SITEMAP_GATHER_DB_ROW) != 0) ? $row : null,
            ),
            'permissions' => array(
                array(
                    'type' => 'zone',
                    'zone_name' => $zone,
                    'is_owned_at_this_level' => true,
                ),
            ),
            'children' => null,
            'has_possible_children' => true,

            // These are likely to be changed in individual hooks
            'sitemap_priority' => SITEMAP_IMPORTANCE_ULTRA,
            'sitemap_refreshfreq' => 'daily',

            'privilege_page' => $this->get_privilege_page($page_link),
        );

        $comcode_page_sitemap_ob = $this->_get_sitemap_object('comcode_page');
        $page_sitemap_ob = $this->_get_sitemap_object('page');

        $children = array();
        $children_orphaned = array();

        // Get more details from default page? (which isn't linked as a child)
        $page_details = _request_page($zone_default_page, $zone);
        if ($page_details !== false) {
            $page_type = $page_details[0];

            if (strpos($page_type, 'comcode') !== false) {
                $child_node = $comcode_page_sitemap_ob->get_node($page_link . $zone_default_page, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather);
            } else {
                $child_node = $page_sitemap_ob->get_node($page_link . $zone_default_page, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather);
            }

            if ($child_node !== null) {
                //$struct['title']=$child_node['title'];
                foreach (array('description', 'image', 'image_2x', 'submitter', 'views', 'meta_keywords', 'meta_description', 'validated') as $key) {
                    if ($child_node['extra_meta'][$key] !== null) {
                        if (($struct['extra_meta'][$key] === null) || (!in_array($key, array('image', 'image_2x')))) {
                            $struct['extra_meta'][$key] = $child_node['extra_meta'][$key];
                        }
                    }
                }
                $struct['permissions'] = array_merge($struct['permissions'], $child_node['permissions']);
                if ($struct['children'] !== null) {
                    $children = array_merge($children, $struct['children']);
                }
            }
        }

        if (!$this->_check_node_permissions($struct)) {
            return null;
        }

        // What page groupings may apply in what zones? (in display order)
        $applicable_page_groupings = array();
        switch ($zone) {
            case 'adminzone':
                $applicable_page_groupings = array(
                    'audit',
                    'security',
                    'structure',
                    'style',
                    'setup',
                    'tools',
                );
                break;

            case '':
                if (get_option('collapse_user_zones') == '0') {
                    $applicable_page_groupings = array();
                } // else flow on...

            case 'site':
                if ($use_page_groupings) {
                    $applicable_page_groupings = array(
                        'pages',
                        'rich_content',
                        'social',
                    );
                    if (has_zone_access(get_member(), 'collaboration')) {
                        $applicable_page_groupings = array_merge($applicable_page_groupings, array(
                            'collaboration',
                        ));
                    }
                    $applicable_page_groupings = array_merge($applicable_page_groupings, array(
                        'site_meta',
                    ));
                }
                break;

            case 'cms':
                $applicable_page_groupings = array(
                    'cms',
                );
                break;
        }

        $call_struct = true;

        // Categories done after node callback, to ensure sensible ordering
        if (($max_recurse_depth === null) || ($recurse_level < $max_recurse_depth)) {
            $root_comcode_pages = get_root_comcode_pages($zone);

            // Locate all page groupings and pages in them
            $page_groupings = array();
            foreach ($applicable_page_groupings as $page_grouping) {
                $page_groupings[$page_grouping] = array();
            }
            $pages_found = array();
            $links = get_page_grouping_links();
            foreach ($links as $link) {
                list($page_grouping) = $link;

                if (($page_grouping == '') || (in_array($page_grouping, $applicable_page_groupings))) {
                    if (is_array($link)) {
                        $pages_found[$link[2][2] . ':' . $link[2][0]] = true;
                    }
                }

                // In a page grouping that is explicitly included
                if (($page_grouping != '') && (in_array($page_grouping, $applicable_page_groupings))) {
                    $page_groupings[$page_grouping][] = $link;
                }
            }

            // Any left-behind pages?
            $orphaned_pages = array();
            $pages = find_all_pages_wrap($zone, false,/*$consider_redirects=*/
                true);
            foreach ($pages as $page => $page_type) {
                if (is_integer($page)) {
                    $page = strval($page);
                }

                if (preg_match('#^redirect:#', $page_type) != 0) {
                    $details = $this->_request_page_details($page, $zone);
                    $page_type = strtolower($details[0]);
                    $pages[$page] = $page_type;
                }

                if ((!isset($pages_found[$zone . ':' . $page])) && ((strpos($page_type, 'comcode') === false) || (isset($root_comcode_pages[$page])))) {
                    if ($this->_is_page_omitted_from_sitemap($zone, $page)) {
                        continue;
                    }

                    $orphaned_pages[$page] = $page_type;
                }
            }

            // Do page-groupings
            if (count($page_groupings) != 1) {
                $page_grouping_sitemap_xml_ob = $this->_get_sitemap_object('page_grouping');

                foreach ($page_groupings as $page_grouping => $page_grouping_pages) {
                    if (count($page_grouping_pages) == 0) {
                        continue;
                    }

                    if ($zone == 'cms') {
                        $child_page_link = 'cms:cms:' . $page_grouping;
                    } else {
                        $child_page_link = 'adminzone:admin:' . $page_grouping; // We don't actually link to this, unless it's one of the ones held in the Admin Zone
                    }
                    $row = array(); // We may put extra nodes in here, beyond what the page_group knows
                    if ($page_grouping == 'pages' || $page_grouping == 'tools' || $page_grouping == 'cms') {
                        $row = $orphaned_pages;
                        $orphaned_pages = array();
                    }

                    if (($valid_node_types !== null) && (!in_array('page_grouping', $valid_node_types))) {
                        continue;
                    }

                    $child_node = $page_grouping_sitemap_xml_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather, $row);
                    if ($child_node !== null) {
                        $children[] = $child_node;
                    }
                }

                // Any remaining orphaned pages (we have to tag these on as there was no catch-all page grouping in this zone)
                if (count($orphaned_pages) > 0) {
                    foreach ($orphaned_pages as $page => $page_type) {
                        if (is_integer($page)) {
                            $page = strval($page);
                        }

                        if ($page == $zone_default_page) {
                            continue;
                        }

                        $child_page_link = $zone . ':' . $page;

                        if (strpos($page_type, 'comcode') !== false) {
                            if (($valid_node_types !== null) && (!in_array('comcode_page', $valid_node_types))) {
                                continue;
                            }

                            if (($consider_validation) && (isset($root_comcode_pages[$page])) && ($root_comcode_pages[$page] == 0)) {
                                continue;
                            }

                            $child_node = $comcode_page_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather);
                        } else {
                            if (($valid_node_types !== null) && (!in_array('page', $valid_node_types))) {
                                continue;
                            }

                            $child_node = $page_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather);
                        }

                        if ($child_node !== null) {
                            if ($zone == 'site' || $zone == 'adminzone') {
                                $child_node['is_unexpected_orphan'] = true; // This should never be set, it indicates a page not in a page grouping
                            }

                            $children_orphaned[] = $child_node;
                        }
                    }
                }
            } elseif (count($page_groupings) == 1) {
                // Show contents of group directly...

                $comcode_page_sitemap_ob = $this->_get_sitemap_object('comcode_page');
                $page_sitemap_ob = $this->_get_sitemap_object('page');

                foreach ($page_groupings as $links) { // Will only be 1 loop iteration, but this finds us that one easily
                    $child_links = array();

                    foreach ($links as $link) {
                        $title = $link[3];
                        $icon = $link[1];

                        $_zone = $link[2][2];
                        $page = $link[2][0];

                        $child_page_link = $_zone . ':' . $page;
                        foreach ($link[2][1] as $key => $val) {
                            if (!is_string($val)) {
                                $val = strval($val);
                            }

                            if ($key == 'type' || $key == 'id') {
                                $child_page_link .= ':' . urlencode($val);
                            } else {
                                $child_page_link .= ':' . urlencode($key) . '=' . urlencode($val);
                            }
                        }

                        $child_description = null;
                        if (isset($link[4])) {
                            $child_description = (is_object($link[4])) ? $link[4] : comcode_lang_string($link[4]);
                        }

                        $child_links[] = array($title, $child_page_link, $icon, null/*unknown/irrelevant $page_type*/, $child_description);
                    }

                    foreach ($orphaned_pages as $page => $page_type) {
                        if (is_integer($page)) {
                            $page = strval($page);
                        }

                        $child_page_link = $zone . ':' . $page;

                        $child_links[] = array(titleify($page), $child_page_link, null, $page_type, null);
                    }

                    // Render children, in title order
                    foreach ($child_links as $child_link) {
                        $title = $child_link[0];
                        $description = $child_link[4];
                        $icon = $child_link[2];
                        $child_page_link = $child_link[1];
                        $page_type = $child_link[3];

                        $child_row = ($icon === null) ? null/*we know nothing of relevance*/ : array($title, $icon, $description);

                        if (($page_type !== null) && (strpos($page_type, 'comcode') !== false)) {
                            if (($valid_node_types !== null) && (!in_array('comcode_page', $valid_node_types))) {
                                continue;
                            }

                            if (($consider_validation) && (isset($root_comcode_pages[$page])) && ($root_comcode_pages[$page] == 0)) {
                                continue;
                            }

                            $child_node = $comcode_page_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather, $child_row);
                        } else {
                            if (($valid_node_types !== null) && (!in_array('page', $valid_node_types))) {
                                continue;
                            }

                            $child_node = $page_sitemap_ob->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather, $child_row);
                        }
                        if ($child_node !== null) {
                            $children_orphaned[] = $child_node;
                        }
                    }
                }
            }

            sort_maps_by($children_orphaned, 'title');
            $children = array_merge($children, $children_orphaned);

            $struct['children'] = $children;
        }

        if ($callback !== null && $call_struct) {
            call_user_func($callback, $struct);
        }

        return ($callback === null || $return_anyway) ? $struct : null;
    }
}

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

/**
 * Hook class.
 */
class Hook_choose_topic
{
    /**
     * Run function for ajax-tree hooks. Generates XML for a tree list, which is interpreted by JavaScript and expanded on-demand (via new calls).
     *
     * @param  ?ID_TEXT                 $id The ID to do under (null: root)
     * @param  array                    $options Options being passed through
     * @param  ?ID_TEXT                 $default The ID to select by default (null: none)
     * @return string                   XML in the special category,entry format
     */
    public function run($id, $options, $default = null)
    {
        require_code('ocf_forums');
        require_code('ocf_forums2');

        $tree = ocf_get_topic_tree(is_null($id) ? null : intval($id), null, null, is_null($id) ? 0 : 1);

        $levels_to_expand = array_key_exists('levels_to_expand', $options) ? ($options['levels_to_expand']) : intval(get_long_value('levels_to_expand__' . substr(get_class($this), 5)));
        $options['levels_to_expand'] = max(0, $levels_to_expand - 1);

        if (!has_actual_page_access(null, 'forumview')) {
            $tree = array();
        }

        $out = '';

        $out .= '<options>' . serialize($options) . '</options>';

        foreach ($tree as $t) {
            $_id = $t['id'];
            if ($id === strval($_id)) { // Possible when we look under as a root
                foreach ($t['entries'] as $eid => $etitle) {
                    $out .= '<entry id="' . xmlentities(strval($eid)) . '" title="' . xmlentities($etitle) . '" selectable="true"></entry>';
                }
                continue;
            }
            $title = $t['title'];
            $has_children = ($t['child_count'] != 0) || ($t['child_entry_count'] != 0);

            $out .= '<category id="' . xmlentities(strval($_id)) . '" title="' . xmlentities($title) . '" has_children="' . ($has_children ? 'true' : 'false') . '" selectable="false"></category>';

            if ($levels_to_expand > 0) {
                $out .= '<expand>' . xmlentities(strval($_id)) . '</expand>';
            }
        }

        // Mark parent cats for pre-expansion
        if ((!is_null($default)) && ($default != '')) {
            $cat = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_topics', 't_forum_id', array('id' => intval($default)));
            while (!is_null($cat)) {
                $out .= '<expand>' . strval($cat) . '</expand>';
                $cat = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums', 'f_parent_forum', array('id' => $cat));
            }
        }

        return '<result>' . $out . '</result>';
    }

    /**
     * Generate a simple selection list for the ajax-tree hook. Returns a normal <select> style <option>-list, for fallback purposes
     *
     * @param  ?ID_TEXT                 $id The ID to do under (null: root) - not always supported
     * @param  array                    $options Options being passed through
     * @param  ?ID_TEXT                 $it The ID to select by default (null: none)
     * @return tempcode                 The nice list
     */
    public function simple($id, $options, $it = null)
    {
        require_code('ocf_forums2');

        return ocf_create_selection_list_topic_tree(is_null($it) ? null : intval($it));
    }
}

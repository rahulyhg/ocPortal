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
 * @package    aggregate_types
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_admin_aggregate_types extends Standard_crud_module
{
    public $lang_type = 'AGGREGATE_TYPE_INSTANCE';
    public $select_name = 'LABEL';
    public $menu_label = 'AGGREGATE_TYPES';
    public $orderer = 'aggregate_label';
    public $title_is_multi_lang = false;
    public $table = 'aggregate_type_instances';

    public $add_one_label = null;
    public $edit_this_label = null;
    public $edit_one_label = null;

    /**
     * Find details of the module.
     *
     * @return ?array                   Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 1;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('aggregate_type_instances');
    }

    /**
     * Install the module.
     *
     * @param  ?integer                 $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer                 $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        $GLOBALS['SITE_DB']->create_table('aggregate_type_instances', array(
            'id' => '*AUTO',
            'aggregate_label' => 'SHORT_TEXT',
            'aggregate_type' => 'ID_TEXT',
            'other_parameters' => 'LONG_TEXT',
            'add_time' => 'TIME',
            'edit_time' => '?TIME',
        ));
        $GLOBALS['SITE_DB']->create_index('aggregate_type_instances', 'aggregate_lookup', array('aggregate_label'/*,'aggregate_type' key would be too long*/));
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  $check_perms Whether to check permissions.
     * @param  ?MEMBER                  $member_id The member to check permissions as (null: current user).
     * @param  boolean                  $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  $be_deferential Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        $ret = array(
            'browse' => array('AGGREGATE_TYPES', 'menu/adminzone/structure/aggregate_types'),
        );
        $ret += parent::get_entry_points();
        $ret += array(
            'xml' => array('EDIT_AGGREGATE_TYPES', 'menu/_generic_admin/xml'),
            'sync' => array('SYNCHRONISE_AGGREGATE_TYPES', 'menu/_generic_admin/sync'),
        );
        return $ret;
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @param  boolean                  $top_level Whether this is running at the top level, prior to having sub-objects called.
     * @param  ?ID_TEXT                 $type The screen type to consider for meta-data purposes (null: read from environment).
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run($top_level = true, $type = null)
    {
        if (is_null($type)) {
            $type = get_param('type', 'browse');
        }

        require_lang('aggregate_types');

        set_helper_panel_tutorial('tut_aggregate_types');
        set_helper_panel_text(comcode_lang_string('DOC_AGGREGATE_TYPES'));

        if ($type == 'xml' || $type == '_xml') {
            $this->title = get_screen_title('EDIT_AGGREGATE_TYPES');
        }

        if ($type == 'sync') {
            inform_non_canonical_parameter('sync_type');

            $this->title = get_screen_title('SYNCHRONISE_AGGREGATE_TYPES');
        }

        if ($type == '_sync') {
            $this->title = get_screen_title('SYNCHRONISE_AGGREGATE_TYPES');
        }

        return parent::pre_run($top_level);
    }

    /**
     * Standard crud_module run_start.
     *
     * @param  ID_TEXT                  $type The type of module execution
     * @return tempcode                 The output of the run
     */
    public function run_start($type)
    {
        require_code('aggregate_types');

        $this->add_one_label = do_lang_tempcode('ADD_AGGREGATE_TYPE_INSTANCE');
        $this->edit_this_label = do_lang_tempcode('EDIT_THIS_AGGREGATE_TYPE_INSTANCE');
        $this->edit_one_label = do_lang_tempcode('EDIT_AGGREGATE_TYPE_INSTANCE');

        if ($type == 'browse') {
            return $this->browse();
        }

        if ($type == 'xml') {
            return $this->xml();
        }
        if ($type == '_xml') {
            return $this->_xml();
        }
        if ($type == 'sync') {
            return $this->sync();
        }
        if ($type == '_sync') {
            return $this->_sync();
        }

        return new Tempcode();
    }

    /**
     * The do-next manager for before setup management.
     *
     * @return tempcode                 The UI
     */
    public function browse()
    {
        require_code('templates_donext');
        return do_next_manager(get_screen_title('AGGREGATE_TYPES'), comcode_lang_string('DOC_AGGREGATE_TYPES'),
            array(
                array('menu/_generic_admin/add_one', array('_SELF', array('type' => 'add'), '_SELF'), do_lang('ADD_AGGREGATE_TYPE_INSTANCE')),
                array('menu/_generic_admin/edit_one', array('_SELF', array('type' => 'edit'), '_SELF'), do_lang('EDIT_AGGREGATE_TYPE_INSTANCE')),
                array('menu/_generic_admin/xml', array('_SELF', array('type' => 'xml'), '_SELF'), do_lang('EDIT_AGGREGATE_TYPES')),
                array('menu/_generic_admin/sync', array('_SELF', array('type' => 'sync'), '_SELF'), do_lang('SYNCHRONISE_AGGREGATE_TYPES')),
            ),
            do_lang('AGGREGATE_TYPES')
        );
    }

    /**
     * Get tempcode for a forum grouping template adding/editing form.
     *
     * @param  ID_TEXT                  $aggregate_type The aggregate type (blank: ask first)
     * @param  SHORT_TEXT               $aggregate_label The label for the instance
     * @param  ?array                   $other_parameters Other parameters (null: no values known yet)
     * @return mixed                    Either Tempcode; or a tuple: form fields, hidden fields, delete fields.
     */
    public function get_form_fields($aggregate_type = '', $aggregate_label = '', $other_parameters = null)
    {
        if ($aggregate_type == '') {
            $aggregate_type = get_param('aggregate_type', '');

            if ($aggregate_type == '') {
                require_code('form_templates');
                $fields = new Tempcode();
                $list = new Tempcode();
                $types = parse_aggregate_xml();
                foreach (array_keys($types) as $type) {
                    $list->attach(form_input_list_entry($type, false, titleify($type)));
                }
                $fields->attach(form_input_list(do_lang_tempcode('AGGREGATE_TYPE'), '', 'aggregate_type', $list, null, true, true));
                $submit_name = do_lang_tempcode('PROCEED');
                $url = get_self_url();
                return do_template('FORM_SCREEN', array('_GUID' => '8bd97d858f2ab1dc885a7453b3dd781c', 'TITLE' => $this->title, 'SKIP_VALIDATION' => true, 'HIDDEN' => '', 'GET' => true, 'URL' => $url, 'FIELDS' => $fields, 'TEXT' => '', 'SUBMIT_ICON' => 'buttons__proceed', 'SUBMIT_NAME' => $submit_name));
            }
        }

        if (is_null($other_parameters)) {
            $other_parameters = array();
        }

        $fields = new Tempcode();
        $hidden = new Tempcode();

        $fields->attach(form_input_line(do_lang_tempcode('LABEL'), do_lang_tempcode('DESCRIPTION_LABEL'), 'aggregate_label', $aggregate_label, true));

        $parameters = find_aggregate_type_parameters($aggregate_type);
        foreach ($parameters as $parameter) {
            if ($parameter != 'label') {
                $required = true;

                $default = array_key_exists($parameter, $other_parameters) ? $other_parameters[$parameter] : '';
                $fields->attach(form_input_line(titleify($parameter), '', $parameter, $default, $required));
            }
        }

        $hidden->attach(form_input_hidden('aggregate_type', $aggregate_type));

        $delete_fields = new Tempcode();
        if ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) {
            $delete_fields->attach(form_input_tick(do_lang_tempcode('DELETE_AGGREGATE_MATCHES'), do_lang_tempcode('DESCRIPTION_DELETE_AGGREGATE_MATCHES'), 'delete_matches', false));
        }

        return array($fields, $hidden, $delete_fields);
    }

    /**
     * Standard crud_module table function.
     *
     * @param  array                    $url_map Details to go to build_url for link to the next screen.
     * @return array                    A quartet: The choose table, Whether re-ordering is supported from this screen, Search URL, Archive URL.
     */
    public function create_selection_list_choose_table($url_map)
    {
        require_code('templates_results_table');

        $current_ordering = get_param('sort', 'aggregate_label ASC', true);
        if (strpos($current_ordering, ' ') === false) {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
        list($sortable, $sort_order) = explode(' ', $current_ordering, 2);
        $sortables = array(
            'aggregate_label' => do_lang_tempcode('TITLE'),
            'aggregate_type' => do_lang_tempcode('TYPE'),
            'add_time' => do_lang_tempcode('DATE'),
        );
        if (((strtoupper($sort_order) != 'ASC') && (strtoupper($sort_order) != 'DESC')) || (!array_key_exists($sortable, $sortables))) {
            log_hack_attack_and_exit('ORDERBY_HACK');
        }

        $header_row = results_field_title(array(
            do_lang_tempcode('LABEL'),
            do_lang_tempcode('TYPE'),
            do_lang_tempcode('TIME'),
            do_lang_tempcode('ACTIONS'),
        ), $sortables, 'sort', $sortable . ' ' . $sort_order);

        $fields = new Tempcode();

        require_code('form_templates');
        list($rows, $max_rows) = $this->get_entry_rows(false, $current_ordering);
        foreach ($rows as $row) {
            $edit_link = build_url($url_map + array('id' => $row['id']), '_SELF');

            $fields->attach(results_entry(array($row['aggregate_label'], $row['aggregate_type'], get_timezoned_date($row['add_time']), protect_from_escaping(hyperlink($edit_link, do_lang_tempcode('EDIT'), false, true, do_lang('EDIT') . ' #' . strval($row['id']))))), true);
        }

        $search_url = null;
        $archive_url = null;

        return array(results_table(do_lang($this->menu_label), get_param_integer('start', 0), 'start', get_param_integer('max', 20), 'max', $max_rows, $header_row, $fields, $sortables, $sortable, $sort_order), false, $search_url, $archive_url);
    }

    /**
     * Standard crud_module edit form filler.
     *
     * @param  ID_TEXT                  $_id The entry being edited
     * @return array                    A triple: fields, hidden-fields, delete-fields
     */
    public function fill_in_edit_form($_id)
    {
        $id = intval($_id);

        $m = $GLOBALS['FORUM_DB']->query_select('aggregate_type_instances', array('*'), array('id' => $id), '', 1);
        if (!array_key_exists(0, $m)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $r = $m[0];

        return $this->get_form_fields($r['aggregate_type'], $r['aggregate_label'], unserialize($r['other_parameters']));
    }

    /**
     * Read in parameters for adding/editing.
     *
     * @return array                    Parameters
     */
    public function _read_in_parameters()
    {
        $aggregate_label = post_param('aggregate_label');
        $aggregate_type = post_param('aggregate_type');
        $other_parameters = array();
        $parameters = find_aggregate_type_parameters($aggregate_type);
        foreach ($parameters as $parameter) {
            if ($parameter != 'label') {
                $other_parameters[$parameter] = post_param($parameter, '');
            }
        }
        return array($aggregate_label, $aggregate_type, $other_parameters);
    }

    /**
     * Standard crud_module add actualiser.
     *
     * @return ID_TEXT                  The entry added
     */
    public function add_actualisation()
    {
        list($aggregate_label, $aggregate_type, $other_parameters) = $this->_read_in_parameters();
        $id = add_aggregate_type_instance($aggregate_label, $aggregate_type, $other_parameters);
        return strval($id);
    }

    /**
     * Standard crud_module edit actualiser.
     *
     * @param  ID_TEXT                  $id The entry being edited
     */
    public function edit_actualisation($id)
    {
        list($aggregate_label, $aggregate_type, $other_parameters) = $this->_read_in_parameters();
        edit_aggregate_type_instance(intval($id), $aggregate_label, $aggregate_type, $other_parameters);
    }

    /**
     * Standard crud_module delete actualiser.
     *
     * @param  ID_TEXT                  $id The entry being deleted
     */
    public function delete_actualisation($id)
    {
        $delete_matches = false;
        if ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) {
            $delete_matches = (post_param_integer('delete_matches', 0) == 1);
        }
        delete_aggregate_type_instance(intval($id), $delete_matches);
    }

    /**
     * The UI to edit the aggregate_types XML file.
     *
     * @return tempcode                 The UI
     */
    public function xml()
    {
        parse_aggregate_xml(true);

        $post_url = build_url(array('page' => '_SELF', 'type' => '_xml'), '_SELF');

        return do_template('XML_CONFIG_SCREEN', array('_GUID' => '2303459e94b959d2edf8444188bbeea9', 'TITLE' => $this->title,
            'POST_URL' => $post_url,
            'XML' => file_exists(get_custom_file_base() . '/data_custom/aggregate_types.xml') ? file_get_contents(get_custom_file_base() . '/data_custom/aggregate_types.xml') : '',
        ));
    }

    /**
     * The UI actualiser edit the aggregate_types XML file.
     *
     * @return tempcode                 The UI
     */
    public function _xml()
    {
        if (!file_exists(get_custom_file_base() . '/data_custom')) {
            require_code('files2');
            make_missing_directory(get_custom_file_base() . '/data_custom');
        }

        $myfile = @fopen(get_custom_file_base() . '/data_custom/aggregate_types.xml', GOOGLE_APPENGINE ? 'wb' : 'wt');
        if ($myfile === false) {
            intelligent_write_error(get_custom_file_base() . '/data_custom/aggregate_types.xml');
        }
        $xml = post_param('xml');
        if (fwrite($myfile, $xml) < strlen($xml)) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
        }
        fclose($myfile);
        fix_permissions(get_custom_file_base() . '/data_custom/aggregate_types.xml');
        sync_file(get_custom_file_base() . '/data_custom/aggregate_types.xml');

        log_it('EDIT_AGGREGATE_TYPES');

        parse_aggregate_xml(true);

        return inform_screen($this->title, do_lang_tempcode('SUCCESS'));
    }

    /**
     * The UI to start a synchronisation of aggregate content type instances.
     *
     * @return tempcode                 The UI
     */
    public function sync()
    {
        require_code('form_templates');

        $_type = get_param('sync_type', '');

        $fields = new Tempcode();

        $list = new Tempcode();
        $types = parse_aggregate_xml();
        foreach (array_keys($types) as $type) {
            $list->attach(form_input_list_entry($type, $_type == $type, titleify($type)));
        }
        $fields->attach(form_input_multi_list(do_lang_tempcode('AGGREGATE_TYPE'), '', 'aggregate_type', $list, null, 15, true));

        $submit_name = do_lang_tempcode('PROCEED');

        $url = build_url(array('page' => '_SELF', 'type' => '_sync'), '_SELF');

        return do_template('FORM_SCREEN', array('_GUID' => '823999c74834fc34a51a6a63cdafeab5', 'TITLE' => $this->title,
            'SKIP_VALIDATION' => true,
            'HIDDEN' => '',
            'URL' => $url,
            'FIELDS' => $fields,
            'TEXT' => do_lang_tempcode('SELECT_AGGREGATE_TYPES_FOR_SYNC'),
            'SUBMIT_ICON' => 'menu___generic_admin__sync',
            'SUBMIT_NAME' => $submit_name,
        ));
    }

    /**
     * The actualiser to start a synchronisation of aggregate content type instances.
     *
     * @return tempcode                 The UI
     */
    public function _sync()
    {
        $types = $_POST['aggregate_type'];
        foreach ($types as $type) {
            resync_all_aggregate_type_instances($type);
        }

        return inform_screen($this->title, do_lang_tempcode('SUCCESS'));
    }
}

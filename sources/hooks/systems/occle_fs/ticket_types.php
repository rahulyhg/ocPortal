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
 * @package    tickets
 */

require_code('resource_fs');

class Hook_occle_fs_ticket_types extends resource_fs_base
{
    public $file_resource_type = 'ticket_type';

    /**
     * Standard occle_fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT                  The resource type
     * @return integer                  How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        return $GLOBALS['SITE_DB']->query_select_value('ticket_types', 'COUNT(*)');
    }

    /**
     * Standard occle_fs function for searching for a resource by label.
     *
     * @param  ID_TEXT                  The resource type
     * @param  LONG_TEXT                The resource label
     * @return array                    A list of resource IDs
     */
    public function find_resource_by_label($resource_type, $label)
    {
        $_ret = $GLOBALS['SITE_DB']->query_select('ticket_types', array('id', 'ticket_type_name'), array($GLOBALS['SITE_DB']->translate_field_ref('ticket_type_name') => $label));
        $ret = array();
        foreach ($_ret as $r) {
            $ret[] = strval($r['id']);
        }
        return $ret;
    }

    /**
     * Standard occle_fs introspection function.
     *
     * @return array                    The properties available for the resource type
     */
    public function _enumerate_file_properties()
    {
        return array(
            'guest_emails_mandatory' => 'BINARY',
            'search_faq' => 'BINARY',
        );
    }

    /**
     * Standard occle_fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array                    Resource row (not full, but does contain the ID)
     * @return ?TIME                    The edit date or add date, whichever is higher (NULL: could not find one)
     */
    public function _get_file_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('param_a', strval($row['ticket_type'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_TICKET_TYPE') . ' OR ' . db_string_equal_to('the_type', 'EDIT_TICKET_TYPE') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard occle_fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT                Filename OR Resource label
     * @param  string                   The path (blank: root / not applicable)
     * @param  array                    Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT                 The resource ID (false: error, could not create via these properties / here)
     */
    public function file_add($filename, $path, $properties)
    {
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('tickets2');

        $guest_emails_mandatory = $this->_default_property_int($properties, 'guest_emails_mandatory');
        $search_faq = $this->_default_property_int($properties, 'search_faq');

        $id = add_ticket_type($label, $guest_emails_mandatory, $search_faq);
        return strval($id);
    }

    /**
     * Standard occle_fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT               Filename
     * @param  string                   The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array                   Details of the resource (false: error)
     */
    public function file_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        $rows = $GLOBALS['SITE_DB']->query_select('ticket_types', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        return array(
            'label' => get_translated_text($row['ticket_type_name']),
            'guest_emails_mandatory' => $row['guest_emails_mandatory'],
            'search_faq' => $row['search_faq'],
        );
    }

    /**
     * Standard occle_fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT                  The filename
     * @param  string                   The path (blank: root / not applicable)
     * @param  array                    Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT                 The resource ID (false: error, could not create via these properties / here)
     */
    public function file_edit($filename, $path, $properties)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('tickets2');

        $label = $this->_default_property_str($properties, 'label');
        $guest_emails_mandatory = $this->_default_property_int($properties, 'guest_emails_mandatory');
        $search_faq = $this->_default_property_int($properties, 'search_faq');

        edit_ticket_type(intval($resource_id), $label, $guest_emails_mandatory, $search_faq);

        return $resource_id;
    }

    /**
     * Standard occle_fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT                  The filename
     * @param  string                   The path (blank: root / not applicable)
     * @return boolean                  Success status
     */
    public function file_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        require_code('tickets2');
        delete_ticket_type(intval($resource_id));

        return true;
    }
}

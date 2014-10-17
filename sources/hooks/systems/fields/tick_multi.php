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
 * @package    core_fields
 */
class Hook_fields_tick_multi
{
    // ==============
    // Module: search
    // ==============

    /**
     * Get special Tempcode for inputting this field.
     *
     * @param  array                    The row for the field to input
     * @return ?array                   List of specially encoded input detail rows (NULL: nothing special)
     */
    public function get_search_inputter($row)
    {
        $fields = array();
        $type = '_LIST';
        $special = new ocp_tempcode();
        $special->attach(form_input_list_entry('', get_param('option_' . strval($row['id']), '') == '', '---'));
        $list = explode('|', $row['cf_default']);
        $display = array_key_exists('trans_name', $row) ? $row['trans_name'] : get_translated_text($row['cf_name']); // 'trans_name' may have been set in CPF retrieval API, might not correspond to DB lookup if is an internal field
        foreach ($list as $l) {
            $special->attach(form_input_list_entry($l, get_param('option_' . strval($row['id']), '') == $l));
        }
        $fields[] = array('NAME' => strval($row['id']), 'DISPLAY' => $display, 'TYPE' => $type, 'SPECIAL' => $special);
        return $fields;
    }

    /**
     * Get special SQL from POSTed parameters for this field.
     *
     * @param  array                    The row for the field to input
     * @param  integer                  We're processing for the ith row
     * @return ?array                   Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (NULL: nothing special)
     */
    public function inputted_to_sql_for_search($row, $i)
    {
        return nl_delim_match_sql($row, $i, 'long');
    }

    // ===================
    // Backend: fields API
    // ===================

    /**
     * Get some info bits relating to our field type, that helps us look it up / set defaults.
     *
     * @param  ?array                   The field details (NULL: new field)
     * @param  ?boolean                 Whether a default value cannot be blank (NULL: don't "lock in" a new default value)
     * @param  ?string                  The given default value as a string (NULL: don't "lock in" a new default value)
     * @return array                    Tuple of details (row-type,default-value-to-use,db row-type)
     */
    public function get_field_value_row_bits($field, $required = null, $default = null)
    {
        /*if ($required!==NULL)
        {
            Nothing special for this hook
        }*/
        return array('long_unescaped', $default, 'long');
    }

    /**
     * Convert a field value to something renderable.
     *
     * @param  array                    The field details
     * @param  mixed                    The raw value
     * @return mixed                    Rendered field (tempcode or string)
     */
    public function render_field_value($field, $ev)
    {
        if (is_object($ev)) {
            return $ev;
        }
        $all = array();
        $exploded = ($ev == '') ? array() : array_flip(explode("\n", $ev));
        foreach (explode('|', $field['cf_default']) as $option) {
            $all[] = array('OPTION' => $option, 'HAS' => isset($exploded[$option]));
        }
        if (!array_key_exists('c_name', $field)) {
            $field['c_name'] = 'other';
        }
        return do_template('CATALOGUE_' . $field['c_name'] . '_FIELD_MULTILIST', array('_GUID' => 'x28e21cdbc38a3037d083f619bb31dae', 'ALL' => $all, 'FIELD_ID' => strval($field['id'])), null, false, 'CATALOGUE_DEFAULT_FIELD_MULTILIST');
    }

    // ======================
    // Frontend: fields input
    // ======================

    /**
     * Get form inputter.
     *
     * @param  string                   The field name
     * @param  string                   The field description
     * @param  array                    The field details
     * @param  ?string                  The actual current value of the field (NULL: none)
     * @return ?tempcode                The Tempcode for the input field (NULL: skip the field - it's not input)
     */
    public function get_field_inputter($_cf_name, $_cf_description, $field, $actual_value)
    {
        $default = $field['cf_default'];
        $list = explode('|', $default);
        $_list = array();
        $exploded = explode("\n", $actual_value);
        foreach ($list as $i => $l) {
            $_list[] = array($l, 'field_' . strval($field['id']) . '_' . strval($i), in_array($l, $exploded), '');
        }
        return form_input_various_ticks($_list, $_cf_description, null, $_cf_name);
    }

    /**
     * Find the posted value from the get_field_inputter field
     *
     * @param  boolean                  Whether we were editing (because on edit, it could be a fractional edit)
     * @param  array                    The field details
     * @param  ?string                  Where the files will be uploaded to (NULL: do not store an upload, return NULL if we would need to do so)
     * @param  ?array                   Former value of field (NULL: none)
     * @return ?string                  The value (NULL: could not process)
     */
    public function inputted_to_field_value($editing, $field, $upload_dir = 'uploads/catalogues', $old_value = null)
    {
        $default = $field['cf_default'];
        $list = explode('|', $default);

        if (fractional_edit()) {
            return $editing ? STRING_MAGIC_NULL : '';
        }

        $id = $field['id'];
        $value = '';
        foreach ($list as $i => $l) {
            $tmp_name = 'field_' . strval($id) . '_' . strval($i);
            if (post_param_integer($tmp_name, 0) == 1) {
                if ($value != '') {
                    $value .= "\n";
                }
                $value .= $l;
            }
        }

        return $value;
    }
}

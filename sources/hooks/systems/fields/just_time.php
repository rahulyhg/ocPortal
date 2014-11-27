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

/**
 * Hook class.
 */
class Hook_fields_just_time
{
    // ==============
    // Module: search
    // ==============

    /**
     * Get special Tempcode for inputting this field.
     *
     * @param  array                    $row The row for the field to input
     * @return ?array                   List of specially encoded input detail rows (null: nothing special)
     */
    public function get_search_inputter($row)
    {
        return null;
    }

    /**
     * Get special SQL from POSTed parameters for this field.
     *
     * @param  array                    $row The row for the field to input
     * @param  integer                  $i We're processing for the ith row
     * @return ?array                   Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (null: nothing special)
     */
    public function inputted_to_sql_for_search($row, $i)
    {
        return exact_match_sql($row, $i);
    }

    // ===================
    // Backend: fields API
    // ===================

    /**
     * Get some info bits relating to our field type, that helps us look it up / set defaults.
     *
     * @param  ?array                   $field The field details (null: new field)
     * @param  ?boolean                 $required Whether a default value cannot be blank (null: don't "lock in" a new default value)
     * @param  ?string                  $default The given default value as a string (null: don't "lock in" a new default value)
     * @return array                    Tuple of details (row-type,default-value-to-use,db row-type)
     */
    public function get_field_value_row_bits($field, $required = null, $default = null)
    {
        if ($required !== null) {
            if (($required) && ($default == '')) {
                $default = date('H:i:s', utctime_to_usertime());
            }
        }
        return array('short_unescaped', $default, 'short');
    }

    /**
     * Convert a field value to something renderable.
     *
     * @param  array                    $field The field details
     * @param  mixed                    $ev The raw value
     * @return mixed                    Rendered field (tempcode or string)
     */
    public function render_field_value($field, $ev)
    {
        if (is_object($ev)) {
            return $ev;
        }

        if ($ev != '') {
            if (strpos(strtolower($ev), 'now') !== false) {
                $time = time();
            } else {
                // Y-m-d H:i:s
                $time_bits = explode(':', $ev, 3);
                if (!array_key_exists(1, $time_bits)) {
                    $time_bits[1] = '00';
                }
                if (!array_key_exists(2, $time_bits)) {
                    $time_bits[2] = '00';
                }
                $time = mktime(intval($time_bits[0]), intval($time_bits[1]), intval($time_bits[2]));
                //$time=utctime_to_usertime($time);   No, as we have no idea what date it is for, so cannot do DST changes
            }
            $ev = get_timezoned_time($time, false, null, true);
        }
        return escape_html($ev);
    }

    // ======================
    // Frontend: fields input
    // ======================

    /**
     * Get form inputter.
     *
     * @param  string                   $_cf_name The field name
     * @param  string                   $_cf_description The field description
     * @param  array                    $field The field details
     * @param  ?string                  $actual_value The actual current value of the field (null: none)
     * @param  boolean                  $new Whether this is for a new entry
     * @return ?tempcode                The Tempcode for the input field (null: skip the field - it's not input)
     */
    public function get_field_inputter($_cf_name, $_cf_description, $field, $actual_value, $new)
    {
        $time = mixed();

        if ((is_null($actual_value)) || ($actual_value == '')) {
            $time = null;
        } elseif (strpos(strtolower($actual_value), 'now') !== false) {
            $time = time();
        } else {
            // H:i:s
            $time_bits = explode(':', $actual_value, 3);
            if (!array_key_exists(1, $time_bits)) {
                $time_bits[1] = '00';
            }
            if (!array_key_exists(2, $time_bits)) {
                $time_bits[2] = '00';
            }

            $time = array(intval($time_bits[1]), intval($time_bits[0]), intval(date('m')), intval(date('d')), intval(date('Y')));
        }
        return form_input_date($_cf_name, $_cf_description, 'field_' . strval($field['id']), $field['cf_required'] == 1, ($field['cf_required'] == 0) && ($actual_value == ''), true, $time, 1, 1900, null, false, null, false);
    }

    /**
     * Find the posted value from the get_field_inputter field
     *
     * @param  boolean                  $editing Whether we were editing (because on edit, it could be a fractional edit)
     * @param  array                    $field The field details
     * @param  ?string                  $upload_dir Where the files will be uploaded to (null: do not store an upload, return NULL if we would need to do so)
     * @param  ?array                   $old_value Former value of field (null: none)
     * @return ?string                  The value (null: could not process)
     */
    public function inputted_to_field_value($editing, $field, $upload_dir = 'uploads/catalogues', $old_value = null)
    {
        $id = $field['id'];
        $stub = 'field_' . strval($id);

        $time = post_param($stub, null);
        if (!is_null($time)) {
            $matches = array();
            if (preg_match('#^(\d\d):(\d\d)$#', $time, $matches) != 0) {
                $hour = intval($matches[1]);
                $minute = intval($matches[2]);
            } else {
                $hour = null;
                $minute = null;
            }
        } else {
            $hour = post_param_integer($stub . '_hour', 0);
            $minute = post_param_integer($stub . '_minute', 0);
        }

        return strval($hour) . ':' . strval($minute);
    }
}

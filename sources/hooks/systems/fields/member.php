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
 * @package		core_fields
 */

class Hook_fields_member
{
    // ==============
    // Module: search
    // ==============

    /**
	 * Get special Tempcode for inputting this field.
	 *
	 * @param  array			The row for the field to input
	 * @return ?array			List of specially encoded input detail rows (NULL: nothing special)
	 */
    public function get_search_inputter($row)
    {
        return NULL;
    }

    /**
	 * Get special SQL from POSTed parameters for this field.
	 *
	 * @param  array			The row for the field to input
	 * @param  integer		We're processing for the ith row
	 * @return ?array			Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (NULL: nothing special)
	 */
    public function inputted_to_sql_for_search($row,$i)
    {
        $param = get_param('option_' . strval($row['id']),'');
        if ($param != '') {
            $param = strval($GLOBALS['FORUM_DRIVER']->get_member_from_username($param));
        }
        return exact_match_sql($row,$i,'long',$param);
    }

    // ===================
    // Backend: fields API
    // ===================

    /**
	 * Get some info bits relating to our field type, that helps us look it up / set defaults.
	 *
	 * @param  ?array			The field details (NULL: new field)
	 * @param  ?boolean		Whether a default value cannot be blank (NULL: don't "lock in" a new default value)
	 * @param  ?string		The given default value as a string (NULL: don't "lock in" a new default value)
	 * @return array			Tuple of details (row-type,default-value-to-use,db row-type)
	 */
    public function get_field_value_row_bits($field,$required = null,$default = null)
    {
        if ($required !== NULL) {
            if (($required) && ($default == '')) {
                $default = strval($GLOBALS['FORUM_DRIVER']->get_guest_id());
            }
        }
        return array('integer_unescaped',$default,'integer');
    }

    /**
	 * Convert a field value to something renderable.
	 *
	 * @param  array			The field details
	 * @param  mixed			The raw value
	 * @return mixed			Rendered field (tempcode or string)
	 */
    public function render_field_value($field,$ev)
    {
        if (is_object($ev)) {
            return $ev;
        }

        if ($ev == '') {
            return new ocp_tempcode();
        }

        return $GLOBALS['FORUM_DRIVER']->member_profile_hyperlink(intval($ev));
    }

    // ======================
    // Frontend: fields input
    // ======================

    /**
	 * Get form inputter.
	 *
	 * @param  string			The field name
	 * @param  string			The field description
	 * @param  array			The field details
	 * @param  ?string		The actual current value of the field (NULL: none)
	 * @param  boolean		Whether this is for a new entry
	 * @return ?tempcode		The Tempcode for the input field (NULL: skip the field - it's not input)
	 */
    public function get_field_inputter($_cf_name,$_cf_description,$field,$actual_value,$new)
    {
        if (is_null($actual_value)) {
            $actual_value = '';
        } // Plug anomaly due to unusual corruption
        if ($actual_value == '') {
            if ($field['cf_default'] == '!') {
                $actual_value = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
            }
        } else {
            $actual_value = $GLOBALS['FORUM_DRIVER']->get_username(intval($actual_value));
        }
        return form_input_username($_cf_name,$_cf_description,'field_' . strval($field['id']),$actual_value,$field['cf_required'] == 1);
    }

    /**
	 * Find the posted value from the get_field_inputter field
	 *
	 * @param  boolean		Whether we were editing (because on edit, it could be a fractional edit)
	 * @param  array			The field details
	 * @param  ?string		Where the files will be uploaded to (NULL: do not store an upload, return NULL if we would need to do so)
	 * @param  ?array			Former value of field (NULL: none)
	 * @return ?string		The value (NULL: could not process)
	 */
    public function inputted_to_field_value($editing,$field,$upload_dir = 'uploads/catalogues',$old_value = null)
    {
        $id = $field['id'];
        $tmp_name = 'field_' . strval($id);
        $value = post_param($tmp_name,strval(INTEGER_MAGIC_NULL));
        if (($value != '') && ($value != strval(INTEGER_MAGIC_NULL))) {
            $member_id = $GLOBALS['FORUM_DRIVER']->get_member_from_username($value);
            $value = is_null($member_id)?'':strval($member_id);
        }
        return $value;
    }
}

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
 * @package    core_abstract_interfaces
 */

/**
 * Get the tempcode for a results table.
 *
 * @param  mixed                        $text_id Some text/word describing what is being browsed (Tempcode or string)
 * @param  integer                      $start The result number our table starts at (x of n)
 * @param  ID_TEXT                      $start_name The parameter name used to store our position in the results (usually, 'start')
 * @param  integer                      $max The total number of results to show per-page
 * @param  ID_TEXT                      $max_name The parameter name used to store the total number of results to show per-page (usually, 'max')
 * @param  integer                      $max_rows The maximum number of rows in the entire dataset
 * @param  tempcode                     $fields_title The titles of the fields we are showing in our table, presented in preprepared tempcode
 * @param  tempcode                     $fields The values of the fields we are showing in our table
 * @param  ?array                       $sortables A map of sortable code (usually, db field names), to strings giving the human name for the sort order (null: no sortables)
 * @param  ?ID_TEXT                     $sortable The current sortable (null: none)
 * @param  ?ID_TEXT                     $sort_order The order we are sorting in (null: none)
 * @set    ASC DESC
 * @param  ?ID_TEXT                     $sort_name The parameter name used to store our sortable (usually 'sort') (null: none)
 * @param  ?tempcode                    $message Message to show (null: auto)
 * @param  ?array                       $widths Widths to specify to the table (null: none sent)
 * @param  ?string                      $tplset The template set to use (null: default)
 * @param  integer                      $max_page_links The maximum number of quick-jump page-links to show
 * @param  string                       $guid GUID to pass to template
 * @param  boolean                      $skip_sortables_form Whether to skip showing a sort form (useful if there is another form wrapped around this)
 * @param  ?ID_TEXT                     $hash URL hash component (null: none)
 * @return tempcode                     The results table
 */
function results_table($text_id, $start, $start_name, $max, $max_name, $max_rows, $fields_title, $fields, $sortables = null, $sortable = null, $sort_order = null, $sort_name = 'sort', $message = null, $widths = null, $tplset = null, $max_page_links = 8, $guid = '1c8645bc2a3ff5bec2e003142185561f', $skip_sortables_form = false, $hash = null)
{
    require_code('templates_pagination');

    if (!is_null($sort_name)) {
        inform_non_canonical_parameter($sort_name);
    }

    if (is_null($widths)) {
        $widths = array();
    }

    if (is_null($message)) {
        $message = new Tempcode();
        if (!is_null($sortables)) {
            foreach ($sortables as $_sortable => $text) {
                if (is_object($text)) {
                    $text = $text->evaluate();
                }
                if ($text == do_lang('DATE_TIME')) {
                    $message = paragraph(do_lang_tempcode('CLICK_DATE_FOR_MORE'));
                }
            }
        }
    }

    // Sorting
    if (!is_null($sortables)) {
        $sort = results_sorter($sortables, $sortable, $sort_order, $sort_name, $hash);
    } else {
        $sort = new Tempcode();
    }

    // Pagination
    $pagination = pagination(is_object($text_id) ? $text_id : make_string_tempcode($text_id), $start, $start_name, $max, $max_name, $max_rows, true, $max_page_links, null, is_null($hash) ? '' : $hash);

    return do_template(
        is_null($tplset) ? 'RESULTS_TABLE' : ('RESULTS_' . $tplset . '_TABLE'),
        array(
            '_GUID' => $guid,
            'TEXT_ID' => $text_id,
            'FIELDS_TITLE' => $fields_title,
            'FIELDS' => $fields,
            'MESSAGE' => $message,
            'SORT' => $skip_sortables_form ? new Tempcode() : $sort,
            'PAGINATION' => $pagination,
            'WIDTHS' => $widths
        ),
        null,
        false,
        'RESULTS_TABLE'
    );
}

/**
 * Get the tempcode for a results sorter.
 *
 * @param  ?array                       $sortables A map of sortable code (usually, db field names), to strings giving the human name for the sort order (null: no sortables)
 * @param  ?ID_TEXT                     $sortable The current sortable (null: none)
 * @param  ?ID_TEXT                     $sort_order The order we are sorting in (null: none)
 * @set    ASC DESC
 * @param  ?ID_TEXT                     $sort_name The parameter name used to store our sortable (usually 'sort') (null: none)
 * @param  ?ID_TEXT                     $hash URL hash component (null: none)
 * @return tempcode                     The results sorter
 */
function results_sorter($sortables, $sortable = null, $sort_order = null, $sort_name = 'sort', $hash = '')
{
    require_code('templates_pagination'); // Required because INCREMENTAL_ID_GENERATOR defined there

    $selectors = new Tempcode();
    foreach ($sortables as $_sortable => $text) {
        $text_ascending = new Tempcode();
        $text_ascending->attach($text);
        if ($_sortable != 'random') {
            $text_ascending->attach(do_lang_tempcode('_ASCENDING'));
        }
        $text_descending = new Tempcode();
        $text_descending->attach($text);
        $text_descending->attach(do_lang_tempcode('_DESCENDING'));
        $selector_value = $_sortable . ' ASC';
        $selected = (($sortable . ' ' . $sort_order) == $selector_value);
        $selectors->attach(do_template('PAGINATION_SORTER', array('_GUID' => '6a57bbaeed04743ba2cafa2d262a1c98', 'SELECTED' => $selected, 'NAME' => $text_ascending, 'VALUE' => $selector_value)));
        $selector_value = $_sortable . ' DESC';
        if ($_sortable != 'random') {
            $selected = (($sortable . ' ' . $sort_order) == $selector_value);
            $selectors->attach(do_template('PAGINATION_SORTER', array('_GUID' => 'bbf97817fa4f5e744a414b303a3d21fe', 'SELECTED' => $selected, 'NAME' => $text_descending, 'VALUE' => $selector_value)));
        }
    }
    $sort_url = get_self_url(false, false, array($sort_name => null));
    if ($selectors->is_empty()) {
        $sort = new Tempcode();
    } else {
        $sort = do_template('PAGINATION_SORT', array('_GUID' => '4afa1bae0f447b68e60192c515b13ca2', 'HASH' => $hash, 'SORT' => $sort_name, 'URL' => $sort_url, 'SELECTORS' => $selectors));
    }
    $GLOBALS['INCREMENTAL_ID_GENERATOR']++;
    return $sort;
}

/**
 * Get the tempcode for a results entry. You would gather together the outputs of several of these functions, then put them in as the $fields in a results_table function call.
 *
 * @param  array                        $values The array of values that make up this entry (of tempcode or string, or mixture)
 * @param  boolean                      $auto_escape Whether to automatically escape each entry so that it cannot contain HTML
 * @param  ?string                      $tplset The template set to use (null: default)
 * @param  string                       $guid GUID to pass to template
 * @return tempcode                     The generated entry
 */
function results_entry($values, $auto_escape = false, $tplset = null, $guid = '9e340dd14173c7320b57243d607718ab')
{
    $cells = new Tempcode();
    foreach ($values as $class => $value) {
        if (($auto_escape) && (!is_object($value))) {
            $value = escape_html($value);
        }
        $cells->attach(do_template(is_null($tplset) ? 'RESULTS_TABLE_FIELD' : ('RESULTS_TABLE_' . $tplset . '_FIELD'), array('_GUID' => $guid, 'VALUE' => $value, 'CLASS' => (is_string($class)) ? $class : ''), null, false, 'RESULTS_TABLE_FIELD'));
    }

    return do_template(is_null($tplset) ? 'RESULTS_TABLE_ENTRY' : ('RESULTS_TABLE_' . $tplset . '_ENTRY'), array('_GUID' => $guid, 'VALUES' => $cells), null, false, 'RESULTS_TABLE_ENTRY');
}

/**
 * Get the tempcode for a results table title row. You would take the output of this, and feed it in as $fields_title, in a results_table function call.
 *
 * @param  array                        $values The array of field titles that define the entries in the results table
 * @param  ?array                       $sortables A map of sortable code (usually, db field names), to strings giving the human name for the sort order (null: no sortables)
 * @param  ID_TEXT                      $order_param The parameter name used to store our sortable
 * @param  ID_TEXT                      $current_ordering The current ordering ("$sortable $sort_order")
 * @param  string                       $guid GUID to pass to template
 * @return tempcode                     The generated title
 */
function results_field_title($values, $sortables = null, $order_param = 'sort', $current_ordering = '', $guid = 'fbcaf8b021e3939bfce1dce9ff8ed63a')
{
    if (is_null($sortables)) {
        $sortables = array();
    }

    $cells = new Tempcode();
    foreach ($values as $value) {
        $found = mixed();
        foreach ($sortables as $key => $sortable) {
            $_value = is_object($value) ? $value->evaluate() : $value;
            if (((is_string($sortable)) && ($sortable == $_value)) || ((is_object($sortable)) && ($sortable->evaluate() == $_value))) {
                $found = $key;
                break;
            }
        }
        if (!is_null($found)) {
            $sort_url_asc = get_self_url(false, false, array($order_param => $found . ' ASC'), true);
            $sort_url_desc = get_self_url(false, false, array($order_param => $found . ' DESC'), true);
            $sort_asc_selected = ($current_ordering == $found . ' ASC');
            $sort_desc_selected = ($current_ordering == $found . ' DESC');
            $cells->attach(do_template('RESULTS_TABLE_FIELD_TITLE_SORTABLE', array('_GUID' => 'e71df89abff7c7d51907867924dbfa7e', 'VALUE' => $value, 'SORT_ASC_SELECTED' => $sort_asc_selected, 'SORT_DESC_SELECTED' => $sort_desc_selected, 'SORT_URL_DESC' => $sort_url_desc, 'SORT_URL_ASC' => $sort_url_asc)));
        } else {
            $cells->attach(do_template('RESULTS_TABLE_FIELD_TITLE', array('_GUID' => '80e9de91bb9e479766bc8568a790735c', 'VALUE' => $value)));
        }
    }

    return $cells;
}

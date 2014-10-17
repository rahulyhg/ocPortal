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

/*
SYNTAX...
An ocFilter is a comma-separated list of match specifier tokens.
A match specifier may be:
 - an acceptable-literal (e.g. '1').
 - an avoiding-literal (e.g. '!1')
 - a bounded acceptable-range (e.g. '1-3')
 - a non-bounded acceptable-range (e.g. '3+')
 - an acceptable subtree (e.g. '3*')
 - an acceptable set of direct descendents (e.g. '3>')
 - an avoiding subtree (e.g. '3~')
 - all-acceptable '*'
Note that:
 - this will work on string IDs as well as numeric IDs (except of course for the range specifiers) -- as the string IDs do not contain any special symbols (!-+*~,>).
 - subtree specifiers work on category-sets rather than record-sets. In other words, it's a different set of IDs, unless the category-set equals the record-set for the specific case. It is possible that there could be no category-set available, in which case subtree specifiers will produce no effect.
 - nothing is accepted by default. If you want this, add '*' into your ocFilter.
 - avoidance overrides acceptance, and there is no ordering. For example, "!3,3*" would get everything under category 3 except ID#3 (if our record-set equals our category-set, this example makes more sense as something useful)
 - whilst ocFilter isn't fully expressive, almost anything can be achieved with a little thought. There is no practical reason to need brackets, order-support, etc.
 - for record searching, look at ocSelect, the companion language

EXAMPLE CALLS...
$results=ocfilter_to_sqlfragment('1,3-10,!6,12*','id','download_categories','parent_id','cat','id');
$results=ocfilter_to_idlist_using_db('1,3-10,!6,12*','downloads','id','download_categories','parent_id','cat','id');
$results=ocfilter_to_idlist_using_memory('1,3-10,!6,12*',array(1=>2,2=>2,3=>2,4=>3),'download_categories','parent_id','cat','id');
$results=ocfilter_to_idlist_using_callback('1,3-10,!6,12*','_callback_get_download_structure','download_categories','parent_id','cat','id');
*/

/**
 * Helper function to generate an SQL "not equal to" fragment.
 *
 * @param  string                       The field name
 * @param  string                       The string value (may actually hold an integer, if $numeric)
 * @param  boolean                      Whether the value is numeric
 * @return string                       SQL fragment
 */
function _ocfilter_neq($field_name, $var, $numeric)
{
    if ($numeric) {
        return $field_name . '<>' . strval(intval($var));
    } else {
        return db_string_not_equal_to($field_name, $var);
    }
}

/**
 * Helper function to generate an SQL "equal to" fragment.
 *
 * @param  string                       The field name
 * @param  string                       The string value (may actually hold an integer, if $numeric)
 * @param  boolean                      Whether the value is numeric
 * @return string                       SQL fragment
 */
function _ocfilter_eq($field_name, $var, $numeric)
{
    if ($numeric) {
        return $field_name . '=' . strval(intval($var));
    } else {
        return db_string_equal_to($field_name, $var);
    }
}

/**
 * Helper function to fetch a subtree from the database.
 *
 * @param  string                       The category-ID we are searching under
 * @param  ?string                      The database's table that contains parent/child relationships in the record-set's category-set (the category-set is equal to the record-set if we're matching categories, but not if we're matching entries) (NULL: don't support subtree [*-style] searches)
 * @param  ?string                      The database's field name for the category-set's parent-category-ID (NULL: don't support subtree [*-style] searches beyond the tree base)
 * @param  string                       The database's field name for the category-set's category-ID
 * @param  boolean                      Whether the category-set IDs are numeric
 * @param  object                       Database connection to use
 * @param  array                        A place to store cached data we've already loaded once in this function. Pass in an NULL variable (not a NULL literal)
 * @param  boolean                      Whether this is the base call to this recursive function (just leave it as the default, true)
 * @param  boolean                      Whether to run recursively
 * @return array                        Subtree: list of IDs in category-set
 */
function _ocfilter_subtree_fetch($look_under, $table_name, $parent_name, $field_name, $numeric_ids, $db, &$cached_mappings, $first = true, $recurse = true)
{
    $under = array();

    if ($table_name === null) {
        return $under;
    }

    if ($first) { // We want base of subtree to be included
        $under[] = $numeric_ids ? intval($look_under) : $look_under;
    }

    if ($parent_name === null) {
        return $under;
    }

    if (get_value('lots_of_data_in_' . $table_name) !== null) {
        if ($numeric_ids) {
            $children = $db->query_select($table_name, array($field_name), array($parent_name => intval($look_under)), '', 400/*reasonable limit*/);
        } else {
            $children = $db->query_select($table_name, array($field_name), array($parent_name => $look_under), '', 400/*reasonable limit*/);
        }
        foreach ($children as $child) {
            $under[] = $child[$field_name];
            if ($recurse) {
                $under = array_merge($under, _ocfilter_subtree_fetch($child[$field_name], $table_name, $parent_name, $field_name, $numeric_ids, $db, $cached_mappings));
            }
        }
    } else {
        if ($cached_mappings === null) {
            $cached_mappings = $db->query_select($table_name, array($field_name, $parent_name), null, '', 1000/*reasonable limit*/);
        }

        $cached_mappings_copy = $cached_mappings; // Works around weird PHP bug in some versions (due to recursing over reference parameter)
        foreach ($cached_mappings_copy as $child) {
            if (($child[$parent_name] !== null) && ((($numeric_ids) && ($child[$parent_name] == intval($look_under))) || ((!$numeric_ids) && ($child[$parent_name] == $look_under)))) {
                $under[] = $child[$field_name];

                if ($recurse) {
                    $under = array_merge($under, _ocfilter_subtree_fetch(is_integer($child[$field_name]) ? strval($child[$field_name]) : $child[$field_name], $table_name, $parent_name, $field_name, $numeric_ids, $db, $cached_mappings, false));
                }
            }
        }
    }
    return $under;
}

/**
 * Helper function to fetch a subtree from the database.
 *
 * @param  string                       The ID field name in the record-set
 * @param  string                       The table name of the record-set
 * @param  ?string                      The database's field name for the category-set's parent-category-ID (NULL: don't support subtree [*-style] searches)
 * @param  object                       Database connection to use
 * @return array                        A map between record-set IDs and record-set parent-category-IDs
 */
function _ocfilter_find_ids_and_parents($field_name, $table_name, $parent_field_name, $db)
{
    if ($parent_field_name === null) {
        return array();
    }

    $rows = $db->query_select($table_name, ($parent_field_name === null) ? array($field_name) : array($field_name, $parent_field_name));
    $ret = array();

    foreach ($rows as $row) {
        $ret[$row[$field_name]] = ($parent_field_name === null) ? '' : $row[$parent_field_name];
    }
    return $ret;
}

/**
 * Function to do an actual data lookup sourced via the database, used as a kind of a callback function (it's name gets passed into the generic API).
 *
 * @param  ?string                      The database's table for the record-set we're matching (NULL: use a different lookup method)
 * @param  ?string                      The database's ID field for the record-set we're matching (NULL: use a different lookup method)
 * @param  ?string                      The database's field name for the record-set's container-category specifier (NULL: don't support subtree [*-style] searches)
 * @param  boolean                      Whether there are parents in the filter
 * @param  ?object                      Database connection to use (NULL: website)
 * @return array                        A list of ID numbers
 */
function _ocfilter_to_generic_callback($table_name, $field_name, $parent_field_name, $has_no_parents, $db)
{
    $vals = $db->query_select($table_name, $has_no_parents ? array($field_name) : array($field_name, $parent_field_name));
    $out = array();
    foreach ($vals as $x) {
        $out[$x[$field_name]] = $has_no_parents ? null : $x[$parent_field_name];
    }
    return $out;
}

/**
 * Turn an ocFilter (a filter specifying which records to match) into a list of ID numbers, relying on the database to extract the record-set.
 *
 * @param  string                       The filter
 * @param  ?string                      The database's ID field for the record-set we're matching (NULL: use a different lookup method)
 * @param  ?string                      The database's table for the record-set we're matching (NULL: use a different lookup method)
 * @param  ?array                       A map between record-set IDs and record-set parent-category-IDs (NULL: use a different lookup method)
 * @param  ?mixed                       A call_user_func_array specifier to a function that will give a map between record-set IDs and record-set parent-category-IDs. We pass a call_user_func_array specifier because we don't want to have to generate it unless we need to (if we need to do 'avoiding' matches or 'subtree' matches) (NULL: use a different lookup method)
 * @param  ?string                      The database's table that contains parent/child relationships in the record-set's category-set (the category-set is equal to the record-set if we're matching categories, but not if we're matching entries) (NULL: don't support subtree [*-style] searches)
 * @param  ?string                      The database's field name for the category-set's parent-category-ID (NULL: don't support subtree [*-style] searches beyond the tree base)
 * @param  ?string                      The database's field name for the record-set's container-category specifier (NULL: don't support subtree [*-style] searches)
 * @param  ?string                      The database's field name for the category-set's category-ID (NULL: don't support subtree [*-style] searches beyond the tree base)
 * @param  boolean                      Whether the record-set IDs are numeric
 * @param  boolean                      Whether the category-set IDs are numeric
 * @param  ?object                      Database connection to use (NULL: website)
 * @return array                        A list of ID numbers
 */
function _ocfilter_to_generic($filter, $field_name, $table_name, $ids_and_parents, $ids_and_parents_callback, $parent_spec__table_name, $parent_spec__parent_name, $parent_field_name, $parent_spec__field_name, $numeric_record_set_ids, $numeric_category_set_ids, $db)
{
    if ($db === null) {
        $db = $GLOBALS['SITE_DB'];
    }

    if ($filter == '') {
        return array();
    }

    if ($parent_spec__table_name !== null) {
        if (($parent_field_name === null) || ($parent_spec__field_name === null)) {
            fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
    } else {
        if (($parent_spec__parent_name !== null) || ($parent_field_name !== null) || ($parent_spec__field_name !== null)) {
            fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
    }

    $out_accept = array();
    $out_avoid = array();

    $cached_mappings = mixed();

    if (($ids_and_parents === null) && ($ids_and_parents_callback === null)) {
        $has_no_parents = ($parent_field_name === null);
        $ids_and_parents_callback = array('_ocfilter_to_generic_callback', array($table_name, $field_name, $parent_field_name, $has_no_parents));
    }

    // Support read_multi_code subsyntax also (this isn't user-edited normally, but we like to be able to use the same ocfilter API)
    if (substr($filter, 0, 1) == '+') {
        $filter = substr($filter, 1);
    } elseif (substr($filter, 0, 1) == '-') {
        $filter = substr($filter, 1);
        $tokens = explode(',', $filter);
        foreach ($tokens as $i => $token) {
            $token = trim($token);

            if (is_numeric($token)) {
                $token = '!' . $token;
            }
            $tokens[$i] = $token;
        }
        $tokens[] = '*';
        $filter = implode(',', $tokens);
    }

    $tokens = explode(',', $filter);
    $matches = array();
    foreach ($tokens as $token) {
        $token = trim($token);

        if ($token == '*') { // '*'
            if ($ids_and_parents === null) {
                if ($field_name !== null) {
                    $ids_and_parents = call_user_func_array($ids_and_parents_callback[0], array_merge($ids_and_parents_callback[1], array($db)));
                } else {
                    $ids_and_parents = _ocfilter_find_ids_and_parents($field_name, $table_name, $parent_field_name, $db);
                }
            }
            foreach (array_keys($ids_and_parents) as $id) {
                $out_accept[] = $numeric_record_set_ids ? $id : strval($id);
            }
        } elseif (preg_match('#^\!(.*)$#', $token, $matches) != 0) { // e.g. '!1'
            if ($matches[1] != '') {// Likely came from referencing some Tempcode that didn't return a result
                $out_avoid[] = $numeric_record_set_ids ? intval($matches[1]) : $matches[1];
            }
        } elseif (($numeric_record_set_ids) && (preg_match('#^(\d+)\-(\d+)$#', $token, $matches) != 0)) { // e.g. '1-3')
            for ($i = intval($matches[1]); $i <= intval($matches[2]); $i++) {
                if ($numeric_record_set_ids) {
                    $out_accept[] = $i;
                } else {
                    $out_accept[] = strval($i);
                }
            }
        } elseif (($numeric_record_set_ids) && (preg_match('#^(\d+)\+$#', $token, $matches) != 0)) { // e.g. '3+'
            if ($ids_and_parents === null) {
                if ($field_name !== null) {
                    $ids_and_parents = call_user_func_array($ids_and_parents_callback[0], array_merge($ids_and_parents_callback[1], array($db)));
                } else {
                    $ids_and_parents = _ocfilter_find_ids_and_parents($field_name, $table_name, $parent_field_name, $db);
                }
            }
            foreach (array_keys($ids_and_parents) as $id) {
                if (is_string($id)) {
                    $id = intval($id);
                }
                if ($id >= intval($matches[1])) {
                    if ($numeric_record_set_ids) {
                        $out_accept[] = $id;
                    } else {
                        $out_accept[] = strval($id);
                    }
                }
            }
        } elseif (preg_match('#^(.+)(\*|>)$#', $token, $matches) != 0) { // e.g. '3*'
            if ($ids_and_parents === null) {
                if ($field_name !== null) {
                    $ids_and_parents = call_user_func_array($ids_and_parents_callback[0], array_merge($ids_and_parents_callback[1], array($db)));
                } else {
                    $ids_and_parents = _ocfilter_find_ids_and_parents($field_name, $table_name, $parent_field_name, $db);
                }
            }
            $subtree = _ocfilter_subtree_fetch($matches[1], $parent_spec__table_name, $parent_spec__parent_name, $parent_spec__field_name, $numeric_category_set_ids, $db, $cached_mappings, $matches[2] != '>', $matches[2] != '>');

            foreach ($subtree as $subtree_i) {
                foreach ($ids_and_parents as $id => $parent_id) {
                    if (!is_string($parent_id)) {
                        $parent_id = is_null($parent_id) ? '' : strval($parent_id);
                    }
                    if (!is_string($subtree_i)) {
                        $subtree_i = strval($subtree_i);
                    }
                    if ($parent_id == $subtree_i) {
                        if ($numeric_record_set_ids) {
                            $out_accept[] = intval($id);
                        } else {
                            $out_accept[] = $id;
                        }
                    }
                }
            }
        } elseif (preg_match('#^(.+)\~$#', $token, $matches) != 0) { // e.g. '3~'
            if ($ids_and_parents === null) {
                if ($field_name !== null) {
                    $ids_and_parents = call_user_func_array($ids_and_parents_callback[0], array_merge($ids_and_parents_callback[1], array($db)));
                } else {
                    $ids_and_parents = _ocfilter_find_ids_and_parents($field_name, $table_name, $parent_field_name, $db);
                }
            }
            $subtree = _ocfilter_subtree_fetch($matches[1], $parent_spec__table_name, $parent_spec__parent_name, $parent_spec__field_name, $numeric_category_set_ids, $db, $cached_mappings);
            foreach ($subtree as $subtree_i) {
                foreach ($ids_and_parents as $id => $parent_id) {
                    if ($parent_id == $subtree_i) {
                        if ($numeric_record_set_ids) {
                            $out_avoid[] = intval($id);
                        } else {
                            $out_avoid[] = $id;
                        }
                    }
                }
            }
        } else { // e.g. "1"
            if ($numeric_record_set_ids) {
                $out_accept[] = intval($token);
            } else {
                $out_accept[] = $token;
            }
        }
    }

    return array_diff($out_accept, $out_avoid);
}

/**
 * Turn an ocFilter (a filter specifying which records to match) into a list of ID numbers, relying on the database to extract the record-set.
 *
 * @param  string                       The filter
 * @param  string                       The database's ID field for the record-set we're matching
 * @param  string                       The database's table for the record-set we're matching
 * @param  ?string                      The database's table that contains parent/child relationships in the record-set's category-set (the category-set is equal to the record-set if we're matching categories, but not if we're matching entries) (NULL: don't support subtree [*-style] searches)
 * @param  ?string                      The database's field name for the category-set's parent-category-ID (NULL: don't support subtree [*-style] searches beyond the tree base)
 * @param  ?string                      The database's field name for the record-set's container-category specifier (NULL: don't support subtree [*-style] searches)
 * @param  ?string                      The database's field name for the category-set's category-ID (NULL: don't support subtree [*-style] searches beyond the tree base)
 * @param  boolean                      Whether the record-set IDs are numeric
 * @param  boolean                      Whether the category-set IDs are numeric
 * @param  ?object                      Database connection to use (NULL: website)
 * @return array                        A list of ID numbers
 */
function ocfilter_to_idlist_using_db($filter, $field_name, $table_name, $parent_spec__table_name = null, $parent_spec__parent_name = null, $parent_field_name = null, $parent_spec__field_name = null, $numeric_record_set_ids = true, $numeric_category_set_ids = true, $db = null)
{
    return _ocfilter_to_generic($filter, $field_name, $table_name, null, null, $parent_spec__table_name, $parent_spec__parent_name, $parent_field_name, $parent_spec__field_name, $numeric_record_set_ids, $numeric_category_set_ids, $db);
}

/**
 * Turn an ocFilter (a filter specifying which records to match) into a list of ID numbers, using a prebuilt memory representation of the record-set.
 *
 * @param  string                       The filter
 * @param  array                        A map between record-set IDs and record-set parent-category-IDs
 * @param  ?string                      The database's table that contains parent/child relationships in the record-set's category-set (the category-set is equal to the record-set if we're matching categories, but not if we're matching entries) (NULL: don't support subtree [*-style] searches)
 * @param  ?string                      The database's field name for the category-set's parent-category-ID (NULL: don't support subtree [*-style] searches beyond the tree base)
 * @param  ?string                      The database's field name for the record-set's container-category specifier (NULL: don't support subtree [*-style] searches)
 * @param  ?string                      The database's field name for the category-set's category-ID (NULL: don't support subtree [*-style] searches beyond the tree base)
 * @param  boolean                      Whether the record-set IDs are numeric
 * @param  boolean                      Whether the category-set IDs are numeric
 * @param  ?object                      Database connection to use (NULL: website)
 * @return array                        A list of ID numbers
 */
function ocfilter_to_idlist_using_memory($filter, $ids_and_parents, $parent_spec__table_name = null, $parent_spec__parent_name = null, $parent_field_name = null, $parent_spec__field_name = null, $numeric_record_set_ids = true, $numeric_category_set_ids = true, $db = null)
{
    return _ocfilter_to_generic($filter, null, null, $ids_and_parents, null, $parent_spec__table_name, $parent_spec__parent_name, $parent_field_name, $parent_spec__field_name, $numeric_record_set_ids, $numeric_category_set_ids, $db);
}

/**
 * Turn an ocFilter (a filter specifying which records to match) into a list of ID numbers.
 *
 * @param  string                       The filter
 * @param  string                       A call_user_func_array specifier to a function that will give a map between record-set IDs and record-set parent-category-IDs. We pass a call_user_func_array specifier because we don't want to have to generate it unless we need to (if we need to do 'avoiding' matches or 'subtree' matches)
 * @param  ?string                      The database's table that contains parent/child relationships in the record-set's category-set (the category-set is equal to the record-set if we're matching categories, but not if we're matching entries) (NULL: don't support subtree [*-style] searches)
 * @param  ?string                      The database's field name for the category-set's parent-category-ID (NULL: don't support subtree [*-style] searches beyond the tree base)
 * @param  ?string                      The database's field name for the record-set's container-category specifier (NULL: don't support subtree [*-style] searches)
 * @param  ?string                      The database's field name for the category-set's category-ID (NULL: don't support subtree [*-style] searches beyond the tree base)
 * @param  boolean                      Whether the record-set IDs are numeric
 * @param  boolean                      Whether the category-set IDs are numeric
 * @param  ?object                      Database connection to use (NULL: website)
 * @return array                        A list of ID numbers
 */
function ocfilter_to_idlist_using_callback($filter, $ids_and_parents_callback, $parent_spec__table_name = null, $parent_spec__parent_name = null, $parent_field_name = null, $parent_spec__field_name = null, $numeric_record_set_ids = true, $numeric_category_set_ids = true, $db = null)
{
    return _ocfilter_to_generic($filter, null, null, null, $ids_and_parents_callback, $parent_spec__table_name, $parent_spec__parent_name, $parent_field_name, $parent_spec__field_name, $numeric_record_set_ids, $numeric_category_set_ids, $db);
}

/**
 * Turn an ocFilter (a filter specifying which records to match) into an SQL query fragment.
 *
 * @param  string                       The filter
 * @param  string                       The database's ID field for the record-set we're matching. E.g. 'id'.
 * @param  ?string                      The database's table that contains parent/child relationships in the record-set's category-set (the category-set is equal to the record-set if we're matching categories, but not if we're matching entries) (NULL: don't support subtree [*-style] searches). E.g. 'categories'.
 * @param  ?string                      The database's field name for the category-set's parent-category-ID (NULL: don't support subtree [*-style] searches beyond the tree base). E.g. 'parent_id'.
 * @param  ?string                      The database's field name for the record-set's container-category specifier (NULL: don't support subtree [*-style] searches). E.g. 'cat'.
 * @param  ?string                      The database's field name for the category-set's category-ID (NULL: don't support subtree [*-style] searches beyond the tree base). E.g. 'id'.
 * @param  boolean                      Whether the record-set IDs are numeric
 * @param  boolean                      Whether the category-set IDs are numeric
 * @param  ?object                      Database connection to use (NULL: website)
 * @return string                       SQL query fragment. Note that brackets will be put around this automatically if required, so there's no need to do this yourself.
 */
function ocfilter_to_sqlfragment($filter, $field_name, $parent_spec__table_name = null, $parent_spec__parent_name = null, $parent_field_name = null, $parent_spec__field_name = null, $numeric_record_set_ids = true, $numeric_category_set_ids = true, $db = null)
{
    if ($db === null) {
        $db = $GLOBALS['SITE_DB'];
    }

    if ($filter == '') {
        return '1=2';
    }
    if ($filter == '*') {
        return '1=1';
    }
    if ($parent_spec__table_name !== 'catalogue_categories') {
        if ($filter == strval(db_get_first_id()) . '*') {
            return '1=1';
        }
    }

    if ($parent_spec__table_name === null) {
        if (($parent_spec__parent_name !== null) || ($parent_field_name !== null) || ($parent_spec__field_name !== null)) {
            fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }
    }

    $out_or = '';
    $out_and = '';

    $cached_mappings = mixed();

    $tokens = explode(',', $filter);
    $matches = array();
    foreach ($tokens as $token) {
        $token = trim($token);

        if ($token == '*') { // '*'
            if ($out_or != '') {
                $out_or .= ' OR ';
            }
            $out_or .= '1=1';
        } elseif (preg_match('#^\!(.*)$#', $token, $matches) != 0) { // e.g. '!1'
            if ($matches[1] != '') { // Likely came from referencing some Tempcode that didn't return a result
                if ($out_and != '') {
                    $out_and .= ' AND ';
                }
                $out_and .= _ocfilter_neq($field_name, $matches[1], $numeric_record_set_ids);
            }
        } elseif (($numeric_record_set_ids) && (preg_match('#^(\d+)\-(\d+)$#', $token, $matches) != 0)) { // e.g. '1-3')
            for ($i = intval($matches[1]); $i <= intval($matches[2]); $i++) {
                if ($out_or != '') {
                    $out_or .= ' OR ';
                }
                $out_or .= _ocfilter_eq($field_name, strval($i), $numeric_record_set_ids);
            }
        } elseif (($numeric_record_set_ids) && (preg_match('#^(\d+)\+$#', $token, $matches) != 0)) { // e.g. '3+'
            if ($out_or != '') {
                $out_or .= ' OR ';
            }
            $out_or .= $field_name . '>=' . strval(intval($matches[1]));
        } elseif ((preg_match('#^(.+)(\*|>)$#', $token, $matches) != 0) && ($parent_spec__parent_name !== null)) { // e.g. '3*'
            if (($parent_spec__table_name == 'catalogue_categories') && (strpos($field_name, 'c_name') === false) && ($parent_field_name == 'cc_id') && ($matches[2] != '>') && (db_has_subqueries($db->connection_read))) { // Special case (optimisation) for catalogues
                // MySQL should be smart enough to not enumerate the 'IN' clause here, which would be bad - instead it can jump into the embedded WHERE clause on each test iteration
                $this_details = $db->query_select('catalogue_categories cc JOIN ' . $db->get_table_prefix() . 'catalogues c ON c.c_name=cc.c_name', array('cc_parent_id', 'cc.c_name', 'c_is_tree'), array('id' => intval($matches[1])), '', 1);
                if ($this_details[0]['c_is_tree'] == 0) {
                    $out_or .= _ocfilter_eq($parent_field_name, $matches[1], $numeric_category_set_ids);
                } elseif (is_null($this_details[0]['cc_parent_id'])) {
                    if ($this_details[0]['cc_parent_id'] === null) {
                        $out_or .= db_string_equal_to('c_name', $this_details[0]['c_name']);
                    } else {
                        $out_or .= $parent_field_name . ' IN (SELECT cc_id FROM ' . $db->get_table_prefix() . 'catalogue_cat_treecache WHERE cc_ancestor_id=' . strval(intval($matches[1])) . ')';
                    }
                } else {
                    $out_or = '1=0';
                }
            } else {
                $subtree = _ocfilter_subtree_fetch($matches[1], $parent_spec__table_name, $parent_spec__parent_name, $parent_spec__field_name, $numeric_category_set_ids, $db, $cached_mappings, $matches[2] != '>', $matches[2] != '>');
                foreach ($subtree as $ii) {
                    if ($out_or != '') {
                        $out_or .= ' OR ';
                    }
                    $out_or .= _ocfilter_eq($parent_field_name, is_integer($ii) ? strval($ii) : $ii, $numeric_category_set_ids);
                }
            }
        } elseif ((preg_match('#^(.+)\~$#', $token, $matches) != 0) && ($parent_spec__parent_name !== null)) { // e.g. '3~'
            $subtree = _ocfilter_subtree_fetch($matches[1], $parent_spec__table_name, $parent_spec__parent_name, $parent_spec__field_name, $numeric_category_set_ids, $db, $cached_mappings);
            foreach ($subtree as $ii) {
                if ($out_and != '') {
                    $out_and .= ' AND ';
                }
                $out_and .= _ocfilter_neq($parent_field_name, is_integer($ii) ? strval($ii) : $ii, $numeric_category_set_ids);
            }
        } else { // e.g. "1"
            if ($out_or != '') {
                $out_or .= ' OR ';
            }
            $out_or .= _ocfilter_eq($field_name, $token, $numeric_record_set_ids);
        }
    }

    if ($out_or == '') {
        return ($out_and == '') ? '0=1' : $out_and;
    }
    if ($out_and == '') {
        return ($out_or == '') ? '0=1' : ('(' . $out_or . ')');
    }

    return '(' . $out_or . ') AND (' . $out_and . ')';
}

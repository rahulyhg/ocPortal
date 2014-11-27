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

This functions in this file are used in the aid of checking whether block parameters are acceptable.
Pre-AJAX the functions are used in conjunction with blocks passing their parameters to templates, which feeds to the 'FACILITATE_AJAX_BLOCK_CALL' symbol which uses the temp_block_permissions table to store what users saw what blocks.
Post-AJAX the functions are used to confirm these permissions (the permission ID is passed in the AJAX request, and gets checked against the particular block/parameters requested).

*/

/**
 * Convert the parameters for a block, to a regexp pattern.
 *
 * @param  array                        $map The parameters
 * @return array                        The parameters, as a pattern
 */
function block_params_to_block_signature($map)
{
    foreach ($map as $key => $val) {
        $map[$key] = preg_quote($val, '#');
    }
    return $map;
}

/**
 * Check whether some block parameters are acceptable.
 *
 * @param  array                        $allowed The acceptable parameter pattern
 * @param  array                        $used The given parameters
 * @return boolean                      Answer
 */
function block_signature_check($allowed, $used)
{
    foreach ($used as $key => $val) {
        if ((!isset($allowed[$key])) || (preg_match('#^' . $allowed[$key] . '$#', $val) == 0)) {
            return false;
        }
    }

    return true;
}

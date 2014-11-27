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
 * @package    printer_friendly_block
 */

/**
 * Block class.
 */
class Block_side_printer_friendly
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (null: block is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array();
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    $map A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        $url = get_self_url(true, false, array('wide_print' => 1));
        return do_template('BLOCK_SIDE_PRINTER_FRIENDLY', array('_GUID' => 'db1d2db67f07a3d6bd130f4cef4c5e9d', 'URL' => $url));
    }
}

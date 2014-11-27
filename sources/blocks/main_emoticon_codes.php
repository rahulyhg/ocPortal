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
 * @package    core_rich_media
 */

/**
 * Block class.
 */
class Block_main_emoticon_codes
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
     * Find cacheing details for the block.
     *
     * @return ?array                   Map of cache details (cache_on and ttl) (null: block is disabled).
     */
    public function cacheing_environment()
    {
        $info = array();
        $info['cache_on'] = 'array(has_privilege(get_member(),\'use_special_emoticons\'))';
        $info['ttl'] = (get_value('no_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 2;
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
        require_code('comcode_compiler');
        require_code('comcode_renderer');

        $smilies = $GLOBALS['FORUM_DRIVER']->find_emoticons(get_member());

        $entries = new Tempcode();
        global $EMOTICON_LEVELS;
        foreach ($smilies as $code => $imgcode) {
            if ((is_null($EMOTICON_LEVELS)) || ($EMOTICON_LEVELS[$code] < 3)) {
                $entries->attach(do_template('BLOCK_MAIN_EMOTICON_CODES_ENTRY', array('_GUID' => '9d723c17133313b327a9485aeb23aa8c', 'CODE' => $code, 'TPL' => do_emoticon($imgcode))));
            }
        }

        return do_template('BLOCK_MAIN_EMOTICON_CODES', array('_GUID' => '56c12281d7e3662b13a7ad7d9958a65c', 'ENTRIES' => $entries));
    }
}

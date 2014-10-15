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
 * @package		testing_platform
 */

/**
 * ocPortal test case class (unit testing).
 */
class bot_list_sync_test_set extends ocp_test_case
{
    public function testBotListInSync()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'aaaa';
        $GLOBALS['BOT_TYPE_CACHE'] = false;
        get_bot_type();

        require_code('files');
        $file_bots = better_parse_ini_file(get_file_base() . '/text/bots.txt');
        ksort($file_bots);

        $_SERVER['HTTP_USER_AGENT'] = '';    // Force away optimisation
        get_bot_type();
        global $BOT_MAP_CACHE;
        ksort($BOT_MAP_CACHE);

        $this->assertTrue($BOT_MAP_CACHE == $file_bots);
    }
}

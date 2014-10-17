<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    testing_platform
 */

/**
 * ocPortal test case class (unit testing).
 */
class cqc_forum_test_set extends ocp_test_case
{
    public function testForum()
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        $result = http_download_file(get_base_url() . '/_tests/codechecker/code_quality.php?subdir=forum&api=1', null, true, false, 'ocPortal', null, null, null, null, null, null, null, null, 10000.0);
        foreach (explode('<br />', $result) as $line) {
            $this->assertTrue(((trim($line) == '') || (substr($line, 0, 5) == 'SKIP:') || (substr($line, 0, 5) == 'DONE ') || (substr($line, 0, 6) == 'FINAL ') || ((strpos($line, 'comment found') !== false) && (strpos($line, '#') !== false)) || (strpos($line, 'FUDGE') !== false) || (strpos($line, 'LEGACY') !== false) || (strpos($line, 'It is best to only have') !== false)), $line);
        }
    }
}

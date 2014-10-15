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
 * @package		devguide
 */

class Block_main_code_documentor
{
    /**
	 * Find details of the block.
	 *
	 * @return ?array	Map of block info (NULL: block is disabled).
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
        $info['parameters'] = array('param');
        return $info;
    }

    /**
	 * Find cacheing details for the block.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: block is disabled).
	 */
    public function cacheing_environment()
    {
        $info = array();
        $info['cache_on'] = 'array(array_key_exists(\'param\',$map)?$map[\'param\']:\'support\')';
        $info['ttl'] = (get_value('no_block_timeout') === '1')?60*60*24*365*5/*5 year timeout*/:120;
        return $info;
    }

    /**
	 * Execute the block.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
    public function run($map)
    {
        require_code('type_validation');
        require_lang('phpdoc');
        require_code('php');
        require_css('devguide');

        disable_php_memory_limit();

        $filename = (array_key_exists('param',$map)?$map['param']:'sources/global2') . '.php';
        if (substr($filename,-8) == '.php.php') {
            $filename = substr($filename,0,strlen($filename)-4);
        }

        $full_path = ((get_file_base() != '')?(get_file_base() . '/'):'') . filter_naughty($filename);
        if (!file_exists($full_path)) {
            return paragraph(do_lang_tempcode('MISSING_RESOURCE'),'','red_alert');
        }

        $_classes = get_php_file_api($filename);

        $classes = new ocp_tempcode();

        foreach ($_classes as $class) {
            if ($class['name'] == '__global') {
                $class['name'] = do_lang('GLOBAL_FUNCTIONS') . '_' . basename($filename);
            }

            $function_summaries = new ocp_tempcode();
            $functions = new ocp_tempcode();

            foreach ($class['functions'] as $function) {
                $ret = render_php_function($function,$class);
                $functions->attach($ret[0]);
                $function_summaries->attach($ret[1]);
            }

            $classes->attach(do_template('PHP_CLASS',array('_GUID' => '5d58fc42c5fd3a5dd190f3f3699610c2','CLASS_NAME' => $class['name'],'FUNCTION_SUMMARIES' => $function_summaries,'FUNCTIONS' => $functions)));
        }

        return do_template('PHP_FILE',array('_GUID' => '6f422e6a6e846d49864d7325b212109f','FILENAME' => $filename,'CLASSES' => $classes));
    }
}

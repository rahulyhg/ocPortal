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
 * @package    search
 */
class Block_main_search
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (NULL: block is disabled).
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
        $info['parameters'] = array('title', 'input_fields', 'limit_to', 'search_under', 'zone', 'sort', 'author', 'days', 'direction', 'only_titles', 'only_search_meta', 'boolean_search', 'conjunctive_operator', 'extra');
        return $info;
    }

    /**
     * Find cacheing details for the block.
     *
     * @return ?array                   Map of cache details (cache_on and ttl) (NULL: block is disabled).
     */
    public function cacheing_environment()
    {
        $info = array();
        $info['cache_on'] = 'array(array_key_exists(\'title\',$map)?$map[\'title\']:NULL,array_key_exists(\'input_fields\',$map)?$map[\'input_fields\']:\'\',array_key_exists(\'extra\',$map)?$map[\'extra\']:\'\',array_key_exists(\'sort\',$map)?$map[\'sort\']:\'relevance\',array_key_exists(\'author\',$map)?$map[\'author\']:\'\',array_key_exists(\'days\',$map)?intval($map[\'days\']):-1,array_key_exists(\'direction\',$map)?$map[\'direction\']:\'DESC\',(array_key_exists(\'only_titles\',$map)?$map[\'only_titles\']:\'\')==\'1\',(array_key_exists(\'only_search_meta\',$map)?$map[\'only_search_meta\']:\'0\')==\'1\',(array_key_exists(\'boolean_search\',$map)?$map[\'boolean_search\']:\'0\')==\'1\',array_key_exists(\'conjunctive_operator\',$map)?$map[\'conjunctive_operator\']:\'AND\',array_key_exists(\'limit_to\',$map)?$map[\'limit_to\']:\'\',array_key_exists(\'search_under\',$map)?$map[\'search_under\']:\'\',array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'search\'))';
        $info['ttl'] = (get_value('no_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 2;
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        require_code('search');
        return do_template('BLOCK_TOP_SEARCH', do_search_block($map));
    }
}

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
 * @package		core
 */

/*EXTRA FUNCTIONS: wincache\_.+*/

/**
 * Cache Driver.
 * @package		core
 */
class ocp_wincache
{
    public $objects_list = null;

    /**
	 * Instruction to load up the objects list.
	 *
	 * @return array			The list of objects
	 */
    public function load_objects_list()
    {
        if (is_null($this->objects_list)) {
            $success = false;
            $this->objects_list = wincache_ucache_get(get_file_base() . 'PERSISTENT_CACHE_OBJECTS',$success);
            if ($this->objects_list === NULL || !$success) {
                $this->objects_list = array();
            }
        }
        return $this->objects_list;
    }

    /**
	 * Get data from the persistent cache.
	 *
	 * @param  string			Key
	 * @param  ?TIME			Minimum timestamp that entries from the cache may hold (NULL: don't care)
	 * @return ?mixed			The data (NULL: not found / NULL entry)
	 */
    public function get($key,$min_cache_date = null)
    {
        $success = false;
        $data = wincache_ucache_get($key,$success);
        if (!$success) {
            return NULL;
        }
        if ((!is_null($min_cache_date)) && ($data[0]<$min_cache_date)) {
            return NULL;
        }
        return $data[1];
    }

    /**
	 * Put data into the persistent cache.
	 *
	 * @param  string			Key
	 * @param  mixed			The data
	 * @param  integer		Various flags (parameter not used)
	 * @param  ?integer		The expiration time in seconds (NULL: no expiry)
	 */
    public function set($key,$data,$flags = 0,$expire_secs = null)
    {
        // Update list of persistent-objects
        $objects_list = $this->load_objects_list();
        if (!array_key_exists($key,$objects_list)) {
            $objects_list[$key] = true;
            wincache_ucache_set(get_file_base() . 'PERSISTENT_CACHE_OBJECTS',$objects_list);
        }

        if ($expire_secs == -1) {
            $expire_secs = 0;
        }
        wincache_ucache_set($key,array(time(),$data),$expire_secs);
    }

    /**
	 * Delete data from the persistent cache.
	 *
	 * @param  string			Key
	 */
    public function delete($key)
    {
        // Update list of persistent-objects
        $objects_list = $this->load_objects_list();
        unset($objects_list[$key]);
        wincache_ucache_set(get_file_base() . 'PERSISTENT_CACHE_OBJECTS',$objects_list);

        wincache_ucache_delete($key);
    }

    /**
	 * Remove all data from the persistent cache.
	 */
    public function flush()
    {
        // Update list of persistent-objects
        $objects_list = array();
        wincache_ucache_set(get_file_base() . 'PERSISTENT_CACHE_OBJECTS',$objects_list);

        wincache_ucache_clear();
    }
}

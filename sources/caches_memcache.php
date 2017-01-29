<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

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

/*EXTRA FUNCTIONS: Memcache*/

/**
 * Cache Driver.
 * @package		core
 */
class memcachecache extends Memcache
{
	protected $object;

	/**
	 * Constructor.
	 */
	public function memcachecache()
	{
		$this->object=new Memcache();
		$this->object->connect('localhost',11211);
	}

	/**
	 * (Plug-in replacement for memcache API) Get data from the persistent cache.
	 *
	 * @param  mixed			Key
	 * @param  ?TIME			Minimum timestamp that entries from the cache may hold (NULL: don't care)
	 * @return ?mixed			The data (NULL: not found / NULL entry)
	 */
	function get($_key,$min_cache_date=NULL)
	{
		$key=serialize($_key);

		$_data=$this->object->get($key);
		if ($_data===false) return NULL;
		$data=unserialize($_data);
		if ((!is_null($min_cache_date)) && ($data[0]<$min_cache_date)) return NULL;
		return $data[1];
	}

	/**
	 * (Plug-in replacement for memcache API) Put data into the persistent cache.
	 *
	 * @param  mixed			Key
	 * @param  mixed			The data
	 * @param  integer		Various flags (parameter not used)
	 * @param  integer		The expiration time in seconds.
	 */
	function set($_key,$data,$flags,$expire_secs)
	{
		$key=serialize($_key);

		// Update list of e-objects
		global $ECACHE_OBJECTS;
		if (!array_key_exists($key,$ECACHE_OBJECTS))
		{
			$ECACHE_OBJECTS[$key]=1;
			$this->set(get_file_base().'ECACHE_OBJECTS',$ECACHE_OBJECTS,0,0);
		}

		$this->object->set($key,serialize(array(time(),$data)),$flags,$expire_secs);
	}

	/**
	 * (Plug-in replacement for memcache API) Delete data from the persistent cache.
	 *
	 * @param  mixed			Key name
	 */
	function delete($_key)
	{
		$key=serialize($_key);

		// Update list of e-objects
		global $ECACHE_OBJECTS;
		unset($ECACHE_OBJECTS[$key]);

		//$this->set(get_file_base().'ECACHE_OBJECTS',$ECACHE_OBJECTS,0,0);

		$this->object->delete($key);
	}

	/**
	 * (Plug-in replacement for memcache API) Remove all data from the persistent cache.
	 */
	function flush()
	{
		$this->object->flush();
	}
}

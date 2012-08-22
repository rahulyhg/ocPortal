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

/**
 * Standard code module initialisation function.
 */
function init__files()
{
	global $DOWNLOAD_LEVEL;
	$DOWNLOAD_LEVEL=0;

	define('IGNORE_DEFAULTS',0);
	// -
	define('IGNORE_ACCESS_CONTROLLERS',1);
	define('IGNORE_CUSTOM_DIR_CONTENTS',2);
	define('IGNORE_HIDDEN_FILES',4);
	define('IGNORE_EDITFROM_FILES',8);
	define('IGNORE_REVISION_FILES',16);
	define('IGNORE_CUSTOM_ZONES',32);
	define('IGNORE_THEMES',64);
	define('IGNORE_CUSTOM_DIR_CONTENTS_CASUAL_OVERRIDE',128);
	define('IGNORE_USER_CUSTOMISE',256);
	define('IGNORE_NONBUNDLED_SCATTERED',512); // Has by default
	define('IGNORE_BUNDLED_VOLATILE',1024); // Has by default
}

/**
 * Find whether we can get away with natural file access, not messing with AFMs, world-writability, etc.
 *
 * @return boolean		Whether we have this
 */
function is_suexec_like()
{
	return (((function_exists('posix_getuid')) && (strpos(@ini_get('disable_functions'),'posix_getuid')===false) && (!isset($_SERVER['HTTP_X_MOSSO_DT'])) && (is_integer(@posix_getuid())) && (@posix_getuid()==@fileowner(get_file_base().'/'.(running_script('install')?'install.php':'index.php'))))
	|| (is_writable_wrap(get_file_base().'/'.(running_script('index')?'index.php':'install.php'))));
}

/**
 * Get the number of bytes for a PHP config option. Code taken from the PHP manual.
 *
 * @param  string			PHP config option value.
 * @return integer		Number of bytes.
 */
function php_return_bytes($val)
{
	$val=trim($val);
	if ($val=='') return 0;
	$last=strtolower($val[strlen($val)-1]);
	$_val=intval($val);
	switch($last)
	{
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$_val*=1024;
		case 'm':
			$_val*=1024;
		case 'k':
			$_val*=1024;
	}

	return $_val;
}

/**
 * Get a formatted-string filesize for the specified file. It is formatted as such: x Mb/Kb/Bytes (or unknown). It is assumed that the file exists.
 *
 * @param  URLPATH		The URL that the file size of is being worked out for. Should be local.
 * @return string			The formatted-string file size
 */
function get_file_size($url)
{
	if (substr($url,0,strlen(get_base_url()))==get_base_url())
		$url=substr($url,strlen(get_base_url()));

	if (!url_is_local($url)) return do_lang('UNKNOWN');

	$_full=rawurldecode($url);
	$_full=get_file_base().'/'.$_full;
	$file_size_bytes=filesize($_full);

	return clean_file_size($file_size_bytes);
}

/**
 * Format the specified filesize.
 *
 * @param  integer		The number of bytes the file has
 * @return string			The formatted-string file size
 */
function clean_file_size($bytes)
{
	if ($bytes<0) return '-'.clean_file_size(-$bytes);

	if (is_null($bytes)) return do_lang('UNKNOWN').' bytes';
	if (floatval($bytes)>2.0*1024.0*1024.0*1024.0) return strval(intval(round(floatval($bytes)/1024.0/1024.0/1024.0))).' Gb';
	if (floatval($bytes)>1024.0*1024.0*1024.0) return float_format(round(floatval($bytes)/1024.0/1024.0/1024.0,2)).' Gb';
	if (floatval($bytes)>2.0*1024.0*1024.0) return strval(intval(round(floatval($bytes)/1024.0/1024.0))).' Mb';
	if (floatval($bytes)>1024.0*1024.0) return float_format(round(floatval($bytes)/1024.0/1024.0,2)).' Mb';
	if (floatval($bytes)>2.0*1024.0) return strval(intval(round(floatval($bytes)/1024.0))).' Kb';
	if (floatval($bytes)>1024.0) return float_format(round(floatval($bytes)/1024.0,2)).' Kb';
	return strval($bytes).' Bytes';
}

/**
 * Get the file extension of the specified file. It returns without a dot.
 *
 * @param  string			The filename
 * @return string			The filename extension (no dot)
 */
function get_file_extension($name)
{
	$dot_pos=strrpos($name,'.');
	if ($dot_pos===false) return '';
	return strtolower(substr($name,$dot_pos+1));
}

/**
 * Parse the specified INI file, and get an array of what it found.
 *
 * @param  ?PATH			The path to the ini file to open (NULL: given contents in $file instead)
 * @param  ?string		The contents of the file (NULL: the file needs opening)
 * @return array			A map of the contents of the ini files
 */
function better_parse_ini_file($filename,$file=NULL)
{
	// NB: 'file()' function not used due to slowness compared to file_get_contents then explode

	if (is_null($file))
	{
		global $FILE_ARRAY;
		if (@is_array($FILE_ARRAY)) $file=file_array_get($filename);
		else $file=file_get_contents($filename,FILE_TEXT);
	}

	$ini_array=array();
	$lines=explode(chr(10),$file);
	foreach ($lines as $line)
	{
		$line=rtrim($line);

		if ($line=='') continue;
		if ($line[0]=='#') continue;

		$bits=explode('=',$line,2);
		if (isset($bits[1]))
		{
			list($property,$value)=$bits;
			$value=trim($value,'"');
			$ini_array[$property]=$value;
		}
	}

	return $ini_array;
}

/**
 * Find whether a file is known to be something that should/could be there but isn't an ocPortal distribution file, or for some other reason should be ignored.
 *
 * @param  string			File path
 * @param  integer		Bitmask of extra stuff to ignore (see IGNORE_* constants)
 * @param  integer		Set this to 0 if you don't want the default IGNORE_* constants to carry through
 * @return boolean		Whether it should be ignored
 */
function should_ignore_file($filepath,$bitmask=0,$bitmask_defaults=1536)
{
	$bitmask=$bitmask | $bitmask_defaults;

	// Normalise
	$filepath=strtolower($filepath);
	if (strpos($filepath,'/')!==false)
	{
		$dir=dirname($filepath);
		$filename=basename($filepath);
	} else
	{
		$dir='';
		$filename=$filepath;
	}

	$ignore_filenames=array(
		'.'=>'.*',
		'..'=>'.*',
		'__macosx'=>'.*',
		'.bash_history'=>'.*',
		'error_log'=>'.*',
		'thumbs.db:encryptable'=>'.*',
		'thumbs.db'=>'.*',
		'.ds_store'=>'.*',
		'_old'=>'.*',
		'.svn'=>'.*',
		'.git'=>'',
		'.gitattributes'=>'',
		'cvs'=>'.*',
		'web-inf'=>'.*',
		'bingsiteauth.xml'=>'',
		'parameters.xml'=>'',
		'manifest.xml'=>'',
		'nbproject'=>'',
		'no_mem_cache'=>'',
		'php.ini'=>'.*',
		'.htpasswd'=>'.*',
		'closed.html'=>'',
		'iirf.ini'=>'',
		'robots.txt'=>'',
		'data.ocp'=>'',
		'install_ok'=>'',
		'install_locked'=>'',
		'install.php'=>'',
		'install.sql'=>'',
		'install1.sql'=>'',
		'install2.sql'=>'',
		'install3.sql'=>'',
		'install4.sql'=>'',
		'user.sql'=>'',
		'postinstall.sql'=>'',
		'restore.php'=>'',
		'info.php.template'=>'',
		'make_files-output-log.html'=>'',
		'400.shtml'=>'',
		'500.shtml'=>'',
		'404.shtml'=>'',
		'403.shtml'=>'',
		'old'=>'.*',
		'gibb'=>'.*',
		'gibberish'=>'.*',
		'.gitignore'=>'',
		'.project'=>'',
		'if_hosted_service.txt'=>'',
		'subs.inc'=>'',
		'docs'=>'data/images',
		'uploads'=>'',
	);
	if (($bitmask & IGNORE_NONBUNDLED_SCATTERED)!=0)
	{
		$ignore_filenames+=array(
			// Non-bundled addon stuff that we can't detect automatically
			'_tests'=>'',
			'killjunk.sh'=>'',
			'transcoder'=>'',
			'facebook_connect.php'=>'',
			'ocworld'=>'',
		);
	}
	if (($bitmask & IGNORE_BUNDLED_VOLATILE)!=0)
	{
		$ignore_filenames+=array(
			// Bundled stuff that is not necessarily in a *_custom dir yet is volatile
			'map.ini'=>'themes',
			'_config.php'=>'',
			'info.php'=>'',
			'ocp_sitemap.xml'=>'',
			'errorlog.php'=>'data_custom',
			'functions.dat'=>'data_custom',
			'breadcrumbs.xml'=>'data_custom',
			'fields.xml'=>'data_custom',
			'execute_temp.php'=>'data_custom',
			'output.log'=>'data_custom/spelling',
			'write.log'=>'data_custom/spelling',
		);
	}
	if (($bitmask & IGNORE_ACCESS_CONTROLLERS)!=0)
	{
		$ignore_filenames+=array(
			'.htaccess'=>'.*',
			'index.html'=>'.*',
		);
	}
	if (($bitmask & IGNORE_USER_CUSTOMISE)!=0) // Ignores directories that user override files go in
	{
		$ignore_filenames+=array(
			'comcode_custom'=>'.*',
			'html_custom'=>'.*',
			'css_custom'=>'.*',
			'templates_custom'=>'.*',
			'images_custom'=>'.*',
			'lang_custom'=>'.*',
			'data_custom'=>'.*',
			'file_backups'=>'.*',
			'text_custom'=>'.*',
			'theme.ini'=>'themes/[^/]*',
		);
	}

	$ignore_extensions=array(
		'tar'=>'(imports|exports)/.*',
		'gz'=>'(imports|exports)/.*',
		'lcd'=>'lang_cached(/.*)?',
		'gcd'=>'persistant_cache',
		'tcp'=>'themes/[^/]*/templates_cached/.*',
		'tcd'=>'themes/[^/]*/templates_cached/.*',
		'css'=>'themes/[^/]*/templates_cached/.*',
		'js'=>'themes/[^/]*/templates_cached/.*',
		'log'=>'.*',
		'clpprj'=>'',
		'tmproj'=>'',
		'zpj'=>'',
		'o'=>'',
		'scm'=>'',
		'heap'=>'',
		'sch'=>'',
		'dll'=>'',
		'fcgi'=>'',
	);
	if (($bitmask & IGNORE_EDITFROM_FILES)!=0)
	{
		$ignore_extensions+=array(
			'editfrom'=>'.*',
		);
	}

	$ignore_filename_patterns=array(
		'\..*\.(png|gif|jpeg|jpg)'=>'.*', // Image meta data file, e.g. ".example.png"
		'\_vti\_.*'=>'.*', // Frontpage
	);
	if (($bitmask & IGNORE_CUSTOM_DIR_CONTENTS)!=0) // Ignore all override directories, for both users and addons
	{
		$ignore_filename_patterns+=array(
			'.*\_custom'=>'.*',
			'.*'=>'.*\_custom(/.*)?',
		);
	}
	elseif (($bitmask & IGNORE_CUSTOM_DIR_CONTENTS_CASUAL_OVERRIDE)!=0) // Slightly different approach to IGNORE_USER_CUSTOMISE, useful when talking more about original files that may have been overridden
	{
		$ignore_filename_patterns+=array(
			'(comcode|html|lang|templates|images)\_custom'=>'.*',
			'.*'=>'(^|/)(lang|templates|images)\_custom(/.*)?',
		);
	}
	if (($bitmask & IGNORE_HIDDEN_FILES)!=0)
	{
		$ignore_filename_patterns+=array(
			'\..*'=>'.*',
		);
	}
	if (($bitmask & IGNORE_REVISION_FILES)!=0)
	{
		$ignore_filename_patterns+=array(
			'.*\.\d+'=>'.*',
		);
	}

	if (isset($ignore_filenames[$filename]))
	{
		if (preg_match('#^'.$ignore_filenames[$filename].'$#',$dir)!=0) return true; // Check dir context
	}

	$extension=get_file_extension($filename);
	if (isset($ignore_extensions[$extension]))
	{
		if (preg_match('#^'.$ignore_extensions[$extension].'$#',$dir)!=0) return true; // Check dir context
	}
	foreach ($ignore_filename_patterns as $filename_pattern=>$dir_pattern)
	{
		if (preg_match('#^'.$filename_pattern.'$#',$filename)!=0)
		{
			if (preg_match('#^'.$dir_pattern.'$#',$dir)!=0) return true; // Check dir context
		}
	}

	if (($dir!='') && (is_dir(get_file_base().'/'.$filepath)) && (file_exists(get_file_base().'/'.$filepath.'/sources_custom'))) // ocPortal dupe (e.g. backup) install
	{
		return true;
	}

	if (($bitmask & IGNORE_THEMES)!=0)
	{
		if ((preg_match('#^themes($|/)#',$dir)!=0) && (!in_array($filename,array('default','index.html','map.ini')))) return true;
	}

	if (($bitmask & IGNORE_CUSTOM_ZONES)!=0)
	{
		if ((file_exists(get_file_base().$filepath.'/index.php')) && (file_exists(get_file_base().$filepath.'/pages')) && (!in_array($filename,array('adminzone','collaboration','cms','forum','site'))))
			return true;
	}

	return false;
}

/**
 * Delete all the contents of a directory, and any subdirectories of that specified directory (recursively).
 *
 * @param  PATH			The pathname to the directory to delete
 * @param  boolean		Whether to preserve files there by default
 * @param  boolean		Whether to just delete files
 */
function deldir_contents($dir,$default_preserve=false,$just_files=false)
{
	require_code('files2');
	_deldir_contents($dir,$default_preserve,$just_files);
}

/**
 * Ensure that the specified file/folder is writeable for the FTP user (so that it can be deleted by the system), and should be called whenever a file is uploaded/created, or a folder is made. We call this function assuming we are giving world permissions
 *
 * @param  PATH			The full pathname to the file/directory
 * @param  integer		The permissions to make (not the permissions are reduced if the function finds that the file is owned by the web user [doesn't need world permissions then])
 */
function fix_permissions($path,$perms=0666) // We call this function assuming we are giving world permissions
{
	// If the file user is different to the FTP user, we need to make it world writeable
	if ((!is_suexec_like()) || (ocp_srv('REQUEST_METHOD')==''))
	{
		@chmod($path,$perms);
	} else // Otherwise we do not
	{
		if ($perms==0666) @chmod($path,0644);
		elseif ($perms==0777) @chmod($path,0755);
		else @chmod($path,$perms);
	}

	global $_CREATED_FILES; // From ocProducts PHP version, for development testing
	if (isset($_CREATED_FILES))
		foreach ($_CREATED_FILES as $i=>$x)
			if ($x==$path) unset($_CREATED_FILES[$i]);
}

/**
 * Return the file in the URL by downloading it over HTTP. If a byte limit is given, it will only download that many bytes. It outputs warnings, returning NULL, on error.
 *
 * @param  URLPATH		The URL to download
 * @param  ?integer		The number of bytes to download. This is not a guarantee, it is a minimum (NULL: all bytes)
 * @range  1 max
 * @param  boolean		Whether to throw an ocPortal error, on error
 * @param  boolean		Whether to block redirects (returns NULL when found)
 * @param  string			The user-agent to identify as
 * @param  ?array			An optional array of POST parameters to send; if this is NULL, a GET request is used (NULL: none)
 * @param  ?array			An optional array of cookies to send (NULL: none)
 * @param  ?string		'accept' header value (NULL: don't pass one)
 * @param  ?string		'accept-charset' header value (NULL: don't pass one)
 * @param  ?string		'accept-language' header value (NULL: don't pass one)
 * @param  ?resource		File handle to write to (NULL: do not do that)
 * @param  ?string		The HTTP referer (NULL: none)
 * @param  ?array			A pair: authentication username and password (NULL: none)
 * @param  float			The timeout
 * @param  boolean		Whether to treat the POST parameters as a raw POST (rather than using MIME)
 * @param  ?array			Files to send. Map between field to file path (NULL: none)
 * @return ?string		The data downloaded (NULL: error)
 */
function http_download_file($url,$byte_limit=NULL,$trigger_error=true,$no_redirect=false,$ua='ocPortal',$post_params=NULL,$cookies=NULL,$accept=NULL,$accept_charset=NULL,$accept_language=NULL,$write_to_file=NULL,$referer=NULL,$auth=NULL,$timeout=6.0,$is_xml=false,$files=NULL)
{
	require_code('files2');
	return _http_download_file($url,$byte_limit,$trigger_error,$no_redirect,$ua,$post_params,$cookies,$accept,$accept_charset,$accept_language,$write_to_file,$referer,$auth,$timeout,$is_xml,$files);
}


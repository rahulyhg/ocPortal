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
 * @package    google_appengine
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!isset($_SERVER['APPLICATION_ID'])) {
    header('Content-type: text/plain');
    exit('This can only run on Google App Engine.');
}

if ((!is_writable(__FILE__)/*suggests locked-down live environment*/) || (preg_replace('#:.*$#', '', $_SERVER['HTTP_HOST']) != 'localhost')) {
    header('Content-type: text/plain');
    exit('This cannot run on live Google App Engine, for security reasons.');
}

$request = urldecode(preg_replace('#\?.*#', '', $_SERVER['QUERY_STRING']));
$request = ltrim($request, '/');

$contents = @file_get_contents('gs://' . preg_replace('#^.*~#', '', $_SERVER['APPLICATION_ID']) . '/' . $request);
if ($contents === false) {
    header('HTTP/1.0 404 Not Found');

    echo 'File not found.';
} else {
    if (substr($request, -4) == '.css') {
        header('Content-type: text/css');
    } elseif (substr($request, -3) == '.js') {
        header('Content-type: text/javascript');
    } elseif ((substr($request, -4) == '.htm') || (substr($request, -5) == '.html')) {
        header('Content-type: text/javascript');
    } else {
        header('Content-type: application/octet-stream');
    }

    echo $contents;
}

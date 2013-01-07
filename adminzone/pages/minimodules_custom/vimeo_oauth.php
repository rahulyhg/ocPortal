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
 * @package		gallery_syndication
 */

require_code('vimeo');
require_code('oauth2'); // Contains some useful ocPortal-ties, for creating config options, etc, that applies to oauth1 also

$service_name='vimeo';

$title=get_screen_title('OAUTH_TITLE',true,array($service_name));

$client_id=ensure_got_oauth_client_id($service_name);

$vimeo=new phpVimeo(get_option($service_name.'_client_id'),get_option($service_name.'_client_secret'));

$oauth_token=get_param('oauth_token','');
if ($oauth_token=='')
{
	// Send to authorize
	$token=$vimeo->getRequestToken();
	$auth_url=$vimeo->getAuthorizeUrl($token['oauth_token'],'write');
	require_code('site2');
	assign_refresh($auth_url,0.0);
	$echo=do_template('REDIRECT_SCREEN',array('URL'=>$auth_url,'TITLE'=>$title,'TEXT'=>do_lang_tempcode('REDIRECTING')));
	$echo->evaluate_echo();
	return;
}

// Got a response back...

$vimeo->setToken(get_long_value('oauth_request_token'),get_long_value('oauth_request_token_secret'));
$token=$vimeo->getAccessToken(get_param('oauth_verifier'));
$vimeo->setToken($token['oauth_token'],$token['oauth_token_secret']);
$ok=true;
try // Check it...
{
	$vimeo->call('vimeo.test.null');
}
catch (VimeoAPIException $e) // Error if not okay
{
	require_lang('gallery_syndication_vimeo');
	attach_message(do_lang_tempcode('VIMEO_ERROR',escape_html(strval($e->getCode())),$e->getMessage(),escape_html(get_site_name())),'warn');
	$out=do_lang_tempcode('SOME_ERRORS_OCCURRED');
	$ok=false;
}
if ($ok)
{
	// Save if it passed through okay
	set_long_value($service_name.'_access_token',$token['oauth_token']);
	set_long_value($service_name.'_access_token_secret',$token['oauth_token_secret']);
	$out=do_lang_tempcode('OAUTH_SUCCESS',$service_name);
}

$title->evaluate_echo();

$out->evaluate_echo();

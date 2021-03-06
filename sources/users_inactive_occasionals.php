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
 * Make sure that the given URL contains a session if cookies are disabled.
 * NB: This is used for login redirection. It had to add the session id into the redirect url.
 *
 * @param  URLPATH		The URL to enforce results in session persistence for the user
 * @return URLPATH		The fixed URL
 */
function _enforce_sessioned_url($url)
{
	// Take hash off
	$hash='';
	$hash_pos=strpos($url,'#');
	if ($hash_pos!==false)
	{
		$hash=substr($url,$hash_pos);
		$url=substr($url,0,$hash_pos);
	}

	// Take hash off
	$hash='';
	$hash_pos=strpos($url,'#');
	if ($hash_pos!==false)
	{
		$hash=substr($url,$hash_pos);
		$url=substr($url,0,$hash_pos);
	}

	if (strpos($url,'?')===false)
	{
		if ((get_option('htm_short_urls')!='1') && (substr($url,-strlen('/index.php'))!='/index.php'))
			$url.='/index.php';
		$url.='?';
	} else $url.='&';
	$url=preg_replace('#keep\_session=\d+&#','',$url);
	$url=preg_replace('#&keep\_session=\d+#','',$url);

	// Get hash back
	$url.=$hash;
	$url=preg_replace('#\?keep\_session=\d+#','',$url);

	// Possibly a nested URL too
	$url=preg_replace('#keep\_session=\d+'.preg_quote(urlencode('&')).'#','',$url);
	$url=preg_replace('#'.preg_quote(urlencode('&')).'keep\_session=\d+#','',$url);
	$url=preg_replace('#'.preg_quote(urlencode('?')).'keep\_session=\d+#','',$url);

	// Put keep_session back
	$url.='keep_session='.strval(get_session_id());

	// Get hash back
	$url.=$hash;

	return $url;
}

/**
 * Set up a new session / Restore an existing one that was lost.
 *
 * @param  MEMBER			Logged in member
 * @param  BINARY			Whether the session should be considered confirmed
 * @param  boolean		Whether the session should be invisible
 * @return AUTO_LINK		New session ID
 */
function create_session($member,$session_confirmed=0,$invisible=false)
{
	global $SESSION_CACHE;

	global $MEMBER_CACHED;
	$MEMBER_CACHED=$member;

	if (($invisible) && (get_option('is_on_invisibility')=='0')) $invisible=false;

	$new_session=mixed();
	$restored_session=delete_expired_sessions_or_recover($member);
	if (is_null($restored_session)) // We're force to make a new one
	{
		// Generate random session
		$new_session=mt_rand(0,mt_getrandmax()-1);

		// Store session
		$username=$GLOBALS['FORUM_DRIVER']->get_username($member);
		$new_session_row=array('the_session'=>$new_session,'last_activity'=>time(),'the_user'=>$member,'ip'=>get_ip_address(3),'session_confirmed'=>$session_confirmed,'session_invisible'=>$invisible?1:0,'cache_username'=>$username,'the_title'=>'','the_zone'=>get_zone_name(),'the_page'=>substr(get_page_name(),0,80),'the_type'=>substr(get_param('type','',true),0,80),'the_id'=>substr(either_param('id',''),0,80));
		$GLOBALS['SITE_DB']->query_insert('sessions',$new_session_row,false,true);

		$SESSION_CACHE[$new_session]=$new_session_row;

		$big_change=true;
	} else
	{
		$new_session=$restored_session;
		$prior_session_row=$SESSION_CACHE[$new_session];
		$new_session_row=array('the_title'=>'','the_zone'=>get_zone_name(),'the_page'=>get_page_name(),'the_type'=>substr(either_param('type',''),0,80),'the_id'=>substr(either_param('id',''),0,80),'last_activity'=>time(),'ip'=>get_ip_address(3),'session_confirmed'=>$session_confirmed);
		$big_change=($prior_session_row['last_activity']<time()-10) || ($prior_session_row['session_confirmed']!=$session_confirmed) || ($prior_session_row['ip']!=$new_session_row['ip']);
		if ($big_change)
			$GLOBALS['SITE_DB']->query_update('sessions',$new_session_row,array('the_session'=>$new_session),'',1,NULL,false,true);

		$SESSION_CACHE[$new_session]=array_merge($SESSION_CACHE[$new_session],$new_session_row);
	}

	if ($big_change) // Only update the persistant cache for non-trivial changes.
	{
		if (get_value('session_prudence')!=='1') // With session prudence we don't store all these in persistant cache due to the size of it all. So only re-save if that's not on.
			persistant_cache_set('SESSION_CACHE',$SESSION_CACHE);
	}

	set_session_id($new_session/*,true*/); // We won't set it true here, but something that really needs it to persist might come back and re-set it

	// New sessions = Login points
	if ((!is_null($member)) && (addon_installed('points')) && (addon_installed('stats')) && (!is_guest($member)))
	{
		$points_per_daily_visit=intval(get_option('points_per_daily_visit',true));
		if ($points_per_daily_visit!=0)
		{
			// See if this is the first visit today
			$test=$GLOBALS['SITE_DB']->query_value('stats','MAX(date_and_time)',array('the_user'=>$member));
			if (!is_null($test))
			{
				require_code('temporal');
				require_code('tempcode');
				if (date('d/m/Y',tz_time($test,get_site_timezone()))!=date('d/m/Y',tz_time(time(),get_site_timezone())))
				{
					require_code('points');
					$_before=point_info($member);
					if (array_key_exists('points_gained_given',$_before))
						$GLOBALS['FORUM_DRIVER']->set_custom_field($member,'points_gained_given',strval(intval($_before['points_gained_given'])+$points_per_daily_visit));
				}
			}
		}
	}

	$GLOBALS['SESSION_CONFIRMED']=$session_confirmed;

	return $new_session;
}

/**
 * Set the session ID of the user.
 *
 * @param  integer		The session ID
 * @param  boolean		Whether this is a guest session (guest sessions will use persistent cookies)
 */
function set_session_id($id,$guest_session=false)  // NB: Guests sessions can persist because they are more benign
{
	// Save cookie
	$timeout=$guest_session?(time()+60*60*max(1,intval(get_option('session_expiry_time')))):NULL;
	/*if (($GLOBALS['DEBUG_MODE']) && (get_param_integer('keep_debug_has_cookies',0)==0))
	{
		$test=false;
	} else*/
	{
		$test=@setcookie('ocp_session',strval($id),$timeout,get_cookie_path()); // Set a session cookie with our session ID. We only use sessions for secure browser-session login... the database and url's do the rest
	}
	$_COOKIE['ocp_session']=strval($id); // So we remember for this page view

	// If we really have to, store in URL
	if (((!has_cookies()) || (!$test)) && (is_null(get_bot_type())))
	{
		$_GET['keep_session']=strval($id);
	}

	if ($id!=get_session_id()) decache('side_users_online');
}

/**
 * Force an HTTP authentication login box / relay it as if it were a posted login. This function is rarely used.
 */
function force_httpauth()
{
	if (empty($_SERVER['PHP_AUTH_USER']))
	{
		header('WWW-Authenticate: Basic realm="'.urlencode(get_site_name()).'"');
		$GLOBALS['HTTP_STATUS_CODE']='401';
		header('HTTP/1.0 401 Unauthorized');
		exit();
	}
	if (isset($_SERVER['PHP_AUTH_PW'])) // Ah, route as a normal login if we can then
	{
		$_POST['login_username']=$_SERVER['PHP_AUTH_USER'];
		$_POST['password']=$_SERVER['PHP_AUTH_PW'];
	}
}

/**
 * Filter a member ID through SU, if SU is on and if the user has permission.
 *
 * @param  MEMBER			Real logged in member
 * @return MEMBER			Simulated member
 */
function try_su_login($member)
{
	$ks=get_param('keep_su','');

	require_code('permissions');
	if (method_exists($GLOBALS['FORUM_DRIVER'],'forum_layer_initialise')) $GLOBALS['FORUM_DRIVER']->forum_layer_initialise();
	if (has_specific_permission($member,'assume_any_member'))
	{
		$su=$GLOBALS['FORUM_DRIVER']->get_member_from_username($ks);
		if ((is_null($su)) && (is_numeric($ks))) $su=intval($ks);

		if (is_null($su))
		{
			require_code('site');
			attach_message(do_lang_tempcode('_USER_NO_EXIST',escape_html($ks)),'warn');
			return get_member();
		}

		if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin($su)) || ($GLOBALS['FORUM_DRIVER']->is_super_admin($member)))
		{
			if (!is_null($su)) $member=$su; elseif (is_numeric($ks)) $member=intval($ks);

			if ((!is_guest($member)) && ($GLOBALS['FORUM_DRIVER']->is_banned($member))) // All hands to the guns
			{
				global $CACHED_THEME;
				$CACHED_THEME='default';
				critical_error('MEMBER_BANNED');
			}
		}
		$GLOBALS['IS_ACTUALLY_ADMIN']=true;
	}

	return $member;
}

/**
 * Try and login via HTTP authentication. This function is only called if HTTP authentication is currently active. With HTTP authentication we trust the PHP_AUTH_USER setting.
 *
 * @return ?MEMBER		Logged in member (NULL: no login happened)
 */
function try_httpauth_login()
{
	global $LDAP_CONNECTION;

	require_code('ocf_members');
	require_code('ocf_groups');
	require_lang('ocf');

	$member=ocf_authusername_is_bound_via_httpauth($_SERVER['PHP_AUTH_USER']);
	if ((is_null($member)) && ((running_script('index')) || (running_script('execute_temp'))))
	{
		require_code('ocf_members_action');
		require_code('ocf_members_action2');
		if ((trim(post_param('email_address',''))=='') && (get_value('no_finish_profile')!=='1'))
		{
			@ob_end_clean();
			if (!function_exists('do_header')) require_code('site');
			$middle=ocf_member_external_linker_ask($_SERVER['PHP_AUTH_USER'],((get_option('windows_auth_is_enabled',true)!='1') || is_null($LDAP_CONNECTION))?'httpauth':'ldap');
			$tpl=globalise($middle,NULL,'',true);
			$tpl->evaluate_echo();
			exit();
		} else
		{
			$member=ocf_member_external_linker($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_USER'],((get_option('windows_auth_is_enabled',true)!='1') || is_null($LDAP_CONNECTION))?'httpauth':'ldap');
		}
	}

	if (!is_null($member))
		create_session($member,1,(isset($_COOKIE[get_member_cookie().'_invisible'])) && ($_COOKIE[get_member_cookie().'_invisible']=='1')); // This will mark it as confirmed

	return $member;
}

/**
 * Do a cookie login.
 *
 * @return MEMBER			Logged in member (NULL: no login happened)
 */
function try_cookie_login()
{
	$member=NULL;

	// Preprocess if this is a serialized cookie
	$member_cookie_name=get_member_cookie();
	$bar_pos=strpos($member_cookie_name,'|');
	$colon_pos=strpos($member_cookie_name,':');
	if ($colon_pos!==false)
	{
		$base=substr($member_cookie_name,0,$colon_pos);
		if ((array_key_exists($base,$_COOKIE)) && ($_COOKIE[$base]!=''))
		{
			$real_member_cookie=substr($member_cookie_name,$colon_pos+1);
			$real_pass_cookie=substr(get_pass_cookie(),$colon_pos+1);

			$the_cookie=$_COOKIE[$base];
			if (get_magic_quotes_gpc())
			{
				$the_cookie=stripslashes($_COOKIE[$base]);
			}

			secure_serialized_data($the_cookie,array());

			$unserialize=@unserialize($the_cookie);

			if (is_array($unserialize))
			{
				if (array_key_exists($real_member_cookie,$unserialize))
				{
					$the_member=$unserialize[$real_member_cookie];
					if (get_magic_quotes_gpc()) $the_member=addslashes(@strval($the_member));
					$_COOKIE[get_member_cookie()]=$the_member;
				}
				if (array_key_exists($real_pass_cookie,$unserialize))
				{
					$the_pass=$unserialize[$real_pass_cookie];
					if (get_magic_quotes_gpc()) $the_pass=addslashes($the_pass);
					$_COOKIE[get_pass_cookie()]=$the_pass;
				}
			}
		}
	}
	elseif ($bar_pos!==false)
	{
		$base=substr($member_cookie_name,0,$bar_pos);
		if ((array_key_exists($base,$_COOKIE)) && ($_COOKIE[$base]!=''))
		{
			$real_member_cookie=substr($member_cookie_name,$bar_pos+1);
			$real_pass_cookie=substr(get_pass_cookie(),$bar_pos+1);

			$the_cookie=$_COOKIE[$base];
			if (get_magic_quotes_gpc())
			{
				$the_cookie=stripslashes($_COOKIE[$base]);
			}

			$cookie_contents=explode('||',$the_cookie);

			$the_member=$cookie_contents[intval($real_member_cookie)];
			if (get_magic_quotes_gpc()) $the_member=addslashes($the_member);
			$_COOKIE[get_member_cookie()]=$the_member;

			$the_pass=$cookie_contents[intval($real_pass_cookie)];
			if (get_magic_quotes_gpc()) $the_pass=addslashes($the_pass);
			$_COOKIE[get_pass_cookie()]=$the_pass;
		}
	}

	if ((array_key_exists(get_member_cookie(),$_COOKIE)) && (array_key_exists(get_pass_cookie(),$_COOKIE)))
	{
		$store=$_COOKIE[get_member_cookie()];
		$pass=$_COOKIE[get_pass_cookie()];
		if (get_magic_quotes_gpc())
		{
			$store=stripslashes($store);
			$pass=stripslashes($pass);
		}
		if ($GLOBALS['FORUM_DRIVER']->is_cookie_login_name())
		{
			$username=$store;
			$store=strval($GLOBALS['FORUM_DRIVER']->get_member_from_username($store));
		} else $username=$GLOBALS['FORUM_DRIVER']->get_username(intval($store));
		$member=intval($store);
		if (!is_guest($member))
		{
			if ($GLOBALS['FORUM_DRIVER']->is_hashed())
			{
				// Test password hash
				$login_array=$GLOBALS['FORUM_DRIVER']->forum_authorise_login(NULL,$member,$pass,$pass,true);
				$member=$login_array['id'];
			} else
			{
				// Test password plain
				$login_array=$GLOBALS['FORUM_DRIVER']->forum_authorise_login(NULL,$member,apply_forum_driver_md5_variant($pass,$username),$pass,true);
				$member=$login_array['id'];
			}

			if (!is_null($member))
			{
				global $IS_A_COOKIE_LOGIN;
				$IS_A_COOKIE_LOGIN=true;

				create_session($member,0,(isset($_COOKIE[get_member_cookie().'_invisible'])) && ($_COOKIE[get_member_cookie().'_invisible']=='1'));
			}
		}
	}

	return $member;
}

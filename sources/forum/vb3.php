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
 * @package		core_forum_drivers
 */

require_code('forum/shared/vb');

/**
 * Forum Driver.
 * @package		core_forum_drivers
 */
class forum_driver_vb3 extends forum_driver_vb_shared
{
	/**
	 * Get a list of custom BBcode tags.
	 *
	 * @return array			The list of tags (each list entry being a map, containing various standard named parameters)
	 */
	function get_custom_bbcode()
	{
		$tags=$this->connection->query_select('bbcode',array('bbcodereplacement','bbcodetag'));
		$ret=array();
		foreach ($tags as $tag)
		{
			$ret[]=array('tag'=>$tag['bbcodetag'],'replace'=>$tag['bbcodereplacement'],'block_tag'=>0,'textual_tag'=>0,'dangerous_tag'=>0,'parameters'=>'');
		}
		return $ret;
	}

	/**
	 * Find if login cookie is md5-hashed.
	 *
	 * @return boolean		Whether the login cookie is md5-hashed
	 */
	function is_hashed()
	{
		return true;
	}

	/**
	 * Get an array of attributes to take in from the installer. Almost all forums require a table prefix, which the requirement there-of is defined through this function.
	 * The attributes have 4 values in an array
	 * - name, the name of the attribute for info.php
	 * - default, the default value (perhaps obtained through autodetection from forum config)
	 * - description, a textual description of the attributes
	 * - title, a textual title of the attribute
	 *
	 * @return array			The attributes for the forum
	 */
	function install_specifics()
	{
		global $INFO;
		$a=array();
		$a['name']='vb_table_prefix';
		$a['default']=array_key_exists('prefix',$INFO)?$INFO['prefix']:'';
		$a['description']=do_lang('MOST_DEFAULT');
		$a['title']='VB '.do_lang('TABLE_PREFIX');
		$b=array();
		$b['name']='vb_unique_id';
		$b['default']=array_key_exists('vb_unique_id',$INFO)?$INFO['vb_unique_id']:'X######x';
		$b['description']=do_lang('VB_UNIQUE_ID_DESCRIP');
		$b['title']='VB '.do_lang('VB_UNIQUE_ID');
		return array($a,$b);
	}

	/**
	 * Searches for forum auto-config at this path.
	 *
	 * @param  PATH			The path in which to search
	 * @return boolean		Whether the forum auto-config could be found
	 */
	function install_test_load_from($path)
	{
		global $INFO;
		if (@file_exists($path.'/includes/config.php'))
		{
			$dbname=NULL;
			$dbusername='';
			$dbpassword='';
			$tableprefix='';
			$config=array();
			@include($path.'/includes/config.php');
			$INFO=array();
			if (!is_null($dbname))
			{
				$INFO['sql_database']=$dbname;
				$INFO['sql_user']=$dbusername;
				$INFO['sql_pass']=$dbpassword;
				$INFO['prefix']=$tableprefix;
				$INFO['cookie_member_id']='bbuserid';
				$INFO['cookie_member_hash']='bbpassword';
			} elseif (array_key_exists('Database',$config))
			{
				$INFO['sql_database']=$config['Database']['dbname'];
				$INFO['sql_user']=$config['MasterServer']['username'];
				$INFO['sql_pass']=$config['MasterServer']['password'];
				$INFO['prefix']=$config['Database']['tableprefix'];
				$INFO['cookie_member_id']=$config['Misc']['cookieprefix'].'userid';
				$INFO['cookie_member_hash']=$config['Misc']['cookieprefix'].'password';
			}

			$INFO['board_url']='';
			$file_contents=file_get_contents($path.'/includes/config.php');
			$matches=array();
			if (preg_match('#Licence Number (.*)#',$file_contents,$matches)!=0)
			{
				$INFO['vb_unique_id']=$matches[1];
			}
			return true;
		}
		return false;
	}

	/**
	 * Get an array of paths to search for config at.
	 *
	 * @return array			The paths in which to search for the forum config
	 */
	function install_get_path_search_list()
	{
		return array(
			0=>'forums',
			1=>'forum',
			2=>'boards',
			3=>'board',
			4=>'vb',
			5=>'vb3',
			6=>'upload',
			7=>'uploads',
			8=>'vbulletin',
			10=>'../forums',
			11=>'../forum',
			12=>'../boards',
			13=>'../board',
			14=>'../vb',
			15=>'../vb3',
			16=>'../upload',
			17=>'../uploads',
			18=>'../vbulletin');
	}

	/**
	 * From a member profile-row, get the member's last visit date.
	 *
	 * @param  array			The profile-row
	 * @return TIME			The last visit date
	 */
	function pnamelast_visit($r)
	{
		return $r['lastactivity'];
	}

	/**
	 * Find out if the given member id is banned.
	 *
	 * @param  MEMBER			The member id
	 * @return boolean		Whether the member is banned
	 */
	function is_banned($member)
	{
		// Are they banned
		$ban=$this->connection->query_value_null_ok('userban','liftdate',array('userid'=>$member));
		if (!is_null($ban))
		{
			return true;
		}

		return false;
	}

	/**
	 * Find if the specified member id is marked as staff or not.
	 *
	 * @param  MEMBER			The member id
	 * @return boolean		Whether the member is staff
	 */
	function _is_staff($member)
	{
		$usergroup=$this->get_member_row_field($member,'usergroupid');
		if (is_null($usergroup)) return false;
		return ((in_array($usergroup,$this->get_super_admin_groups())) || (in_array($usergroup,$this->get_moderator_groups())));
	}

	/**
	 * Find if the specified member id is marked as a super admin or not.
	 *
	 * @param  MEMBER			The member id
	 * @return boolean		Whether the member is a super admin
	 */
	function _is_super_admin($member)
	{
		$usergroup=$this->get_member_row_field($member,'usergroupid');
		if (is_null($usergroup)) return false;
		return (in_array($usergroup,$this->get_super_admin_groups()));
	}

	/**
	 * Get the ids of the admin usergroups.
	 *
	 * @return array			The admin usergroup ids
	 */
	function _get_super_admin_groups()
	{
		return array(6);
	//	$admin_group=$this->connection->query_value('usergroup','usergroupid',array('title'=>'Administrators'));
	//	return array($admin_group);
	}

	/**
	 * Get the ids of the moderator usergroups.
	 * It should not be assumed that a member only has one usergroup - this depends upon the forum the driver works for. It also does not take the staff site filter into account.
	 *
	 * @return array			The moderator usergroup ids
	 */
	function _get_moderator_groups()
	{
		return array(5);
	//	$moderator_group=$this->connection->query_value('usergroup','usergroupid',array('title'=>'Super Moderators'));
	//	return array($moderator_group);
	}

	/**
	 * Get the forum usergroup list.
	 *
	 * @return array			The usergroup list
	 */
	function _get_usergroup_list()
	{
		return collapse_2d_complexity('usergroupid','title',$this->connection->query_select('usergroup',array('usergroupid','title')));
	}

	/**
	 * Get the forum usergroup relating to the specified member id.
	 *
	 * @param  MEMBER			The member id
	 * @return array			The array of forum usergroups
	 */
	function _get_members_groups($member)
	{
		if ($member==$this->get_guest_id()) return array(1);

		$group=$this->get_member_row_field($member,'usergroupid');
		return array($group);
	}

	/**
	 * Create a member login cookie.
	 *
	 * @param  MEMBER			The member id
	 * @param  ?SHORT_TEXT	The username (NULL: lookup)
	 * @param  string			The password
	 */
	function forum_create_cookie($id,$name,$password)
	{
		unset($name);
		unset($password);

		// User
		ocp_setcookie(get_member_cookie(),strval($id));
		$_COOKIE[get_member_cookie()]=strval($id);

		// Password
		$password_hashed=$this->get_member_row_field($id,'password');
		global $SITE_INFO;
		$_password=md5($password_hashed.$SITE_INFO['vb_unique_id']);
		ocp_setcookie(get_pass_cookie(),$_password);
		$_COOKIE[get_pass_cookie()]=$_password;
	}

	/**
	 * Find if the given member id and password is valid. If username is NULL, then the member id is used instead.
	 * All authorisation, cookies, and form-logins, are passed through this function.
	 * Some forums do cookie logins differently, so a Boolean is passed in to indicate whether it is a cookie login.
	 *
	 * @param  ?SHORT_TEXT	The member username (NULL: don't use this in the authentication - but look it up using the ID if needed)
	 * @param  MEMBER			The member id
	 * @param  MD5				The md5-hashed password
	 * @param  string			The raw password
	 * @param  boolean		Whether this is a cookie login
	 * @return array			A map of 'id' and 'error'. If 'id' is NULL, an error occurred and 'error' is set
	 */
	function forum_authorise_login($username,$userid,$password_hashed,$password_raw,$cookie_login=false)
	{
		$out=array();
		$out['id']=NULL;

		if (is_null($userid))
		{
			$rows=$this->connection->query_select('user',array('*'),array('username'=>$username),'',1);
			if (array_key_exists(0,$rows))
			{
				$this->MEMBER_ROWS_CACHED[$rows[0]['userid']]=$rows[0];
			}
		} else
		{
			$rows=array();
			$rows[0]=$this->get_member_row($userid);
		}

		if (!array_key_exists(0,$rows) || $rows[0]==null) // All hands to lifeboats
		{
			$out['error']=(do_lang_tempcode('_USER_NO_EXIST',escape_html($username)));
			return $out;
		}
		$row=$rows[0];
		if ($this->is_banned($row['userid'])) // All hands to the guns
		{
			$out['error']=(do_lang_tempcode('USER_BANNED'));
			return $out;
		}

		global $SITE_INFO;
		if (!(((md5($row['password'].$SITE_INFO['vb_unique_id'])==$password_hashed) && ($cookie_login))
			|| ((!$cookie_login) && ($row['password']==md5($password_hashed.$row['salt'])))))
		{
			$out['error']=(do_lang_tempcode('USER_BAD_PASSWORD'));
			return $out;
		}

		ocp_eatcookie('sessionhash');

		$out['id']=$row['userid'];
		return $out;
	}

}



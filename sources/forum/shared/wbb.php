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

/**
 * Base class for WBB forum drivers.
 * @package		core_forum_drivers
 */
class forum_driver_wbb_shared extends forum_driver_base
{
	/**
	 * Check the connected DB is valid for this forum driver.
	 *
	 * @return boolean		Whether it is valid
	 */
	function check_db()
	{
		$test=$this->connection->query('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'users',NULL,NULL,true);
		return !is_null($test);
	}

	/**
	 * Get the rows for the top given number of posters on the forum.
	 *
	 * @param  integer		The limit to the number of top posters to fetch
	 * @return array			The rows for the given number of top posters in the forum
	 */
	function get_top_posters($limit)
	{
		return $this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'users WHERE userid<>'.strval((integer)$this->get_guest_id()).' ORDER BY userposts DESC',$limit);
	}

	/**
	 * Attempt to to find the member's language from their forum profile. It converts between language-identifiers using a map (lang/map.ini).
	 *
	 * @param  MEMBER			The member who's language needs to be fetched
	 * @return ?LANGUAGE_NAME The member's language (NULL: unknown)
	 */
	function forum_get_lang($member)
	{
		unset($member);
		return NULL;
	}

	/**
	 * Find if the login cookie contains the login name instead of the member id.
	 *
	 * @return boolean		Whether the login cookie contains a login name or a member id
	 */
	function is_cookie_login_name()
	{
		return false;
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
	 * Find the member id of the forum guest member.
	 *
	 * @return MEMBER			The member id of the forum guest member
	 */
	function get_guest_id()
	{
		return 0;
	}

	/**
	 * Get the forums' table prefix for the database.
	 *
	 * @return string			The forum database table prefix
	 */
	function get_drivered_table_prefix()
	{
		global $SITE_INFO;
		return 'bb'.$SITE_INFO['bb_forum_number'].'_';
	}

	/**
	 * Add the specified custom field to the forum (some forums implemented this using proper custom profile fields, others through adding a new field).
	 *
	 * @param  string			The name of the new custom field
	 * @param  integer		The length of the new custom field
	 * @param  BINARY			Whether the field is locked
	 * @param  BINARY			Whether the field is for viewing
	 * @param  BINARY			Whether the field is for setting
	 * @param  BINARY			Whether the field is required
	 * @return boolean		Whether the custom field was created successfully
	 */
	function install_create_custom_field($name,$length,$locked=1,$viewable=0,$settable=0,$required=0)
	{
		if (!array_key_exists('bb_forum_number',$_POST)) $_POST['bb_forum_number']=''; // for now

		$name='ocp_'.$name;
		$test=$this->connection->query('SELECT profilefieldid FROM bb'.$_POST['bb_forum_number'].'_profilefields WHERE '.db_string_equal_to('title',$name));
		if (!array_key_exists(0,$test))
		{
			$this->connection->query('INSERT INTO bb'.$_POST['bb_forum_number'].'_profilefields (title,description,required,hidden,maxlength,fieldsize) VALUES (\''.db_escape_string($name).'\',\'\','.strval(intval($required)).','.strval(1-intval($viewable)).','.strval((integer)$length).','.strval((integer)$length).')');
			$_key=$this->connection->query('SELECT MAX(profilefieldid) AS v FROM bb'.$_POST['bb_forum_number'].'_profilefields');
			$key=$_key[0]['v'];
			$this->connection->query('ALTER TABLE bb'.$_POST['bb_forum_number'].'_userfields ADD field'.$key.' TEXT',NULL,NULL,true);
			return true;
		}
		return false;
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
		$a['name']='bb_forum_number';
		$a['default']=array_key_exists('sql_tbl_prefix',$INFO)?$INFO['sql_tbl_prefix']:'1';
		$a['description']=do_lang('MOST_DEFAULT');
		$a['title']=do_lang('BOARD_INSTALL_NUMBER');
		return array($a);
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
		if (@file_exists($path.'/acp/lib/config.inc.php'))
		{
			$sqldb='';
			$sqluser='';
			$sqlpassword='';
			$n='';
			@include($path.'/acp/lib/config.inc.php');
			$INFO['sql_database']=$sqldb;
			$INFO['sql_user']=$sqluser;
			$INFO['sql_pass']=$sqlpassword;
			$INFO['cookie_member_id']='wbb_userid';
			$INFO['cookie_member_hash']='wbb_userpassword';
			$INFO['board_url']='';
			$INFO['sql_tbl_prefix']=$n;
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
		4=>'bb',
		5=>'bb2',
		6=>'upload',
		7=>'uploads',
		8=>'burningboard',
		9=>'wbb',
		10=>'../forums',
		11=>'../forum',
		12=>'../boards',
		13=>'../board',
		14=>'../bb',
		15=>'../bb2',
		16=>'../upload',
		17=>'../uploads',
		18=>'../burningboard',
		19=>'../wbb');
	}

	/**
	 * Get an emoticon chooser template.
	 *
	 * @param  string			The ID of the form field the emoticon chooser adds to
	 * @return tempcode		The emoticon chooser template
	 */
	function get_emoticon_chooser($field_name='post')
	{
		require_code('comcode_text');
		$emoticons=$this->connection->query_select('smilies',array('*'));
		$em=new ocp_tempcode();
		foreach ($emoticons as $emo)
		{
			$code=$emo['smiliecode'];
			$em->attach(do_template('EMOTICON_CLICK_CODE',array('_GUID'=>'c016421840b36b3f70bf5da34740dfaf','FIELD_NAME'=>$field_name,'CODE'=>$code,'IMAGE'=>apply_emoticons($code))));
		}

		return $em;
	}

	/**
	 * Pin a topic.
	 *
	 * @param  AUTO_LINK		The topic ID
	 */
	function pin_topic($id)
	{
		$this->connection->query_update('threads',array('important'=>1),array('threadid'=>$id),'',1);
	}

	/**
	 * Get a member profile-row for the member of the given name.
	 *
	 * @param  SHORT_TEXT	The member name
	 * @return ?array			The profile-row (NULL: could not find)
	 */
	function pget_row($name)
	{
		$rows=$this->connection->query_select('users',array('*'),array('username'=>$name),'',1);
		if (!array_key_exists(0,$rows)) return NULL;
		return $rows[0];
	}

	/**
	 * From a member profile-row, get the member's member id.
	 *
	 * @param  array			The profile-row
	 * @return MEMBER			The member id
	 */
	function pname_id($r)
	{
		return $r['userid'];
	}

	/**
	 * From a member profile-row, get the member's last visit date.
	 *
	 * @param  array			The profile-row
	 * @return TIME			The last visit date
	 */
	function pnamelast_visit($r)
	{
		return $r['lastvisit'];
	}

	/**
	 * From a member profile-row, get the member's name.
	 *
	 * @param  array			The profile-row
	 * @return string			The member name
	 */
	function pname_name($r)
	{
		return $r['username'];
	}

	/**
	 * From a member profile-row, get the member's e-mail address.
	 *
	 * @param  array			The profile-row
	 * @return SHORT_TEXT	The member e-mail address
	 */
	function pname_email($r)
	{
		return $r['email'];
	}

	/**
	 * Get a URL to the specified member's home (control panel).
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL to the members home
	 */
	function member_home_url($id)
	{
		unset($id);
		return get_forum_base_url().'/usercp.php';
	}

	/**
	 * Get the photo thumbnail URL for the specified member id.
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL (blank: none)
	 */
	function get_member_photo_url($member)
	{
		unset($member);

		return '';
	}

	/**
	 * Get the avatar URL for the specified member id.
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL (blank: none)
	 */
	function get_member_avatar_url($member)
	{
		$avatar=$this->connection->query_value_null_ok('avatars','avatarname',array('userid'=>$member));
		if ((is_null($avatar)) || ($avatar=='') || (!url_is_local($avatar))) return $avatar;
		return get_forum_base_url().'/images/avatars/'.$avatar;
	}

	/**
	 * Get a URL to the specified member's profile.
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL to the member profile
	 */
	function _member_profile_url($id)
	{
		return get_forum_base_url().'/profile.php?userid='.strval($id);
	}

	/**
	 * Get a URL to the registration page (for people to create member accounts).
	 *
	 * @return URLPATH		The URL to the registration page
	 */
	function _join_url()
	{
		return get_forum_base_url().'/register.php';
	}

	/**
	 * Get a URL to the members-online page.
	 *
	 * @return URLPATH		The URL to the members-online page
	 */
	function _online_members_url()
	{
		return get_forum_base_url().'/wiw.php';
	}

	/**
	 * Get a URL to send a private/personal message to the given member.
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL to the private/personal message page
	 */
	function _member_pm_url($id)
	{
		return get_forum_base_url().'/pms.php?action=newpm&userid='.strval($id);
	}

	/**
	 * Get a URL to the specified forum.
	 *
	 * @param  integer		The forum ID
	 * @return URLPATH		The URL to the specified forum
	 */
	function _forum_url($id)
	{
		return get_forum_base_url().'/board.php?boardid='.strval($id);
	}

	/**
	 * Get the forum ID from a forum name.
	 *
	 * @param  SHORT_TEXT	The forum name
	 * @return integer		The forum ID
	 */
	function forum_id_from_name($forum_name)
	{
		return is_numeric($forum_name)?intval($forum_name):$this->connection->query_value_null_ok('boards','boardid',array('title'=>$forum_name));
	}

	/**
	 * Get the topic ID from a topic identifier in the specified forum. It is used by comment topics, which means that the unique-topic-name assumption holds valid.
	 *
	 * @param  string			The forum name / ID
	 * @param  SHORT_TEXT	The topic identifier
	 * @return ?integer		The topic ID (NULL: not found)
	 */
	function find_topic_id_for_topic_identifier($forum,$topic_identifier)
	{
		if (is_integer($forum)) $forum_id=$forum;
		else $forum_id=$this->forum_id_from_name($forum);
		return $this->connection->query_value_null_ok_full('SELECT threadid FROM '.$this->connection->get_table_prefix().'threads WHERE boardid='.strval((integer)$forum_id).' AND ('.db_string_equal_to('topic',$topic_identifier).' OR topic LIKE \'%: #'.db_encode_like($topic_identifier).'\')');
	}

	/**
	 * Makes a post in the specified forum, in the specified topic according to the given specifications. If the topic doesn't exist, it is created along with a spacer-post.
	 * Spacer posts exist in order to allow staff to delete the first true post in a topic. Without spacers, this would not be possible with most forum systems. They also serve to provide meta information on the topic that cannot be encoded in the title (such as a link to the content being commented upon).
	 *
	 * @param  SHORT_TEXT	The forum name
	 * @param  SHORT_TEXT	The topic identifier (usually <content-type>_<content-id>)
	 * @param  MEMBER			The member ID
	 * @param  LONG_TEXT		The post title
	 * @param  LONG_TEXT		The post content in Comcode format
	 * @param  string			The topic title; must be same as content title if this is for a comment topic
	 * @param  string			This is put together with the topic identifier to make a more-human-readable topic title or topic description (hopefully the latter and a $content_title title, but only if the forum supports descriptions)
	 * @param  ?URLPATH		URL to the content (NULL: do not make spacer post)
	 * @param  ?TIME			The post time (NULL: use current time)
	 * @param  ?IP				The post IP address (NULL: use current members IP address)
	 * @param  ?BINARY		Whether the post is validated (NULL: unknown, find whether it needs to be marked unvalidated initially). This only works with the OCF driver.
	 * @param  ?BINARY		Whether the topic is validated (NULL: unknown, find whether it needs to be marked unvalidated initially). This only works with the OCF driver.
	 * @param  boolean		Whether to skip post checks
	 * @param  SHORT_TEXT	The name of the poster
	 * @param  ?AUTO_LINK	ID of post being replied to (NULL: N/A)
	 * @param  boolean		Whether the reply is only visible to staff
	 * @return array			Topic ID (may be NULL), and whether a hidden post has been made
	 */
	function make_post_forum_topic($forum_name,$topic_identifier,$member,$post_title,$post,$content_title,$topic_identifier_encapsulation_prefix,$content_url=NULL,$time=NULL,$ip=NULL,$validated=NULL,$topic_validated=1,$skip_post_checks=false,$poster_name_if_guest='',$parent_id=NULL,$staff_only=false)
	{
		if (is_null($time)) $time=time();
		if (is_null($ip)) $ip=get_ip_address();
		$forum_id=$this->forum_id_from_name($forum_name);
		if (is_null($forum_id)) warn_exit(do_lang_tempcode('MISSING_FORUM',escape_html($forum_name)));
		$username=$this->get_username($member);
		$topic_id=$this->find_topic_id_for_topic_identifier($forum_name,$topic_identifier);
		$is_new=is_null($topic_id);
		if ($is_new)
		{
			$topic_id=$this->connection->query_insert('threads',array('topic'=>$content_title.', '.$topic_identifier_encapsulation_prefix.': #'.$topic_identifier,'starttime'=>$time,'boardid'=>$forum_id,'closed'=>0,'starter'=>$username,'starterid'=>$member,'lastposter'=>$username,'lastposttime'=>$time,'visible'=>1),true);
			$home_link=hyperlink($content_url,escape_html($content_title));
			$this->connection->query_insert('posts',array('threadid'=>$topic_id,'username'=>do_lang('SYSTEM','','','',get_site_default_lang()),'userid'=>0,'posttopic'=>'','posttime'=>$time,'message'=>do_lang('SPACER_POST',$home_link->evaluate(),'','',get_site_default_lang()),'allowsmilies'=>1,'ipaddress'=>'127.0.0.1','visible'=>1));
			$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'boards SET threadcount=(threadcount+1), postcount=(postcount+1) WHERE boardid='.strval((integer)$forum_id),1);
		}

		$GLOBALS['LAST_TOPIC_ID']=$topic_id;
		$GLOBALS['LAST_TOPIC_IS_NEW']=$is_new;

		if ($post=='') return array($topic_id,false);

		$this->connection->query_insert('posts',array('threadid'=>$topic_id,'username'=>$username,'userid'=>$member,'posttopic'=>$post_title,'posttime'=>$time,'message'=>$post,'allowsmilies'=>1,'ipaddress'=>$ip,'visible'=>1));
		$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'boards SET lastthreadid='.strval((integer)$topic_id).', postcount=(postcount+1), lastposttime='.strval($time).', lastposterid='.strval((integer)$member).', lastposter=\''.db_escape_string($username).'\' WHERE boardid=\''.strval((integer)$forum_id).'\'',1);
		$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'threads SET replycount=(replycount+1), lastposttime='.strval($time).', lastposterid='.strval((integer)$member).', lastposter=\''.db_escape_string($username).'\' WHERE threadid='.strval((integer)$topic_id),1);

		return array($topic_id,false);
	}

	/**
	 * Get an array of maps for the topic in the given forum.
	 *
	 * @param  integer		The topic ID
	 * @param  integer		The comment count will be returned here by reference
	 * @param  integer		Maximum comments to returned
	 * @param  integer		Comment to start at
	 * @param  boolean		Whether to mark the topic read (ignored for this forum driver)
	 * @param  boolean		Whether to show in reverse
	 * @return mixed			The array of maps (Each map is: title, message, member, date) (-1 for no such forum, -2 for no such topic)
	 */
	function get_forum_topic_posts($topic_id,&$count,$max=100,$start=0,$mark_read=true,$reverse=false)
	{
		if (is_null($topic_id)) return (-2);
		$order=$reverse?'posttime DESC':'posttime';
		$rows=$this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'posts WHERE threadid='.strval((integer)$topic_id).' AND message NOT LIKE \''.db_encode_like(substr(do_lang('SPACER_POST','','','',get_site_default_lang()),0,20).'%').'\' ORDER BY '.$order,$max,$start);
		$count=$this->connection->query_value_null_ok_full('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'posts WHERE threadid='.strval((integer)$topic_id).' AND message NOT LIKE \''.db_encode_like(substr(do_lang('SPACER_POST','','','',get_site_default_lang()),0,20).'%').'\'');
		$out=array();
		foreach ($rows as $myrow)
		{
			$temp=array();
			$temp['title']=$myrow['posttopic'];
			if (is_null($temp['title'])) $temp['title']='';
			global $LAX_COMCODE;
			$temp2=$LAX_COMCODE;
			$LAX_COMCODE=true;
			$temp['message']=comcode_to_tempcode($myrow['message'],$myrow['userid']);
			$LAX_COMCODE=$temp2;
			$temp['user']=$myrow['userid'];
			$temp['date']=$myrow['posttime'];

			$out[]=$temp;
		}

		return $out;
	}

	/**
	 * Get a URL to the specified topic ID. Most forums don't require the second parameter, but some do, so it is required in the interface.
	 *
	 * @param  integer		The topic ID
	 * @param string			The forum ID
	 * @return URLPATH		The URL to the topic
	 */
	function topic_url($id,$forum)
	{
		unset($forum);
		return get_forum_base_url().'/thread.php?threadid='.strval($id);
	}

	/**
	 * Get a URL to the specified post id.
	 *
	 * @param  integer		The post id
	 * @param string			The forum ID
	 * @return URLPATH		The URL to the post
	 */
	function post_url($id,$forum)
	{
		unset($forum);
		return get_forum_base_url().'/thread.php?postid='.strval($id).'#post'.strval($id);
	}

	/**
	 * Get an array of topics in the given forum. Each topic is an array with the following attributes:
	 * - id, the topic ID
	 * - title, the topic title
	 * - lastusername, the username of the last poster
	 * - lasttime, the timestamp of the last reply
	 * - closed, a Boolean for whether the topic is currently closed or not
	 * - firsttitle, the title of the first post
	 * - firstpost, the first post (only set if $show_first_posts was true)
	 *
	 * @param  mixed			The forum name or an array of forum IDs
	 * @param  integer		The limit
	 * @param  integer		The start position
	 * @param  integer		The total rows (not a parameter: returns by reference)
	 * @param  SHORT_TEXT	The topic title filter
	 * @param  boolean		Whether to show the first posts
	 * @param  string			The date key to sort by
	 * @set    lasttime firsttime
	 * @param  boolean		Whether to limit to hot topics
	 * @param  SHORT_TEXT	The topic description filter
	 * @return ?array			The array of topics (NULL: error)
	 */
	function show_forum_topics($name,$limit,$start,&$max_rows,$filter_topic_title='',$show_first_posts=false,$date_key='lasttime',$hot=false,$filter_topic_description='')
	{
		if (is_integer($name)) $id_list='boardid='.strval((integer)$name);
		elseif (!is_array($name))
		{
			$id=$this->forum_id_from_name($name);
			if (is_null($id)) return NULL;
			$id_list='boardid='.strval((integer)$id);
		} else
		{
			$id_list='';
			foreach (array_keys($name) as $id)
			{
				if ($id_list!='') $id_list.=' OR ';
				$id_list.='boardid='.strval((integer)$id);
			}
			if ($id_list=='') return NULL;
		}

		$topic_filter=($filter_topic_title!='')?('AND topic LIKE \''.db_encode_like($filter_topic_title).'\''):'';
		$rows=$this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'threads WHERE ('.$id_list.') '.$topic_filter.' ORDER BY '.(($date_key=='lasttime')?'lastposttime':'starttime').' DESC',$limit,$start);
		$max_rows=$this->connection->query_value_null_ok_full('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'threads WHERE ('.$id_list.') '.$topic_filter);
		$out=array();
		foreach ($rows as $i=>$r)
		{
			$out[$i]=array();
			$out[$i]['id']=$r['threadid'];
			$out[$i]['num']=$r['replycount']+1;
			$out[$i]['title']=$r['topic'];
			$out[$i]['description']=$r['topic'];
			$out[$i]['firstusername']=$r['starter'];
			$out[$i]['lastusername']=$r['lastposter'];
			$out[$i]['firsttime']=$r['starttime'];
			$out[$i]['lasttime']=$r['lastposttime'];
			$out[$i]['closed']=($r['closed']==1);
			$fp_rows=$this->connection->query('SELECT posttopic,message,userid FROM '.$this->connection->get_table_prefix().'posts WHERE message NOT LIKE \''.db_encode_like(do_lang('SPACER_POST','','','',get_site_default_lang()).'%').'\' AND threadid='.strval((integer)$out[$i]['id']).' ORDER BY posttime',1);
			if (!array_key_exists(0,$fp_rows))
			{
				unset($out[$i]);
				continue;
			}
			$out[$i]['firsttitle']=$fp_rows[0]['posttopic'];
			if ($show_first_posts)
			{
				global $LAX_COMCODE;
				$temp=$LAX_COMCODE;
				$LAX_COMCODE=true;
				$out[$i]['firstpost']=comcode_to_tempcode($fp_rows[0]['message'],$fp_rows[0]['userid']);
				$LAX_COMCODE=$temp;
			}
		}
		if (count($out)!=0) return $out;
		return NULL;
	}

	/**
	 * This is the opposite of the get_next_member function.
	 *
	 * @param  MEMBER			The member id to decrement
	 * @return ?MEMBER		The previous member id (NULL: no previous member)
	 */
	function get_previous_member($member)
	{
		$tempid=$this->connection->query_value_null_ok_full('SELECT userid FROM '.$this->connection->get_table_prefix().'users WHERE userid<'.strval((integer)$member).' AND userid<>\'0\' ORDER BY userid DESC');
		return $tempid;
	}

	/**
	 * Get the member id of the next member after the given one, or NULL.
	 * It cannot be assumed there are no gaps in member ids, as members may be deleted.
	 *
	 * @param  MEMBER			The member id to increment
	 * @return ?MEMBER		The next member id (NULL: no next member)
	 */
	function get_next_member($member)
	{
		$tempid=$this->connection->query_value_null_ok_full('SELECT userid FROM '.$this->connection->get_table_prefix().'users WHERE userid>'.strval((integer)$member).' ORDER BY userid');
		return $tempid;
	}

	/**
	 * Try to find a member with the given IP address
	 *
	 * @param  IP				The IP address
	 * @return array			The distinct rows found
	 */
	function probe_ip($ip)
	{
		return $this->connection->query_select('posts',array('DISTINCT userid AS id'),array('ipaddress'=>$ip));
	}

	/**
	 * Get the name relating to the specified member id.
	 * If this returns NULL, then the member has been deleted. Always take potential NULL output into account.
	 *
	 * @param  MEMBER			The member id
	 * @return ?SHORT_TEXT	The member name (NULL: member deleted)
	 */
	function _get_username($member)
	{
		if ($member==$this->get_guest_id()) return do_lang('GUEST');
		return $this->get_member_row_field($member,'username');
	}

	/**
	 * Get the e-mail address for the specified member id.
	 *
	 * @param  MEMBER			The member id
	 * @return SHORT_TEXT	The e-mail address
	 */
	function _get_member_email_address($member)
	{
		return $this->get_member_row_field($member,'email');
	}

	/**
	 * Find if this member may have e-mails sent to them
	 *
	 * @param  MEMBER			The member id
	 * @return boolean		Whether the member may have e-mails sent to them
	 */
	function get_member_email_allowed($member)
	{
		$v=$this->get_member_row_field($member,'emailnotify');
		if ($v==1) return true;
		return false;
	}

	/**
	 * Get the timestamp of a member's join date.
	 *
	 * @param  MEMBER			The member id
	 * @return TIME			The timestamp
	 */
	function get_member_join_timestamp($member)
	{
		return $this->get_member_row_field($member,'regdate');
	}

	/**
	 * Find all members with a name matching the given SQL LIKE string.
	 *
	 * @param  string			The pattern
	 * @param  ?integer		Maximum number to return (limits to the most recent active) (NULL: no limit)
	 * @return ?array			The array of matched members (NULL: none found)
	 */
	function get_matching_members($pattern,$limit=NULL)
	{
		$rows=$this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'users WHERE username LIKE \''.db_encode_like($pattern).'\' AND userid<>'.strval($this->get_guest_id()).' ORDER BY lastactivity DESC',$limit);
		global $M_SORT_KEY;
		$M_SORT_KEY='username';
		uasort($rows,'multi_sort');
		return $rows;
	}

	/**
	 * Get the given member's post count.
	 *
	 * @param  MEMBER			The member id
	 * @return integer		The post count
	 */
	function get_post_count($member)
	{
		$c=$this->get_member_row_field($member,'userposts');
		if (is_null($c)) return 0;
		return $c;
	}

	/**
	 * Get the given member's topic count.
	 *
	 * @param  MEMBER			The member id
	 * @return integer		The topic count
	 */
	function get_topic_count($member)
	{
		return $this->connection->query_value('threads','COUNT(*)',array('starterid'=>$member));
	}

	/**
	 * Find the base URL to the emoticons.
	 *
	 * @return URLPATH		The base URL
	 */
	function get_emo_dir()
	{
		return get_forum_base_url().'/';
	}

	/**
	 * Get a map between smiley codes and templates representing the HTML-image-code for this smiley. The smilies present of course depend on the forum involved.
	 *
	 * @return array			The map
	 */
	function find_emoticons()
	{
		global $EMOTICON_CACHE;
		if (!is_null($EMOTICON_CACHE)) return $EMOTICON_CACHE;
		$rows=$this->connection->query_select('smilies',array('*'));
		$EMOTICON_CACHE=array();
		foreach ($rows as $myrow)
		{
			$src=str_replace('{imagefolder}'.'/','images/',$myrow['smiliepath']);
			if (url_is_local($src)) $src=$this->get_emo_dir().$src;
			$EMOTICON_CACHE[$myrow['smiliecode']]=array('EMOTICON_IMG_CODE_DIR',$src,$myrow['smiliecode']);
		}
		uksort($EMOTICON_CACHE,'strlen_sort');
		$EMOTICON_CACHE=array_reverse($EMOTICON_CACHE);
		return $EMOTICON_CACHE;
	}

	/**
	 * Get the number of members currently online on the forums.
	 *
	 * @return integer		The number of members
	 */
	function get_num_users_forums()
	{
		return $this->connection->query_value_null_ok_full('SELECT COUNT(DISTINCT userid) FROM '.$this->connection->get_table_prefix().'sessions WHERE lastactivity>'.strval(time()-60*intval(get_option('users_online_time'))));
	}

	/**
	 * Get the number of members registered on the forum.
	 *
	 * @return integer		The number of members
	 */
	function get_members()
	{
		return $this->connection->query_value('users','COUNT(*)');
	}

	/**
	 * Get the total topics ever made on the forum.
	 *
	 * @return integer		The number of topics
	 */
	function get_topics()
	{
		return $this->connection->query_value('threads','COUNT(*)');
	}

	/**
	 * Get the total posts ever made on the forum.
	 *
	 * @return integer		The number of posts
	 */
	function get_num_forum_posts()
	{
		return $this->connection->query_value('posts','COUNT(*)');
	}

	/**
	 * Get the number of new forum posts.
	 *
	 * @return integer		The number of posts
	 */
	function _get_num_new_forum_posts()
	{
		return $this->connection->query_value_null_ok_full('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'posts WHERE posttime>'.strval(time()-60*60*24));
	}

	/**
	 * Set a custom profile fields value. It should not be called directly.
	 *
	 * @param  MEMBER			The member id
	 * @param  string			The field name
	 * @param  string			The value
	 */
	function set_custom_field($member,$field,$amount)
	{
		$id=$this->connection->query_value_null_ok('profilefields','profilefieldid',array('title'=>'ocp_'.$field));
		if (is_null($id)) return;
		$this->connection->query_update('userfields',array('field'.strval($id)=>$amount),array('userid'=>$member),'',1);
	}

	/**
	 * Get custom profile fields values for all 'ocp_' prefixed keys.
	 *
	 * @param  MEMBER			The member id
	 * @return ?array			A map of the custom profile fields, key_suffix=>value (NULL: no fields)
	 */
	function get_custom_fields($member)
	{
		$rows=$this->connection->query('SELECT profilefieldid,title FROM '.$this->connection->get_table_prefix().'profilefields WHERE title LIKE \''.db_encode_like('ocp_%').'\'');
		$values=$this->connection->query_select('userfields',array('*'),array('userid'=>$member),'',1);
		if (!array_key_exists(0,$values)) return NULL;

		$out=array();
		foreach ($rows as $row)
		{
			$title=substr($row['title'],4);
			$out[$title]=$values[0]['field'.strval($row['profilefieldid'])];
		}
		return $out;
	}

	/**
	 * Get a member id from the given member's username.
	 *
	 * @param  SHORT_TEXT	The member name
	 * @return MEMBER			The member id
	 */
	function get_member_from_username($name)
	{
		return $this->connection->query_value_null_ok('users','userid',array('username'=>$name));
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
	function forum_authorise_login($username,$memberid,$password_hashed,$password_raw,$cookie_login=false)
	{
		unset($cookie_login);

		$out=array();
		$out['id']=NULL;

		if (is_null($memberid))
		{
			$rows=$this->connection->query_select('users',array('*'),array('username'=>$username),'',1);
			if (array_key_exists(0,$rows))
			{
				$this->MEMBER_ROWS_CACHED[$rows[0]['userid']]=$rows[0];
			}
		} else
		{
			$rows=array();
			$rows[0]=$this->get_member_row($memberid);
		}

		if (!array_key_exists(0,$rows) || $rows[0]==null) // All hands to lifeboats
		{
			$out['error']=(do_lang_tempcode('_USER_NO_EXIST',$username));
			return $out;
		}
		$row=$rows[0];
		if ($this->is_banned($row['userid'])) // All hands to the guns
		{
			$out['error']=(do_lang_tempcode('USER_BANNED'));
			return $out;
		}
		if ($row['password']!=$password_hashed)
		{
			$out['error']=(do_lang_tempcode('USER_BAD_PASSWORD'));
			return $out;
		}

		ocp_eatcookie('cookiehash');

		$out['id']=$row['userid'];
		return $out;
	}

	/**
	 * Get a first known IP address of the given member.
	 *
	 * @param  MEMBER			The member id
	 * @return IP				The IP address
	 */
	function get_member_ip($member)
	{
		return $this->get_member_row_field($member,'ipaddress');
	}

	/**
	 * Gets a whole member row from the database.
	 *
	 * @param  MEMBER			The member id
	 * @return ?array			The member row (NULL: no such member)
	 */
	function get_member_row($member)
	{
		if (array_key_exists($member,$this->MEMBER_ROWS_CACHED)) return $this->MEMBER_ROWS_CACHED[$member];

		$rows=$this->connection->query_select('users',array('*'),array('userid'=>$member),'',1);
		if ($member==$this->get_guest_id())
		{
			$rows[0]['username']=do_lang('GUEST');
			$rows[0]['email']=NULL;
			$rows[0]['avatar']='';
			$rows[0]['emailnotify']=0;
			$rows[0]['regdate']=time();
			$rows[0]['userposts']=0;
			$rows[0]['groupid']=$this->_get_guest_group();
			$rows[0]['userid']=$this->get_guest_id();
			$rows[0]['styleid']=NULL;
		}
		if (!array_key_exists(0,$rows)) return NULL;
		$this->MEMBER_ROWS_CACHED[$member]=$rows[0];
		return $this->MEMBER_ROWS_CACHED[$member];
	}

	/**
	 * Gets a named field of a member row from the database.
	 *
	 * @param  MEMBER			The member id
	 * @param  string			The field identifier
	 * @return mixed			The field
	 */
	function get_member_row_field($member,$field)
	{
		$row=$this->get_member_row($member);
		return is_null($row)?NULL:$row[$field];
	}

}



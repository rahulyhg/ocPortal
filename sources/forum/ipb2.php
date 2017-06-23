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

require_code('forum/shared/ipb');

class forum_driver_ipb2 extends forum_driver_ipb_shared
{

	/**
	 * From a member profile-row, get the member's name.
	 *
	 * @param  array			The profile-row
	 * @return string			The member name
	 */
	function pname_name($r)
	{
		return $this->ipb_unescape($r['members_display_name']);
	}

	/**
	 * Get a member profile-row for the member of the given name.
	 *
	 * @param  SHORT_TEXT	The member name
	 * @return ?array			The profile-row (NULL: could not find)
	 */
	function pget_row($name)
	{
		$rows=$this->connection->query_select('members',array('*'),array('members_display_name'=>$this->ipb_escape($name)),'',1);
		if (!array_key_exists(0,$rows)) return NULL;
		return $rows[0];
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
		return $this->ipb_unescape($this->get_member_row_field($member,'members_display_name'));
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
		$query='SELECT * FROM '.$this->connection->get_table_prefix().'members WHERE members_display_name LIKE \''.db_encode_like($pattern).'\' AND id<>'.strval($this->get_guest_id()).' ORDER BY last_post DESC';
		$rows=$this->connection->query($query,$limit);
		global $M_SORT_KEY;
		$M_SORT_KEY='members_display_name';
		uasort($rows,'multi_sort');
		return $rows;
	}

	/**
	 * Get a member id from the given member's username.
	 *
	 * @param  SHORT_TEXT	The member name
	 * @return MEMBER			The member id
	 */
	function get_member_from_username($name)
	{
		return $this->connection->query_value_null_ok('members','id',array('members_display_name'=>$name));
	}

	/**
	 * Get a list of custom BBcode tags.
	 *
	 * @return array			The list of tags (each list entry being a map, containing various standard named parameters)
	 */
	function get_custom_bbcode()
	{
		$tags=$this->connection->query_select('custom_bbcode',array('bbcode_replace','bbcode_tag'));
		$ret=array();
		foreach ($tags as $tag)
		{
			$ret[]=array('tag'=>$tag['bbcode_tag'],'replace'=>$tag['bbcode_replace'],'block_tag'=>0,'textual_tag'=>0,'dangerous_tag'=>0,'parameters'=>'');
		}
		return $ret;
	}

	/**
	 * Add the specified custom field to the forum (some forums implemented this using proper custom profile fields, others through adding a new field).
	 *
	 * @param  string			The name of the new custom field
	 * @param  integer		The length of the new custom field
	 * @param  BINARY			Whether the field is locked
	 * @param  BINARY			Whether the field is for viewing
	 * @param  BINARY			Whether the field is for setting
	 * @return boolean		Whether the custom field was created successfully
	 */
	function install_create_custom_field($name,$length,$locked=1,$viewable=0,$settable=0)
	{
		$name='ocp_'.$name;
		$id=$this->connection->query_value_null_ok('pfields_data','pf_id',array('pf_title'=>$name));
		if (is_null($id))
		{
			$id=$this->connection->query_insert('pfields_data',array('pf_input_format'=>'','pf_topic_format'=>'{title} : {content}','pf_content'=>'','pf_title'=>$name,'pf_type'=>'text','pf_member_hide'=>1-$viewable,'pf_max_input'=>$length,'pf_member_edit'=>$settable,'pf_position'=>0),true);
			$this->connection->query('ALTER TABLE '.$this->connection->get_table_prefix().'pfields_content ADD field_'.strval($id).' TEXT',NULL,NULL,true);
		}
		return !is_null($id);
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
		$id=$this->connection->query_value_null_ok('pfields_data','pf_id',array('pf_title'=>'ocp_'.$field));
		if (is_null($id)) return;
		$old=$this->connection->query_value_null_ok('pfields_content','member_id',array('member_id'=>$member));
		if (is_null($old)) $this->connection->query_insert('pfields_content',array('member_id'=>$member));
		$this->connection->query_update('pfields_content',array('field_'.strval($id)=>$amount),array('member_id'=>$member),'',1);
	}

	/**
	 * Get custom profile fields values for all 'ocp_' prefixed keys.
	 *
	 * @param  MEMBER			The member id
	 * @return ?array			A map of the custom profile fields, key_suffix=>value (NULL: no fields)
	 */
	function get_custom_fields($member)
	{
		$rows=$this->connection->query('SELECT pf_id,pf_title FROM '.$this->connection->get_table_prefix().'pfields_data WHERE pf_title LIKE \''.db_encode_like('ocp_%').'\'');
		$values=$this->connection->query_select('pfields_content',array('*'),array('member_id'=>$member),'',1);
		if (!array_key_exists(0,$values)) return NULL;

		$out=array();
		foreach ($rows as $row)
		{
			$title=substr($row['pf_title'],4);
			$out[$title]=$values[0]['field_'.strval($row['pf_id'])];
		}
		return $out;
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
		if (@file_exists($path.'/conf_global.php'))
		{
			if (!@file_exists($path.'/conf_shared.php')) // We can't work with ipb->site bound forums
			{
				@include($path.'/conf_global.php');
				$INFO['cookie_member_id']='member_id';
				$INFO['cookie_member_hash']='pass_hash';
				return true;
			}
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
		4=>'ipb2',
		5=>'ipb',
		6=>'upload',
		7=>'uploads',
		8=>'ipboard',
		10=>'../forums',
		11=>'../forum',
		12=>'../boards',
		13=>'../board',
		14=>'../ipb2',
		15=>'../ipb',
		16=>'../upload',
		17=>'../uploads',
		18=>'../ipboard');
	}

	/**
	 * Get the avatar URL for the specified member id.
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL (blank: none)
	 */
	function get_member_avatar_url($member)
	{
		$avatar=$this->connection->query_value_null_ok('member_extra','avatar_location',array('id'=>$member));
		if ($avatar=='noavatar') $avatar='';
		elseif (is_null($avatar)) $avatar='';
		elseif (substr($avatar,0,7)=='upload:') $avatar=get_forum_base_url().'/uploads/'.substr($avatar,7);
		elseif ((url_is_local($avatar)) && ($avatar!='')) $avatar=get_forum_base_url().'/uploads/'.$avatar;

		return $avatar;
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
	function make_post_forum_topic($forum_name,$topic_identifier,$member,$post_title,$_post,$content_title,$topic_identifier_encapsulation_prefix,$content_url=NULL,$time=NULL,$ip=NULL,$validated=NULL,$topic_validated=1,$skip_post_checks=false,$poster_name_if_guest='',$parent_id=NULL,$staff_only=false)
	{
		$__post=comcode_to_tempcode($_post);
		$post=$__post->evaluate();

		if (is_null($time)) $time=time();
		if (is_null($ip)) $ip=get_ip_address();
		if (!is_integer($forum_name))
		{
			$forum_id=$this->forum_id_from_name($forum_name);
			if (is_null($forum_id)) warn_exit(do_lang_tempcode('MISSING_FORUM',escape_html($forum_name)));
		}
		else $forum_id=(integer)$forum_name;
		$username=$this->get_username($member);

		$topic_id=$this->find_topic_id_for_topic_identifier($forum_name,$topic_identifier);

		$is_new=is_null($topic_id);
		if ($is_new)
		{
			$topic_id=$this->connection->query_insert('topics',array('moved_to'=>0,'pinned'=>0,'views'=>0,'description'=>$topic_identifier_encapsulation_prefix.': #'.$topic_identifier,'title'=>$this->ipb_escape($content_title),'state'=>'open','posts'=>1,'starter_id'=>$member,'start_date'=>$time,'icon_id'=>0,'starter_name'=>$this->ipb_escape($username),'poll_state'=>0,'last_vote'=>0,'forum_id'=>$forum_id,'approved'=>1,'author_mode'=>1),true);
			$home_link=hyperlink($content_url,escape_html($content_title));
			$this->connection->query_insert('posts',array('author_id'=>$member,'author_name'=>$this->ipb_escape($username),'ip_address'=>'127.0.0.1','post_date'=>$time,'icon_id'=>0,'post'=>do_lang('SPACER_POST',$home_link->evaluate(),'','',get_site_default_lang()),'queued'=>0,'topic_id'=>$topic_id,'new_topic'=>1,'post_htmlstate'=>1,'post_title'=>$post_title,'post_key'=>md5(microtime(false))));
			$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'forums SET topics=(topics+1) WHERE id='.strval((integer)$forum_id),1);
			$first_post=true;
		} else $first_post=false;

		$GLOBALS['LAST_TOPIC_ID']=$topic_id;
		$GLOBALS['LAST_TOPIC_IS_NEW']=$is_new;

		if ($post=='') return array($topic_id,false);

		$post_id=$this->connection->query_insert('posts',array('author_id'=>$member,'author_name'=>$this->ipb_escape($username),'ip_address'=>$ip,'post_date'=>$time,'icon_id'=>0,'post'=>$post,'queued'=>0,'topic_id'=>$topic_id,'new_topic'=>1,'post_htmlstate'=>1,'post_title'=>$post_title,'post_key'=>md5(microtime(false))),true);
		$test=$this->connection->query_select('forums',array('*'),NULL,'',1);
		if (array_key_exists('newest_title',$test[0]))
		{
			$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'forums SET posts=(posts+1), last_post='.strval($time).', last_poster_id='.strval((integer)$member).', last_poster_name=\''.db_escape_string($this->ipb_escape($username)).'\', newest_id='.strval((integer)$topic_id).', newest_title=\''.db_escape_string($this->ipb_escape($post_title)).'\', last_id='.strval((integer)$topic_id).', last_title=\''.db_escape_string($this->ipb_escape($post_title)).'\' WHERE id='.strval((integer)$forum_id),1);
		} else
		{
			$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'forums SET posts=(posts+1), last_post='.strval($time).', last_poster_id='.strval((integer)$member).', last_poster_name=\''.db_escape_string($this->ipb_escape($username)).'\', last_id='.strval((integer)$topic_id).', last_title=\''.db_escape_string($this->ipb_escape($post_title)).'\' WHERE id='.strval((integer)$forum_id),1);
		}
		if ($first_post)
		{
			$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'topics SET topic_firstpost='.strval($post_id).', posts=(posts+1), last_post='.strval($time).', last_poster_id='.strval((integer)$member).', last_poster_name=\''.db_escape_string($this->ipb_escape($username)).'\' WHERE tid='.strval((integer)$topic_id),1);
		} else
		{
			$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'topics SET posts=(posts+1), last_post='.strval($time).', last_poster_id='.strval((integer)$member).', last_poster_name=\''.db_escape_string($this->ipb_escape($username)).'\' WHERE tid='.strval((integer)$topic_id),1);
		}

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
		$order=$reverse?'post_date DESC':'post_date';
		$rows=$this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'posts WHERE topic_id='.strval((integer)$topic_id).' AND post NOT LIKE \''.db_encode_like(substr(do_lang('SPACER_POST','','','',get_site_default_lang()),0,20).'%').'\' ORDER BY '.$order,$max,$start);
		$count=$this->connection->query_value_null_ok_full('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'posts WHERE topic_id='.strval((integer)$topic_id).' AND post NOT LIKE \''.db_encode_like(substr(do_lang('SPACER_POST','','','',get_site_default_lang()),0,20).'%').'\'');
		$out=array();
		$emoticons_set_dir=$this->get_emo_dir();
		foreach ($rows as $myrow)
		{
			$temp=array();

			$temp['title']=$this->ipb_unescape($myrow['post_title']);
			if (is_null($temp['title'])) $temp['title']='';
			$post=preg_replace('#style_emoticons/<\#EMO_DIR\#>(.+?)\'#is',$emoticons_set_dir.'\\1\'',$myrow['post']);
			$temp['message']=$post;
			$temp['user']=$myrow['author_id'];
			$temp['date']=$myrow['post_date'];

			$out[]=$temp;
		}

		return $out;
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
		require_code('xhtml');

		if (is_integer($name)) $id_list='forum_id='.strval((integer)$name);
		elseif (!is_array($name))
		{
			if (($name=='<announce>') || (is_null($name)))
			{
				$id_list='(forum_id IS NULL)';
			} else
			{
				$id=$this->forum_id_from_name($name);
				if (is_null($id)) return NULL;
				$id_list='forum_id='.strval((integer)$id);
			}
		} else
		{
			$id_list='';
			$id_list_2='';
			foreach (array_keys($name) as $id)
			{
				if ($id_list!='') $id_list.=' OR ';
				if ((is_null($id)) || ($id==''))
				{
					$id_list.='(forum_id IS NULL)';
				} else
				{
					$id_list.='forum_id='.strval((integer)$id);
				}
			}
			if ($id_list=='') return NULL;
		}

		$topic_filter=($filter_topic_title!='')?'AND title LIKE \''.db_encode_like($this->ipb_escape($filter_topic_title)).'\'':'';
		if ($filter_topic_description!='')
			$topic_filter.=' AND description LIKE \''.db_encode_like($this->ipb_escape($filter_topic_description)).'\'';
		$rows=$this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'topics WHERE ('.$id_list.') '.$topic_filter.' ORDER BY '.(($date_key=='lasttime')?'last_post':'start_date').' DESC',$limit,$start);
		$max_rows=$this->connection->query_value_null_ok_full('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'topics WHERE ('.$id_list.') '.$topic_filter);
		$emoticons_set_dir=$this->get_emo_dir();
		$out=array();
		foreach ($rows as $i=>$r)
		{
			$out[$i]=array();
			$out[$i]['id']=$r['tid'];
			$out[$i]['num']=$r['posts'];
			$out[$i]['title']=$this->ipb_unescape($r['title']);
			$out[$i]['firstusername']=$this->ipb_unescape($r['starter_name']);
			$out[$i]['lastusername']=$this->ipb_unescape($r['last_poster_name']);
			$out[$i]['firstmemberid']=$r['starter_id'];
			$out[$i]['lastmemberid']=$r['last_poster_id'];
			$out[$i]['firsttime']=$r['start_date'];
			$out[$i]['lasttime']=$r['last_post'];
			$out[$i]['closed']=($r['state']=='closed');
			$fp_rows=$this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'posts WHERE post NOT LIKE \''.db_encode_like(do_lang('SPACER_POST','','','',get_site_default_lang()).'%').'\' AND topic_id='.strval((integer)$out[$i]['id']).' ORDER BY post_date',1);
			if (!array_key_exists(0,$fp_rows))
			{
				unset($out[$i]);
				continue;
			}
			$out[$i]['firsttitle']=$this->ipb_unescape($fp_rows[0]['post_title']);
			if ($show_first_posts)
			{
				$post_id=$fp_rows[0]['pid'];
				$post=$fp_rows[0]['post'];
				if ((array_key_exists('post_htmlstate',$fp_rows[0])) && ($fp_rows[0]['post_htmlstate']!=0))
				{
					if ($fp_rows[0]['post_htmlstate']==1) $post=str_replace('<br />','',$post);
					$post=@html_entity_decode($post,ENT_QUOTES,get_charset());
				}
				$post=preg_replace('#style_emoticons/<\#EMO_DIR\#>(.+?)\'#is',$emoticons_set_dir.'\\1\'',$post);

				$post=str_replace("class='quotetop'","class='comcode_quote_h4'",$post);
				$post=str_replace("class='quotemain'","class='comcode_quote_content'",$post);

				// Attachments
				$attachments=$this->connection->query_select('attachments',array('attach_member_id','attach_id','attach_file','attach_location','attach_thumb_location','attach_is_image','attach_filesize','attach_hits'),array('attach_post_key'=>$fp_rows[0]['post_key'],'attach_approved'=>1));
				foreach ($attachments as $attachment)
				{
					if (($attachment['attach_thumb_location']!='') || ($attachment['attach_is_image']==0)) // Not fully inline
					{
						$url=get_forum_base_url().'/index.php?act=Attach&type=post&id='.$attachment['attach_id'];
						if ($attachment['attach_thumb_location']!='')
						{
							$special=do_template('FORUM_ATTACHMENT_IMAGE_THUMB',array('_GUID'=>'98a66462f270f53101c4c0a1b63f0bfc','FULL'=>$url,'URL'=>get_forum_base_url().'/uploads/'.$attachment['attach_thumb_location']));
						} else
						{
							$special=do_template('FORUM_ATTACHMENT_LINK',array('_GUID'=>'002a3220f35debbe567ce7a225aa221e','FULL'=>$url,'FILENAME'=>$attachment['attach_file'],'CLEAN_SIZE'=>clean_file_size($attachment['attach_filesize']),'NUM_DOWNLOADS'=>integer_format($attachment['attach_hits'])));
						}
					} else // Must be an inline image
					{
						$special=do_template('FORUM_ATTACHMENT_IMAGE',array('_GUID'=>'49dbf65cb5e20340a5ad4379ea6344c3','URL'=>get_forum_base_url().'/uploads/'.$attachment['attach_location']));
					}

					// See if we have to place it somewhere special inside the post
					$old_post=$post;
					$post=str_replace('[attachmentid='.$attachment['attach_id'].']',$special->evaluate(),$post);
					if ($old_post==$post) $post.=$special->evaluate();
				}

				global $LAX_COMCODE;
				$end=0;
				while (($pos=strpos($post,'[right]',$end))!==false)
				{
					$e_pos=strpos($post,'[/right]',$pos);
					if ($e_pos===false) break;
					$end=$e_pos+strlen('[/right]');
					$segment=substr($post,$pos,$end-$pos);
					$temp=$LAX_COMCODE;
					$LAX_COMCODE=true;
					$comcode=comcode_to_tempcode($segment,$r['starter_id']);
					$LAX_COMCODE=$temp;
					$post=substr($post,0,$pos).$comcode->evaluate().substr($post,$end);
				}
				$temp=$LAX_COMCODE;
				$LAX_COMCODE=true;
				$out[$i]['firstpost']=comcode_to_tempcode(xhtmlise_html($post),$r['starter_id'],false,60,NULL,NULL,false,false,true); // Assumes HTML for posts
				$LAX_COMCODE=$temp;
			}
		}
		if (count($out)!=0) return $out;
		return NULL;
	}

	/**
	 * Find the base URL to the emoticons.
	 *
	 * @return URLPATH		The base URL
	 */
	function get_emo_dir()
	{
		global $EMOTICON_SET_DIR;
		if (is_null($EMOTICON_SET_DIR))
		{
			$EMOTICON_SET_DIR=$this->connection->query_value_null_ok('skin_sets','set_emoticon_folder',array('set_image_dir'=>$this->get_theme()));
			if (is_null($EMOTICON_SET_DIR)) $EMOTICON_SET_DIR='default';
		}
		return get_forum_base_url().'/style_emoticons/'.$EMOTICON_SET_DIR.'/';
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
		$rows=$this->connection->query_select('emoticons',array('*'));
		if (!is_array($rows)) return array(); // weird importer trouble
		$EMOTICON_CACHE=array();
		foreach ($rows as $myrow)
		{
			if (strlen($myrow['image'])>0)
			{
				$src=$myrow['image'];
				if (url_is_local($src)) $src=$this->get_emo_dir().$src;
				$EMOTICON_CACHE[$this->ipb_unescape($myrow['typed'])]=array('EMOTICON_IMG_CODE_DIR',$src,$myrow['typed']);
			}
		}
		uksort($EMOTICON_CACHE,'strlen_sort');
		$EMOTICON_CACHE=array_reverse($EMOTICON_CACHE);
		return $EMOTICON_CACHE;
	}

	/**
	 * Find a list of all forum skins (aka themes).
	 *
	 * @return array			The list of skins
	 */
	function get_skin_list()
	{
		$table='skin_sets';
		$codename='set_image_dir';

		$rows=$this->connection->query_select($table,array($codename));
		return collapse_1d_complexity($codename,$rows);
	}

	/**
	 * Try to find the theme that the logged-in/guest member is using, and map it to an ocPortal theme.
	 * The themes/map.ini file functions to provide this mapping between forum themes, and ocPortal themes, and has a slightly different meaning for different forum drivers. For example, some drivers map the forum themes theme directory to the ocPortal theme name, whilst others made the humanly readeable name.
	 *
	 * @param  boolean		Whether to avoid member-specific lookup
	 * @return ID_TEXT		The theme
	 */
	function _get_theme($skip_member_specific=false)
	{
		$def='';

		// Load in remapper
		$map=file_exists(get_file_base().'/themes/map.ini')?better_parse_ini_file(get_file_base().'/themes/map.ini'):array();

		if (!$skip_member_specific)
		{
			// Work out
			$member=get_member();
			if ($member>0)
				$skin=$this->get_member_row_field($member,'skin'); else $skin=0;
			if ($skin>0) // User has a custom theme
			{
				$ipb=$this->connection->query_value_null_ok('skin_sets','set_image_dir',array('set_skin_set_id'=>$skin));
				if (!is_null($ipb)) $def=array_key_exists($ipb,$map)?$map[$ipb]:$ipb;
			}
		}

		// Look for a skin according to our site name (we bother with this instead of 'default' because ocPortal itself likes to never choose a theme when forum-theme integration is on: all forum [via map] or all ocPortal seems cleaner, although it is complex)
		if ((!(strlen($def)>0)) || (!file_exists(get_custom_file_base().'/themes/'.$def)))
		{
			$ipb=$this->connection->query_value_null_ok('skin_sets','set_image_dir',array('set_name'=>get_site_name()));
			if (!is_null($ipb)) $def=array_key_exists($ipb,$map)?$map[$ipb]:$ipb;
		}

		// Hmm, just the very-default then
		if ((!(strlen($def)>0)) || (!file_exists(get_custom_file_base().'/themes/'.$def)))
		{
			$ipb=$this->connection->query_value_null_ok('skin_sets','set_image_dir',array('set_default'=>1));
			if (!is_null($ipb)) $def=array_key_exists($ipb,$map)?$map[$ipb]:$ipb;
		}

		// Default then!
		if ((!(strlen($def)>0)) || (!file_exists(get_custom_file_base().'/themes/'.$def)))
			$def=array_key_exists('default',$map)?$map['default']:'default';

		return $def;
	}

	/**
	 * Get the number of members registered on the forum.
	 *
	 * @return integer		The number of members
	 */
	function get_members()
	{
		$r=$this->connection->query('SELECT COUNT(*) AS a FROM '.$this->connection->get_table_prefix().'members WHERE mgroup<>1');
		return $r[0]['a'];
	}

	/**
	 * Get the total topics ever made on the forum.
	 *
	 * @return integer		The number of topics
	 */
	function get_topics()
	{
		return $this->connection->query_value('topics','COUNT(*)');
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
	 * Get the forum usergroup relating to the specified member id.
	 *
	 * @param  MEMBER			The member id
	 * @return array			The array of forum usergroups
	 */
	function _get_members_groups($member)
	{
		$group=$this->get_member_row_field($member,'mgroup');
		$secondary=array($group);
		$more=$this->get_member_row_field($member,'mgroup_others');
		if (($more!='') && (!is_null($more))) $secondary=array_merge($secondary,explode(',',$more));
		return $secondary;
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
		$_password=$this->get_member_row_field($id,'member_login_key');
		ocp_setcookie(get_pass_cookie(),$_password);
		$_COOKIE[get_pass_cookie()]=$_password;

		// Set stronghold
		global $SITE_INFO;
		if ((array_key_exists('stronghold_cookies',$SITE_INFO)) && ($SITE_INFO['stronghold_cookies']=='1'))
		{
			$ip_octets=explode('.',ocp_srv('REMOTE_ADDR'));
			$crypt_salt=md5(get_db_forums_password().get_db_forums_user());
			$a=get_member_cookie();
			$b=get_pass_cookie();
			for ($i=0;$i<strlen($a) && $i<strlen($b);$i++)
			{
				if ($a[$i]!=$b[$i]) break;
			}
			$cookie_prefix=substr($a,0,$i);
			$stronghold=md5(md5(strval($id).'-'.$ip_octets[0].'-'.$ip_octets[1].'-'.$_password).$crypt_salt);
			ocp_setcookie($cookie_prefix.'ipb_stronghold',$stronghold);
		}
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
			$rows=$this->connection->query_select('members',array('*'),array('name'=>$this->ipb_escape($username)),'',1);
			if (array_key_exists(0,$rows))
			{
				$this->MEMBER_ROWS_CACHED[$rows[0]['id']]=$rows[0];
			} else
			{
				$rows=$this->connection->query_select('members',array('*'),array('members_display_name'=>$this->ipb_escape($username)),'',1);
				if (array_key_exists(0,$rows))
				{
					$this->MEMBER_ROWS_CACHED[$rows[0]['id']]=$rows[0];
				}
			}
		} else
		{
			$rows[0]=$this->get_member_row($userid);
		}

		if (!array_key_exists(0,$rows)) // All hands to lifeboats
		{
			$out['error']=do_lang_tempcode('_USER_NO_EXIST',escape_html($username));
			return $out;
		}
		$row=$rows[0];
		if ($this->is_banned($row['id'])) // All hands to the guns
		{
			$out['error']=do_lang_tempcode('USER_BANNED');
			return $out;
		}
		if ($cookie_login)
		{
			if ($password_hashed!=$row['member_login_key'])
			{
				$out['error']=do_lang_tempcode('USER_BAD_PASSWORD');
				return $out;
			}

			// Check stronghold
			global $SITE_INFO;
			if ((array_key_exists('stronghold_cookies',$SITE_INFO)) && ($SITE_INFO['stronghold_cookies']=='1'))
			{
				$ip_octets=explode('.',ocp_srv('REMOTE_ADDR'));
				$crypt_salt=md5(get_db_forums_password().get_db_forums_user());
				$a=get_member_cookie();
				$b=get_pass_cookie();
				for ($i=0;$i<strlen($a) && $i<strlen($b);$i++)
				{
					if ($a[$i]!=$b[$i]) break;
				}
				$cookie_prefix=substr($a,0,$i);
				$cookie=ocp_admirecookie($cookie_prefix.'ipb_stronghold');
				$stronghold=md5(md5(strval($row['id']).'-'.$ip_octets[0].'-'.$ip_octets[1].'-'.$row['member_login_key']).$crypt_salt);
				if ($cookie!=$stronghold)
				{
					$out['error']=do_lang_tempcode('USER_BAD_STRONGHOLD');
					return $out;
				}
			}
		} else
		{
			if (!$this->_auth_hashed($row['id'],$password_hashed))
			{
				$out['error']=do_lang_tempcode('USER_BAD_PASSWORD');
				return $out;
			}
		}

		$pos=strpos(get_member_cookie(),'member_id');
		ocp_eatcookie(substr(get_member_cookie(),0,$pos).'session_id');

		$out['id']=$row['id'];
		return $out;
	}

	/**
	 * Do converge authentication.
	 *
	 * @param  MEMBER			The member id
	 * @param  string			The password
	 * @return boolean		Whether authentication succeeded
	 */
	function _auth_hashed($id,$password)
	{
		$rows=$this->connection->query_select('members_converge',array('converge_pass_hash','converge_pass_salt'),array('converge_id'=>$id),'',1);
		if (!array_key_exists(0,$rows)) return false;
		$row=$rows[0];
		if (md5(md5($row['converge_pass_salt']).$password)!=$row['converge_pass_hash']) return false;
		return true;
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

		if ($member==0)
		{
			$rows=array();
			$rows[0]=array();
			$rows[0]['id']=0;
			$rows[0]['name']=do_lang('GUEST');
			$rows[0]['members_display_name']=do_lang('GUEST');
			$rows[0]['mgroup']=2;
			$rows[0]['language']=NULL;
		} else
		{
			$rows=$this->connection->query_select('members',array('*'),array('id'=>$member),'',1);
		}

		$this->MEMBER_ROWS_CACHED[$member]=array_key_exists(0,$rows)?$rows[0]:NULL;
		return $this->MEMBER_ROWS_CACHED[$member];
	}

}



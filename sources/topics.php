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
 * @package		core_feedback_features
 */

/*

This is an ocPortal sub-system (not specifically OCF!) for both threaded and non-threaded topic rendering.

API interfaces with...
 Feedback system (calls 'render_as_comment_topic' method which knows about content types and content IDs)
  Or can be called standalone using topic IDs
 Forum driver system (which knows about topic_identifier's [a derivative of content types and content IDs] and plain topic IDs)

This API does not handle posting, although it can render a posting form. The feedback system and the ocf_forum addon handle posting separately.


The chat rooms and activity feeds are NOT topics, and not handled through this system.
The non-threaded ocf_forum view has its own rendering.

*/

/**
 * Manage threaded topics / comment topics.
 * @package		core_feedback_features
 */
class OCP_Topic
{
	// Settable...
	//Influences comment form
	var $reviews_rating_criteria=array();
	//Influences spacer post detection (usually only on first render, and only in the OCF topicview)
	var $first_post_id=NULL;
	var $topic_description=NULL;
	var $topic_description_link=NULL;
	var $topic_info=NULL;

	// Will be filled up during processing
	var $all_posts_ordered=NULL;
	var $is_threaded=NULL;
	var $topic_id=NULL; // May need setting, if posts were loaded in manually rather than letting the class load them; may be left as NULL but functionality degrades somewhat
	var $reverse=false;

	// Will be filled up like 'return results'
	var $error=false;
	var $replied=false;
	var $total_posts=NULL;
	var $topic_title=NULL;

	/**
	 * Constructor.
	 */
	function OCP_Topic()
	{
	}

	/**
	 * Render a comment topic.
	 *
	 * @param  ID_TEXT		Content type to show topic for
	 * @param  ID_TEXT		Content ID of content type to show topic for
	 * @param  boolean		Whether this resource allows comments (if not, this function does nothing - but it's nice to move out this common logic into the shared function)
	 * @param  boolean		Whether the comment box will be invisible if there are not yet any comments (and you're not staff)
	 * @param  ?string		The name of the forum to use (NULL: default comment forum)
	 * @param  ?string		The default post to use (NULL: standard courtesy warning)
	 * @param  ?mixed			The raw comment array (NULL: lookup). This is useful if we want to pass it through a filter
	 * @param  boolean		Whether to skip permission checks
	 * @param  boolean		Whether to reverse the posts
	 * @param  ?MEMBER		User to highlight the posts of (NULL: none)
	 * @param  boolean		Whether to allow ratings along with the comment (like reviews)
	 * @return tempcode		The tempcode for the comment topic
	 */
	function render_as_comment_topic($content_type,$content_id,$allow_comments,$invisible_if_no_comments,$forum_name,$post_warning,$preloaded_comments,$explicit_allow,$reverse,$highlight_by_user,$allow_reviews)
	{
		if ((get_forum_type()=='ocf') && (!addon_installed('ocf_forum'))) return new ocp_tempcode();

		$topic_id=$GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier($forum_name,$content_type.'_'.$content_id);

		// Settings we need
		$max_thread_depth=get_param_integer('max_thread_depth',intval(get_option('max_thread_depth')));
		$num_to_show_limit=get_param_integer('max_comments',intval(get_option('comments_to_show_in_thread')));
		$start=get_param_integer('start_comments',0);

		// Load up posts from DB
		if (is_null($preloaded_comments))
		{
			if (!$this->load_from_topic($topic_id,$num_to_show_limit,$start,$reverse))
				attach_message(do_lang_tempcode('MISSING_FORUM',escape_html($forum_name)),'warn');
		} else
		{
			$this->_inject_posts_for_scoring_algorithm($preloaded_comments);
		}

		if (!$this->error)
		{
			if ((count($this->all_posts_ordered)==0) && ($invisible_if_no_comments))
				return new ocp_tempcode();

			$may_reply=has_specific_permission(get_member(),'comment',get_page_name());

			// Prepare review titles
			global $REVIEWS_STRUCTURE;
			if ($allow_reviews)
			{
				if (array_key_exists($content_type,$REVIEWS_STRUCTURE))
				{
					$this->set_reviews_rating_criteria($REVIEWS_STRUCTURE[$content_type]);
				} else
				{
					$this->set_reviews_rating_criteria(array(''));
				}
			}

			// Load up reviews
			if ((get_forum_type()=='ocf') && ($allow_reviews))
			{
				$all_individual_review_ratings=$GLOBALS['SITE_DB']->query_select('review_supplement',array('*'),array('r_topic_id'=>$topic_id));
			} else
			{
				$all_individual_review_ratings=array();
			}

			$forum_id=$GLOBALS['FORUM_DRIVER']->forum_id_from_name($forum_name);

			// Posts
			list($posts,$serialized_options,$hash)=$this->render_posts($num_to_show_limit,$max_thread_depth,$may_reply,$highlight_by_user,$all_individual_review_ratings,$forum_id);

			// Pagination
			$pagination=NULL;
			if ((!$this->is_threaded) && (is_null($preloaded_comments)))
			{
				if ($this->total_posts>$num_to_show_limit)
				{
					require_code('templates_pagination');
					$pagination=pagination(do_lang_tempcode('COMMENTS'),NULL,$start,'start_comments',$num_to_show_limit,'max_comments',$this->total_posts,NULL,NULL,true);
				}
			}

			// Environment meta data
			$this->inject_rss_url($forum_name,$content_type,$content_id);
			$this->inject_meta_data();

			// Make-a-comment form
			if ($may_reply)
			{
				$post_url=get_self_url();
				$form=$this->get_posting_form($content_type,$content_id,$allow_reviews,$post_url,$post_warning);
			} else
			{
				$form=new ocp_tempcode();
			}

			// Existing review ratings
			$reviews_rating_criteria=array();
			if ((get_forum_type()=='ocf') && ($allow_reviews))
			{
				foreach ($this->reviews_rating_criteria as $review_title)
				{
					$_rating=$GLOBALS['SITE_DB']->query_value('review_supplement','AVG(r_rating)',array('r_rating_type'=>$review_title,'r_topic_id'=>$topic_id));
					$rating=mixed();
					$rating=is_null($_rating)?NULL:$_rating;
					$reviews_rating_criteria[]=array('REVIEW_TITLE'=>$review_title,'REVIEW_RATING'=>make_string_tempcode(is_null($rating)?'':float_format($rating)));
					if (!is_null($rating))
					{
						$GLOBALS['META_DATA']+=array(
							'rating'=>float_to_raw_string($rating),
						);
					}
				}
			}

			// Direct links to forum
			$forum_url=is_null($topic_id)?'':$GLOBALS['FORUM_DRIVER']->topic_url($topic_id,$forum_name,true);
			if (($GLOBALS['FORUM_DRIVER']->is_staff(get_member())) || ($forum_name==get_option('comments_forum_name')))
			{
				$authorised_forum_url=$forum_url;
			} else
			{
				$authorised_forum_url='';
			}

			// Show it all
			return do_template('COMMENTS_WRAPPER',array(
				'_GUID'=>'a89cacb546157d34vv0994ef91b2e707',
				'PAGINATION'=>$pagination,
				'TYPE'=>$content_type,
				'ID'=>$content_id,
				'REVIEW_RATING_CRITERIA'=>$reviews_rating_criteria,
				'FORUM_LINK'=>$forum_url,
				'AUTHORISED_FORUM_URL'=>$authorised_forum_url,
				'FORM'=>$form,
				'COMMENTS'=>$posts,
				'HASH'=>$hash,
				'SERIALIZED_OPTIONS'=>$serialized_options,
			));
		}

		return new ocp_tempcode();
	}

	/**
	 * Render posts from a topic (usually tied into AJAX, to get iterative results).
	 *
	 * @param  AUTO_LINK		The topic ID
	 * @param  integer		Maximum to load if non-threaded
	 * @param  boolean		Whether this resource allows comments (if not, this function does nothing - but it's nice to move out this common logic into the shared function)
	 * @param  boolean		Whether the comment box will be invisible if there are not yet any comments (and you're not staff)
	 * @param  ?string		The name of the forum to use (NULL: default comment forum)
	 * @param  ?mixed			The raw comment array (NULL: lookup). This is useful if we want to pass it through a filter
	 * @param  boolean		Whether to reverse the posts
	 * @param  boolean		Whether the current user may reply to the topic (influences what buttons show)
	 * @param  ?MEMBER		User to highlight the posts of (NULL: none)
	 * @param  boolean		Whether to allow ratings along with the comment (like reviews)
	 * @param  array			List of post IDs to load
	 * @param  AUTO_LINK		Parent node being loaded to
	 * @return tempcode		The tempcode for the comment topic
	 */
	function render_posts_from_topic($topic_id,$num_to_show_limit,$allow_comments,$invisible_if_no_comments,$forum_name,$preloaded_comments,$reverse,$may_reply,$highlight_by_user,$allow_reviews,$posts,$parent_id)
	{
		if ((get_forum_type()=='ocf') && (!addon_installed('ocf_forum'))) return new ocp_tempcode();

		$max_thread_depth=get_param_integer('max_thread_depth',intval(get_option('max_thread_depth')));
		$start=0;

		// Load up posts from DB
		if (!$this->load_from_topic($topic_id,$num_to_show_limit,$start,$reverse,$posts))
			attach_message(do_lang_tempcode('MISSING_FORUM',escape_html($forum_name)),'warn');

		if (!$this->error)
		{
			if ((count($this->all_posts_ordered)==0) && ($invisible_if_no_comments))
				return new ocp_tempcode();

			// Prepare review titles
			$this->set_reviews_rating_criteria(array(''));

			// Load up reviews
			if ((get_forum_type()=='ocf') && ($allow_reviews))
			{
				$all_individual_review_ratings=$GLOBALS['SITE_DB']->query_select('review_supplement',array('*'),array('r_topic_id'=>$topic_id));
			} else
			{
				$all_individual_review_ratings=array();
			}

			$forum_id=$GLOBALS['FORUM_DRIVER']->forum_id_from_name($forum_name);

			// Render
			$rendered=$this->render_posts($num_to_show_limit,$max_thread_depth,$may_reply,$highlight_by_user,$all_individual_review_ratings,$forum_id,$parent_id,true);
			$ret=$rendered[0];
			return $ret;
		}

		return new ocp_tempcode();
	}

	/**
	 * Load from a given topic ID.
	 *
	 * @param  AUTO_LINK		Topic ID
	 * @param  integer		Maximum to load if non-threaded
	 * @param  integer		Pagination start if non-threaded
	 * @param  boolean		Whether to show in reverse date order
	 * @param  ?array			List of post IDs to load (NULL: no filter)
	 * @param  boolean		Whether to allow spacer posts to flow through the renderer
	 * @return boolean		Success status
	 */
	function load_from_topic($topic_id,$num_to_show_limit,$start=0,$reverse=false,$posts=NULL,$load_spacer_posts_too=false)
	{
		$this->topic_id=$topic_id;
		$this->reverse=$reverse;

		if (get_param_integer('threaded',NULL)===1)
		{
			$this->is_threaded=true;
		} else
		{
			$this->is_threaded=$GLOBALS['FORUM_DRIVER']->topic_is_threaded($topic_id);
		}

		$posts=$GLOBALS['FORUM_DRIVER']->get_forum_topic_posts(
			$topic_id,
			$this->total_posts,
			$this->is_threaded?5000:$num_to_show_limit,
			$this->is_threaded?0:$start,
			true,
			($reverse && !$this->is_threaded),
			true,
			$posts,
			$load_spacer_posts_too
		);

		if ($posts!==-1)
		{
			if ($posts===-2)
			{
				$posts=array();
			}
			$this->_inject_posts_for_scoring_algorithm($posts);

			return true;
		}

		$this->error=true;
		return false;
	}

	/**
	 * Put in posts to our scoring algorithm in preparation for shooting out later.
	 *
	 * @param  array			Review titles
	 */
	function _inject_posts_for_scoring_algorithm($posts)
	{
		$all_posts_ordered=array();
		foreach ($posts as $post)
		{
			if (is_null($post)) continue;

			if (!isset($post['parent_id'])) $post['parent_id']=NULL;
			if (!isset($post['id'])) $post['id']=mt_rand(0,mt_getrandmax());

			$post_key='post_'.strval($post['id']);
			$all_posts_ordered[$post_key]=$post;
		}
		$this->all_posts_ordered=$all_posts_ordered;
	}

	/**
	 * Set the particular review criteria we'll be dealing with.
	 *
	 * @param  array			Review criteria
	 */
	function set_reviews_rating_criteria($reviews_rating_criteria)
	{
		$this->reviews_rating_criteria=$reviews_rating_criteria;
	}

	/**
	 * Render a topic.
	 *
	 * @param  ?integer		Number of posts to show initially (NULL: no limit)
	 * @param  integer		Maximum thread depth
	 * @param  boolean		Whether the current user may reply to the topic (influences what buttons show)
	 * @param  ?MEMBER		User to highlight the posts of (NULL: none)
	 * @param  array			Review ratings rows
	 * @param  AUTO_LINK		ID of forum this topic in in
	 * @param  ?AUTO_LINK	Only show posts under here (NULL: show posts from root)
	 * @param  boolean		Whether to just render everything as flat (used when doing AJAX post loading). NOT actually used since we wrote better post-orphaning-fixing code.
	 * @return array			Tuple: Rendered topic, serialized options to render more posts, secure hash of serialized options to prevent tampering
	 */
	function render_posts($num_to_show_limit,$max_thread_depth,$may_reply,$highlight_by_user,$all_individual_review_ratings,$forum_id,$parent_post_id=NULL,$maybe_missing_links=false)
	{
		require_code('feedback');

		if ((get_forum_type()=='ocf') && (!addon_installed('ocf_forum'))) return array();

		$posts=array();
		$queue=$this->all_posts_ordered;
		if ((!is_null($parent_post_id)) && (!$maybe_missing_links))
		{
			$queue=$this->_grab_at_and_underneath($parent_post_id,$queue);
		}
		if (is_null($this->is_threaded)) $this->is_threaded=false;
		if ((is_null($num_to_show_limit)) || (!$this->is_threaded))
		{
			$posts=$queue;
			$queue=array();
		} else
		{
			$posts=$this->_decide_what_to_render($num_to_show_limit,$queue);
		}

		require_javascript('javascript_ajax');
		require_javascript('javascript_more');
		require_javascript('javascript_transitions');

		// Precache member/group details in one fell swoop
		if (get_forum_type()=='ocf')
		{
			require_code('ocf_topicview');
			$members=array();
			foreach ($posts as $_postdetails)
			{
				$members[$_postdetails['p_poster']]=1;
			}
			ocf_cache_member_details(array_keys($members));
		}

		if (!is_null($this->topic_id)) // If FALSE then Posts will have been passed in manually as full already anyway
			$posts=$this->_grab_full_post_details($posts);

		if ($this->is_threaded)
		{
			$tree=$this->_arrange_posts_in_tree($parent_post_id,$posts/*passed by reference*/,$queue,$max_thread_depth);
			if (count($posts)!=0) // E.g. if parent was deleted at some time
			{
				global $M_SORT_KEY;
				$M_SORT_KEY='date';
				usort($posts,'multi_sort');
				while (count($posts)!=0)
				{
					$orphaned_post=array_shift($posts);

					$tree2=$this->_arrange_posts_in_tree($orphaned_post['id'],$posts/*passed by reference*/,$queue,$max_thread_depth);

					$orphaned_post['parent_id']=NULL;
					$orphaned_post['children']=$tree2;
					$tree[0][]=$orphaned_post;
				}
			}
		} else
		{
			$tree=array($posts);
		}

		$ret=$this->_render_post_tree($num_to_show_limit,$tree,$may_reply,$highlight_by_user,$all_individual_review_ratings,$forum_id);

		$other_ids=mixed();
		if ($this->is_threaded)
		{
			$other_ids=array();
			foreach ($tree[1] as $u)
			{
				$other_ids[]=strval($u['id']);
			}
		}
		$ret->attach(do_template('POST_CHILD_LOAD_LINK',array('NUM_TO_SHOW_LIMIT'=>strval($num_to_show_limit),'OTHER_IDS'=>$other_ids,'ID'=>'','CHILDREN'=>empty($other_ids)?'':'1')));

		if (!is_null($this->topic_id))
		{
			$serialized_options=serialize(array($this->topic_id,$num_to_show_limit,true,false,strval($forum_id),$this->reverse,$may_reply,$highlight_by_user,count($all_individual_review_ratings)!=0));
			$hash=best_hash($serialized_options,get_site_salt());
		} else
		{
			$serialized_options=mixed();
			$hash=mixed();
		}

		return array($ret,$serialized_options,$hash);
	}

	/**
	 * Filter posts, deciding what to render.
	 *
	 * @param  integer		Number of posts to show initially
	 * @param  array			Posts to choose from, in preference order
	 * @return array			Chosen posts
	 */
	function _decide_what_to_render($num_to_show_limit,&$queue)
	{
		$posts=array();
		while ((count($posts)<$num_to_show_limit) && (count($queue)!=0))
		{
			$next=reset($queue);

			if ($next['p_poster']==get_member())
				$this->replied=true;

			$post_id=$next['id'];
			$this->_grab_at_and_above_and_remove($post_id,$queue,$posts);
		}

		// Any posts by current member must be grabbed too (up to 3 root ones though - otherwise risks performance), and also first post
		$num_poster_grabbed=0;
		foreach ($queue as $i=>$q)
		{
			if ((($q['p_poster']==get_member()) && ($q['parent_id']===NULL) && ($num_poster_grabbed<3)) || ($q['id']===$this->first_post_id))
			{
				$this->replied=true;
				if ($q['id']===$this->first_post_id) // First post must go first
				{
					$posts_backup=$posts;
					$posts=array();
					$posts['post_'.strval($q['id'])]=$q;
					$posts+=$posts_backup;
				} else
				{
					$posts['post_'.strval($q['id'])]=$q;
				}
				if ($q['p_poster']==get_member())
				{
					$num_poster_grabbed++;
				}
				unset($queue[$i]);
			}
		}

		return $posts;
	}

	/**
	 * Grab posts at or above a reference post and remove from queue.
	 *
	 * @param  AUTO_LINK		Reference post in thread
	 * @param  array			Posts to choose from (the queue)
	 * @param  array			Posts picked out (passed by reference)
	 */
	function _grab_at_and_above_and_remove($post_id,&$queue,&$posts)
	{
		if ((!isset($posts[$post_id])) && (isset($queue['post_'.strval($post_id)])))
		{
			$grabbed=$queue['post_'.strval($post_id)];
			if ($post_id===$this->first_post_id) // First post must go first
			{
				$posts_backup=$posts;
				$posts=array();
				$posts['post_'.strval($post_id)]=$grabbed;
				$posts+=$posts_backup;
			} else
			{
				$posts['post_'.strval($post_id)]=$grabbed;
			}
			unset($queue['post_'.strval($post_id)]);
			$parent=$grabbed['parent_id'];
			if (!is_null($parent))
			{
				$this->_grab_at_and_above_and_remove($parent,$queue,$posts);
			}
		}
	}

	/**
	 * Grab posts at or underneath a reference post.
	 *
	 * @param  ?AUTO_LINK	Reference post in thread (NULL: root)
	 * @param  array			Posts to choose from
	 * @return array			Relevant posts
	 */
	function _grab_at_and_underneath($parent_post_id,$posts_in)
	{
		$posts_out=array();

		if (!is_null($parent_post_id))
		{
			if (isset($posts_in['post_'.strval($parent_post_id)]))
			{
				$grabbed=$posts_in['post_'.strval($parent_post_id)];
				$posts_out['post_'.strval($parent_post_id)]=$grabbed;
			}
		}

		// Underneath
		foreach ($posts_in as $x)
		{
			if ($x['parent_id']===$parent_post_id)
			{
				$underneath=$this->_grab_at_and_underneath($x['id'],$posts_in);
				foreach ($underneath as $id=>$y)
				{
					$posts_out['post_'.strval($id)]=$y;
				}
			}
		}

		return $posts_out;
	}

	/**
	 * Load full details for posts (we had not done so far to preserve memory).
	 *
	 * @param  array			Posts to load
	 * @return array			Upgraded posts
	 */
	function _grab_full_post_details($posts)
	{
		$id_list=array();
		foreach ($posts as $p)
		{
			if (!isset($p['post'])) $id_list[]=$p['id'];
		}
		$posts_extended=list_to_map('id',$GLOBALS['FORUM_DRIVER']->get_post_remaining_details($this->topic_id,$id_list));
		foreach ($posts as $i=>$p)
		{
			if (isset($posts_extended[$p['id']]))
			{
				$posts[$i]+=$posts_extended[$p['id']];
			}
		}
		return $posts;
	}

	/**
	 * Arrange posts underneath a post in the thread (not including the post itself).
	 *
	 * @param  ?AUTO_LINK	Reference post in thread (NULL: root)
	 * @param  array			Posts we will be rendering and have not arranged yet (only some of which will be underneath $post_id)
	 * @param  array			Posts we won't be rendering
	 * @param  integer		Maximum depth to render to
	 * @param  integer		Current depth in recursion
	 * @return array			Array structure of rendered posts
	 */
	function _arrange_posts_in_tree($post_id,&$posts,$queue,$max_thread_depth,$depth=0)
	{
		$rendered=array();
		$non_rendered=array();

		$posts_copy=$posts; // So the foreach's array iteration pointer is not corrupted by the iterations in our recursive calls (issue on some PHP versions)
		foreach ($posts_copy as $i=>$p)
		{
			if ($p['parent_id']===$post_id)
			{
				unset($posts[$i]);

				$children=$this->_arrange_posts_in_tree($p['id'],$posts,$queue,$max_thread_depth,$depth+1);

				if ($depth+1>=$max_thread_depth) // Ones that are too deep need flattening down with post Comcode
				{
					foreach ($children[0] as $j=>$c)
					{
						if (strpos($c['message_comcode'],'[quote')===false)
						{
							$c['message_comcode']='[quote="'.comcode_escape($p['username']).'"]'.$p['message_comcode'].'[/quote]'."\n\n".$c['message_comcode'];
							$new=do_template('COMCODE_QUOTE_BY',array('SAIDLESS'=>false,'BY'=>$p['username'],'CONTENT'=>$p['message']));
							$new->attach($c['message']);
							$c['message']=$new;
						}
						$c['parent_id']=$p['parent_id'];
						$children[0][$j]=$c;
					}

					$p['children']=array(array(),array());
					$rendered[]=$p;
					$rendered=array_merge($rendered,$children[0]);
					$non_rendered=array_merge($non_rendered,$children[1]);
				} else
				{
					$p['children']=$children;
					$rendered[]=$p;
				}
			}
		}

		$non_rendered=array_merge($non_rendered,$this->_grab_at_and_underneath($post_id,$queue));

		return array($rendered,$non_rendered);
	}

	/**
	 * Render posts.
	 *
	 * @param  integer		Maximum to load if non-threaded
	 * @param  array			Tree structure of posts
	 * @param  boolean		Whether the current user may reply to the topic (influences what buttons show)
	 * @param  ?AUTO_LINK	Only show posts under here (NULL: show posts from root)
	 * @param  array			Review ratings rows
	 * @param  AUTO_LINK		ID of forum this topic in in
	 * @return tempcode		Rendered tree structure
	 */
	function _render_post_tree($num_to_show_limit,$tree,$may_reply,$highlight_by_user,$all_individual_review_ratings,$forum_id)
	{
		list($rendered,)=$tree;
		$sequence=new ocp_tempcode();
		foreach ($rendered as $post)
		{
			if (get_forum_type()=='ocf')
			{
				require_code('ocf_topicview');
				require_code('ocf_posts');
				$post+=ocf_get_details_to_show_post($post);
			}

			// Misc details
			$datetime_raw=$post['date'];
			$datetime=get_timezoned_date($post['date']);
			$poster_url=is_guest($post['user'])?new ocp_tempcode():$GLOBALS['FORUM_DRIVER']->member_profile_url($post['user'],false,true);
			$poster_name=array_key_exists('username',$post)?$post['username']:$GLOBALS['FORUM_DRIVER']->get_username($post['user']);
			if (is_null($poster_name)) $poster_name=do_lang('UNKNOWN');
			$highlight=($highlight_by_user===$post['user']);

			// Find review, if there is one
			$individual_review_ratings=array();
			foreach ($all_individual_review_ratings as $potential_individual_review_rating)
			{
				if ($potential_individual_review_rating['r_post_id']==$post['id'])
				{
					$individual_review_ratings[$potential_individual_review_rating['r_rating_type']]=array(
						'REVIEW_TITLE'=>$potential_individual_review_rating['r_rating_type'],
						'REVIEW_RATING'=>float_to_raw_string($potential_individual_review_rating['r_rating']),
					);
				}
			}

			// Edit URL
			$emphasis=new ocp_tempcode();
			$buttons=new ocp_tempcode();
			$last_edited=new ocp_tempcode();
			$last_edited_raw='';
			$unvalidated=new ocp_tempcode();
			$poster=mixed();
			$poster_details=new ocp_tempcode();
			$is_spacer_post=false;
			if (get_forum_type()=='ocf')
			{
				// Spacer post fiddling
				if ((!is_null($this->first_post_id)) && (!is_null($this->topic_title)) && (!is_null($this->topic_description)) && (!is_null($this->topic_description_link)))
				{
					$is_spacer_post=(($post['id']==$this->first_post_id) && (substr($post['message_comcode'],0,strlen('[semihtml]'.do_lang('SPACER_POST_MATCHER')))=='[semihtml]'.do_lang('SPACER_POST_MATCHER')));

					if ($is_spacer_post)
					{
						$c_prefix=do_lang('COMMENT').': #';
						if ((substr($this->topic_description,0,strlen($c_prefix))==$c_prefix) && ($this->topic_description_link!=''))
						{
							list($linked_type,$linked_id)=explode('_',substr($this->topic_description,strlen($c_prefix)),2);
							$linked_url=$this->topic_description_link;

							require_code('ocf_posts');
							list($new_description,$new_post)=ocf_display_spacer_post($linked_type,$linked_id);
							//if (!is_null($new_description)) $this->topic_description=$new_description;	Actually, it's a bit redundant
							if (!is_null($new_post))
							{
								$post['message']=$new_post;
							}
							$highlight=false;

							$this->topic_title=do_lang('SPACER_TOPIC_TITLE_WRAP',$this->topic_title);
							$post['title']=do_lang('SPACER_TOPIC_TITLE_WRAP',$post['title']);
							$this->topic_description='';
						}
					}
				}

				// Misc meta details for post
				$emphasis=ocf_get_post_emphasis($post);
				$unvalidated=($post['validated']==0)?do_lang_tempcode('UNVALIDATED'):new ocp_tempcode();
				if (array_key_exists('last_edit_time',$post))
				{
					$last_edited=do_template('OCF_TOPIC_POST_LAST_EDITED',array('LAST_EDIT_DATE_RAW'=>is_null($post['last_edit_time'])?'':strval($post['last_edit_time']),'LAST_EDIT_DATE'=>$post['last_edit_time_string'],'LAST_EDIT_PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url($post['last_edit_by'],false,true),'LAST_EDIT_USERNAME'=>$post['last_edit_by_username']));
					$last_edited_raw=(is_null($post['last_edit_time'])?'':strval($post['last_edit_time']));
				}

				// Post buttons
				if (!$is_spacer_post)
				{
					if (!is_null($this->topic_id))
					{
						if (is_null($this->topic_info))
						{
							$this->topic_info=ocf_read_in_topic($this->topic_id,0,0,false,false);
						}
						require_lang('ocf');
						$buttons=ocf_render_post_buttons($this->topic_info,$post,$may_reply);
					}
				}

				// OCF renderings of poster
				static $hooks=NULL;
				if (is_null($hooks)) $hooks=find_all_hooks('modules','topicview');
				static $hook_objects=NULL;
				if (is_null($hook_objects))
				{
					$hook_objects=array();
					foreach (array_keys($hooks) as $hook)
					{
						require_code('hooks/modules/topicview/'.filter_naughty_harsh($hook));
						$object=object_factory('Hook_'.filter_naughty_harsh($hook),true);
						if (is_null($object)) continue;
						$hook_objects[$hook]=$object;
					}
				}
				if (!$is_spacer_post)
				{
					if (!is_guest($post['poster']))
					{
						require_code('ocf_members2');
						$poster_details=render_member_box($post,false,$hooks,$hook_objects,false);
					} else
					{
						$custom_fields=new ocp_tempcode();
						if ((array_key_exists('ip_address',$post)) && (addon_installed('ocf_forum')))
						{
							$custom_fields->attach(do_template('OCF_MEMBER_BOX_CUSTOM_FIELD',array('NAME'=>do_lang_tempcode('IP_ADDRESS'),'VALUE'=>($post['ip_address']))));
							$poster_details=do_template('OCF_GUEST_DETAILS',array('_GUID'=>'df42e7d5003834a60fdb3bf476b393c5','CUSTOM_FIELDS'=>$custom_fields));
						} else
						{
							$poster_details=new ocp_tempcode();
						}
					}
				}
				if (addon_installed('ocf_forum'))
				{
					if (!is_guest($post['poster']))
					{
						$poster=do_template('OCF_POSTER_MEMBER',array('ONLINE'=>member_is_online($post['poster']),'ID'=>strval($post['poster']),'POSTER_DETAILS'=>$poster_details,'PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url($post['poster'],false,true),'POSTER_USERNAME'=>$post['poster_username']));
					} else
					{
						$ip_link=((array_key_exists('ip_address',$post)) && (has_actual_page_access(get_member(),'admin_lookup')))?build_url(array('page'=>'admin_lookup','param'=>$post['ip_address']),get_module_zone('admin_lookup')):new ocp_tempcode();
						$poster=do_template('OCF_POSTER_GUEST',array('_GUID'=>'93107543c6a0138f379e7124b72b24ff','LOOKUP_IP_URL'=>$ip_link,'POSTER_DETAILS'=>$poster_details,'POSTER_USERNAME'=>$post['poster_username']));
					}
				} else
				{
					$poster=make_string_tempcode(escape_html($post['poster_username']));
				}
			}

			// Child posts
			$children=mixed(); // NULL
			$other_ids=array();
			if (array_key_exists('children',$post))
			{
				foreach ($post['children'][1] as $u)
				{
					$other_ids[]=strval($u['id']);
				}
				if ($this->is_threaded)
				{
					$children=$this->_render_post_tree($num_to_show_limit,$post['children'],$may_reply,$highlight_by_user,$all_individual_review_ratings,$forum_id);
				}
			}

			if (get_forum_type()=='ocf')
			{
				require_code('feedback');
				actualise_rating(true,'post',strval($post['id']),get_self_url(),$post['title']);
				$rating=display_rating(get_self_url(),$post['title'],'post',strval($post['id']),'RATING_INLINE_DYNAMIC',$post['user']);
			} else
			{
				$rating=new ocp_tempcode();
			}

			if (array_key_exists('intended_solely_for',$post))
			{
				decache('side_ocf_personal_topics',array(get_member()));
				decache('_new_pp',array(get_member()));
			}

			// Render
			$sequence->attach(/*performance*/static_evaluate_tempcode(do_template('POST',array(
				'_GUID'=>'eb7df038959885414e32f58e9f0f9f39',
				'INDIVIDUAL_REVIEW_RATINGS'=>$individual_review_ratings,
				'HIGHLIGHT'=>$highlight,
				'TITLE'=>$post['title'],
				'TIME_RAW'=>strval($datetime_raw),
				'TIME'=>$datetime,
				'POSTER_ID'=>strval($post['user']),
				'POSTER_URL'=>$poster_url,
				'POSTER_NAME'=>$poster_name,
				'POSTER'=>$poster,
				'POSTER_DETAILS'=>$poster_details,
				'ID'=>strval($post['id']),
				'POST'=>$post['message'],
				'POST_COMCODE'=>isset($post['message_comcode'])?$post['message_comcode']:NULL,
				'CHILDREN'=>$children,
				'OTHER_IDS'=>(count($other_ids)==0)?NULL:$other_ids,
				'RATING'=>$rating,
				'EMPHASIS'=>$emphasis,
				'BUTTONS'=>$buttons,
				'LAST_EDITED_RAW'=>$last_edited_raw,
				'LAST_EDITED'=>$last_edited,
				'TOPIC_ID'=>is_null($this->topic_id)?'':strval($this->topic_id),
				'UNVALIDATED'=>$unvalidated,
				'IS_SPACER_POST'=>$is_spacer_post,
				'NUM_TO_SHOW_LIMIT'=>strval($num_to_show_limit),
			))));
		}

		return $sequence;
	}

	/**
	 * Put comments RSS link into environment.
	 *
	 * @param  ID_TEXT		The forum we are working in
	 * @param  ID_TEXT		The content type the comments are for
	 * @param  ID_TEXT		The content ID the comments are for
	 */
	function inject_rss_url($forum,$type,$id)
	{
		$GLOBALS['FEED_URL_2']=find_script('backend').'?mode=comments&forum='.urlencode($forum).'&filter='.urlencode($type.'_'.$id);
	}

	/**
	 * Put posts count into environment.
	 */
	function inject_meta_data()
	{
		$GLOBALS['META_DATA']+=array(
			'numcomments'=>strval(count($this->all_posts_ordered)),
		);
	}

	/**
	 * Get a form for posting.
	 *
	 * @param  ID_TEXT		The content type of what this posting will be for
	 * @param  ID_TEXT		The content ID of what this posting will be for
	 * @param  boolean		Whether to accept reviews
	 * @param  tempcode		URL where form submit will go
	 * @param  ?string		The default post to use (NULL: standard courtesy warning)
	 * @return tempcode		Posting form
	 */
	function get_posting_form($type,$id,$allow_reviews,$post_url,$post_warning)
	{
		require_lang('comcode');

		require_javascript('javascript_editing');
		require_javascript('javascript_validation');
		require_javascript('javascript_swfupload');
		require_css('swfupload');

		$em=$GLOBALS['FORUM_DRIVER']->get_emoticon_chooser();

		$comment_text=get_option('comment_text');

		if (is_null($post_warning)) $post_warning=do_lang('POST_WARNING');

		if (addon_installed('captcha'))
		{
			require_code('captcha');
			$use_captcha=use_captcha();
			if ($use_captcha)
			{
				generate_captcha();
			}
		} else $use_captcha=false;

		$title=do_lang_tempcode($allow_reviews?'POST_REVIEW':'MAKE_COMMENT');

		$join_bits=new ocp_tempcode();
		if (is_guest())
		{
			$redirect=get_self_url(true,true);
			$login_url=build_url(array('page'=>'login','type'=>'misc','redirect'=>$redirect),get_module_zone('login'));
			$join_url=$GLOBALS['FORUM_DRIVER']->join_url();
			$join_bits=do_template('JOIN_OR_LOGIN',array('_GUID'=>'2d26dba6fa5e6b665fbbe3f436289f7b','LOGIN_URL'=>$login_url,'JOIN_URL'=>$join_url));
		}

		$reviews_rating_criteria=array();
		foreach ($this->reviews_rating_criteria as $review_title)
		{
			$reviews_rating_criteria[]=array(
				'REVIEW_TITLE'=>$review_title,
			);
		}

		if ($this->is_threaded)
			$post_warning=do_lang('THREADED_REPLY_NOTICE',$post_warning);

		return do_template('COMMENTS_POSTING_FORM',array(
			'_GUID'=>'c87025f81ee64c885f0ac545efa5f16c',
			'EXPAND_TYPE'=>'contract',
			'FIRST_POST_URL'=>'',
			'FIRST_POST'=>'',
			'JOIN_BITS'=>$join_bits,
			'REVIEWS'=>$allow_reviews,
			'TYPE'=>$type,
			'ID'=>$id,
			'REVIEW_RATING_CRITERIA'=>$reviews_rating_criteria,
			'USE_CAPTCHA'=>$use_captcha,
			'GET_EMAIL'=>false,
			'EMAIL_OPTIONAL'=>true,
			'GET_TITLE'=>true,
			'POST_WARNING'=>$post_warning,
			'COMMENT_TEXT'=>$comment_text,
			'EM'=>$em,
			'DISPLAY'=>'block',
			'COMMENT_URL'=>$post_url,
			'TITLE'=>$title,
		));
	}
}

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

class video_syndication_vimeo
{
	var $_vimeo_ob;
	var $_request_token=NULL;

	function __construct()
	{
		require_code('vimeo');

		// Ensure config options installed
		$service_name='vimeo';
		$client_id=get_option($service_name.'_client_id',true);
		if (is_null($client_id))
		{
			require_code('oauth2');
			install_oauth_settings_for($service_name);
		}

		// Initialise official client
		$this->_vimeo_ob=new phpVimeo(
			get_option($service_name.'_client_id'),
			get_option($service_name.'_client_secret'),
			get_long_value($service_name.'_access_token'),
			get_long_value($service_name.'_access_token_secret')
		);
	}

	function get_service_title()
	{
		return 'Vimeo';
	}

	function recognises_as_remote($url)
	{
		return (preg_match('#^http://vimeo\.com/(\d+)#',$url)!=0);
	}

	function is_active()
	{
		$vimeo_client_id=get_option('vimeo_client_id',true);
		if (is_null($vimeo_client_id))
		{
			return false;
		}

		return ($vimeo_client_id!='');
	}

	function get_remote_videos($local_id=NULL)
	{
		$videos=array();

		$transcoding_id=mixed();
		if (!is_null($local_id))
		{
			// This code is a bit annoying. Ideally we'd do a remote tag search (vimeo.videos.search), but Vimeo's API seems to be buggy/lagged here. We'll therefore look at our local mappings.
			$transcoding_id=$GLOBALS['SITE_DB']->query_value_if_there('SELECT t_id FROM '.get_table_prefix().'video_transcoding WHERE t_local_id='.strval($local_id).' AND t_id LIKE \''.db_encode_like('vimeo\_%').'\'');
			if (is_null($transcoding_id)) return array(); // Not uploaded yet
		}

		$page=1;
		do
		{
			$query_params=array();

			if (!is_null($local_id))
			{
				$query_params['video_id']=preg_replace('#^vimeo_#','',$transcoding_id);
				$api_method='vimeo.videos.getInfo';

				$result=$this->_vimeo_ob->call($api_method,$query_params);
				if ($result===false) break;

				$result=array('video'=>array($result));
			} else
			{
				$query_params['per_page']=strval(50);
				$query_params['page']=strval($page);
				$query_params['full_response']=true;
				$query_params['user_id']=$this->_request_token;
				$api_method='vimeo.videos.getUploaded';

				$result=$this->_vimeo_ob->call($api_method,$query_params);
				if ($result===false) return $videos;
			}

			if (!isset($result['video'])) break;

			foreach ($result['video'] as $p)
			{
				$detected_video=$this->_process_remote_video($p);
				if (!is_null($detected_video))
				{
					$remote_id=$detected_video['remote_id'];
					if ((!array_key_exists($remote_id,$videos)) || (!$videos[$remote_id]['validated'])) // If new match, or last match was unvalidated (i.e. old version)
					{
						$videos[$remote_id]=$detected_video;
					}
				}
			}

			$page++;
		}
		while ((!is_null($local_id)) && (count($result['entry'])>0));

		return $videos;
	}

	function _process_remote_video($p)
	{
		$detected_video=mixed();

		$remote_id=$p->id;

		$add_date=strtotime($p->upload_date);
		$edit_date=isset($p->modified_date)?strtotime($p->modified_date):$add_date;

		$allow_rating=NULL; // Specification of this not supported by Vimeo
		$allow_comments=NULL; // Specification of this not supported by Vimeo in API
		$validated=($p->privacy!='nobody');

		$got_best_video_type=false;
		foreach ($p->urls->url as $_url)
		{
			if (($_url['type']=='video') && (!$got_best_video_type))
			{
				$url=$_url['_content']; // Non-ideal, as is a link to vimeo.com
			}
			if ($_url['type']=='sd') // Ideal because it lets us use jwplayer
			{ // But hmm, vimeo has not implemented yet https://vimeo.com/forums/api/topic:41030 !
				$url=$_url['_content'];
				$got_best_video_type=true;
			}
		}

		$category=NULL;
		$keywords=array();
		$bound_to_local_id=mixed();
		if (isset($p->tags))
		{
			foreach ($p->tags->tag as $tag)
			{
				$matches=array();
				if (preg_match('#^sync(\d+)$#',$tag['_content'],$matches)!=0)
				{
					$bound_to_local_id=intval($matches[1]);
				} else
				{
					$keywords[]=$tag['_content'];
				}
			}
		}

		if (!is_null($bound_to_local_id))
		{
			$detected_video=array(
				'bound_to_local_id'=>$bound_to_local_id,
				'remote_id'=>$remote_id,

				'title'=>$p->title,
				'description'=>$p->description,
				'mtime'=>$edit_date,
				'tags'=>$keywords,
				'url'=>$url,
				'allow_rating'=>$allow_rating,
				'allow_comments'=>$allow_comments,
				'validated'=>$validated,
			);
		} // else we ignore remote videos that aren't bound to local ones

		return $detected_video;
	}

	function upload_video($video)
	{
		if (function_exists('set_time_limit')) @set_time_limit(10000);
		list($file_path,$is_temp_file)=$this->_url_to_file_path($video['url']);
		try
		{
			$remote_id=$this->_vimeo_ob->upload($file_path,true,2097152);

			if ($is_temp_file) @unlink($file_path);
			if ($remote_id===false) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

			$this->_vimeo_ob->call('vimeo.videos.setDownloadPrivacy',array($remote_id,false)); // If we want to allow downloading, we'll handle that locally. Most users won't want downloading.

			// Now do settings, which is like doing an immediate edit...

			// We change $video (which is a local video array) to be like a remote video array. This is because change_remote_video expects that.
			$video['remote_id']=$remote_id;
			$video['bound_to_local_id']=$video['local_id'];
			unset($video['local_id']);
			$video['url']=NULL;
			// We pass whole $video as $changes; unchangable/irrelevant keys will be ignored, due to how change_remote_video is coded.
			$changes=$video;
			unset($changes['url']); // this is correct already of course
			$this->change_remote_video($video,$changes);
		}
		catch (VimeoAPIException $e)
		{
			require_lang('gallery_syndication_vimeo');
			attach_message(do_lang_tempcode('VIMEO_ERROR',escape_html(strval($e->getCode())),$e->getMessage(),escape_html(get_site_name())),'warn');
			return NULL;
		}

		// Find live details
		$query_params=array();
		$query_params['video_id']=$remote_id;
		$api_method='vimeo.videos.getInfo';
		$result=$this->_vimeo_ob->call($api_method,$query_params);
		if ($result===false) return NULL;
		$video=$this->_process_remote_video($result);

		return $video;
	}

	function _url_to_file_path($url)
	{
		$is_temp_file=false;

		if (substr($url,0,strlen(get_custom_base_url()))!=get_custom_base_url())
		{
			$temppath=ocp_tempnam('vimeo_temp_dload');
			$tempfile=fopen($temppath,'wb');
			http_download_file($url,1024*1024*1024*5,true,false,'ocPortal',NULL,NULL,NULL,NULL,NULL,$tempfile);

			$is_temp_file=true;

			$video_path=$temppath;
		} else
		{
			$video_path=preg_replace('#^'.preg_quote(get_custom_base_url().'/').'#',get_custom_file_base().'/',$url);
		}

		return array($video_path,$is_temp_file);
	}

	function change_remote_video($video,$changes)
	{
		foreach (array_keys($changes) as $key)
		{
			switch ($key)
			{
				case 'title':
					$this->_vimeo_ob->call('vimeo.videos.setTitle',array($video['remote_id'],$changes['title']));
					break;

				case 'description':
					$this->_vimeo_ob->call('vimeo.videos.setDescription',array($video['remote_id'],$changes['description']));
					break;

				case 'tags':
					$this->_vimeo_ob->call('vimeo.videos.clearTags',array($video['remote_id']));
					$this->_vimeo_ob->call('vimeo.videos.addTags',array($video['remote_id'],'sync'.strval($video['bound_to_local_id']).','.implode(',',$changes['tags'])));
					break;

				case 'validated':
					$this->_vimeo_ob->call('vimeo.videos.setPrivacy',array($video['remote_id'],$changes['validated']?'anybody':'nobody'));
					break;

				case 'url':
					if (function_exists('set_time_limit')) @set_time_limit(10000);
					list($file_path,$is_temp_file)=$this->_url_to_file_path($video['url']);
					$remote_id=$this->_vimeo_ob->upload($this->_url_to_file_path($file_path),true,2097152,$video['remote_id']);
					if ($is_temp_file) @unlink($file_path);
					if ($remote_id===false) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
					break;
			}
		}
	}

	function unbind_remote_video($video)
	{
		$this->_vimeo_ob->call('vimeo.videos.clearTags',array($video['remote_id']));
		$this->_vimeo_ob->call('vimeo.videos.addTags',array($video['remote_id'],implode(',',$video['tags'])));
	}

	function delete_remote_video($video)
	{
		$this->_vimeo_ob->call('vimeo.videos.delete',array($video['remote_id']));
	}

	function leave_comment($video,$comment)
	{
		$this->_vimeo_ob->call('vimeo.videos.comments.addComment',array('video_id'=>$video['remote_id'],'comment_text'=>$comment));
	}
}

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
 * @package		galleries
 */

class Hook_video_embed_facebook
{

	/**
	 * If we can handle this URL, get the render template and ID for it.
	 *
	 * @param  URLPATH		Video URL
	 * @return ?array			A pair: the template, and ID (NULL: no match)
	 */
	function get_template_name_and_id($url)
	{
		$matches=array();
		if (preg_match('#^http://www\.facebook\.com/video/video\.php\?v=(\w+)#',$url,$matches)!=0)
		{
			$id=rawurldecode($matches[1]);
			return array('GALLERY_VIDEO_FACEBOOK',$id);
		}
		return NULL;
	}

	/**
	 * If we can handle this URL, get the thumbnail URL.
	 *
	 * @param  URLPATH		Video URL
	 * @return ?string		The thumbnail URL (NULL: no match).
	 */
	function get_video_thumbnail($src_url)
	{
		$matches=array();
		if (preg_match('#^http://www\.facebook\.com/video/video\.php\?v=(\w+)#',$src_url,$matches)!=0)
		{
			require_code('files');
			$contents=http_download_file($src_url);
			if (preg_match('#addVariable\("thumb_url", "([^"]*)"\);#',$contents,$matches)!=0)
			{
				return rawurldecode(str_replace('\u0025','%',$matches[1]));
			}
		}
		return NULL;
	}

	/**
	 * Add a custom comcode field for this URL type.
	 */
	function add_custom_comcode_field()
	{
		$map=array(
			'tag_tag'=>'facebook_video',
			'tag_replace'=>'{$SET,VIDEO,{$PREG_REPLACE,(http://.*\?v=)?(\w+)(.*)?,$\{2\},{content}}}<object width="640" height="385"><param name="movie" value="http://www.facebook.com/v/{$GET*,VIDEO};hl=en_US"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.facebook.com/v/{$GET*,VIDEO};hl=en_US" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="640" height="385"></embed></object>',
			'tag_example'=>'[facebook_video]http://www.facebook.com/video/video.php?v=10150307159560581[/facebook_video]',
			'tag_parameters'=>'',
			'tag_enabled'=>1,
			'tag_dangerous_tag'=>0,
			'tag_block_tag'=>1,
			'tag_textual_tag'=>0
		);
		$map+=lang_code_to_default_content('tag_title','custom_comcode:FACEBOOK_TAG_TITLE');
		$map+=lang_code_to_default_content('tag_description','custom_comcode:FACEBOOK_TAG_DESCRIPTION');
		$GLOBALS['SITE_DB']->query_insert('custom_comcode',$map);
	}

}



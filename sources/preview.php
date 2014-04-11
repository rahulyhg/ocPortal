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
 * @package		core_form_interfaces
 */

/**
 * Build up a preview based on what was submitted.
 *
 * @param  boolean	Whether to return additional data
 * @return mixed		Either tempcode for the preview, or a tuple of details
 */
function build_preview($multi_return=false)
{
	// Check CAPTCHA if it is passed
	if (addon_installed('captcha'))
	{
		if (((array_key_exists('post',$_POST)) && ($_POST['post']!='')) && (array_key_exists('security_image',$_POST)))
		{
			require_code('captcha');
			enforce_captcha(false);
		}
	}

	require_code('attachments2');
	$hooks=find_all_hooks('systems','preview');
	$output=NULL;
	$new_post_value=NULL;
	$attachment_type=NULL;
	$forum_db=false;
	$limit_to=NULL;
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/preview/'.$hook);
		$object=object_factory('Hook_Preview_'.$hook,true);
		if (is_null($object)) continue;
		$apply_bits=$object->applies();
		$applies=$apply_bits[0];
		if ($applies)
		{
			$attachment_type=$apply_bits[1];
			$forum_db=array_key_exists(2,$apply_bits)?$apply_bits[2]:false;
			$limit_to=array_key_exists(3,$apply_bits)?$apply_bits[3]:NULL;

			if (method_exists($object,'run')) list($output,$new_post_value)=$object->run();

			break;
		}
	}
	$validation=new ocp_tempcode();
	$keyword_density=new ocp_tempcode();
	$spelling=new ocp_tempcode();
	$meta_keywords=post_param('meta_keywords','');
	$spellcheck=post_param_integer('perform_spellcheck',0)==1;
	$keywordcheck=(post_param_integer('perform_keywordcheck',0)==1) && ($meta_keywords!='');
	if (post_param_integer('perform_validation',0)!=0)
	{
		foreach ($_POST as $key=>$val)
		{
			if (!is_string($val)) continue;

			$val=post_param($key,''); // stripslashes, and wysiwyg output handling

			$tempcodecss=(post_param_integer('tempcodecss__'.$key,0)==1);
			$supports_comcode=(post_param_integer('comcode__'.$key,0)==1);

			if ($supports_comcode)
			{
				$temp=$_FILES;
				$_FILES=array();
				$valt=comcode_to_tempcode($val);
				$_FILES=$temp;

				require_code('view_modes');
				require_code('obfuscate');
				require_code('validation');
				$validation->attach(do_xhtml_validation($valt->evaluate(),false,post_param_integer('perform_validation',0),true));
			} elseif ($tempcodecss)
			{
				$i=0;
				$color=post_param(strval($i),'');
				while ($color!='')
				{
					$val=str_replace('<color-'.strval($i).'>','#'.$color,$val);
					$i++;

					$color=post_param(strval($i),'');
				}
				$_val_orig=$val;

				require_lang('validation');
				require_css('adminzone');
				require_code('view_modes');
				require_code('obfuscate');
				require_code('validation');
				require_code('validation2');
				$error=check_css($_val_orig);
				$show=(count($error['errors'])!=0);
				if ($show)
					$validation->attach(display_validation_results($_val_orig,$error,true,true));
			}
		}
	}
	if ($spellcheck)
	{
		if (addon_installed('wordfilter'))
		{
			$words_skip=collapse_1d_complexity('w_replacement',$GLOBALS['SITE_DB']->query_select('wordfilter',array('w_replacement')));
		} else
		{
			$words_skip=array();
		}
		require_once(get_file_base().'/data/areaedit/plugins/SpellChecker/spell-check-logic.php');
	}
	$db=$forum_db?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];
	$view_space_map=array();
	require_code('templates_view_space');
	foreach ($_POST as $key=>$val)
	{
		if (!is_string($val)) continue;

		if ((!is_null($limit_to)) && (!in_array($key,$limit_to))) continue;

		$val=post_param($key,''); // stripslashes, and wysiwyg output handling
		if ($val=='0') $val=do_lang('NO');
		if ($val=='1') $val=do_lang('YES');

		if ((substr($key,0,14)=='review_rating') || (substr($key,0,7)=='rating')) $val.='/10';

		$is_hidden=in_array($key,array('from_url','password','confirm_password','edit_password','MAX_FILE_SIZE','perform_validation','_validated','id','posting_ref_id','f_face','f_colour','f_size','http_referer')) || (strpos($key,'hour')!==false) || (strpos($key,'access_')!==false) || (strpos($key,'minute')!==false) || (strpos($key,'confirm')!==false) || (strpos($key,'pre_f_')!==false) || (strpos($key,'label_for__')!==false) || (strpos($key,'wysiwyg_version_of_')!==false) || (strpos($key,'is_wysiwyg')!==false) || (strpos($key,'require__')!==false) || (strpos($key,'tempcodecss__')!==false) || (strpos($key,'comcode__')!==false) || (strpos($key,'_parsed')!==false) || (preg_match('#^caption\d+$#',$key)!=0) || (preg_match('#^attachmenttype\d+$#',$key)!=0) || (substr($key,0,1)=='_') || (substr($key,0,9)=='hidFileID') || (substr($key,0,11)=='hidFileName');
		if (substr($key,0,14)=='tick_on_form__')
		{
			if (post_param_integer(substr($key,14),0)==1) $is_hidden=true; else $key=substr($key,14);
		}

		if (substr($key,-4)=='_day')
		{
			$key=substr($key,0,strlen($key)-4);
			$timestamp=get_input_date($key);
			if (is_null($timestamp))
			{
				$is_hidden=true;
			} else
			{
				$val=get_timezoned_date($timestamp,false,true,false,true);
			}
		}
		elseif ((substr($key,-6)=='_month') || (substr($key,-5)=='_year')) $is_hidden=true;

		$key_nice=post_param('label_for__'.$key,ucwords(str_replace('_',' ',$key)));
		if ($key_nice=='') $is_hidden=true;

		if (!$is_hidden)
		{
			if ($spellcheck)
			{
				require_code('comcode_from_html');
				$mispellings=spellchecklogic('check',strip_comcode(semihtml_to_comcode($val,true)),$words_skip,true);
				$_misspellings=array();
				foreach ($mispellings as $misspelling)
				{
					list($word_bad,$words_good)=$misspelling;
					$_misspellings[]=array('WORD'=>$word_bad,'CORRECTIONS'=>implode(', ',$words_good));
				}
				if (count($_misspellings)!=0)
					$spelling->attach(do_template('PREVIEW_SCRIPT_SPELLING',array('_GUID'=>'9649572982c01995a8f47c58d16fda39','FIELD'=>$key_nice,'MISSPELLINGS'=>$_misspellings)));
			}
			if (($keywordcheck) && ((strpos($val,' ')!==false) || ($key=='title')))
			{
				$keyword_explode=explode(',',$meta_keywords);
				$keywords=array();
				$word_count=str_word_count($val);
				if ($word_count!=0)
				{
					foreach ($keyword_explode as $meta_keyword)
					{
						$meta_keyword=trim($meta_keyword);
						if ($meta_keyword!='')
						{
							$density=substr_count($val,$meta_keyword)/$word_count;
							$ideal_density=1.0/(9.0*count($keyword_explode)); // Pretty rough -- common sense is needed
							$keywords[]=array('sort'=>$ideal_density,'KEYWORD'=>$meta_keyword,'IDEAL_DENSITY'=>strval(intval(round($ideal_density*100))),'DENSITY'=>strval(intval(round($density*100))));
						}
					}
					global $M_SORT_KEY;
					$M_SORT_KEY='sort';
					usort($keywords,'multi_sort');
					foreach ($keywords as $ti=>$meta_keyword)
					{
						unset($keywords[$ti]['sort']);
					}
					if (count($keywords)!=0)
						$keyword_density->attach(do_template('PREVIEW_SCRIPT_KEYWORD_DENSITY',array('_GUID'=>'4fa05e9f52023958a3594d1610b00747','FIELD'=>$key_nice,'KEYWORDS'=>$keywords)));
				}
			}
		}

		if (is_null($output))
		{
			if ((is_null($attachment_type)) || ($key!='post')) // Not an attachment-supporting field
			{
				$tempcodecss=(post_param_integer('tempcodecss__'.$key,0)==1);
				$supports_comcode=(post_param_integer('comcode__'.$key,0)==1);
				$preformatted=(post_param_integer('pre_f_'.$key,0)==1);

				if ($is_hidden) continue;

				if ($preformatted)
				{
					$valt=with_whitespace($val);
				} elseif ($supports_comcode)
				{
					$valt=comcode_to_tempcode($val);
				} elseif ($tempcodecss)
				{
					$i=0;
					$color=post_param(strval($i),'');
					while ($color!='')
					{
						$val=str_replace('<color-'.strval($i).'>','#'.$color,$val);
						$i++;

						$color=post_param(strval($i),'');
					}
					$_val_orig=$val;
					$valt=comcode_to_tempcode("[code=\"CSS\"]".$val."[/code]");
				} else
				{
					$valt=make_string_tempcode(escape_html($val));
				}

				$view_space_map[$key_nice]=$valt;
			} else // An attachment-supporting field
			{
				$tempcodecss=false;
				$posting_ref_id=post_param_integer('posting_ref_id');
				if ($posting_ref_id<0) fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
				$post_bits=do_comcode_attachments($val,$attachment_type,strval(-$posting_ref_id),true,$db);
				$new_post_value=$post_bits['comcode'];

				$view_space_map[$key_nice]=$post_bits['tempcode'];

				$val=$post_bits['tempcode'];
				$supports_comcode=true;
			}
		}
	}

	// Make attachments temporarily readable without any permission context
	global $COMCODE_ATTACHMENTS;
	$posting_ref_id=post_param_integer('posting_ref_id',NULL);
	if (!is_null($posting_ref_id))
	{
		if (array_key_exists(strval(-$posting_ref_id),$COMCODE_ATTACHMENTS))
		{
			foreach ($COMCODE_ATTACHMENTS[strval(-$posting_ref_id)] as $attachment)
			{
				$db->query_delete('attachment_refs',array('r_referer_type'=>'null','r_referer_id'=>strval(-$posting_ref_id),'a_id'=>$attachment['id']),'',1);
				$db->query_insert('attachment_refs',array('r_referer_type'=>'null','r_referer_id'=>strval(-$posting_ref_id),'a_id'=>$attachment['id']));
			}
		}
	}

	if (is_null($output))
	{
		if (count($view_space_map)==1)
		{
			$output=array_pop($view_space_map);
		} else
		{
			$view_space_fields=new ocp_tempcode();
			foreach ($view_space_map as $key=>$val)
			{
				$view_space_fields->attach(view_space_field($key,$val,true));
			}
			$output=do_template('VIEW_SPACE',array('_GUID'=>'3f548883b9eb37054c500d1088d9efa3','WIDTH'=>'170','FIELDS'=>$view_space_fields));
		}
	}

	// This is to get the Comcode attachments updated to the new IDs
	if (!is_null($new_post_value))
	{
		$new_post_value_html=comcode_to_tempcode($new_post_value,NULL,false,60,NULL,$db,true);
		if (strpos($new_post_value_html->evaluate(),'<!-- CC-error -->')===false)
			$output->attach(do_template('PREVIEW_SCRIPT_CODE',array('_GUID'=>'bc7432af91e1eaf212dc210f3bf2f756','NEW_POST_VALUE_HTML'=>$new_post_value_html,'NEW_POST_VALUE'=>$new_post_value)));
	}

	$output->handle_symbol_preprocessing();

	if ($multi_return) return array($output,$validation,$keyword_density,$spelling);
	return $output;
}



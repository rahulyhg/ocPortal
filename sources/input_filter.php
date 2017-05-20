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
 * Standard code module initialisation function.
 */
function init__input_filter()
{
	global $FORCE_INPUT_FILTER_FOR_ALL;
	$FORCE_INPUT_FILTER_FOR_ALL=false;
}

/**
 * Check an input field isn't 'evil'.
 *
 * @param  string			The name of the parameter
 * @param  string			The value retrieved
 * @param  ?boolean		Whether the parameter is a POST parameter (NULL: undetermined)
 */
function check_input_field_string($name,&$val,$posted=false)
{
	if ((preg_match('#^\s*((((j\s*a\s*v\s*a\s*)|(v\s*b\s*))?s\s*c\s*r\s*i\s*p\s*t)|(d\s*a\s*t\s*a\s*))\s*:#i',$val)!=0) && ($name!='value')/*Don't want autosave triggering this*/)
	{
		log_hack_attack_and_exit('SCRIPT_URL_HACK_2',$val);
	}

	// Security check for known URL fields. Check for specific things, plus we know we can be pickier in general
	$is_url=($name=='from') || ($name=='preview_url') || ($name=='redirect') || ($name=='redirect_passon') || ($name=='url');
	if ($is_url)
	{
		if ($is_url)
		{
			if (preg_match('#\n|\000|<|(".*[=<>])|^\s*((((j\s*a\s*v\s*a\s*)|(v\s*b\s*))?s\s*c\s*r\s*i\s*p\s*t)|(d\s*a\s*t\s*a\s*))\s*:#mi',$val)!=0)
			{
				if ($name=='page') $_GET[$name]=''; // Stop loops
				log_hack_attack_and_exit('DODGY_GET_HACK',$name,$val);
			}

			// Don't allow external redirections
			if (!$posted)
			{
				if (looks_like_url($val))
				{
					$bus=array(
						get_base_url(false),
						get_base_url(true),
						get_forum_base_url(),
						'http://ocportal.com/',
						'https://ocportal.com/',
					);
					$ok=false;
					foreach ($bus as $bu)
					{
 						if (substr($val,0,strlen($bu))==$bu)
						{
							$ok=true;
							break;
						}
					}
					if (!$ok)
					{
						$val=get_base_url(false);
					}
				}
			}
		}
	}

	if ($GLOBALS['BOOTSTRAPPING']==0)
	{
		// Quickly depose of common spam attacks. Not really security, just a sensible barrier
		if (((!function_exists('is_guest')) || (is_guest())) && ((strpos($val,'[url=http://')!==false) || (strpos($val,'[link')!==false)) && (strpos($val,'<a ')!==false)) // Combination of non-ocPortal-supporting bbcode and HTML, almost certainly a bot trying too hard to get link through
		{
			log_hack_attack_and_exit('LAME_SPAM_HACK',$val);
		}

		// Additional checks for non-privileged users
		if (function_exists('has_specific_permission') && $name!='page'/*Too early in boot if 'page'*/)
		{
			if (false /*v9+*/)
			{
				hard_filter_input_data__html($val);
				hard_filter_input_data__filesystem($val);
			}
		}
	}
}

/**
 * Check a posted field isn't part of a malicious CSRF attack via referer checking (we do more checks for post fields than get fields).
 *
 * @param  string			The name of the parameter
 * @param  string			The value retrieved
 * @return string			The filtered value
 */
function check_posted_field($name,&$val)
{
	if (strtolower(ocp_srv('REQUEST_METHOD'))=='post')
	{
		$evil=false;

		$referer=ocp_srv('HTTP_REFERER');

		$is_true_referer=(substr($referer,0,7)=='http://') || (substr($referer,0,8)=='https://');

		if ((strtolower(ocp_srv('REQUEST_METHOD'))=='post') && (!is_guest()))
		{
			if ($is_true_referer)
			{
				$canonical_referer_domain=strip_url_to_representative_domain($referer);
				$canonical_baseurl_domain=strip_url_to_representative_domain(get_base_url());
				if ($canonical_referer_domain!=$canonical_baseurl_domain)
				{
					if (!in_array($name,array('login_username','password','remember','login_invisible')))
					{
						$allowed_partners=explode(chr(10),get_option('allowed_post_submitters'));
						$allowed_partners[]='paypal.com';
						$found=false;
						foreach ($allowed_partners as $partner)
						{
							$partner=trim($partner);

							if (($partner!='') && (($canonical_referer_domain=='www.'.$partner) || ($canonical_referer_domain==$partner)))
							{
								$found=true;
								break;
							}
						}
						if (!$found)
						{
							$evil=true;
						}
					}
				}
			}
		}

		if ($evil)
		{
			$_POST=array(); // To stop loops
			log_hack_attack_and_exit('EVIL_POSTED_FORM_HACK',$referer);
		}
	}

	// Custom fields.xml filter system
	$val=filter_form_field_default($name,$val);
}

/**
 * Convert a full URL to a domain name we will consider this a trust on.
 *
 * @param  URLPATH		The URL
 * @return string			The domain
 */
function strip_url_to_representative_domain($url)
{
	return preg_replace('#^www\.#','',strtolower(parse_url($url,PHP_URL_HOST)));
}

/**
 * Filter input data for safety within potential filesystem calls.
 * Only called for non-privileged users, filters/alters rather than blocks, due to false-positive likelihood.
 *
 * @param  string			The data
 */
function hard_filter_input_data__filesystem(&$val)
{
	static $nastiest_path_signals=array(
		'(^|[/\\\\])info\.php($|\0)', // LEGACY
		'(^|[/\\\\])_config\.php($|\0)',
		'\.\.[/\\\\]',
		'(^|[/\\\\])data_custom[/\\\\].*log.*',
	);
	$matches=array();
	foreach ($nastiest_path_signals as $signal)
	{
		if (preg_match('#'.$signal.'#',$val,$matches)!=0)
		{
			$val=str_replace($matches[0],str_replace('.','&#46;',$matches[0]),$val); // Break the paths
		}
	}
}

/**
 * Filter input data for safety within frontend markup, taking account of HTML/JavaScript/CSS/embed attacks.
 * Only called for non-privileged users, filters/alters rather than blocks, due to false-positive likelihood.
 *
 * @param  string			The data
 */
function hard_filter_input_data__html(&$val)
{
	require_code('comcode');

	global $POTENTIAL_JS_NAUGHTY_ARRAY;

	// Null vector
	$val=str_replace(chr(0),'',$val);

	// Comment vector
	$old_val='';
	do
	{
		$old_val=$val;
		$val=preg_replace('#/\*.*\*/#Us','',$val);
	}
	while ($old_val!=$val);

	// Entity vector
	$matches=array();
	do
	{
		$old_val=$val;
		$count=preg_match_all('#&\#(\d+)#i',$val,$matches); // No one would use this for an html tag unless it was malicious. The ASCII could have been put in directly.
		for ($i=0;$i<$count;$i++)
		{
			$matched_entity=intval($matches[1][$i]);
			if (($matched_entity<127) && (array_key_exists(chr($matched_entity),$POTENTIAL_JS_NAUGHTY_ARRAY)))
			{
				if ($matched_entity==0) $matched_entity=ord(' ');
				$val=str_replace($matches[0][$i].';',chr($matched_entity),$val);
				$val=str_replace($matches[0][$i],chr($matched_entity),$val);
			}
		}
		$count=preg_match_all('#&\#x([\da-f]+)#i',$val,$matches); // No one would use this for an html tag unless it was malicious. The ASCII could have been put in directly.
		for ($i=0;$i<$count;$i++)
		{
			$matched_entity=intval(base_convert($matches[1][$i],16,10));
			if (($matched_entity<127) && (array_key_exists(chr($matched_entity),$POTENTIAL_JS_NAUGHTY_ARRAY)))
			{
				if ($matched_entity==0) $matched_entity=ord(' ');
				$val=str_replace($matches[0][$i].';',chr($matched_entity),$val);
				$val=str_replace($matches[0][$i],chr($matched_entity),$val);
			}
		}
	}
	while ($old_val!=$val);

	// Tag vectors
	$bad_tags='noscript|script|link|style|meta|iframe|frame|object|embed|applet|html|xml|body|head|form|base|layer|v:vmlframe|svg';
	$val=preg_replace('#\<('.$bad_tags.')#i','<span',$val); // Intentionally does not strip so as to avoid attacks like <<scriptscript --> <script
	$val=preg_replace('#\</('.$bad_tags.')#i','</span',$val);

	// CSS attack vectors
	$val=preg_replace('#\\\\(\d+)#i','${1}',$val); // CSS escaping
	$val=preg_replace('#e\s*(x\s*p\s*r\s*e\s*s\s*s\s*i\s*o\s*n\()#i','&eacute;${1}',$val); // expression(
	$val=preg_replace('#b\s*(e\s*h\s*a\s*v\s*i\s*o\s*r\s*\()#i','&szlig;${1}',$val); // behavior(
	$val=preg_replace('#b\s*(i\s*n\s*d\s*i\s*n\s*g\s*\()#i','&szlig;${1}',$val); // bindings(

	// Script-URL vectors (protocol handlers)
	$val=preg_replace('#((j[\\\\\s]*a[\\\\\s]*v[\\\\\s]*a[\\\\\s]*|v[\\\\\s]*b[\\\\\s]*)s[\\\\\s]*c[\\\\\s]*r[\\\\\s]*i[\\\\\s]*p[\\\\\s]*t[\\\\\s]*):#i','${1};',$val);

	// Behavior protocol handler
	$val=preg_replace('#(b[\\\\\s]*e[\\\\\s]*h[\\\\\s]*a[\\\\\s]*v[\\\\\s]*i[\\\\\s]*o[\\\\\s]*r[\\\\\s]*):#i','${1};',$val);

	// Event vectors (anything that *might* have got into a tag context, or out of an attribute context, that looks like it could potentially be a JS attribute -- intentionally broad as invalid-but-working HTML can trick regexps)
	do
	{
		$before=$val;
		$val=preg_replace('#([<"\'].*\s)o([nN])(.*=)#s','${1}&#111;${2}${3}',$val);
		$val=preg_replace('#([<"\'].*\s)O([nN])(.*=)#s','${1}&#79;${2}${3}',$val);
	}
	while ($before!=$val);

	// Check tag balancing (we don't want to allow partial tags to compound together against separately checked chunks)
	$len=strlen($val);
	$depth=0;
	for ($i=0;$i<$len;$i++)
	{
		$at=$val[$i];
		if ($at=='<')
		{
			$depth++;
		} elseif ($at=='>')
		{
			$depth--;
		}
		if ($depth<0) break;
	}
	if ($depth>=1)
	{
		$val.='">'; // Ugly way to make sure all is closed off
	}
}

/**
 * Filter to alter form field values based on fields.xml. Usually a no-op.
 *
 * @param  string			The name of the parameter
 * @param  ?string		The current value of the parameter (NULL: none)
 * @return string			The filtered value of the parameter
 */
function filter_form_field_default($name,$val)
{
	$restrictions=load_field_restrictions();

	foreach ($restrictions as $_r=>$_restrictions)
	{
		$_r_exp=explode(',',$_r);
		foreach ($_r_exp as $__r)
		{
			if ((trim($__r)=='') || (simulated_wildcard_match($name,trim($__r),true)))
			{
				foreach ($_restrictions as $bits)
				{
					list($restriction,$attributes)=$bits;

					if ((array_key_exists('error',$attributes)) && (substr($attributes['error'],0,1)=='!'))
					{
						$attributes['error']=do_lang(substr($attributes['error'],1));
					}

					switch (strtolower($restriction))
					{
						case 'minlength':
							if (strlen($val)<intval($attributes['embed']))
								warn_exit(array_key_exists('error',$attributes)?make_string_tempcode($attributes['error']):do_lang_tempcode('FXML_FIELD_TOO_SHORT',escape_html($name),strval(intval($attributes['embed']))));
							break;
						case 'maxlength':
							if (strlen($val)>intval($attributes['embed']))
								warn_exit(array_key_exists('error',$attributes)?make_string_tempcode($attributes['error']):do_lang_tempcode('FXML_FIELD_TOO_LONG',escape_html($name),strval(intval($attributes['embed']))));
							break;
						case 'shun':
							if (simulated_wildcard_match(strtolower($val),strtolower($attributes['embed']),true))
								warn_exit(array_key_exists('error',$attributes)?make_string_tempcode($attributes['error']):do_lang_tempcode('FXML_FIELD_SHUNNED',escape_html($name)));
							break;
						case 'pattern':
							if (preg_match('#'.str_replace('#','\#',$attributes['embed']).'#',$val)==0)
								warn_exit(array_key_exists('error',$attributes)?make_string_tempcode($attributes['error']):do_lang_tempcode('FXML_FIELD_PATTERN_FAIL',escape_html($name),escape_html($attributes['embed'])));
							break;
						case 'possibilityset':
							$values=explode(',',$attributes['embed']);
							$found=false;
							foreach ($values as $value)
							{
								if (($val==trim($value)) || ($val==$value) || (simulated_wildcard_match($val,$value,true))) $found=true;
							}
							$secretive=(array_key_exists('secretive',$attributes) && ($attributes['secretive']=='1'));
							if (!$found)
								warn_exit(array_key_exists('error',$attributes)?make_string_tempcode($attributes['error']):do_lang_tempcode($secretive?'FXML_FIELD_NOT_IN_SET_SECRETIVE':'FXML_FIELD_NOT_IN_SET',escape_html($name),escape_html($attributes['embed'])));
							break;
						case 'disallowedsubstring':
							if (simulated_wildcard_match(strtolower($val),strtolower($attributes['embed'])))
								warn_exit(array_key_exists('error',$attributes)?make_string_tempcode($attributes['error']):do_lang_tempcode('FXML_FIELD_SHUNNED_SUBSTRING',escape_html($name),escape_html($attributes['embed'])));
							break;
						case 'disallowedword':
							if (addon_installed('wordfilter'))
							{
								global $WORDS_TO_FILTER;
								$temp_remember=$WORDS_TO_FILTER;
								$WORDS_TO_FILTER=array($attributes['embed']=>array('word'=>$attributes['embed'],'w_replacement'=>'','w_substr'=>0));
								require_code('word_filter');
								check_word_filter($val,$name,false,true,false);
								$WORDS_TO_FILTER=$temp_remember;
							} else
							{
								if (strpos($val,$attributes['embed'])!==false)
									warn_exit_wordfilter($name,do_lang_tempcode('WORD_FILTER_YOU',escape_html($attributes['embed']))); // In soviet Russia, words filter you
							}
							break;
						case 'replace':
							if (!array_key_exists('from',$attributes))
							{
								$val=$attributes['embed'];
							} else
							{
								$val=str_replace($attributes['from'],$attributes['embed'],$val);
							}
							break;
						case 'removeshout':
							$val=preg_replace_callback('#[^a-z]*[A-Z]{4}[^a-z]*#','deshout_callback',$val);
							break;
						case 'sentencecase':
							if (strlen($val)!=0)
							{
								$val=strtolower($val);
								$val[0]=strtoupper($val); // assumes no leading whitespace
								$val=preg_replace_callback('#[\.\!\?]\s+[a-z]#m','make_sentence_case_callback',$val);
							}
							break;
						case 'titlecase':
							$val=ucwords(strtolower($val));
							break;
						case 'prepend':
							if (substr($val,0,strlen($attributes['embed']))!=$attributes['embed']) $val=$attributes['embed'].$val;
							break;
						case 'append':
							if (substr($val,-strlen($attributes['embed']))!=$attributes['embed']) $val.=$attributes['embed'];
							break;
					}
				}
			}
		}
	}

	return $val;
}

/**
 * preg_replace callback to apply sentence case.
 *
 * @param  array			Matches
 * @return string			De-shouted string
 */
function make_sentence_case_callback($matches)
{
	return strtoupper($matches[0]);
}

/**
 * preg_replace callback to de-shout text.
 *
 * @param  array			Matches
 * @return string			De-shouted string
 */
function deshout_callback($matches)
{
	return ucwords(strtolower($matches[0]));
}

/**
 * Find all restrictions that apply to our page/type.
 *
 * @param  ?string		The page name scoped for (NULL: current page)
 * @param  ?string		The page type scoped for (NULL: current type)
 * @return array			List of fields, each of which is a map (restriction => attributes)
 */
function load_field_restrictions($this_page=NULL,$this_type=NULL)
{
	global $FIELD_RESTRICTIONS;
	if ($FIELD_RESTRICTIONS===NULL)
	{
		$FIELD_RESTRICTIONS=array();
		if (function_exists('xml_parser_create'))
		{
			$temp=new field_restriction_loader();
			if (is_null($this_page)) $this_page=get_page_name();
			if (is_null($this_type)) $this_type=get_param('type',array_key_exists('type',$_POST)?$_POST['type']:'misc');
			$temp->this_page=$this_page;
			$temp->this_type=$this_type;
			$temp->go();
		}
	}

	return $FIELD_RESTRICTIONS;
}

class field_restriction_loader
{
	// Used during parsing
	var $tag_stack,$attribute_stack,$text_so_far;
	var $this_page,$this_type;
	var $levels_from_filtered;
	var $field_qualification_stack;

	/**
	 * Run the loader, to load up field-restrictions from the XML file.
	 */
	function go()
	{
		if (!addon_installed('xml_fields')) return;
		if (!is_file(get_custom_file_base().'/data_custom/fields.xml')) return;

		$this->tag_stack=array();
		$this->attribute_stack=array();
		$this->levels_from_filtered=0;
		$this->field_qualification_stack=array('*');

		// Create and setup our parser
		$xml_parser=@xml_parser_create();
		if ($xml_parser===false)
		{
			return; // PHP5 default build on windows comes with this function disabled, so we need to be able to escape on error
		}
		xml_set_object($xml_parser,$this);
		@xml_parser_set_option($xml_parser,XML_OPTION_TARGET_ENCODING,get_charset());
		xml_set_element_handler($xml_parser,'startElement','endElement');
		xml_set_character_data_handler($xml_parser,'startText');

		// Run the parser
		$data=file_get_contents(get_custom_file_base().'/data_custom/fields.xml',FILE_TEXT);
		if (trim($data)=='') return;
		if (@xml_parse($xml_parser,$data,true)==0)
		{
			attach_message('fields.xml: '.xml_error_string(xml_get_error_code($xml_parser)),'warn');
			return;
		}
		@xml_parser_free($xml_parser);
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 * @param  string			The name of the element found
	 * @param  array			Array of attributes of the element
	 */
	function startElement($parser,$tag,$_attributes)
	{
		unset($parser);

		array_push($this->tag_stack,$tag);
		$attributes=array();
		foreach ($_attributes as $key=>$val)
		{
			$attributes[strtolower($key)]=$val;
		}
		array_push($this->attribute_stack,$attributes);

		switch (strtolower($tag))
		{
			case 'qualify':
				if ($this->levels_from_filtered==0)
				{
					$applies=true;
					if ($applies)
					{
						if (array_key_exists('pages',$attributes))
						{
							$applies=false;
							$pages=explode(',',$attributes['pages']);
							foreach ($pages as $page)
							{
								if (simulated_wildcard_match($this->this_page,trim($page),true)) $applies=true;
							}
						}
					}
					if ($applies)
					{
						if (array_key_exists('types',$attributes))
						{
							$applies=false;
							$types=explode(',',$attributes['types']);
							foreach ($types as $type)
							{
								if (simulated_wildcard_match($this->this_type,trim($type),true)) $applies=true;
							}
						}
					}

					if (!array_key_exists('fields',$attributes)) $attributes['fields']='*';
					array_push($this->field_qualification_stack,$attributes['fields']);
					if (!$applies)
						$this->levels_from_filtered=1;
				} elseif ($this->levels_from_filtered!=0) $this->levels_from_filtered++;
				break;
			case 'filter':
				if ($this->levels_from_filtered==0)
				{
					$applies=true;
					if ((array_key_exists('notstaff',$attributes)) && ($attributes['notstaff']=='1') && (isset($GLOBALS['FORUM_DRIVER'])) && ($GLOBALS['FORUM_DRIVER']->is_staff(get_member())))
						$applies=false;
					if ($applies)
					{
						if (array_key_exists('groups',$attributes))
						{
							$applies=false;
							$members_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups(get_member());
							$groups=explode(',',$attributes['groups']);
							foreach ($groups as $group)
							{
								if (in_array(intval(trim($group)),$members_groups)) $applies=true;
							}
						}
					}
					if ($applies)
					{
						if (array_key_exists('members',$attributes))
						{
							$applies=false;
							$members=explode(',',$attributes['members']);
							foreach ($members as $member)
							{
								if (intval(trim($member))==get_member()) $applies=true;
							}
						}
					}

					if (!$applies)
						$this->levels_from_filtered=1;
				} elseif ($this->levels_from_filtered!=0) $this->levels_from_filtered++;
				break;
			default:
				if ($this->levels_from_filtered!=0) $this->levels_from_filtered++;
				break;
		}
		$this->text_so_far='';
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 */
	function endElement($parser)
	{
		$text=str_replace('\n',chr(10),$this->text_so_far);
		$tag=array_pop($this->tag_stack);
		$attributes=array_pop($this->attribute_stack);

		switch (strtolower($tag))
		{
			case 'qualify':
				array_pop($this->field_qualification_stack);
				break;
			case 'filter':
				break;
			default:
				if ($this->levels_from_filtered==0)
				{
					global $FIELD_RESTRICTIONS;
					$qualifier=array_peek($this->field_qualification_stack);
					if (!array_key_exists($qualifier,$FIELD_RESTRICTIONS))
						$FIELD_RESTRICTIONS[$qualifier]=array();
					$FIELD_RESTRICTIONS[$qualifier][]=array($tag,array_merge(array('embed'=>$text),$attributes));
				}
				break;
		}

		if ($this->levels_from_filtered!=0) $this->levels_from_filtered--;
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 * @param  string			The text
	 */
	function startText($parser,$data)
	{
		unset($parser);

		$this->text_so_far.=$data;
	}

}


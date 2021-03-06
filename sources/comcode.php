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
 * @package		core_rich_media
 */

/**
 * Standard code module initialisation function.
 */
function init__comcode()
{
	global $VALID_COMCODE_TAGS;
	$VALID_COMCODE_TAGS=array( 'samp'=>1,'q'=>1,'var'=>1,'overlay'=>1,'tooltip'=>1,'section'=>1,'section_controller'=>1,'big_tab'=>1,'big_tab_controller'=>1,'tabs'=>1,'tab'=>1,'carousel'=>1,'cite'=>1,'ins'=>1,'del'=>1,'dfn'=>1,'address'=>1,'acronym'=>1,'abbr'=>1,'contents'=>1,'concepts'=>1,'list'=>1,'flash'=>1,'indent'=>1,'staff_note'=>1,'menu'=>1,'b'=>1,'i'=>1,'u'=>1,'s'=>1,'sup'=>1,'sub'=>1,
										'if_in_group'=>1,'title'=>1,'size'=>1,'color'=>1,'highlight'=>1,'font'=>1,'tt'=>1,'box'=>1,'internal_table'=>1,'external_table'=>1,'img'=>1,
										'url'=>1,'email'=>1,'reference'=>1,'upload'=>1,'page'=>1,'php'=>1,'codebox'=>1,'sql'=>1,'no_parse'=>1,'code'=>1,'hide'=>1,
										'quote'=>1,'block'=>1,'semihtml'=>1,'html'=>1,'exp_thumb'=>1,'exp_ref'=>1,'concept'=>1,'thumb'=>1,'attachment'=>1,'attachment2'=>1,'attachment_safe'=>1,'align'=>1,'left'=>1,'center'=>1,'right'=>1,
										'snapback'=>1,'post'=>1,'thread'=>1,'topic'=>1,'include'=>1,'random'=>1,'ticker'=>1,'jumping'=>1,'surround'=>1,'pulse'=>1,'shocker'=>1);
	//if (addon_installed('ecommerce'))
	{
		$VALID_COMCODE_TAGS['currency']=1;
	}

	global $IMPORTED_CUSTOM_COMCODE,$REPLACE_TARGETS;
	$IMPORTED_CUSTOM_COMCODE=false;
	$REPLACE_TARGETS=array();

	global $COMCODE_ATTACHMENTS,$ATTACHMENTS_ALREADY_REFERENCED;
	$COMCODE_ATTACHMENTS=array();
	$ATTACHMENTS_ALREADY_REFERENCED=array();

	global $COMCODE_PARSE_URLS_CHECKED;
	$COMCODE_PARSE_URLS_CHECKED=0;
	if (!defined('MAX_URLS_TO_READ')) define('MAX_URLS_TO_READ',5);

	global $OVERRIDE_SELF_ZONE;
	$OVERRIDE_SELF_ZONE=NULL; // This is not pretty, but needed to properly scope links for search results.

	// We're not allowed to specify any of these as entities
	global $POTENTIAL_JS_NAUGHTY_ARRAY;
	$POTENTIAL_JS_NAUGHTY_ARRAY=array(/*'v'=>1,*/'b'=>1,/*'V'=>1,*/'B'=>1,'d'=>1,'D'=>1,/*'a'=>1,'t'=>1,'a'=>1,*/'j'=>1,'a'=>1,'v'=>1,'s'=>1,'c'=>1,'r'=>1,'i'=>1,'p'=>1,'t'=>1,'J'=>1,'A'=>1,'V'=>1,'S'=>1,'C'=>1,'R'=>1,'I'=>1,'P'=>1,'T'=>1,' '=>1,"\t"=>1,"\n"=>1,"\r"=>1,':'=>1,'/'=>1,'*'=>1,'\\'=>1);
	$POTENTIAL_JS_NAUGHTY_ARRAY[chr(0)]=1;

	global $LAX_COMCODE;
	$LAX_COMCODE=false;
}

/**
 * Censor some Comcode raw code so that another user can see it.
 * This function isn't designed to be perfectly secure, and we don't guarantee it's always run, but as a rough thing we prefer to do it.
 *
 * @param  string			Comcode
 * @param  ?MEMBER		Force an HTML-evaluation of the Comcode through this security ID then back to Comcode, as a security technique (NULL: don't)
 * @return string			Censored Comcode
 */
function comcode_censored_raw_code_access($comcode, $aggressive = null)
{
	if ($aggressive !== null)
	{
		$eval=comcode_to_tempcode($comcode,$aggressive);
		require_code('comcode_from_html');
		$comcode =semihtml_to_comcode($comcode,true);
		return $comcode;
	}

	$comcode=preg_replace('#\[staff_note\].*\[/staff_note\]#Us','',$comcode);
	return $comcode;
}

/**
 * Make text usable inside a string inside comcode
 *
 * @param  string			Raw text
 * @return string			Escaped text
 */
function comcode_escape($in)
{
	return str_replace('{','\\{',str_replace('[','\\[',str_replace('"','\\"',str_replace('\\','\\\\',$in))));
}

/**
 * Convert (X)HTML into comcode
 *
 * @param  LONG_TEXT		The HTML to converted
 * @return LONG_TEXT		The equivalent comcode
 */
function html_to_comcode($html)
{
	// First we allow this to be semi-html
	$html=str_replace('[','&#091;',$html);

	require_code('comcode_from_html');

	return semihtml_to_comcode($html);
}

/**
 * Get the text with all the smilie codes replaced with the correct XHTML. Smiles are determined by your forum system.
 * This is not used in the normal comcode chain - it's for non-comcode things that require smilies (actually in reality it is used in the Comcode chain if the optimiser sees that a full parse is not needed)
 *
 * @param  string			The text to add smilies to (assumption: that this is XHTML)
 * @return string			The XHTML with the image-substitution of smilies
 */
function apply_emoticons($text)
{
	$_smilies=$GLOBALS['FORUM_DRIVER']->find_emoticons(); // Sorted in descending length order

	if ($GLOBALS['XSS_DETECT']) $orig_escaped=ocp_is_escaped($text);

	// Pre-check, optimisation
	$smilies=array();
	foreach ($_smilies as $code=>$imgcode)
	{
		if (strpos($text,$code)!==false)
			$smilies[$code]=$imgcode;
	}

	if (count($smilies)!=0)
	{
		$len=strlen($text);
		for ($i=0;$i<$len;++$i) // Has to go through in byte order so double application cannot happen (i.e. smiley contains [all or portion of] smiley code somehow)
		{
			$char=$text[$i];

			if ($char=='"') // This can cause severe HTML corruption so is a disallowed character
			{
				$i++;
				continue;
			}
			foreach ($smilies as $code=>$imgcode)
			{
				$code_len=strlen($code);
				if (($char==$code[0]) && (substr($text,$i,$code_len)==$code))
				{
					$eval=do_emoticon($imgcode);
					$_eval=$eval->evaluate();
					if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($_eval);
					$before=substr($text,0,$i);
					$after=substr($text,$i+$code_len);
					if (($before=='') && ($after=='')) $text=$_eval; else $text=$before.$_eval.$after;
					$len=strlen($text);
					$i+=strlen($_eval)-1;
					break;
				}
			}
		}

		if (($GLOBALS['XSS_DETECT']) && ($orig_escaped)) ocp_mark_as_escaped($text);
	}

	return $text;
}

/**
 * Turn a triple of emoticon parameters into some actual tempcode.
 *
 * @param  array			Parameter triple(template,src,code)
 * @return mixed			Either a tempcode result, or a string result, depending on $evaluate
 */
function do_emoticon($imgcode)
{
	$tpl=do_template($imgcode[0],array('UNIQID'=>uniqid('',true),'SRC'=>$imgcode[1],'EMOTICON'=>$imgcode[2]));
	return $tpl;
}

/**
 * Convert the specified comcode (unknown format) into a tempcode tree. You shouldn't output the tempcode tree to the browser, as it looks really horrible. If you are in a rare case where you need to output directly (not through templates), you should call the evaluate method on the tempcode object, to convert it into a string.
 *
 * @param  LONG_TEXT		The comcode to convert
 * @param  ?MEMBER		The member the evaluation is running as. This is a security issue, and you should only run as an administrator if you have considered where the comcode came from carefully (NULL: current member)
 * @param  boolean		Whether to explicitly execute this with admin rights. There are a few rare situations where this should be done, for data you know didn't come from a member, but is being evaluated by one. Note that if this is passed false, and $source_member is an admin, it will be parsed using admin privileges anyway.
 * @param  ?integer		The position to conduct wordwrapping at (NULL: do not conduct word-wrapping)
 * @param  ?string		A special identifier that can identify this resource in a sea of our resources of this class; usually this can be ignored, but may be used to provide a binding between Javascript in evaluated comcode, and the surrounding environment (NULL: no explicit binding)
 * @param  ?object		The database connection to use (NULL: standard site connection)
 * @param  boolean		Whether to parse so as to create something that would fit inside a semihtml tag. It means we generate HTML, with Comcode written into it where the tag could never be reverse-converted (e.g. a block).
 * @param  boolean		Whether this is being pre-parsed, to pick up errors before row insertion.
 * @param  boolean		Whether to treat this whole thing as being wrapped in semihtml, but apply normal security otherwise.
 * @param  boolean		Whether we are only doing this parse to find the title structure
 * @param  boolean		Whether to only check the Comcode. It's best to use the check_comcode function which will in turn use this parameter.
 * @param  ?array			A list of words to highlight (NULL: none)
 * @param  ?MEMBER		The member we are running on behalf of, with respect to how attachments are handled; we may use this members attachments that are already within this post, and our new attachments will be handed to this member (NULL: member evaluating)
 * @return tempcode		The tempcode generated
 */
function comcode_to_tempcode($comcode,$source_member=NULL,$as_admin=false,$wrap_pos=60,$pass_id=NULL,$connection=NULL,$semiparse_mode=false,$preparse_mode=false,$is_all_semihtml=false,$structure_sweep=false,$check_only=false,$highlight_bits=NULL,$on_behalf_of_member=NULL)
{
	$matches=array();
	if (preg_match('#^\{\!([A-Z\_]+)\}$#',$comcode,$matches)!=0) return do_lang_tempcode($matches[1]);

	if ($semiparse_mode) $wrap_pos=100000;

	$attachments=(count($_FILES)!=0);
	foreach ($_POST as $key=>$value)
	{
		if (!is_string($key)) $key=strval($key);
		if (preg_match('#^hidFileID\_#i',$key)!=0) $attachments=true;
	}
	if ((!$attachments || ($GLOBALS['IN_MINIKERNEL_VERSION']==1)) && (preg_match('#^[\w\d\-\_\(\) \.,:;/"\!\?]*$#'/*NB: No apostophes allowed in here, as they get changed by escape_html and can interfere then with apply_emoticons*/,$comcode)!=0) && (strpos($comcode,'  ')===false) && (strpos($comcode,'://')===false) && (get_page_name()!='search'))
	{
		if (running_script('stress_test_loader')) return make_string_tempcode(escape_html($comcode));
		return make_string_tempcode(apply_emoticons(escape_html($comcode)));
	}

	require_code('comcode_renderer');
	return _comcode_to_tempcode($comcode,$source_member,$as_admin,$wrap_pos,$pass_id,$connection,$semiparse_mode,$preparse_mode,$is_all_semihtml,$structure_sweep,$check_only,$highlight_bits,$on_behalf_of_member);
}

/**
 * Strip out any Comcode from this "plain text". Useful for semantic text is wanted but where Comcode is used as "the next best thing" we have.
 *
 * @param  string			Plain-text/Comcode
 * @return string			Purified plain-text
 */
function strip_comcode($text)
{
	require_code('mail');
	if (function_exists('comcode_to_clean_text')) // For benefit of installer, which disables mail.php
		$text=comcode_to_clean_text($text);

	global $VALID_COMCODE_TAGS;
	foreach (array_keys($VALID_COMCODE_TAGS) as $tag)
	{
		if ($tag=='i')
		{
			$text=preg_replace('#\[/?'.$tag.'\]#','',$text);
		} else
		{
			$text=preg_replace('#\[/?'.$tag.'[^\]]*\]#','',$text);
		}
	}
	return $text;
}

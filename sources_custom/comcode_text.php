<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.

*/

if (!function_exists('init__comcode_text'))
{
	function init__comcode_text($in=NULL)
	{
		if (is_null($in)) return $in; // HipHop PHP can't do code rewrites, but will call init functions if there is none in the original. Do nothing.

		$new_code='
			require_code(\'textfiles\');
			$whitelists=explode(chr(10),read_text_file(\'comcode_whitelist\'));
			foreach ($whitelists as $w)
			{
				if (trim($w)!=\'\')
				{
					if ($w[0]!=\'/\') $w=preg_quote($w,\'#\'); else $w=substr($w,1,strlen($w)-2);
					$allowed_html_seqs[]=$w;
				}
			}
		';
		$in=str_replace('if ($as_admin)',$new_code.' if ($as_admin)',$in);

		$new_code='
			foreach ($OBSCURE_REPLACEMENTS as $rep=>$from)
			{
				$ahead=str_replace($rep,$from,$ahead);
			}
		';
		$in=str_replace('// Tidy up',$new_code,$in);

		return $in;
	}
}

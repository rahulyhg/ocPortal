<?php

require_code('files2');

$member_id=isset($map['member_id'])?intval($map['member_id']):get_member();

$basedir=get_custom_file_base().'/uploads/filedump/'.$GLOBALS['FORUM_DRIVER']->get_username($member_id);
$baseurl=get_custom_base_url().'/uploads/filedump/'.rawurlencode($GLOBALS['FORUM_DRIVER']->get_username($member_id));

$files=file_exists($basedir)?get_directory_contents($basedir):array();

if (count($files)==0)
{
	echo '<p class="nothing_here">No files have been uploaded for you yet.</p>';
} else
{
	natsort($files);
	echo '<div class="wide_table_wrap"><table class="wide_table solidborder">';
	echo '<colgroup><col width="25%" /><col width="75%" /><col width="100px" /></colgroup>';
	echo '<thead><tr><th>Filename</th><th>Description</th><th>File size</th></tr></thead>';
	echo '<tbody>';
	foreach ($files as $file)
	{
		$dbrows=$GLOBALS['SITE_DB']->query_select('filedump',array('description','the_member'),array('name'=>$file,'path'=>'/'.$GLOBALS['FORUM_DRIVER']->get_username($member_id).'/'));
		if (!array_key_exists(0,$dbrows)) $description=do_lang_tempcode('NONE_EM'); else $description=make_string_tempcode(get_translated_text($dbrows[0]['description']));

		echo '
			<tr>
				<td><a target="_blank" href="'.escape_html($baseurl.'/'.$file).'">'.escape_html($file).'</a></td>
				<td>'.$description->evaluate().'</td>
				<td>'.escape_html(clean_file_size(filesize($basedir.'/'.$file))).'</td>
			</tr>
		';
	}
	echo '</tbody>';
	echo '</table></div>';
}


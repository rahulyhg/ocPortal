<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		catalogues
 */

class Hook_task_export_catalogue
{
    /**
	 * Run the task hook.
	 *
	 * @param  ID_TEXT		The catalogue to export
	 * @return ?array			A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (NULL: show standard success message)
	 */
    public function run($catalogue_name)
    {
        $filename = $catalogue_name . '-' . date('Y-m-d') . '.csv';

        $headers = array();
        $headers['Content-type'] = 'text/csv';
        $headers['Content-Disposition'] = 'attachment; filename="' . str_replace("\r",'',str_replace("\n",'',addslashes($filename))) . '"';

        $ini_set = array();
        $ini_set['ocproducts.xss_detect'] = '0';

        $catalogue_row = $GLOBALS['SITE_DB']->query_select('catalogues',array('*'),array('c_name' => $catalogue_name),'',null,null,true);
        if (is_null($catalogue_row)) {
            $catalogue_row = array();
        }
        if (isset($catalogue_row[0])) {
            $catalogue_row = $catalogue_row[0];
        }

        $category_names = array();

        $outfile_path = ocp_tempnam('csv');
        $outfile = fopen($outfile_path,'w+b');

        $fields = $GLOBALS['SITE_DB']->query_select('catalogue_fields',array('*'),array('c_name' => $catalogue_name),'ORDER BY cf_order');
        global $CAT_FIELDS_CACHE;
        $CAT_FIELDS_CACHE[$catalogue_name] = $fields;
        fwrite($outfile,'ID,');
        fwrite($outfile,'CATEGORY');
        foreach ($fields as $k) {
            fwrite($outfile,',');
            fwrite($outfile,'"' . str_replace('"','""',get_translated_text($k['cf_name'])) . '"');
        }
        fwrite($outfile,"\n");

        $start = 0;
        do {
            $entry_rows = $GLOBALS['SITE_DB']->query_select('catalogue_entries',array('*'),array('c_name' => $catalogue_name),'ORDER BY ce_add_date ASC',4000,$start);

            foreach ($entry_rows as $entry_row) {
                if (is_null($entry_row)) {
                    $entry_row = array();
                }
                if (isset($entry_row[0])) {
                    $entry_row = $entry_row[0];
                }

                $details = get_catalogue_entry_field_values($catalogue_name,$entry_row);

                $better_results = array();
                foreach ($details as $i => $val) {
                    $better_results[get_translated_text($fields[$i]['cf_name'])] = $val['effective_value_pure'];
                }

                if (!isset($category_names[$entry_row['cc_id']])) {
                    if (!array_key_exists($entry_row['cc_id'],$category_names)) {
                        $category_names[$entry_row['cc_id']] = get_translated_text($GLOBALS['SITE_DB']->query_select_value('catalogue_categories','cc_title',array('id' => $entry_row['cc_id'])));
                    }
                }
                fwrite($outfile,strval($entry_row['id']) . ',');
                fwrite($outfile,'"' . str_replace('"','""',$category_names[$entry_row['cc_id']]) . '"');
                foreach ($better_results as $v) {
                    fwrite($outfile,',');
                    fwrite($outfile,'"' . str_replace('"','""',$v) . '"');
                }
                fwrite($outfile,"\n");
            }

            $start += 4000;
        } while (count($entry_rows) != 0);

        fclose($outfile);

        return array('text/csv',array($filename,$outfile_path),$headers,$ini_set);
    }
}

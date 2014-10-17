<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    banners
 */

/**
 * Get the tempcode for the form to add a banner, with the information passed along to it via the parameters already added in.
 *
 * @param  boolean                      Whether to simplify the banner interface (for the Point Store buy process)
 * @param  ID_TEXT                      The name of the banner
 * @param  URLPATH                      The URL to the banner image
 * @param  URLPATH                      The URL to the site the banner leads to
 * @param  SHORT_TEXT                   The caption of the banner
 * @param  LONG_TEXT                    Complete HTML/PHP for the banner
 * @param  LONG_TEXT                    Any notes associated with the banner
 * @param  integer                      The banners "importance modulus"
 * @range  1 max
 * @param  ?integer                     The number of hits the banner may have (NULL: not applicable for this banner type)
 * @range  0 max
 * @param  SHORT_INTEGER                The type of banner (0=permanent, 1=campaign, 2=default)
 * @set    0 1 2
 * @param  ?TIME                        The banner expiry date (NULL: never expires)
 * @param  ?MEMBER                      The banners submitter (NULL: current member)
 * @param  BINARY                       Whether the banner has been validated
 * @param  ID_TEXT                      The banner type (can be anything, where blank means 'normal')
 * @param  SHORT_TEXT                   The title text for the banner (only used for text banners, and functions as the 'trigger text' if the banner type is shown inline)
 * @return array                        A pair: The input field tempcode, JavaScript code
 */
function get_banner_form_fields($simplified = false, $name = '', $image_url = '', $site_url = '', $caption = '', $direct_code = '', $notes = '', $importancemodulus = 3, $campaignremaining = 50, $the_type = 1, $expiry_date = null, $submitter = null, $validated = 1, $b_type = '', $title_text = '')
{
    require_code('images');

    $fields = new ocp_tempcode();
    require_code('form_templates');
    $fields->attach(form_input_codename(do_lang_tempcode('CODENAME'), do_lang_tempcode('DESCRIPTION_BANNER_NAME'), 'name', $name, true));
    $fields->attach(form_input_line(do_lang_tempcode('DESTINATION_URL'), do_lang_tempcode('DESCRIPTION_BANNER_URL'), 'site_url', $site_url, false)); // Blank implies iframe or direct code
    if (!$simplified) {
        $types = create_selection_list_banner_types($b_type);
        if ($types->is_empty()) {
            warn_exit(do_lang_tempcode('NO_CATEGORIES'));
        }
        $fields->attach(form_input_list(do_lang_tempcode('_BANNER_TYPE'), do_lang_tempcode('_DESCRIPTION_BANNER_TYPE'), 'b_type', $types, null, false, false));
    } else {
        $fields->attach(form_input_hidden('b_type', $b_type));
    }
    if (get_option('enable_staff_notes') == '1') {
        $fields->attach(form_input_text(do_lang_tempcode('NOTES'), do_lang_tempcode('DESCRIPTION_NOTES'), 'notes', $notes, false));
    }

    if (has_privilege(get_member(), 'bypass_validation_midrange_content', 'cms_banners')) {
        if ($validated == 0) {
            $validated = get_param_integer('validated', 0);
            if (($validated == 1) && (addon_installed('unvalidated'))) {
                attach_message(do_lang_tempcode('WILL_BE_VALIDATED_WHEN_SAVING'));
            }
        }
        if (addon_installed('unvalidated')) {
            $fields->attach(form_input_tick(do_lang_tempcode('VALIDATED'), do_lang_tempcode($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()) ? 'DESCRIPTION_VALIDATED_SIMPLE' : 'DESCRIPTION_VALIDATED'), 'validated', $validated == 1));
        }
    }

    $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => 'b110d585eea7d6e29dab4870c5a15c4a', 'TITLE' => do_lang_tempcode('SOURCE_MEDIA'))));

    $set_name = 'media';
    $required = false;
    $set_title = do_lang_tempcode('MEDIA');
    $field_set = alternate_fields_set__start($set_name);

    $field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'), do_lang_tempcode('DESCRIPTION_UPLOAD_BANNER'), 'file', false, null, null, true, str_replace(' ', '', get_option('valid_images')) . ',swf'));

    $field_set->attach(form_input_line(do_lang_tempcode('IMAGE_URL'), do_lang_tempcode('DESCRIPTION_URL_BANNER'), 'image_url', $image_url, false));

    $field_set->attach(form_input_line_comcode(do_lang_tempcode('BANNER_TITLE_TEXT'), do_lang_tempcode('DESCRIPTION_BANNER_TITLE_TEXT'), 'title_text', $title_text, false));

    if (has_privilege(get_member(), 'use_html_banner')) {
        $field_set->attach(form_input_text(do_lang_tempcode('BANNER_DIRECT_CODE'), do_lang_tempcode('DESCRIPTION_BANNER_DIRECT_CODE'), 'direct_code', $direct_code, false));
    }

    $fields->attach(alternate_fields_set__end($set_name, $set_title, '', $field_set, $required));

    $fields->attach(form_input_line_comcode(do_lang_tempcode('DESCRIPTION'), do_lang_tempcode('DESCRIPTION_BANNER_DESCRIPTION'), 'caption', $caption, false));

    $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '1184532268cd8a58adea01c3637dc4c5', 'TITLE' => do_lang_tempcode('DEPLOYMENT_DETERMINATION'))));
    if (has_privilege(get_member(), 'full_banner_setup')) {
        $radios = new ocp_tempcode();
        $radios->attach(form_input_radio_entry('the_type', strval(BANNER_PERMANENT), ($the_type == BANNER_PERMANENT), do_lang_tempcode('BANNER_PERMANENT')));
        $radios->attach(form_input_radio_entry('the_type', strval(BANNER_CAMPAIGN), ($the_type == BANNER_CAMPAIGN), do_lang_tempcode('BANNER_CAMPAIGN')));
        $radios->attach(form_input_radio_entry('the_type', strval(BANNER_DEFAULT), ($the_type == BANNER_DEFAULT), do_lang_tempcode('BANNER_DEFAULT')));
        $fields->attach(form_input_radio(do_lang_tempcode('DEPLOYMENT_AGREEMENT'), do_lang_tempcode('DESCRIPTION_BANNER_TYPE'), 'the_type', $radios));
        $fields->attach(form_input_integer(do_lang_tempcode('HITS_ALLOCATED'), do_lang_tempcode('DESCRIPTION_HITS_ALLOCATED'), 'campaignremaining', $campaignremaining, false));
        $total_importance = $GLOBALS['SITE_DB']->query_value_if_there('SELECT SUM(importance_modulus) FROM ' . get_table_prefix() . 'banners WHERE ' . db_string_not_equal_to('name', $name));
        if (is_null($total_importance)) {
            $total_importance = 0;
        }
        $fields->attach(form_input_integer(do_lang_tempcode('IMPORTANCE_MODULUS'), do_lang_tempcode('DESCRIPTION_IMPORTANCE_MODULUS', strval($total_importance), strval($importancemodulus)), 'importancemodulus', $importancemodulus, true));
    }
    $fields->attach(form_input_date(do_lang_tempcode('EXPIRY_DATE'), do_lang_tempcode('DESCRIPTION_EXPIRY_DATE'), 'expiry_date', false, is_null($expiry_date), true, $expiry_date, 2));

    $javascript = '
        if (document.getElementById(\'campaignremaining\'))
        {
            var form=document.getElementById(\'campaignremaining\').form;
            var crf=function() {
                    form.elements[\'campaignremaining\'].disabled=(!form.elements[\'the_type\'][1].checked);
            };
            crf();
            form.elements[\'the_type\'][0].onclick=crf;
            form.elements[\'the_type\'][1].onclick=crf;
            form.elements[\'the_type\'][2].onclick=crf;
        }
    ';

    return array($fields, $javascript);
}

/**
 * Check the uploaded banner is valid.
 *
 * @param  SHORT_TEXT                   The title text for the banner (only used for text banners, and functions as the 'trigger text' if the banner type is shown inline)
 * @param  LONG_TEXT                    Complete HTML/PHP for the banner
 * @param  ID_TEXT                      The banner type (can be anything, where blank means 'normal')
 * @return array                        A pair: The URL, and the title text
 * @param  string                       Param name for possible URL field
 * @param  string                       Param name for possible upload field
 */
function check_banner($title_text = '', $direct_code = '', $b_type = '', $url_param_name = 'image_url', $file_param_name = 'file')
{
    require_code('uploads');
    $is_upload = (is_plupload()) || (array_key_exists($file_param_name, $_FILES)) && (array_key_exists('tmp_name', $_FILES[$file_param_name]) && (is_uploaded_file($_FILES[$file_param_name]['tmp_name'])));

    require_code('uploads');

    $url = '';

    // Check according to banner type
    $_banner_type_rows = $GLOBALS['SITE_DB']->query_select('banner_types', array('*'), array('id' => $b_type), '', 1);
    if (!array_key_exists(0, $_banner_type_rows)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }
    $banner_type_row = $_banner_type_rows[0];
    if ($banner_type_row['t_is_textual'] == 0) {
        if ($direct_code == '') {
            $urls = get_url($url_param_name, $file_param_name, 'uploads/banners', 0, $is_upload ? (OCP_UPLOAD_IMAGE | OCP_UPLOAD_SWF) : OCP_UPLOAD_ANYTHING);
            $url = fixup_protocolless_urls($urls[0]);
            if ($url == '') {
                warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN_UPLOAD_BANNERS'));
            }

            // Check width, height, size
            $test_url = $url;
            if (url_is_local($test_url)) {
                $data = file_get_contents(get_custom_file_base() . '/' . rawurldecode($test_url));
                $test_url = get_custom_base_url() . '/' . $test_url;
            } else {
                $data = http_download_file($test_url);
            }
            if (strlen($data) > $banner_type_row['t_max_file_size'] * 1024) {
                if (url_is_local($test_url)) {
                    @unlink(get_custom_file_base() . '/' . rawurldecode($test_url));
                    sync_file(rawurldecode($test_url));
                }
                warn_exit(do_lang_tempcode('BANNER_TOO_LARGE', integer_format(intval(ceil(strlen($data) / 1024))), integer_format($banner_type_row['t_max_file_size'])));
            }
            if ((function_exists('imagetypes')) && (substr($test_url, -4) != '.swf')) {
                require_code('images');
                if (is_image($test_url)) {
                    require_code('files');
                    $img_res = @imagecreatefromstring($data);
                    if ($img_res === false) {
                        if (url_is_local($test_url)) {
                            @unlink(get_custom_file_base() . '/' . rawurldecode($test_url));
                            sync_file(rawurldecode($test_url));
                        }
                        warn_exit(do_lang_tempcode('CORRUPT_FILE', escape_html($test_url)));
                    }

                    if (get_file_extension($test_url) == 'gif') {
                        $header = unpack('@6/' . 'vwidth/' . 'vheight', $data);
                        $sx = $header['width'];
                        $sy = $header['height'];
                    } else {
                        $sx = imagesx($img_res);
                        $sy = imagesy($img_res);
                    }

                    if ((get_option('banner_autosize') != '1') && (($sx != $banner_type_row['t_image_width']) || ($sy != $banner_type_row['t_image_height']))) {
                        if (url_is_local($test_url)) {
                            @unlink(get_custom_file_base() . '/' . rawurldecode($test_url));
                            sync_file(rawurldecode($test_url));
                        }
                        warn_exit(do_lang_tempcode('BANNER_RES_BAD', integer_format($banner_type_row['t_image_width']), integer_format($banner_type_row['t_image_height'])));
                    }
                }
            }
        } else {
            check_privilege('use_html_banner');
            if (strpos($direct_code, '<?') !== false) {
                check_privilege('use_php_banner');
                if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) {
                    warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
                }
            }
        }
    } else {
        if ($title_text == '') {
            warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN_BANNERS'));
        }

        if (strlen($title_text) > $banner_type_row['t_max_file_size']) {
            warn_exit(do_lang_tempcode('BANNER_TOO_LARGE_2', integer_format(strlen($title_text)), integer_format($banner_type_row['t_max_file_size'])));
        }
    }

    return array($url, $title_text);
}

/**
 * Add a banner to the database, and return the new ID of that banner in the database.
 *
 * @param  ID_TEXT                      The name of the banner
 * @param  URLPATH                      The URL to the banner image
 * @param  SHORT_TEXT                   The title text for the banner (only used for text banners, and functions as the 'trigger text' if the banner type is shown inline)
 * @param  SHORT_TEXT                   The caption of the banner
 * @param  LONG_TEXT                    Complete HTML/PHP for the banner
 * @param  ?integer                     The number of hits the banner may have (NULL: not applicable for this banner type)
 * @range  0 max
 * @param  URLPATH                      The URL to the site the banner leads to
 * @param  integer                      The banners "importance modulus"
 * @range  1 max
 * @param  LONG_TEXT                    Any notes associated with the banner
 * @param  SHORT_INTEGER                The type of banner (0=permanent, 1=campaign, 2=default)
 * @set    0 1 2
 * @param  ?TIME                        The banner expiry date (NULL: never)
 * @param  ?MEMBER                      The banners submitter (NULL: current member)
 * @param  BINARY                       Whether the banner has been validated
 * @param  ID_TEXT                      The banner type (can be anything, where blank means 'normal')
 * @param  ?TIME                        The time the banner was added (NULL: now)
 * @param  integer                      The number of return hits from this banners site
 * @param  integer                      The number of banner hits to this banners site
 * @param  integer                      The number of return views from this banners site
 * @param  integer                      The number of banner views to this banners site
 * @param  ?TIME                        The banner edit date  (NULL: never)
 * @param  boolean                      Whether to force the name as unique, if there's a conflict
 * @return ID_TEXT                      The name
 */
function add_banner($name, $imgurl, $title_text, $caption, $direct_code, $campaignremaining, $site_url, $importancemodulus, $notes, $the_type, $expiry_date, $submitter, $validated = 0, $b_type = '', $time = null, $hits_from = 0, $hits_to = 0, $views_from = 0, $views_to = 0, $edit_date = null, $uniqify = false)
{
    if (is_null($campaignremaining)) {
        $campaignremaining = 0;
    }

    if (is_null($time)) {
        $time = time();
    }
    if (is_null($submitter)) {
        $submitter = get_member();
    }

    $test = $GLOBALS['SITE_DB']->query_select_value_if_there('banners', 'name', array('name' => $name));
    if (!is_null($test)) {
        if ($uniqify) {
            $name .= '_' . uniqid('', true);
        } else {
            warn_exit(do_lang_tempcode('ALREADY_EXISTS', escape_html($name)));
        }
    }

    if (!addon_installed('unvalidated')) {
        $validated = 1;
    }
    $map = array(
        'b_title_text' => $title_text,
        'b_direct_code' => $direct_code,
        'b_type' => $b_type,
        'edit_date' => $edit_date,
        'add_date' => $time,
        'expiry_date' => $expiry_date,
        'the_type' => $the_type,
        'submitter' => $submitter,
        'name' => $name,
        'img_url' => $imgurl,
        'campaign_remaining' => $campaignremaining,
        'site_url' => $site_url,
        'importance_modulus' => $importancemodulus,
        'notes' => $notes,
        'validated' => $validated,
        'hits_from' => $hits_from,
        'hits_to' => $hits_to,
        'views_from' => $views_from,
        'views_to' => $views_to
    );
    $map += insert_lang_comcode('caption', $caption, 2);
    $GLOBALS['SITE_DB']->query_insert('banners', $map);

    decache('main_banner_wave');
    decache('main_topsites');

    log_it('ADD_BANNER', $name, $caption);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('banner', $name, null, null, true);
    }

    require_code('member_mentions');
    dispatch_member_mention_notifications('banner', $name, $submitter);

    return $name;
}

/**
 * Edit a banner.
 *
 * @param  ID_TEXT                      The current name of the banner
 * @param  ID_TEXT                      The new name of the banner
 * @param  URLPATH                      The URL to the banner image
 * @param  SHORT_TEXT                   The title text for the banner (only used for text banners, and functions as the 'trigger text' if the banner type is shown inline)
 * @param  SHORT_TEXT                   The caption of the banner
 * @param  LONG_TEXT                    Complete HTML/PHP for the banner
 * @param  ?integer                     The number of hits the banner may have (NULL: not applicable for this banner type)
 * @range  0 max
 * @param  URLPATH                      The URL to the site the banner leads to
 * @param  integer                      The banners "importance modulus"
 * @range  1 max
 * @param  LONG_TEXT                    Any notes associated with the banner
 * @param  SHORT_INTEGER                The type of banner (0=permanent, 1=campaign, 2=default)
 * @set    0 1 2
 * @param  ?TIME                        The banner expiry date (NULL: never)
 * @param  ?MEMBER                      The banners submitter (NULL: leave unchanged)
 * @param  BINARY                       Whether the banner has been validated
 * @param  ID_TEXT                      The banner type (can be anything, where blank means 'normal')
 * @param  ?TIME                        Edit time (NULL: either means current time, or if $null_is_literal, means reset to to NULL)
 * @param  ?TIME                        Add time (NULL: do not change)
 * @param  boolean                      Determines whether some NULLs passed mean 'use a default' or literally mean 'set to NULL'
 * @param  boolean                      Whether to force the name as unique, if there's a conflict
 * @return ID_TEXT                      The name
 */
function edit_banner($old_name, $name, $imgurl, $title_text, $caption, $direct_code, $campaignremaining, $site_url, $importancemodulus, $notes, $the_type, $expiry_date, $submitter, $validated, $b_type, $edit_time = null, $add_time = null, $null_is_literal = false, $uniqify = false)
{
    if ($old_name != $name) {
        $test = $GLOBALS['SITE_DB']->query_select_value_if_there('banners', 'name', array('name' => $name));
        if (!is_null($test)) {
            if ($uniqify) {
                $name .= '_' . uniqid('', true);
            } else {
                warn_exit(do_lang_tempcode('ALREADY_EXISTS', escape_html($name)));
            }
        }

        if (addon_installed('catalogues')) {
            update_catalogue_content_ref('banner', $old_name, $name);
        }
    }

    if (is_null($edit_time)) {
        $edit_time = $null_is_literal ? null : time();
    }

    $_caption = $GLOBALS['SITE_DB']->query_select_value('banners', 'caption', array('name' => $old_name));

    require_code('files2');
    delete_upload('uploads/banners', 'banners', 'img_url', 'name', $old_name, $imgurl);

    log_it('EDIT_BANNER', $name);

    decache('main_banner_wave');
    decache('main_topsites');

    if (!addon_installed('unvalidated')) {
        $validated = 1;
    }

    require_code('submit');
    $just_validated = (!content_validated('banner', $name)) && ($validated == 1);
    if ($just_validated) {
        send_content_validated_notification('banner', $name);
    }

    $update_map = array(
        'b_title_text' => $title_text,
        'b_direct_code' => $direct_code,
        'expiry_date' => $expiry_date,
        'the_type' => $the_type,
        'name' => $name,
        'img_url' => $imgurl,
        'campaign_remaining' => $campaignremaining,
        'site_url' => $site_url,
        'importance_modulus' => $importancemodulus,
        'notes' => $notes,
        'validated' => $validated,
        'b_type' => $b_type
    );
    $update_map += lang_remap_comcode('caption', $_caption, $caption);

    if (!is_null($submitter)) {
        $update_map['submitter'] = $submitter;
    }
    $update_map['edit_date'] = $edit_time;
    if (!is_null($add_time)) {
        $update_map['add_date'] = $add_time;
    }

    $GLOBALS['SITE_DB']->query_update('banners', $update_map, array('name' => $old_name), '', 1);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('banner', $name);
    }

    return $name;
}

/**
 * Delete a banner.
 *
 * @param  ID_TEXT                      The name of the banner
 */
function delete_banner($name)
{
    $caption = $GLOBALS['SITE_DB']->query_select_value_if_there('banners', 'caption', array('name' => $name));
    if (is_null($caption)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
    }

    if (addon_installed('catalogues')) {
        update_catalogue_content_ref('banner', $name, '');
    }

    delete_lang($caption);

    require_code('files2');
    delete_upload('uploads/banners', 'banners', 'img_url', 'name', $name);

    decache('main_banner_wave');
    decache('main_topsites');

    $GLOBALS['SITE_DB']->query_delete('banners', array('name' => $name), '', 1);

    log_it('DELETE_BANNER', $name, get_translated_text($caption));

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        expunge_resourcefs_moniker('banner', $name);
    }
}

/**
 * Add a banner type.
 *
 * @param  ID_TEXT                      The ID of the banner type
 * @param  BINARY                       Whether this is a textual banner
 * @param  integer                      The image width (ignored for textual banners)
 * @param  integer                      The image height (ignored for textual banners)
 * @param  integer                      The maximum file size for the banners (this is a string length for textual banners)
 * @param  BINARY                       Whether the banner will be automatically shown via Comcode hot-text (this can only happen if banners of the title are given title-text)
 * @param  boolean                      Whether to force the name as unique, if there's a conflict
 * @return ID_TEXT                      The name
 */
function add_banner_type($id, $is_textual, $image_width, $image_height, $max_file_size, $comcode_inline, $uniqify = false)
{
    $test = $GLOBALS['SITE_DB']->query_select_value_if_there('banner_types', 'id', array('id' => $id));
    if (!is_null($test)) {
        if ($uniqify) {
            $id .= '_' . uniqid('', true);
        } else {
            warn_exit(do_lang_tempcode('ALREADY_EXISTS', escape_html($id)));
        }
    }

    $GLOBALS['SITE_DB']->query_insert('banner_types', array(
        'id' => $id,
        't_is_textual' => $is_textual,
        't_image_width' => $image_width,
        't_image_height' => $image_height,
        't_max_file_size' => $max_file_size,
        't_comcode_inline' => $comcode_inline
    ));

    log_it('ADD_BANNER_TYPE', $id);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('banner_type', $id);
    }

    require_code('member_mentions');
    dispatch_member_mention_notifications('banner_type', $id);

    return $id;
}

/**
 * Edit a banner type.
 *
 * @param  ID_TEXT                      The original ID of the banner type
 * @param  ID_TEXT                      The ID of the banner type
 * @param  BINARY                       Whether this is a textual banner
 * @param  integer                      The image width (ignored for textual banners)
 * @param  integer                      The image height (ignored for textual banners)
 * @param  integer                      The maximum file size for the banners (this is a string length for textual banners)
 * @param  BINARY                       Whether the banner will be automatically shown via Comcode hot-text (this can only happen if banners of the title are given title-text)
 * @param  boolean                      Whether to force the name as unique, if there's a conflict
 * @return ID_TEXT                      The name
 */
function edit_banner_type($old_id, $id, $is_textual, $image_width, $image_height, $max_file_size, $comcode_inline, $uniqify = false)
{
    if ($old_id != $id) {
        $test = $GLOBALS['SITE_DB']->query_select_value_if_there('banner_types', 'id', array('id' => $id));
        if (!is_null($test)) {
            if ($uniqify) {
                $id .= '_' . uniqid('', true);
            } else {
                warn_exit(do_lang_tempcode('ALREADY_EXISTS', escape_html($id)));
            }
        }
        $GLOBALS['SITE_DB']->query_update('banners', array('b_type' => $id), array('b_type' => $old_id));

        if (addon_installed('catalogues')) {
            update_catalogue_content_ref('banner_type', $old_id, $id);
        }
    }

    $GLOBALS['SITE_DB']->query_update('banner_types', array(
        'id' => $id,
        't_is_textual' => $is_textual,
        't_image_width' => $image_width,
        't_image_height' => $image_height,
        't_max_file_size' => $max_file_size,
        't_comcode_inline' => $comcode_inline
    ), array('id' => $old_id), '', 1);

    log_it('EDIT_BANNER_TYPE', $old_id, $id);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        generate_resourcefs_moniker('banner_type', $id);
    }

    return $id;
}

/**
 * Delete a banner type.
 *
 * @param  ID_TEXT                      The ID of the banner type
 */
function delete_banner_type($id)
{
    $GLOBALS['SITE_DB']->query_update('banners', array('b_type' => ''), array('b_type' => $id));

    $GLOBALS['SITE_DB']->query_delete('banner_types', array('id' => $id), '', 1);

    if (addon_installed('catalogues')) {
        update_catalogue_content_ref('banner_type', strval($id), '');
    }

    log_it('DELETE_BANNER_TYPE', $id);

    if ((addon_installed('occle')) && (!running_script('install'))) {
        require_code('resource_fs');
        expunge_resourcefs_moniker('banner_type', $id);
    }
}

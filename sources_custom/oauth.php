<?php

function init__oauth()
{
    require_lang('oauth');
}

function ensure_got_oauth_client_id($service_name,$has_sep_key = false)
{
    $client_id = get_option($service_name . '_client_id');

    if ($client_id == '') {
        $title = get_screen_title('OAUTH_TITLE',true,array($service_name));

        $config_url = build_url(array('page' => 'admin_config','type' => 'category','id' => 'FEATURE','redirect' => get_self_url(true)),'_SELF',null,false,false,false,'group_GALLERY_SYNDICATION');
        require_code('site2');
        smart_redirect($config_url);
    }

    return $client_id;
}

/*
The following is ocPortal's oAuth2 implementation.
oAuth2 is simpler than oAuth1, because SSL is used for encryption, rather than a complex native implementation.

Our policy with oAuth is to use whatever oAuth is bundled with service APIs first, if there is one.
(Most web services provide PHP APIs and include an oAuth implementation within them)
If a service provides no oAuth implementation and isn't a simple oAuth2, we would probably use a further
third party library.
The requirements of all these third party APIs and implementations need codifying within the description
of whatever ocPortal addon uses them, as it will typically exceed ocPortal base requirements.

Because of all this, we can have no top level oAuth API in ocPortal. Hook systems will need to tie into
whatever oAuth implementation happens to be used, on a case-by-case basis.
We may, however, define some common conventions and language strings, and use these on a case-by-case basis.
*/

function retrieve_oauth2_token($service_name,$service_title,$auth_url,$endpoint)
{
    $title = get_screen_title('OAUTH_TITLE',true,array($service_name));

    $client_id = ensure_got_oauth_client_id($service_name);

    if (get_param('state','') != 'authorized') {
        $auth_url = str_replace('_CLIENT_ID_',$client_id,$auth_url);
        require_code('site2');
        assign_refresh($auth_url,0.0);
        $echo = redirect_screen($title,$auth_url);
        $echo->evaluate_echo();
        return;
    }

    $code = get_param('code','');

    if ($code != '') {
        $post_params = array(
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => get_option($service_name . '_client_secret'),
            'redirect_uri' => static_evaluate_tempcode(build_url(array('page' => '_SELF'),'_SELF',null,false,false,true)),
            'grant_type' => 'authorization_code',
        );

        $result = http_download_file($endpoint . '/token',null,true,false,'ocPortal',$post_params);
        $parsed_result = json_decode($result);
        set_long_value($service_name . '_refresh_token',$parsed_result->refresh_token);

        $out = do_lang_tempcode('OAUTH_SUCCESS',$service_name);
    } else {
        $out = do_lang_tempcode('SOME_ERRORS_OCCURRED');
    }

    $title->evaluate_echo();

    $out->evaluate_echo();
}

function refresh_oauth2_token($service_name,$url,$client_id,$client_secret,$refresh_token,$endpoint)
{
    $post_params = array(
        'client_id' => get_option($service_name . '_client_id'),
        'client_secret' => get_option($service_name . '_client_secret'),
        'refresh_token' => get_long_value($service_name . '_refresh_token'),
        'grant_type' => 'refresh_token',
    );

    require_code('files');

    $result = http_download_file($endpoint . '/token',null,true,false,'ocPortal',$post_params);
    $parsed_result = json_decode($result);

    if (!array_key_exists('access_token',$parsed_result)) {
        warn_exit(do_lang_tempcode('ERROR_OBTAINING_ACCESS_TOKEN'));
    }

    return $parsed_result->access_token;
}

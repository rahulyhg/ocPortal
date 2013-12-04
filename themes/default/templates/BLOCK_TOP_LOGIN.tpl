<form title="{!_LOGIN}" onsubmit="if (check_field_for_blankness(this.elements['login_username'],event)) { disable_button_just_clicked(this); return true; } return false;" action="{LOGIN_URL*}" method="post" class="autocomplete inline top_login">
	<div class="inline">
		<div class="accessibility_hidden"><label for="s_login_username">{!USERNAME}{+START,IF,{$AND,{$OCF},{$CONFIG_OPTION,one_per_email_address}}} / {!EMAIL_ADDRESS}{+END}</label></div>
		<input maxlength="80" size="10" accesskey="l" class="field_input_non_filled" type="text" onfocus="placeholder_focus(this);" onblur="placeholder_blur(this);" value="{!USERNAME}" id="s_login_username" name="login_username" />
		<div class="accessibility_hidden"><label for="s_password">{!PASSWORD}</label></div>
		<input maxlength="255" size="10" type="password" placeholder="{!PASSWORD}" value="" name="password" id="s_password" />

		{+START,IF,{$CONFIG_OPTION,password_cookies}}
			<label class="accessibility_hidden" for="s_remember">{!REMEMBER_ME}</label>
			<input title="{!REMEMBER_ME}" {+START,IF,{$CONFIG_OPTION,remember_me_by_default}}checked="checked" {+END}{+START,IF,{$NOT,{$CONFIG_OPTION,remember_me_by_default}}}onclick="if (this.checked) { var t=this; window.fauxmodal_confirm('{!REMEMBER_ME_COOKIE;}',function(answer) { if (!answer) { t.checked=false; } }); }" {+END}type="checkbox" value="1" id="s_remember" name="remember" />
		{+END}

		<input class="menu__site_meta__user_actions__login button_screen_item" type="submit" value="{!_LOGIN}" />
	</div>

	<ul class="horizontal_links">
		{+START,IF_NON_EMPTY,{JOIN_URL}}<li><img alt="" src="{$IMG*,icons/24x24/menu/site_meta/user_actions/join}" srcset="{$IMG*,icons/48x48/menu/site_meta/user_actions/join} 2x" /> <a href="{JOIN_URL*}">{!_JOIN}</a></li>{+END}
		<li><img alt="" src="{$IMG*,icons/24x24/menu/site_meta/user_actions/login}" srcset="{$IMG*,icons/48x48/menu/site_meta/user_actions/login} 2x" /> <a onclick="return open_link_as_overlay(this);" rel="nofollow" href="{FULL_LOGIN_URL*}" title="{!MORE}: {!_LOGIN}">{!FULL_LOGIN}</a></li>
	</ul>
</form>

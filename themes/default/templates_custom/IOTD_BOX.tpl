<div class="box box___iotd_box"><div class="box_inner">
	{+START,IF,{GIVE_CONTEXT}}
		<h3>
			{+START,IF_NON_EMPTY,{I_TITLE}}{!CONTENT_IS_OF_TYPE,{!IOTD},{I_TITLE}}{+END}
			{+START,IF_EMPTY,{I_TITLE}}{!IOTD}{+END}
		</h3>
	{+END}

	{+START,IF_NON_EMPTY,{THUMB_URL}}
		<div class="right float_separation">
			 {+START,IF_NON_EMPTY,{VIEW_URL}}<a title="{I_TITLE*}" href="{VIEW_URL*}">{+END}<img alt="{!THUMBNAIL}" src="{THUMB_URL*}" />{+START,IF_NON_EMPTY,{VIEW_URL}}</a>{+END}
		</div>
	{+END}

	{+START,IF_PASSED,USERNAME}{+START,IF_NON_EMPTY,{USERNAME}}
		<div class="meta_details" role="contentinfo">
			<ul class="meta_details_list">
				<li>{!SUBMITTED_BY,<a href="{$MEMBER_PROFILE_URL*,{SUBMITTER}}">{$DISPLAYED_USERNAME*,{USERNAME}}</a>}</li>
			</ul>
		</div>
	{+END}{+END}

	{+START,IF_NON_EMPTY,{CAPTION}}
		<div class="associated_details">
			{$PARAGRAPH,{CAPTION}}
		</div>
	{+END}

	{+START,IF_PASSED,CHOOSE_URL}{+START,IF_PASSED,EDIT_URL}{+START,IF_PASSED,IS_CURRENT}
		<div style="margin-right: {$CONFIG_OPTION,thumb_width}px" class="buttons_group">
			{+START,IF,{$NOT,{IS_CURRENT}}}<form title="{!CHOOSE} {!IOTD} #{ID*}" class="inline" action="{CHOOSE_URL*}" method="post"><input type="hidden" name="id" value="{ID*}" /><input class="buttons__choose button_pageitem" type="submit" value="{!CHOOSE}" title="{!CHOOSE} {!IOTD} #{ID*}" /></form>{+END}
			<a class="buttons__edit button_pageitem" rel="edit" href="{EDIT_URL*}"><span>{!EDIT}: {!IOTD} #{ID*}</span></a>
			<form title="{!DELETE} {!IOTD} #{ID*}" onsubmit="var t=this; window.fauxmodal_confirm('{!ARE_YOU_SURE_DELETE=;}',function(answer) { if (answer) t.submit(); }); return false;" class="inline" action="{DELETE_URL*}" method="post"><input type="hidden" name="id" value="{ID*}" /><input class="menu___generic_admin__delete button_pageitem" type="submit" value="{!DELETE}" title="{!DELETE} {!IOTD} #{ID*}" /></form>
		</div>
	{+END}{+END}{+END}
</div></div>


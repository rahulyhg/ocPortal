{$,If we've finished a row of 4, start a new row}
{+START,IF,{$AND,{$NEQ,{I},0},{$EQ,{$REM,{I},4},0}}}
	</tr>
	<tr>
{+END}
{$SET,rand_donextitem,{$RAND}}
<td id="donext_item_{$GET*,rand_donextitem}" class="do_next_item" onclick="var as=this.getElementsByTagName('a'); var a=as[as.length-1]; click_link(a);" onkeypress="if (enter_pressed(event)) this.onclick(event);" onmouseout="this.className='do_next_item'; if (typeof window.doc_onmouseout!='undefined') doc_onmouseout();" onmouseover="this.className='do_next_item_hover'; if (typeof window.doc_onmouseover!='undefined') doc_onmouseover('{I2}');">
	{+START,IF_NON_EMPTY,{DOC}}<div id="doc_{I2}" style="display: none">{DOC}</div>{+END}

	<div>
		{+START,IF_EMPTY,{WARNING}}
			<a {+START,IF_PASSED,TARGET}target="{TARGET*}" {+END}onclick="cancelBubbling(event);" href="{LINK*}"><img class="do_next_icon" title="" alt="{$STRIP_TAGS*,{DESCRIPTION}}" src="{$IMG*,bigicons/{PICTURE*}}" /></a>
		{+END}
		{+START,IF_NON_EMPTY,{WARNING}}
			<a {+START,IF_PASSED,TARGET}target="{TARGET*}" {+END}onclick="cancelBubbling(event); var t=this; window.fauxmodal_confirm('{WARNING*;}',function(answer) { if (answer) click_link(t); }); return false;" href="{LINK*}"><img class="do_next_icon" title="" alt="{$STRIP_TAGS*,{DESCRIPTION}}" src="{$IMG*,bigicons/{PICTURE*}}" /></a>
		{+END}
	</div>

	<div>
		<a {+START,IF_PASSED,TARGET}target="{TARGET*}" {+END}onclick="cancelBubbling(event);" href="{LINK*}">{DESCRIPTION*}</a>
	</div>

	{+START,IF_PASSED,AUTO_ADD}
		<script type="text/javascript">// <![CDATA[
			addEventListenerAbstract(window,'load',function() {
				var as=document.getElementById('donext_item_{$GET*,rand_donextitem}').getElementsByTagName('a');
				for (var i=0;i<as.length;i++)
				{
					as[i].onclick=function(_this) {
						return function(event) {
							if (typeof event.preventDefault!='undefined') event.preventDefault();
							cancelBubbling(event);
							test=window.fauxmodal_confirm(
								'{!KEEP_ADDING_QUESTION;}',
								function(test)
								{
									if (test)
									{
										_this.href+=(_this.href.indexOf('?')!=-1)?'&':'?';
										_this.href+='{AUTO_ADD;}=1';
									}
									click_link(_this);
								}
							);
							return false;
						};
					} (as[i]);
				}
			} );
		//]]></script>
	{+END}
</td>

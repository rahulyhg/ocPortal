{$,Read the catalogue tutorial for information on custom catalogue layouts}

<div class="wide_table_wrap"><table summary="{!MAP_TABLE}" class="wide_table solidborder spaced_table">
	{+START,IF,{$NOT,{$MOBILE}}}
		<colgroup>
			<col style="width: 150px" />
			<col style="width: 100%" />
		</colgroup>
	{+END}

	<tbody>
		{FIELDS}
	</tbody>
</table></div>

<div class="float_surrounder">
	<p class="left">
		<a target="_self" href="#"><img class="inline_image" title="{!BACK_TO_TOP}" alt="{!BACK_TO_TOP}" src="{$IMG*,top}" /></a>
	</p>
	{+START,IF_NON_EMPTY,{VIEW_URL}}
		<p class="right">
			<a href="{VIEW_URL*}" title="{!VIEW}: {$STRIP_TAGS*,{FIELD_0}}"><img title="{!VIEW}{+START,IF_PASSED,COMMENT_COUNT} ({$STRIP_TAGS,{$COMMENT_COUNT,catalogues,{ID}}}){+END}" alt="{!COMMENTS} / {!VIEW}" class="button_pageitem" src="{$IMG*,pageitem/goto}" /></a>
		</p>
	{+END}
</div>

<br />

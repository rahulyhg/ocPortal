{$,Read the catalogue tutorial for information on custom catalogue layouts}

{$SET,EDIT_URL,{EDIT_URL}}

<tr>
	{FIELDS_TABULAR}
	{+START,IF_NON_EMPTY,{VIEW_URL}}
		<td>
			<!--VIEWLINK-->
			<a class="buttons__more button_pageitem" href="{VIEW_URL*}"><span>{!VIEW}</span></a>
		</td>
	{+END}
	{$, Uncomment to show ratings
	<td>
		{+START,IF_NON_EMPTY,{$TRIM,{RATING}}}
			{RATING}
		{+END}
		{+START,IF_EMPTY,{$TRIM,{RATING}}}
			{!UNRATED}
		{+END}
	</td>
	}
</tr>


<section class="box box___simple_preview_box"><div class="box_inner">
	{+START,IF_NON_EMPTY,{TITLE}}
		<h3>
			{TITLE`}
		</h3>
	{+END}

	{+START,IF_NON_EMPTY,{HTML}}
		{HTML`}
	{+END}

	<p class="shunted_button">
		<a class="buttons__goto button_pageitem" href="{URL*}"><span>{!VIEW}</span></a>
	</p>
</div></section>

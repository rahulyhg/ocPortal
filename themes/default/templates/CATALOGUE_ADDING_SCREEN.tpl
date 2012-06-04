{TITLE}

{TEXT}

<div class="required_field_warning"><span class="required_star">*</span> {!REQUIRED}</div>

<form title="{!PRIMARY_PAGE_FORM}" method="post" action="{URL*}" target="_top">
	<div>
		{HIDDEN}

		<div class="wide_table_wrap"><table summary="{!MAP_TABLE}" class="dottedborder wide_table scrollable_inside">
			<colgroup>
				<col style="width: 198px" />
				<col style="width: 100%" />
			</colgroup>

			<tbody>
				{FIELDS}
			</tbody>
		</table></div>

		<h2>{!FIELDS_NEW}</h2>
		<p>{!FIELDS_NEW_HELP}</p>
		{FIELDS_NEW}

		<script type="text/javascript">// <![CDATA[
			catalogue_field_change_watching();
		//]]></script>

		<br />
		<br />

		{+START,INCLUDE,FORM_STANDARD_END}{+END}
	</div>
</form>


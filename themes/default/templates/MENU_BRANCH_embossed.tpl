<li class="{$?,{CURRENT},current,non_current}">
	<a{+START,INCLUDE,MENU_LINK_PROPERTIES}{+END}{+START,IF,{LAST}} class="last"{+END}>{+START,IF_NON_EMPTY,{IMG}}<img alt="" src="{IMG*}" /> {+END}<span>{CAPTION}</span></a>
</li>
{CHILDREN}

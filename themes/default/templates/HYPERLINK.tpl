{+START,IF_NON_PASSED,REL}
	{$SET,lightbox,{$EQ,{$LCASE,{$SUBSTR,{URL},-4}},jpeg,.jpg,.gif,.png}}
{+END}

<a {+START,IF_PASSED,REL}rel="{REL*}" {+END}{+START,IF,{$GET,lightbox}}rel="lightbox" {+END}{+START,IF,{NEW_WINDOW}}target="_blank" {+END}{+START,IF,{$OR,{$IS_NON_EMPTY,{TITLE}},{NEW_WINDOW}}}title="{$STRIP_TAGS,{CAPTION}}{+START,IF_NON_EMPTY,{TITLE}}: {$STRIP_TAGS*,{TITLE},1}{+END}{+START,IF,{NEW_WINDOW}}: {!LINK_NEW_WINDOW}{+END}" {+END}{+START,IF_PASSED,ACCESSKEY}accesskey="{ACCESSKEY*}" {+END}href="{URL*}">{$TRIM,{CAPTION}}</a>
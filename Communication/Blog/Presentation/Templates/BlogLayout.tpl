{extends file=$EXTENSION_SETTINGS.LayoutTemplate}

{block name="PAGE_CONTENT"}
    <style>
        .BlogPostContentSection h1
        {
            margin: 0;
            padding: .5em 0 0 0;
        }
        .BlogPostContentSection .SubHeader
        {
            border-bottom: solid 1px #0074cc;
            margin-bottom: 1em;
        }
        .BlogPostMenuLayout
        {
            z-index: 900;
            position: fixed;
        }
        .BlogPostDescription
        {
            font-size: 1em;
        }
        .BlogPostMenuLayout .BlogPostSubMenu>li
        {
            padding: .5em;
        }
        .BlogBody
        {
            padding: 1em;
            border: solid 2px #dfdfdf;
            border-radius: 3px;
        }
        .BlogSearch
        {
            z-index: 900;
            position: fixed;
            right: 0px;
        }
        .BlogSearch form
        {
            margin: 16px 0;
            max-width: 100%;
        }
        .BlogSearch form>input
        {
            width: 100%;
        }
        .BlogPostContentSection header h1
        {
            margin: 0;
            padding: 0;
        }
    </style>
	<div class="Auto Layout Grid PageBodyContents">
		<div class="Row">
			<div class="BlogPostMenuLayout Spans2" data-ui-monitor data-ui-hdmonitor>
				<ul class="BlogPostSubMenu Menu" >
                    {block name="BlogLeftPanel" nocache}
                    <li>
                        <b class="Header">{$EXTENSION_SETTINGS.Title}</b><br>
                        <span class="Description">{$EXTENSION_SETTINGS.Description}</span>
                    </li>
                    <li><a rel="nofollow" href="{$EXTENSION_SETTINGS.BaseUrl}">&leftarrow; More Blog Posts</a></li>
                    {/block}
				</ul>
			</div>
			<div class="Spans8 Skips2 BlogBody">
				<div class="BlogPostContentSection">{block name="BlogPostContentSection"}{/block}</div>
            </div>
			<div class="Spans2 BlogSearch" data-ui-monitor data-ui-hdmonitor>
                {block name="BlogRightPanel"}
                <form action="Search" method="post" style="margin-top: 0;">
                    <input type="text" placeholder="Search Blog" name="Search" value="" onfocus="this.SelectAllText();" onmouseup="DblEj.EventHandling.Events.PreventDefaultEvent(event);" />
                </form>
                {/block}
			</div>
        </div>
    </div>
{/block}
{extends file=$EXTENSION_SETTINGS.LayoutTemplate}
{block name="PAGE_CONTENT"}
    <style>
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
        .BlogSearch
        {
            z-index: 900;
            position: fixed;
            right: 0px;
        }
        .BlogSearch form
        {
            margin: 16px 0;
        }
    </style>
	<div class="Auto Layout Grid PageBodyContents">
		<div class="Row">
			<div class="BlogPostMenuLayout Spans2">
				<ul class="BlogPostSubMenu Menu">
                    <li>
                        <b class="Header">{$EXTENSION_SETTINGS.Title}</b><br>
                        <span class="Description">{$EXTENSION_SETTINGS.Description}</span>
                    </li>
                    {nocache}
                    {repeater $ALL_POSTS as $SIDE_POST}
					<li><small>{$SIDE_POST->Get_PostDate()|date_format}</small><br><a href="{$EXTENSION_SETTINGS.BaseUrl}{$SIDE_POST->GetUrlTitle()}">{$SIDE_POST->Get_Title()}</a></li>
                    {/repeater}
                    {/nocache}
				</ul>
			</div>
            {ui name="FixedScroller" FixedElementSelector=".BlogPostMenuLayout" CelieingElementSelector="header" FloorElementSelector="footer"}
			<div class="Spans8 Skips2">
				<div class="BlogPostContentSection">
                    <header>
                        <h1>{$EXTENSION_SETTINGS.Title}</h1>
                        <div class="BlogPostDescription">{$EXTENSION_SETTINGS.Description}</div>
                    </header>


                    {nocache}
                    <h3>Tagged with <i>{$SEARCH}</i></h3>
                    {repeater $POSTS as $BLOG_POST}
                        <section class="Panel">
                            <h4><a href="{$EXTENSION_SETTINGS.BaseUrl}{$BLOG_POST->GetUrlTitle()}">{$BLOG_POST->Get_Title()}</a></h4>
                            <h5>Posted {$BLOG_POST->Get_PostDate()|date_format} by {$BLOG_POST->GetUser()->Get_DisplayName()}</h5>
                            {$BLOG_POST->Get_Contents()|strip_tags|truncate:200}
                            <br><a href="{$EXTENSION_SETTINGS.BaseUrl}{$BLOG_POST->GetUrlTitle()}">Read More...</a>
                            &emsp;
                            {repeater $BLOG_POST->GetMainTagsCrossReferencedByBlogPostTags() as $POST_TAG}
                                <span class="Tag Info">{$POST_TAG->Get_Tag()}</span>
                            {/repeater}
                        </section>
                    {/repeater}
                    {/nocache}
                </div>
            </div>
			<div class="Spans2" style="z-index: 900; position: fixed;right: 0px;">
                <form style="margin: 16px 0;" action="Search" method="post">
                    <input type="text" placeholder="Search Blog" name="Search" value="" onfocus="this.SelectAllText();" onmouseup="DblEj.EventHandling.Events.PreventDefaultEvent(event);" />
                </form>
                <div>
                {nocache}
                {repeater $ALL_TAGS as $POST_TAG}
                    <a href="{$EXTENSION_SETTINGS.BaseUrl}Tag?Tag={$POST_TAG->GetFieldValue($EXTENSION_SETTINGS.TagField)}"><span class="Tag Success">{$POST_TAG->GetFieldValue($EXTENSION_SETTINGS.TagField)}</span></a>&nbsp;
                {/repeater}
                {/nocache}
                </div>
			</div>
        </div>
    </div>
{/block}
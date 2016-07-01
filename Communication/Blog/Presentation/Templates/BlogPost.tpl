{extends file=$EXTENSION_SETTINGS.LayoutTemplate}
{block name="HTML_HEAD_PAGE_AREA" nocache}{$BLOG_POST->Get_Title()}{/block}
{block name="HTML_HEAD_PAGE_NAME" nocache}{$EXTENSION_SETTINGS.Title}{/block}
{block name="HTML_HEAD_PAGE_DESCRIPTION" nocache}{$BLOG_POST->Get_PostDate()|date_format} - {$BLOG_POST->Get_Title()} - {$EXTENSION_SETTINGS.Title} - {$EXTENSION_SETTINGS.Description}{/block}
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
                    {repeater $POSTS as $SIDE_POST}
					<li {if $SIDE_POST->Get_BlogPostId() == $BLOG_POST->Get_BlogPostId()}class="Selected"{/if}><small>{$SIDE_POST->Get_PostDate()|date_format}</small><br><a href="{$EXTENSION_SETTINGS.BaseUrl}{$SIDE_POST->GetUrlTitle()}">{$SIDE_POST->Get_Title()}</a></li>
                    {/repeater}
                    {/nocache}
				</ul>
			</div>
            {ui name="FixedScroller" FixedElementSelector=".BlogPostMenuLayout" CelieingElementSelector="header" FloorElementSelector="footer"}
			<div class="Spans8 Skips2">
				<div class="BlogPostContentSection">
                    {nocache}
                    <article itemscope itemtype="http://schema.org/BlogPosting">
                        <h1 itemprop="headline">{$BLOG_POST->Get_Title()}</h1>
                        <div class="SubHeader">
                            <i><small>Posted <span itemprop="dateCreated" datetime="{$BLOG_POST->Get_PostDate()|date_format:"Y-m-d"}">{$BLOG_POST->Get_PostDate()|date_format}</span> <span itemprop="author" itemscope itemtype="http://schema.org/Person">by <span itemprop="name">{$BLOG_POST->GetUser()->Get_DisplayName()}</span></span></small></i>
                        </div>
                        <div class="ArticleBody" itemprop="articleBody">{$BLOG_POST->Get_Contents()}</div>
                    </article>
                    {/nocache}
                </div>
            </div>
			<div class="Spans2 BlogSearch">
                <form action="Search" method="post">
                    <input type="text" placeholder="Search Blog" name="Search" value="" onfocus="this.SelectAllText();" onmouseup="DblEj.EventHandling.Events.PreventDefaultEvent(event);" />
                </form>
                {nocache}
                <div>
                {repeater $BLOG_POST->GetMainTagsCrossReferencedByBlogPostTags() as $POST_TAG}
                    <a href="{$EXTENSION_SETTINGS.BaseUrl}Tag?Tag={$POST_TAG->Get_Tag()}"><span class="Tag Success">{$POST_TAG->Get_Tag()}</span></a>&nbsp;
                {/repeater}
                </div>
                {/nocache}
			</div>
        </div>
    </div>
{/block}
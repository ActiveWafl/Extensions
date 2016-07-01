{extends file=$EXTENSION_SETTINGS.LayoutTemplate}

{block name="HTML_HEAD_PAGE_NAME"}{$EXTENSION_SETTINGS.Title}{/block}
{block name="HTML_HEAD_PAGE_DESCRIPTION"}{$EXTENSION_SETTINGS.Description}{/block}

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
        .BlogPostContentSection header h1
        {
            margin: 0;
            padding: 0;
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



                    {repeater $POSTS as $BLOG_POST}
                        <section class="Panel BlogPost">
                            <a id="{$BLOG_POST->GetUrlTitle()}" href="#{$BLOG_POST->GetUrlTitle()}" class="NavJump"></a>
                            <grid-layout auto>
                                <layout-row>
                                    <layout-cell spans="9">
                                        <h3><a href="{$EXTENSION_SETTINGS.BaseUrl}{$BLOG_POST->GetUrlTitle()}">{$BLOG_POST->Get_Title()}</a></h3>
                                    </layout-cell>
                                    <layout-cell spans="3" style="font-size: .85em;">
                                        Posted {$BLOG_POST->Get_PostDate()|date_format} in <a href="{$EXTENSION_SETTINGS.BaseUrl}Category?CategoryId={$BLOG_POST->GetBlogCategory()->Get_BlogCategoryId()}">{$BLOG_POST->GetBlogCategory()->Get_Title()}</a><br>
                                        <small>by {$BLOG_POST->GetUser()->Get_DisplayName()}</small>
                                    </layout-cell>
                                </layout-row>
                            </grid-layout>
                            {$BLOG_POST->Get_Contents()|strip_tags|truncate:500:""} <a href="{$EXTENSION_SETTINGS.BaseUrl}{$BLOG_POST->GetUrlTitle()}">continue reading...</a>
                        </section>
                    {/repeater}

                </div>
            </div>
			<div class="Spans2" style="z-index: 900; position: fixed; right: 0px;">
                <form style="margin: 16px 0;" action="Search" method="post">
                    <input type="text" placeholder="Search Blog" name="Search" value="" onfocus="this.SelectAllText();" onmouseup="DblEj.EventHandling.Events.PreventDefaultEvent(event);" />
                </form>
                <div class="BlogTags">
                    {repeater $ALL_TAGS as $POST_TAG}
                        <a href="{$EXTENSION_SETTINGS.BaseUrl}Tag?Tag={$POST_TAG->Get_Tag()}"><span class="Tag Success">{$POST_TAG->Get_Tag()}</span></a>&nbsp;
                    {/repeater}
                </div>
                <br>

                <header>Blog Categories</header>
                <ul class="BlogCategories">
                    {repeater $ALL_CATEGORIES as $BLOG_CATEGORY}
                    <li><a href="{$EXTENSION_SETTINGS.BaseUrl}Category?CategoryId={$BLOG_CATEGORY->Get_BlogCategoryId()}">{$BLOG_CATEGORY->Get_Title()}</a>
                    {/repeater}
                </ul>
			</div>
        </div>
    </div>
{/block}
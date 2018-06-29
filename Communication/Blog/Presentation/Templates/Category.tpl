{extends file="./BlogLayout.tpl"}
{block name="PAGE_TITLE" nocache}{$BLOG_CATEGORY->Get_Title()}{/block}
{block name="PAGE_DESCRIPTION" nocache}{$BLOG_CATEGORY->Get_Title()} {$EXTENSION_SETTINGS.Description}{/block}
{block name="PAGE_SECTION" nocache}{$EXTENSION_SETTINGS.Title}{/block}

{block name="BlogPostContentSection"}
    {nocache}
    <h1 style="margin: 0; padding: 0;">{$EXTENSION_SETTINGS.Title}</h1>
    <h3 style="padding-bottom: 1em;">{$BLOG_CATEGORY->Get_Title()}</h3>
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
            {$BLOG_POST->Get_Contents()|strip_tags|truncate:500:""}
            <br><a href="{$EXTENSION_SETTINGS.BaseUrl}{$BLOG_POST->GetUrlTitle()}">continue reading...</a>&emsp;
            {repeater $BLOG_POST->GetMainTagsCrossReferencedByBlogPostTags() as $POST_TAG}
                <span class="Tag Info">{$POST_TAG->Get_Tag()}</span>
            {/repeater}
        </section>
    {/repeater}

    {/nocache}
{/block}

{block name="BlogRightPanel" append}
{nocache}
<header>Blog Categories</header>
<ul class="BlogCategories">
    {repeater $ALL_CATEGORIES as $BLOG_CATEGORY}
    <li><a rel="nofollow" href="{$EXTENSION_SETTINGS.BaseUrl}Category?CategoryId={$BLOG_CATEGORY->Get_BlogCategoryId()}">{$BLOG_CATEGORY->Get_Title()}</a>
    {/repeater}
</ul>
{/nocache}
{/block}
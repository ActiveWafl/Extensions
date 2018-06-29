{extends file="./BlogLayout.tpl"}
{block name="HTML_HEAD_PAGE_NAME"}Search Results {$EXTENSION_SETTINGS.Title}{/block}
{block name="HTML_HEAD_PAGE_DESCRIPTION"}Search results in {$EXTENSION_SETTINGS.Description}{/block}
{block name="BlogPostContentSection"}
    {nocache}
    <header>
        <h1>{$EXTENSION_SETTINGS.Title}</h1>
        <div class="BlogPostDescription">{$EXTENSION_SETTINGS.Description}</div>
    </header>



    <h3>Search Results</h3>
    
    {if $SEARCH_ERROR}
    <div class="Notification">{$SEARCH_ERROR}</div>
    {else}
    <div class="SubHeader" style="margin-bottom: 1em;"><small>Search Term: {$SEARCH}</small></div>
    {/if}
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
    {/nocache}
{/block}
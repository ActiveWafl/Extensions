{extends file="./BlogLayout.tpl"}
{block name="HTML_HEAD_PAGE_NAME"}{$EXTENSION_SETTINGS.Title}{/block}
{block name="HTML_HEAD_PAGE_DESCRIPTION"}{$EXTENSION_SETTINGS.Description}{/block}
{block name="BlogLeftPanel"}
    <li>
        <b class="Header">{$EXTENSION_SETTINGS.Title}</b><br>
        <span class="Description">{$EXTENSION_SETTINGS.Description}</span>
    </li>
{/block}
{block name="BlogPostContentSection"}
{nocache}
    {repeater $POSTS as $BLOG_POST}
        <section class="Panel BlogPost" style="background-color: #ffffff; border: none; border-bottom: solid 1px #f5dede;">
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
            <div style="padding: .5em; font-size: .85em;">
                {$BLOG_POST->Get_Contents()|strip_tags|truncate:500:""} <a href="{$EXTENSION_SETTINGS.BaseUrl}{$BLOG_POST->GetUrlTitle()}">continue reading...</a>
            </div>
        </section>
    {/repeater}
{/nocache}
{/block}
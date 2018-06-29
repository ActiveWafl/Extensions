{extends file="./BlogLayout.tpl"}
{block name="PAGE_TITLE" nocache}{$BLOG_POST->Get_Title()}{/block}
{block name="PAGE_TAGS" nocache}{implode(", ", $BLOG_POST->GetMainTagsCrossReferencedByBlogPostTags())}{/block}
{block name="PAGE_DESCRIPTION" nocache}{$BLOG_POST->Get_PostDate()|date_format} - {$BLOG_POST->Get_Title()} - {$EXTENSION_SETTINGS.Title} - {$EXTENSION_SETTINGS.Description}{/block}
{block name="PAGE_URL" nocache}{$EXTENSION_SETTINGS.BaseUrl}{$BLOG_POST->GetUrlTitle()}{/block}
{block name="PAGE_SECTION" nocache}{$EXTENSION_SETTINGS.Title}{/block}
{block name="PAGE_PUBLISH_TIME" nocache}{date('c', $BLOG_POST->Get_PostDate())}{/block}
{block name="PAGE_MODIFY_TIME" nocache}{date('c',  $BLOG_POST->Get_PostDate())}{/block}

{block name="BlogLeftPanel" append}
    {nocache}
    {repeater $POSTS as $SIDE_POST}
    <li {if $SIDE_POST->Get_BlogPostId() == $SIDE_POST->Get_BlogPostId()}class="Selected"{/if}><small>{$SIDE_POST->Get_PostDate()|date_format}</small><br><a href="{$EXTENSION_SETTINGS.BaseUrl}{$SIDE_POST->GetUrlTitle()}">{$SIDE_POST->Get_Title()}</a></li>
    {/repeater}
    {/nocache}
{/block}

{block name="BlogPostContentSection"}
    {nocache}
    <article itemscope itemtype="http://schema.org/BlogPosting" style="margin: 0;">
        <h1 itemprop="headline" style="margin: 0; padding: 0;">{$BLOG_POST->Get_Title()}</h1>
        <div class="SubHeader">
            <i><small>Posted <span itemprop="dateCreated" datetime="{$BLOG_POST->Get_PostDate()|date_format:"Y-m-d"}">{$BLOG_POST->Get_PostDate()|date_format}</span> <span itemprop="author" itemscope itemtype="http://schema.org/Person">by <span itemprop="name">{$BLOG_POST->GetUser()->Get_DisplayName()}</span></span></small></i>
        </div>
        <div class="ArticleBody" itemprop="articleBody">{$BLOG_POST->Get_Contents()}</div>
    </article>
    {/nocache}
{/block}
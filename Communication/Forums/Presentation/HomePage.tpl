{foreach name=categoryLoop item=CATEGORY from=$ALL_CATEGORIES}
<div class="CategoryInfo">
    <div class="CategoryHeader">
        <div class="CategoryTitle">{$CATEGORY->Get_Title()}</div>
        <div>{$CATEGORY->Get_Description()}</div>
    </div>
    <table class="ForumsInfo">
        {foreach name=forumLoop item=FORUM from=$CATEGORY->GetChildForums()}
        <tr class="ForumInfo">
            <td class="Icon">
                <img src="" />
            </td>
            <td class="ForumTitleAndDescription">
                <div><a href="{$FORUM->GetPageLink()}">{$FORUM->Get_Title()}</a></div>
                <div>{$FORUM->Get_Description()}</div>
                <div>
                {foreach name=subforumLoop item=SUBFORUM from=$FORUM->GetChildForums()}
                    {if $smarty.foreach.subforumLoop.iteration == 1}Subforums: {else}, {/if}
                    {$SUBFORUM->Get_Title()}
                {/foreach}
                </div>
            </td>
            <td class="ThreadAndPostCount">
                <div>Threads: {$FORUM->Get_ThreadCount()}</div>
                <div>Posts: {$FORUM->Get_PostCount()}</div>
            </td>
            <td class="LastPostInfo">
                {$FORUM->Get_LastPostId()}
            </td>
        </tr>
        {/foreach}
    </table>
</div>
{/foreach}
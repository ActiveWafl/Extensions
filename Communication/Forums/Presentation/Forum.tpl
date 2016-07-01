{if isset($FORUM)}
<div>
<a href="{$FORUM->GetNewThreadLink()}">Post New Thread</a>
</div>
<div>
    <div id="ForumTitle">
        Forum: {$FORUM->Get_Title()}
    </div>
    <div id="ThreadPagination">
        Threads 1 to x of y
    </div>
</div>
<div>
{$FORUM->Get_Description()}
</div>
<div id="ForumTools">
    <div>Tools Menu</div>
    <div>Admin Menu</div>
    <div>Search Menu</div>
    <div>Mod Menu</div>
</div>
{foreach name=threadLoop item=THREAD from=$FORUM->GetChildThreads()}
<table class="ThreadTable" cellpadding="0" cellspacing="0">
    <tr class="Header">
        <th colspan="2" id="ThreadTableTitleHeader">Title / Thread Starter</th>
        <th>Replies / Views</th>
        <th colspan="2" id="ThreadTableLastPostHeader">Last Post By</th>
    </tr>
    <tr class="Row">
        <td class="Icon">Thread Icon</td>
        <td>
            <div>Title</div>
            <div>Thread Starter</div>
        </td>
        <td class="RepliesAndViews">
            <div>Replies</div>
            <div>Views</div>
        </td>
        <td class="LastPostInfo">
            <div>Username</div>
            <div>Date And Stuff</div>
        </td>
        <td class="ActionColumn">
            <input type="checkbox" />
        </td>
    </tr>
</table>
{/foreach}
{else}
Invalid forum specified
{/if}
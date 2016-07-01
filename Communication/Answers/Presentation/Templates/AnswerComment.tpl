{extends file="Master/MainLayout.tpl"}
{block name="PAGE_CONTENT"}
    {nocache}
        <div class="Comment" style="padding: 2em; margin: auto; max-width: 60em;">
            <div class="Small">{$COMMENT->Get_CommentDate()|localdate_format} by {$COMMENT->GetUsername()}</div>
            {$COMMENT->Get_Comment()}
        </div>
    {/nocache}
{/block}
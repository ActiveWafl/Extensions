{if isset($VALIDATION_ERRORS)}
    <div>
    {foreach name=validationErrorLoop item=ERROR_STRING from=$VALIDATION_ERRORS}
    Error: {$ERROR_STRING}
    {/foreach}
    </div>
{/if}
{if $CURRENT_USER->Get_UserId()}
    {if !isset($THREAD_ID)}
    Post New Thread
    Forum:
    {else}
    Reply to Thread
    Thread:
    {/if}

    <div class="NewPost">
        <div class="Title">Your Message</div>
        <div class="PostDetails">
            <form action="{$FORUM->GetNewThreadLink()}" method="post">
                {if isset($THREAD)}
                    <input type="hidden" name="ThreadId" id="ThreadId" value="{$THREAD->Get_ThreadId()}" />
                {else}
                    Title:
                    <input type="text" id="ThreadTitle" name="ThreadTitle" />
                {/if}
                {control name="WissyWig" inputname="PostText"  height="200px" style="margin-top: 20px;"}
                <div style="text-align: right">
                    <input type="submit" value="Submit New Thread" />
                </div>
                <input type="hidden" name="ForumId" id="ForumId" value="{$FORUM->Get_ForumId()}" />
                <input type="hidden" name="Action" id="Action" value="NewPost" />
            </form>
        </div>
    </div>
    uid: {$CURRENT_USER->Get_UserId()}
{else}
You must be logged in to post
{/if}
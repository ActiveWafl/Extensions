{extends file="Master/MainLayout.tpl"}
{block name="PAGE_CONTENT"}
    {nocache}
    <a href="./">&leftarrow; Back to Questions</a>
    <div style="padding: 2em; margin: auto; max-width: 60em;">
        <h2 style="padding-bottom: 1em;">{$QUESTION->Get_Question()}</h2>
        <div class="Question">
            <div class="QuestionDate">asked {$QUESTION->Get_DateAsked()|localdate_format} by {$QUESTION->GetUsername()}</div>
            {if $QUESTION->Get_Question() != $QUESTION->Get_Details()}
            <div class="QuestionContent">
                {$QUESTION->Get_Details()|replace:"  ":"&nbsp;&nbsp;"}
            </div>
            {/if}
        </div>
        <div class="Answers">
        {foreach $QUESTION->GetAnswers() as $ANSWER}
            <div class="Answer{if $ANSWER->Get_AnswerAccepted()} Accepted{/if}">
                {if !$QUESTION->GetIsAnswered() && ($QUESTION->Get_UserId() == $CURRENT_USER->Get_UserId())}
                    <div style="font-size: .85em; max-width: 20em; display: inline-block; float: right;"><a href="./Question?AnswerId={$ANSWER->Get_AnswerId()}&amp;QuestionId={$QUESTION->Get_QuestionId()}&amp;Action=MarkCorrect">Mark this answer correct</a></div>
                {/if}
                <div class="AnswerDate">answered {$ANSWER->Get_DateAnswered()|localdate_format} by {$ANSWER->GetUsername()}</div>
                <div class="AnswerContent">
                    {assign var="COMMENTS" value=$ANSWER->GetComments()}
                    {$ANSWER->Get_Answer()|replace:"  ":"&nbsp;&nbsp;"|nl2br}
                    <div class="Comments">
                        {if count($COMMENTS) > 0}
                            {foreach $ANSWER->GetComments() as $COMMENT}
                                {include file="./AnswerComment.tpl"}
                            {/foreach}
                        {/if}
                        {if !$REQUIRE_USERID || $CURRENT_USER->Get_UserId()}
                        <a class="AddCommentLink Comment" href="" onclick='$("QuestionCommentBox_{$QUESTION->Get_QuestionId()}").ToggleVertical(); return false;'>Add Comment</a>
                        {/if}
                    </div>
                    {if !$REQUIRE_USERID || $CURRENT_USER->Get_UserId()}
                    <div id="QuestionCommentBox_{$QUESTION->Get_QuestionId()}" class="QuestionCommentBox Auto Layout Grid" hidden>
                        <div class="Spans8">
                            <textarea class="NewComment" placeholder="Type your comments here"></textarea>
                            {if $REQUIRE_CAPTCHA}
                            <div>
                                {ui name="Captcha"}<br>
                                <input type="text" id="CaptchaCode" placeholder="Type the code you see in the box above" />
                            </div>
                            {/if}
                        </div>
                        <div class="Spans4">
                            <button onclick="PostAnswerComment({$ANSWER->Get_AnswerId()},$('NewComment').value,$('CaptchaCode').value,this);">Post Comment</button>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="SuccessMsg" hidden>
                        Your comment has been added
                    </div>
                </div>
                {/if}
            </div>
        {/foreach}
        </div>
        {if !$CLOSE_IF_ANSWERED || !$QUESTION->GetIsAnswered()}
        <div id="AnswerContainer">
            <h3>Your Answer</h3>
            <form action="./Question" method="post" id="AnswerForm">
                <div class="Auto Grid Layout">
                    <div class="Row">
                        <div class="Spans8">
                            <textarea id="Answer" name="Answer"></textarea>
                        </div>
                        <div class="Spans4" style="font-size: .85em;">
                            Enter your answer to this question.<br>
                            Be as detailed and thorough as possible.
                        </div>
                    </div>
                    {if $REQUIRE_CAPTCHA}
                    <div class="Row">
                        <div class="Skips2 Spans6">
                            <input type="text" name="CaptchaCode" placeholder="Human Verification" value="" />
                        </div>
                        <div class="Spans2" style="font-size: .85em;">
                            Enter the code you see in the box
                        </div>
                        <div class="Spans2">
                            {ui name="Captcha"}
                        </div>
                    </div>
                    {/if}
                    <div class="Row">
                        <div class="Spans12">
                            <button type="submit" class="Primary">Post Your Answer</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="QuestionId" value="{$QUESTION->Get_QuestionId()}" />
            </form>
        </div>
        {/if}
    </div>
    {/nocache}
{/block}
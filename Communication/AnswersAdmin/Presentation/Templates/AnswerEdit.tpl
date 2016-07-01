{extends file="Master/MainLayout.tpl"}
{block name="PAGE_CONTENT"}
    {nocache}
        <div style="padding: 2em; margin: auto; max-width: 60em;">
            <h3>Edit Question</h3>
            <form id="EditQuestionForm" action="./EditQuestion" method="post">
                <div class="Layout Grid Auto">
                    <div class="Row">
                        <div class="Skips1 Spans10">
                            <input style="width: 100%;" placeholder="Ask Your Question Here" type="text" name="Question" value="{$QUESTION->Get_Question()}" />
                        </div>
                    </div>
                    <div class="Row">
                        <div class="Skips1 Spans6">
                            <textarea style="width: 90%; height: 12em;" placeholder="Details about your question" name="QuestionDetails">{$QUESTION->Get_Details()}</textarea>
                        </div>
                        <div class="Spans4">
                            Write more detailed information about your question here.&nbsp;&nbsp;
                            Provide as many details as you can.
                        </div>
                    </div>
                    <div class="Row">
                        <div class="Skips1 Spans6">
                            <input placeholder="List, of, tags" style="width: 90%;" type="text" name="QuestionTags" value="{$QUESTION->Get_Tags()}" />
                        </div>
                        <div class="Spans4">
                            List of keywords to tag this question with, spearated by commas.
                        </div>
                    </div>
                    <div class="Row">
                        <div class="Skips1 Spans6">
                            <input type="text" name="CaptchaCode" placeholder="Human Verification" value="" />
                        </div>
                        <div class="Spans2">
                            Enter the code you see in the box
                        </div>
                        <div class="Spans2">
                            {ui name="Captcha"}
                        </div>
                    </div>
                </div>
                <div class="Align Centered" style="margin-top: 16px;">
                    {if $QUESTION->Get_QuestionId()}
                        <button type="submit">Update Question</button>
                    {else}
                        <button type="submit">Save Question</button>
                    {/if}
                    &nbsp;
                    <a class="Button" href="{$WEB_ROOT}Extensions/Communication/AnswersAdmin/">Cancel</a>
                </div>
                <input type="hidden" name="Action" value="SubmitQuestion" />
                <input type="hidden" name="QuestionId" value="{$QUESTION->Get_QuestionId()}" />
            </form>
        </div>
    {/nocache}
{/block}
{extends file="Master/MainLayout.tpl"}
{block name="PAGE_CONTENT"}
    {nocache}
        <div style="padding: 2em; margin: auto; max-width: 60em;">
        {if isset($NEW_QUESTION_SUBMITTED) && $NEW_QUESTION_SUBMITTED}
            <h3>Your Question Has Been Submitted</h3>
            <div>
                Our staff regularly monitor these questions.&nbsp;&nbsp;
                Your question will likely be answered by us or a member of the community soon.
                <div class="Small">*Before being made public, all questions are reviewed to ensure they meet <a href="">our support question guidelines</a></div>
            </div>
            <div style="margin-top: 8px;">
                You may also <a href="/ContactUs">contact us</a> if you need support.
            </div>
            <ul style="margin-top: 8px;">
                <li><a href="./">Return to support home</a></li>
                <li><a href="/">Go to the home page</a></li>
            </ul>
        {else}
            <h3>Edit Question</h3>
            <form id="EditQuestionForm" action="./EditQuestion" method="post">
                <div class="Layout Grid Auto">
                    <div class="Row">
                        <div class="Skips1 Spans10">
                            <input style="width: 100%;" placeholder="Ask Your Question Here" type="text" name="Question" value="{$QUESTION->Get_Question()}" required />
                        </div>
                    </div>
                    <div class="Row">
                        <div class="Skips1 Spans6">
                            <textarea style="height: 12em;" placeholder="Details about your question" name="QuestionDetails" required>{$QUESTION->Get_Details()}</textarea>
                        </div>
                        <div class="Spans4">
                            Write more detailed information about your question here.&nbsp;&nbsp;
                            Provide as many details as you can.
                        </div>
                    </div>
                    <div class="Row">
                        <div class="Skips1 Spans6">
                            <select name="QuestionCategoryId" required>
                                <option value="">Choose a category</option>
                                {foreach $QUESTION_CATEGORIES as $QUESTION_CATEGORY}
                                <option value="{$QUESTION_CATEGORY->Get_CategoryId()}" {if $QUESTION->Get_CategoryId() == $QUESTION_CATEGORY->Get_CategoryId()}selected{/if}>{$QUESTION_CATEGORY->Get_Title()}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="Spans4">
                            List of keywords to tag this question with, spearated by commas.
                        </div>
                    </div>
                    <div class="Row">
                        <div class="Skips1 Spans6">
                            <input placeholder="List, of, tags" type="text" name="QuestionTags" value="{$QUESTION->Get_Tags()}" />
                        </div>
                        <div class="Spans4">
                            List of keywords to tag this question with, spearated by commas.
                        </div>
                    </div>
                    {if $REQUIRE_CAPTCHA}
                    <div class="Row">
                        <div class="Skips1 Spans6">
                            <input type="text" name="CaptchaCode" placeholder="Human Verification" value="" required />
                        </div>
                        <div class="Spans2">
                            Enter the code you see in the box
                        </div>
                        <div class="Spans2">
                            {ui name="Captcha"}
                        </div>
                    </div>
                    {/if}
                </div>
                <div class="Align Centered" style="margin-top: 16px;">
                    {if $QUESTION->Get_QuestionId()}
                        <button type="submit">Update Question</button>
                    {else}
                        <button type="submit">Submit Question</button>
                    {/if}
                    &nbsp;
                    <a class="Button" href="{$WEB_ROOT}Extensions/Communication/Answers/">Cancel</a>
                </div>
                <input type="hidden" name="Action" value="SubmitQuestion" />
                <input type="hidden" name="QuestionId" value="{$QUESTION->Get_QuestionId()}" />
            </form>
        {/if}
        </div>
    {/nocache}
{/block}
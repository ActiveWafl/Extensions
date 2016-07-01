{extends file="Master/MainLayout.tpl"}
{block name="PAGE_CONTENT"}
    {nocache}
        <div style="padding: 2em; margin: auto; max-width: 60em;">
            <h3>Edit Question</h3>
            <form id="EditQuestionForm" action="./QuestionEdit" method="post">
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
                    </div>
                    <div class="Row">
                        <div class="Skips1 Spans6">
                            <input placeholder="List, of, tags" type="text" name="QuestionTags" value="{$QUESTION->Get_Tags()}" />
                        </div>
                        <div class="Spans4">
                            List of keywords to tag this question with, spearated by commas.
                        </div>
                    </div>
                </div>
                <div class="Align Centered" style="margin-top: 16px;">
                    {if $QUESTION->Get_QuestionId()}
                        {if !$QUESTION->Get_DateModerated()}
                            <a class="Button Success" href="QuestionEdit?Action=Approve&amp;QuestionId={$QUESTION->Get_QuestionId()}">Approve</a>
                            <a class="Button Warning" href="QuestionEdit?Action=Reject&amp;QuestionId={$QUESTION->Get_QuestionId()}">Reject</a>
                        {/if}
                        <button type="submit">Save</button>
                    {else}
                        <button type="submit">Save Question</button>
                    {/if}
                    <a class="Button" href="./">Cancel</a>
                </div>
                <input type="hidden" name="Action" value="SaveQuestion" />
                <input type="hidden" name="QuestionId" value="{$QUESTION->Get_QuestionId()}" />
            </form>
        </div>
    {/nocache}
{/block}
{extends file="Master/AdminLayout.tpl"}
{block name="PAGE_CONTENT"}
    <div id="ContentSection" style="padding: 2em; margin: auto; max-width: 60em;">
        <h1>Recent Questions</h1>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Question</th>
                    <th>Category</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {nocache}
                {foreach $QUESTIONS as $QUESTION}
                    <tr>
                        <td>{$QUESTION->Get_DateAsked()|datetime_format}</td>
                        <td>{$QUESTION->Get_Question()}</td>
                        <td>{if $QUESTION->Get_CategoryId()}{$QUESTION->GetCategory()->Get_Title()}{else}N/A{/if}</td>
                        <td>
                            <a class="Button Success" href="QuestionEdit?Action=Approve&amp;QuestionId={$QUESTION->Get_QuestionId()}">Approve</a>
                            <a class="Button Warning" href="QuestionEdit?Action=Reject&amp;QuestionId={$QUESTION->Get_QuestionId()}">Reject</a>
                            <a class="Button" href="QuestionEdit?QuestionId={$QUESTION->Get_QuestionId()}">Edit</a>
                        </td>
                    </tr>
                {/foreach}
                {/nocache}
            </tbody>
        </table>
        <a href="QuestionEdit" class="Button">New Question</a>
    </div>
{/block}
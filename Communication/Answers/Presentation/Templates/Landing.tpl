{extends file="Master/MainLayout.tpl"}
{block name="PAGE_CONTENT"}
    {nocache}
	<div class="Auto Grid Layout" id="AnswersMainDiv" style="padding: 2em; margin: auto; max-width: 60em;">
		<div class="Row">
			<div class="Spans12">
				<h1 class="Title">Answers to Common Questions</h1>
				<div id="AskQuestionPanel" {if $MODEL->GetFieldValue("SearchString")}hidden{/if}>
					<form method="post" action="./" onsubmit="if ($('SearchTextBox').value.trim() == '') {
								return false;
							}">
						<input type="hidden" name="Action" value="Search" />
						<input name="SearchText" id="SearchTextBox" 
							   type="text" 
							   style="width: 100%; height: 40px; font-size: 26px;" 
							   placeholder="Ask a new question..." />
					</form>
				</div>
			</div>
		</div>
		<div class="Row">
			<div class="Spans12">
				{if $MODEL->GetFieldValue("SearchString")}
					<h4>You asked</h4>
					<div style="font-size: 24px; line-height: 30px;">"{$MODEL->GetFieldValue("SearchString")}?"</div>
					{if count($MODEL->GetFieldValue("Questions")) > 0}
						<div class="Title" style="margin-top: 12px;">
						<b class="Small">{count($MODEL->GetFieldValue("Questions"))} questions match yours</b>
						</div>
					{/if}
				{else}
					<h4 style="margin-top: 1em;">Recent Questions</h4>
				{/if}
			</div>
		</div>
		{foreach $MODEL->GetFieldValue("Questions") as $QUESTION}
			<div class="Row">
				<div class="Spans2">
					<div style="width: 100px; height: 100px; border: solid 1px; text-align: center; line-height: 100px; background-color: {if $QUESTION->GetIsAnswered()}#D9EDF7{else}#FCF8E3{/if}">
						<a href="./Question?QuestionId={$QUESTION->Get_QuestionId()}">{$QUESTION->GetAnswerCount()} Answers</a>
					</div>
				</div>
				<div class="Spans10">
					{if $MODEL->GetFieldValue("SearchString")}
					<div class="Small">Match #{$QUESTION@iteration}</div>
					{/if}
					<h3><a href="./Question?QuestionId={$QUESTION->Get_QuestionId()}">{$QUESTION->Get_Question()}</a></h3>
					<div>
						{if $QUESTION->GetIsAnswered()}
							<a href="./Question?QuestionId={$QUESTION->Get_QuestionId()}">answered</a> {$QUESTION->GetDateAnswered()|localdate_format} by {$QUESTION->GetAnsweredByUserName()}
						{/if}
					</div>
					<div>
						{$QUESTION->Get_Details()|replace:"  ":"&nbsp;&nbsp;"|truncate:350}
					</div>
				</div>
			</div>
        {foreachelse}
            {if !$MODEL->GetFieldValue("SearchString")}
            No questions have been asked recently.
            {/if}
		{/foreach}
		{if $MODEL->GetFieldValue("SearchString")}
			<div class="Row">
				<div class="Spans12">
					<form method="post" action="./EditQuestion">
						{if count($MODEL->GetFieldValue("Questions")) > 0}
							<div class="Align Center">
								<h4>Don't see what you're looking for?</h4>
								<div>Submit it as a new question, and we'll email you as soon as it is answered.</div>
							</div>
						{else}
							<div class="Align Center">
								<h4>That question has not been asked/answered yet.</h4>
								<div>Submit it, and we'll email you as soon as it is answered.</div>
							</div>
						{/if}
						<input type="hidden" name="Action" value="StartQuestion" />
						<input type="hidden" name="Question" value="{$MODEL->GetFieldValue("SearchString")}" />
						<div class="Align Center">
							<button type="submit" class="Info" href="" style="margin-top: 12px;">Submit My Question</button>
							<div class="Small Align Left">
								<b>Notes</b>
								<ul>
									<li>You'll be able to edit your question before it is posted</li>
									<li>Before being made public, all questions are reviewed to ensure they meet <a href="">our support question guidelines</a>.</li>
								</ul>
							</div>
						</div>
					</form>
				</div>
			</div>
		{/if}
	</div>
    {/nocache}
{/block}
function PostAnswerComment(answerId,comment,captchaCode, btn)
{
	var responseObject = __("Support.PostAnswerComment",{ AnswerId: answerId, Comment:comment, CaptchaCode: captchaCode });
	var commentsContainer = btn.FindClosest(".Comments");
	if (IsDefined(responseObject.ResultString))
	{
		commentsContainer.Prepend(responseObject.ResultString);
		btn.FindClosest(".QuestionCommentBox").Hide();
		btn.FindClosest(".QuestionCommentBox textarea").value="";
		btn.FindClosest(".SuccessMsg").Show();
		setTimeout(
				function()
				{
					this.FindClosest(".SuccessMsg").FadeOut(.5);
				}.Bind(btn), 5000
			);
	} else {
		alert(responseObject.ErrorMessage);
		btn.FindClosest("img").src = "/CaptchaImage.gif#"+new Date().getTime();
	}
}
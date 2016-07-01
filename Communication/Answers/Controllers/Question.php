<?php
namespace Wafl\Extensions\Communication\Answers\Controllers;

use DblEj\Application\IMvcWebApplication,
	DblEj\Communication\Http\Request,
	DblEj\Communication\Http\Util,
	DblEj\Data\ArrayModel,
	DblEj\Mvc\ControllerBase;

class Question extends \DblEj\Extension\ExtensionControllerBase
{
	public function DefaultAction(Request $request, IMvcWebApplication $app)
	{
        $questionId = $request->GetInput("QuestionId");
        $newAnswerText = $request->GetInput("Answer");
        $captchaCode = $request->GetInput("CaptchaCode");
        $questionClass = $this->_extension->Get_QuestionClass();
        $question = new $questionClass($questionId);
        if ($newAnswerText && $this->Get_Extension()->AnswerQuestion($questionId, $newAnswerText, $captchaCode))
        {
            return $this->createRedirectUrlResponse("./Question?QuestionId=".$question->Get_QuestionId());
        }
        return $this->createResponseFromRequest($request, $app, new ArrayModel(["QUESTION"=>$question, "CLOSE_IF_ANSWERED"=>\Wafl\Extensions\Communication\Answers\Answers::Get_CloseAnswerQuestions(), "REQUIRE_USERID"=>\Wafl\Extensions\Communication\Answers\Answers::Get_RequireUserId(), "REQUIRE_CAPTCHA"=>\Wafl\Extensions\Communication\Answers\Answers::Get_RequireCaptcha()]));
    }
	public function MarkCorrect(Request $request, IMvcWebApplication $app)
	{
        $answerId = $request->GetInput("AnswerId");
        $questionClass = $this->_extension->Get_QuestionClass();
        $answerClass = $this->_extension->Get_AnswerClass();

        $answer = new $answerClass($answerId);
        $question=new $questionClass($answer->Get_QuestionId());
        if ((\Wafl\Core::$CURRENT_USER->Get_UserId() == $question->Get_UserId()))
        {
            if (!$question->GetIsAnswered())
            {
                \Wafl\UserFeedback::AppendInfo("You have updated the accepted answer.");
                $answer->Set_AnswerAccepted(true);
                $answer->Save();
            } else {
                \Wafl\Core::AppendError("Error Marking Answer", "This question has already been answered.");
            }
        } else {
            \Wafl\Core::AppendError("Error Marking Answer", "You are not authorized to mark this question correct.");
        }
        return $this->DefaultAction($request, $app);
    }
}
<?php
namespace Wafl\Extensions\Communication\Answers\Controllers;

use DblEj\Application\IMvcWebApplication,
	DblEj\Communication\Http\Request,
	DblEj\Communication\Http\Util,
	DblEj\Data\ArrayModel,
	DblEj\Mvc\ControllerBase,
	Wafl\Extensions\Communication\Answers\Models\FunctionalModel\Question;

class EditQuestion extends \DblEj\Extension\ExtensionControllerBase
{
	public function DefaultAction(Request $request, IMvcWebApplication $app)
	{
        $questionId = $request->GetInput("QuestionId");
        $questionClass = $this->_extension->Get_QuestionClass();

        $question = new $questionClass($questionId);
        return $this->createResponseFromRequest($request, $app, new ArrayModel(["QUESTION"=>$question, "REQUIRE_CAPTCHA"=>\Wafl\Extensions\Communication\Answers\Answers::Get_RequireCaptcha(), "QUESTION_CATEGORIES"=>\Wafl\Extensions\Communication\Answers\Models\FunctionalModel\Category::Filter(null, "Title")]));
    }
	public function StartQuestion(Request $request, IMvcWebApplication $app)
	{
        $questionClass = $this->_extension->Get_QuestionClass();

        $questionId = $request->GetInput("QuestionId");
        $questionText = $request->GetInput("Question");
        $question = new $questionClass($questionId);
        $question->Set_Question($questionText);
        return $this->createResponseFromRequest($request, $app, new ArrayModel(["QUESTION"=>$question, "REQUIRE_CAPTCHA"=>\Wafl\Extensions\Communication\Answers\Answers::Get_RequireCaptcha(), "QUESTION_CATEGORIES"=>\Wafl\Extensions\Communication\Answers\Models\FunctionalModel\Category::Filter(null, "Title")]));
    }
	public function SubmitQuestion(Request $request, IMvcWebApplication $app)
	{
        $captchaCode = $request->GetInput("CaptchaCode");
        $questionId = $request->GetInput("QuestionId");
        $questionText = $request->GetInput("Question");
        $questionDetails = $request->GetInput("QuestionDetails");
        $questionCatId = $request->GetInput("QuestionCategoryId");
        $questionTags = $request->GetInput("QuestionTags");
        $question = $this->Get_Extension()->AskQuestion($questionText, $questionDetails, $questionTags, $questionCatId, $questionId, $captchaCode);
        $dataModel = new ArrayModel(["QUESTION"=>$question, "REQUIRE_CAPTCHA"=>\Wafl\Extensions\Communication\Answers\Answers::Get_RequireCaptcha(), "QUESTION_CATEGORIES"=>\Wafl\Extensions\Communication\Answers\Models\FunctionalModel\Category::Filter(null, "Title")]);
        $dataModel->SetFieldValue("NEW_QUESTION_SUBMITTED", true);
        \Wafl\UserFeedback::AppendInfo("Your question has been submitted");
        return $this->createResponseFromRequest($request, $app, $dataModel);
    }
}
?>
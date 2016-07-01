<?php

namespace Wafl\Extensions\Communication\AnswersAdmin\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;

class QuestionEdit
extends \DblEj\Extension\ExtensionControllerBase
{
    public function DefaultAction(Request $request, IMvcWebApplication $app)
    {
        $questionId = $request->GetInput("QuestionId");
        $questionClass = $this->_extension->Get_QuestionClass();
        $categoryClass = $this->_extension->Get_CategoryClass();

        $question = new $questionClass($questionId);
        return $this->createResponseFromRequest
        (
            $request, $app, new \DblEj\Data\ArrayModel
            (
                [
                    "QUESTION" => $question,
                    "QUESTION_CATEGORIES" => $categoryClass::Filter(null, "Title")
                ]
            )
        );
    }

    public function SaveQuestion(Request $request, IMvcWebApplication $app)
    {
        $questionClass = $this->_extension->Get_QuestionClass();

        $questionId = $request->GetInput("QuestionId");
        $question = new $questionClass($questionId);
        if (!$question->Get_QuestionId())
        {
            $question->Set_DateAsked(time());
            $question->Set_UserId(\Wafl\Core::$CURRENT_USER->Get_UserId());

            $dupCount = $questionClass::Count("Title = '" . addslashes($request->GetInput("QuestionTitle")) . "' and CategoryId = " . $request->GetInput("QuestionCategory"));
            if ($dupCount)
            {
                \Wafl\UserFeedback::AppendError("Duplicate Question", "There is already a question with this title in this category.", "There cannot be multiple questions with the same title unless they are in different categories.");
                return $this->DefaultAction($request, $app);
            }
        }
        $question->Set_Question($request->GetInput("Question"));
        $question->Set_Details($request->GetInput("QuestionDetails"));
        $question->Set_CategoryId($request->GetInput("QuestionCategoryId"));
        $question->Save();
        $questionId = $question->Get_QuestionId();
        $request->SetInput("QuestionId", $questionId, Request::INPUT_POST);
        \Wafl\UserFeedback::AppendInfo("The questions has been saved");
        return $this->DefaultAction($request, $app);
    }
    public function Approve(Request $request, IMvcWebApplication $app)
    {
        $questionId = $request->GetInput("QuestionId");
        $this->Get_Extension()->ApproveQuestion($questionId);
        return $this->createRedirectUrlResponse("./?ApproveResult=1&amp;QuestionId=$questionId");
    }
    public function Reject(Request $request, IMvcWebApplication $app)
    {
        $questionId = $request->GetInput("QuestionId");
        $this->Get_Extension()->RejectQuestion($questionId);
        return $this->createRedirectUrlResponse("./?ApproveResult=0&amp;QuestionId=$questionId");
    }
}
<?php

namespace Wafl\Extensions\Communication\AnswersAdmin\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;

class AnswerEdit
extends \DblEj\Extension\ExtensionControllerBase
{
    public function DefaultAction(Request $request, IMvcWebApplication $app)
    {
        $answerId = $request->GetInput("AnswerId");
        $answerClass = $this->_extension->Get_AnswerClass();
        $categoryClass = $this->_extension->Get_AnswerClass();
        $answer = new $answerClass($answerId);
        return $this->createResponseFromRequest
        (
            $request, $app, new \DblEj\Data\ArrayModel
            (
                [
                    "QUESTION" => $answer->GetQuestion(),
                    "ANSWER" => $answer,
                    "ANSWERS_CATEGORIES" => $categoryClass::Filter(null, "Title")
                ]
            )
        );
    }

    public function SaveAnswer(Request $request, IMvcWebApplication $app)
    {
        $answerId = $request->GetInput("AnswerId");
        $answerClass = $this->_extension->Get_AnswerClass();

        $answer = new $answerClass($answerId);
        if (!$answer->Get_AnswerId())
        {
            $answer->Set_DateAnswered(time());
            $answer->Set_UserId(\Wafl\Core::$CURRENT_USER->Get_UserId());
        }
        $answer->Set_Answer($request->GetInput("Answer"));
        $answer->Set_IsApproved($request->GetInput("AnswerIsApproved")?true:false);
        $answer->Save();
        $answerId = $answer->Get_AnswerId();
        $request->SetInput("AnswerId", $answerId, Request::INPUT_POST);
        \Wafl\UserFeedback::AppendInfo("The answer has been saved");
        return $this->DefaultAction($request, $app);
    }
    public function Approve(Request $request, IMvcWebApplication $app)
    {
        \Wafl\UserFeedback::AppendInfo("The answer has been approved");
        return $this->DefaultAction($request, $app);
    }
    public function Reject(Request $request, IMvcWebApplication $app)
    {
        \Wafl\UserFeedback::AppendInfo("The answer has been rejected");
        return $this->DefaultAction($request, $app);
    }
}
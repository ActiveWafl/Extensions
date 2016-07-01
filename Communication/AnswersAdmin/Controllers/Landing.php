<?php

namespace Wafl\Extensions\Communication\AnswersAdmin\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;

class Landing
extends \DblEj\Extension\ExtensionControllerBase
{
    public function DefaultAction(Request $request, IMvcWebApplication $app)
    {
        if ($request->GetInput("ApproveResult") == "1")
        {
            \Wafl\UserFeedback::AppendInfo("The question has been approved");
        }
        elseif ($request->GetInput("ApproveResult") == "0")
        {
            \Wafl\UserFeedback::AppendInfo("The question has been rejected");
        }

        $questionClass = \Wafl\Extensions\Communication\AnswersAdmin\AnswersAdmin::Get_QuestionClass();
        return $this->createResponseFromRequest
        (
            $request, $app, new \DblEj\Data\ArrayModel
            (
                [
                    "QUESTIONS"=>$questionClass::Filter("DateModerated is null", "DateAsked desc", 20)
                ]
            )
        );
    }
}
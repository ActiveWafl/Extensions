<?php

namespace Wafl\Extensions\Communication\AnswersAdmin;

use DblEj\Extension\ExtensionBase;
use DblEj\Extension\DependencyCollection;

class AnswersAdmin extends ExtensionBase
{
	private static $_tablePrefix;
	private static $_sitePages;
    private static $_autoInstall;
    private static $_questionClass = "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Question";
    private static $_answerClass = "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Answer";
    private static $_commentClass = "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\AnswerComment";
    private static $_categoryClass = "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Category";

    const EVENT_QUESTION_MODERATED = "EVENT_QUESTION_MODERATED";

	public function Initialize(\DblEj\Application\IApplication $app)
	{
        $questionClass = self::$_questionClass;
        $answerClass = self::$_answerClass;
        $commentClass = self::$_commentClass;
        $categoryClass = self::$_categoryClass;

        $questionClass::Set_Extension($this);
        $answerClass::Set_Extension($this);
        $commentClass::Set_Extension($this);
        $categoryClass::Set_Extension($this);
	}

    public function ApproveQuestion($questionId)
    {
        $question = new self::$_questionClass($questionId);
        $question->Set_DateModerated(time());
        $question->Set_IsApproved(true);
        $question->Save();

        $this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_QUESTION_MODERATED, $question, \Wafl\Core::$CURRENT_USER));
    }

    public function RejectQuestion($questionId)
    {
        $question = new self::$_questionClass($questionId);
        $question->Set_DateModerated(time());
        $question->Set_IsApproved(false);
        $question->Save();

        $this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_QUESTION_MODERATED, $question, \Wafl\Core::$CURRENT_USER));
    }

    public static function TranslateUrl(\DblEj\Communication\Http\Request $request)
    {
        $extensionPath = "/Extensions/Communication/AnswersAdmin/";
        $extensionPathLength = strlen($extensionPath);
        if (strlen($request->Get_RequestUrl()) >= strlen($extensionPath))
        {
            if (substr($request->Get_RequestUrl(), 0, strlen($extensionPath)) == $extensionPath)
            {
                $url = substr($request->Get_RequestUrl(), $extensionPathLength);
                if ($url == "")
                {
                    $request->Set_RequestUrl($extensionPath."Landing");
                }
            }
        }
        return $request;
    }
	protected static function getAvailableSettings()
	{
		return array("TablePrefix", "AutoInstall", "QuestionClass", "AnswerClass", "CommentClass", "CategoryClass");
	}


	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
			case "TablePrefix":
				self::$_tablePrefix = $settingValue;
				break;
			case "AutoInstall":
                self::$_autoInstall = $settingValue;
				break;
            case "QuestionClass":
                self::$_questionClass = $settingValue;
				break;
            case "AnswerClass":
                self::$_answerClass = $settingValue;
				break;
            case "CommentClass":
                self::$_commentClass = $settingValue;
				break;
            case "CategoryClass":
                self::$_categoryClass = $settingValue;
				break;
		}
	}
    protected function getLocalSettingValue($settingName)
    {
		switch ($settingName)
		{
			case "TablePrefix":
				return self::$_tablePrefix;
				break;
			case "AutoInstall":
                return self::$_autoInstall;
				break;
            case "QuestionClass":
                return self::$_questionClass;
				break;
            case "AnswerClass":
                return self::$_answerClass;
				break;
            case "CommentClass":
                return self::$_commentClass;
				break;
            case "CategoryClass":
                return self::$_categoryClass;
				break;
		}
    }

	public function Get_RequiresInstallation()
	{
		 return self::$_autoInstall?(!\Wafl\Core::$STORAGE_ENGINE->DoesLocationExist(self::$_tablePrefix."Questions")):false;
	}

	public static function Get_DatabaseInstallScripts()
	{
		return array(realpath(__DIR__.DIRECTORY_SEPARATOR."Config/CreateTables.sql"));
	}

	public static function Get_DatabaseInstalledTables()
	{
		return array(
			self::$_tablePrefix."Answers",
			self::$_tablePrefix."AnswerComments",
			self::$_tablePrefix."Questions",
			self::$_tablePrefix."Categories"
		);
	}
    public static function Get_AutoInstall()
    {
        return self::$_autoInstall;
    }
	public static function Get_Dependencies()
	{
		$depends = new DependencyCollection();
		return $depends;
	}
    public static function Get_QuestionClass()
    {
        return self::$_questionClass;
    }
    public static function Get_AnswerClass()
    {
        return self::$_answerClass;
    }
    public static function Get_CategoryClass()
    {
        return self::$_categoryClass;
    }
    public static function Get_CommentClass()
    {
        return self::$_commentClass;
    }

	public static function Get_GlobalScripts()
	{
		return array();
	}

	public static function Get_GlobalStylesheets()
	{
		return array(
		);
	}

	public static function Get_SitePages()
	{
        if (self::$_sitePages == null)
        {
            self::$_sitePages = array();
            self::$_sitePages["Landing"] = new \DblEj\Extension\ExtensionSitePage("Answers.Admin.LandingPage", "", "Communication/AnswersAdmin/Presentation/Templates/Landing.tpl");
            self::$_sitePages["QuestionEdit"] = new \DblEj\Extension\ExtensionSitePage("Answers.Admin.QuestionEdit", "", "Communication/AnswersAdmin/Presentation/Templates/QuestionEdit.tpl");
            self::$_sitePages["AnswerEdit"] = new \DblEj\Extension\ExtensionSitePage("Answers.Admin.AnswerEdit", "", "Communication/AnswersAdmin/Presentation/Templates/AnswerEdit.tpl");
        }
        return self::$_sitePages;
	}

	public static function Get_TablePrefix()
	{
		return self::$_tablePrefix;
	}

	public function Get_IsReady()
	{
		return true;
	}

	public function GetSettingDefault($settingName)
	{
        switch ($settingName)
        {
            case "TablePrefix":
                return "";
            case "AutoInstall":
                return true;
            case "QuestionClass":
                return "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Question";
				break;
            case "AnswerClass":
                return "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Answer";
				break;
            case "CommentClass":
                return "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Comment";
				break;
            case "CategoryClass":
                return "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Category";
				break;
        }
	}

	public function GetRaisedEventTypes()
	{
        return new \DblEj\EventHandling\EventTypeCollection(
        [
            "EVENT_QUESTION_MODERATED"   => self::EVENT_QUESTION_MODERATED
        ]);
    }
    public static function Get_WebOnly()
    {
        return true;
    }
}
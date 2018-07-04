<?php

namespace Wafl\Extensions\Communication\Answers;

use DblEj\Extension\ExtensionBase;
use DblEj\Extension\DependencyCollection;

class Answers extends ExtensionBase
implements \DblEj\Communication\Integration\IAnswerGiverExtension
{
	private static $_tablePrefix;
    private static $_requireCaptcha;
    private static $_requireUserId;
    private static $_closeAnsweredQuestions;
	private static $_sitePages;
    private static $_autoInstall;
    private static $_questionClass = "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Question";
    private static $_answerClass = "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Answer";
    private static $_commentClass = "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\AnswerComment";
    private static $_categoryClass = "\\Wafl\\Extensions\\Communication\\Answers\\Models\\FunctionalModel\\Category";

    const EVENT_QUESTION_ASKED = "EVENT_QUESTION_ASKED";
    const EVENT_QUESTION_ANSWERED = "EVENT_QUESTION_ANSWERED";

	private $_parentApp;
	public function Initialize(\DblEj\Application\IApplication $app)
	{
		if (is_a($app,"\DblEj\Application\IWebApplication"))
		{
            $this->_parentApp = $app;
            $questionClass = self::$_questionClass;
            $answerClass = self::$_answerClass;
            $commentClass = self::$_commentClass;
            $categoryClass = self::$_categoryClass;

            $questionClass::Set_Extension($this);
            $answerClass::Set_Extension($this);
            $commentClass::Set_Extension($this);
            $categoryClass::Set_Extension($this);
		}
	}

	protected static function getAvailableSettings()
	{
		return array("TablePrefix", "RequireCaptcha", "RequireUserId", "CloseAnsweredQuestions", "AutoInstall", "QuestionClass", "AnswerClass", "CommentClass", "CategoryClass");
	}

    public static function Get_RequireCaptcha()
    {
        return self::$_requireCaptcha;
    }
    public static function Get_RequireUserId()
    {
        return self::$_requireUserId;
    }
    public static function Get_AutoInstall()
    {
        return self::$_autoInstall;
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
	protected function ConfirmedConfigure($settingName, $settingValue)
	{
		switch ($settingName)
		{
			case "TablePrefix":
				self::$_tablePrefix = $settingValue;
				break;
			case "RequireCaptcha":
				self::$_requireCaptcha = $settingValue;
				break;
			case "RequireUserId":
				self::$_requireUserId = $settingValue;
				break;
            case "CloseAnsweredQuestions":
                self::$_closeAnsweredQuestions = $settingValue;
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
	public function PrepareSitePage($pageName)
	{
		parent::PrepareSitePage($pageName);
	}
    public static function Get_CloseAnswerQuestions()
    {
        return self::$_closeAnsweredQuestions;
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

	public static function Get_Dependencies()
	{
		$depends = new DependencyCollection();
		return $depends;
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
            self::$_sitePages["Landing"] = new \DblEj\Extension\ExtensionSitePage("AnswersLandingPage", "", "Communication/Answers/Presentation/Templates/Landing.tpl");
            self::$_sitePages["EditQuestion"] = new \DblEj\Extension\ExtensionSitePage("EditQuestion", "", "Communication/Answers/Presentation/Templates/EditQuestion.tpl");
            self::$_sitePages["Question"] = new \DblEj\Extension\ExtensionSitePage("Question", "", "Communication/Answers/Presentation/Templates/Question.tpl");
        }
        return self::$_sitePages;
	}

    public static function TranslateUrl(\DblEj\Communication\Http\Request $request)
    {
        $extensionPath = "/Extensions/Communication/Answers/";
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
                elseif (substr($url, 0, 7) == "Search?" || $url == "Search")
                {
                    $request->Set_RequestUrl($extensionPath."Search");
                }
                elseif (substr($url, 0, 4) == "Tag?" || $url == "Tag")
                {
                    $request->Set_RequestUrl($extensionPath."Tag");
                }
                elseif (substr($url, 0, 9) == "Category?" || $url == "Category")
                {
                    $request->Set_RequestUrl($extensionPath."Category");
                }
                elseif (substr($url, 0, 9) == "Question/")
                {
                    $questionTitle = substr($url, 9);
                    $questionTitle = preg_replace("/[^A-Za-z0-9]/", "-", $questionTitle); //sanitize to protect against injection
                    $questionTitle = substr($questionTitle, 32);
                    $question = Models\FunctionalModel\Question::FilterFirst("Question = '$questionTitle'");
                    if (!$question)
                    {
                        throw new \Exception("Invalid question");
                    }
                    $request->Set_RequestUrl($extensionPath."Question");
                    $request->SetInput("QuestionId", $question->Get_BlogPostId());
                }
            }
        }
        return $request;
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
			case "AutoInstall":
                return true;
				break;
			case "TablePrefix":
                return "";
				break;
			case "RequireCaptcha":
                return false;
				break;
			case "RequireUserId":
                return true;
				break;
            case "CloseAnsweredQuestions":
                return true;
                break;
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
            default:
                return null;
		}
	}

	public function GetRaisedEventTypes()
	{
        return new \DblEj\EventHandling\EventTypeCollection(
        [
            "EVENT_QUESTION_ASKED"   => self::EVENT_QUESTION_ASKED,
            "EVENT_QUESTION_ANSWERED" => self::EVENT_QUESTION_ANSWERED
        ]);
    }

    public static function Get_WebOnly()
    {
        return true;
    }

    /**
     * Submit a question
     *
     * @param string $questionText The question heading
     * @param string $questionDetails More details to the question
     * @param string $questionTags Tags that are relevent to the question, comma delimited
     * @param int $questionCatId
     * @param int $questionId Default = null.  If updating an existing question, pass this in
     * @param string $captchaCode Default = null
     * @return \Wafl\Extensions\Communication\Answers\Models\FunctionalModel\Question
     * @throws Exception
     */
    public function AskQuestion($questionText, $questionDetails, $questionTags, $questionCatId, $questionId = null, $captchaCode = null)
    {
        $question = new self::$_questionClass($questionId);
        $question->Set_Question($questionText);
        $question->Set_Details($questionDetails);
        $question->Set_Tags($questionTags);
        $question->Set_CategoryId($questionCatId);
        if (!self::Get_RequireCaptcha() || \Wafl\Util\Captcha::Authenticate($captchaCode))
        {
            if (!self::Get_RequireUserId() || \Wafl\Core::$CURRENT_USER->Get_UserId())
            {
                if (!$question->Get_QuestionId())
                {
                    $question->Set_DateAsked(time());
                    $question->Set_UserId(\Wafl\Core::$CURRENT_USER->Get_UserId());
                } elseif ($question->Get_UserId() != \Wafl\Core::$CURRENT_USER->Get_UserId()) {
                    throw new \Wafl\Exceptions\Exception("You cannot edit someone else's question", E_WARNING, null, "You cannot edit someone else's question");
                }
                $question->Set_IsApproved(0);
                $question->Save();
                $this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_QUESTION_ASKED, $question, \Wafl\Core::$CURRENT_USER));
            }
        } else {
            \Wafl\UserFeedback::AppendError("Human Verification Error", "Invalid human verification code entered");
        }
        return $question;
    }

    public function AnswerQuestion($questionId, $newAnswerText, $captchaCode = null)
    {
        $newAnswer = null;
        if (!\Wafl\Extensions\Communication\Answers\Answers::Get_RequireCaptcha() || \Wafl\Util\Captcha::Authenticate($captchaCode))
        {
            if (!\Wafl\Extensions\Communication\Answers\Answers::Get_RequireUserId() || \Wafl\Core::$CURRENT_USER->Get_UserId())
            {
                $question = new self::$_questionClass($questionId);
                $newAnswer = new self::$_answerClass();
                $newAnswer->Set_Answer(strip_tags($newAnswerText,"<a><b><i><b><p><br><h1><h2><h3><h4><h5><ol><ul><li>"));
                $newAnswer->Set_AnswerAccepted(0);
                $newAnswer->Set_UpVotes(0);
                $newAnswer->Set_DownVotes(0);
                $newAnswer->Set_DateAnswered(time());
                $newAnswer->Set_UserId(\Wafl\Core::$CURRENT_USER->Get_UserId());
                $newAnswer->Set_QuestionId($question->Get_QuestionId());
                $newAnswer->Save();
                $this->raiseEvent(new \DblEj\EventHandling\EventInfo(self::EVENT_QUESTION_ANSWERED, $newAnswer, \Wafl\Core::$CURRENT_USER));
            } else {
                \Wafl\UserFeedback::AppendError("Not Authorized", "You must be logged in to answer a question");
            }
        } else {
            \Wafl\UserFeedback::AppendError("Human Verification Error", "Invalid human verification code entered");
        }
        return $newAnswer;
    }
}
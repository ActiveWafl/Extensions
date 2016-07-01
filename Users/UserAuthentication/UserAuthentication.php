<?php

namespace Wafl\Extensions\Users\UserAuthentication;

use DblEj\Application\IApplication,
    DblEj\Communication\Http\Util,
    DblEj\EventHandling\Events,
    DblEj\Extension\ExtensionBase,
    DblEj\Extension\DependencyCollection,
    Exception,
    Wafl\Extensions\Users\UserAuthentication\SignonHandlers\Database,
    Wafl\Extensions\Users\UserAuthentication\SignonHandlers\ISignonHandler,
    Wafl\UserFeedback,
    Wafl\Util\Captcha;

class UserAuthentication extends ExtensionBase
implements \DblEj\Authentication\Integration\IUserAuthenticatorExtension
{
    private $_application;
    private static $_actionRequestVariable				= "UserAuthAction";
    private static $_actionRequestVariableNewUserValue	= "NewAccount";
    private static $_actionRequestVariableLogoutValue	= "Logout";
    private static $_logoutRedirect						= null;
    private static $_formField1                         = "EmailAddress";
    private static $_formField2                         = "Password";
    private static $_formField3                         = "ConfirmPassword";
    private static $_signonHandlerIndexFormField        = "SignonHandlerIndex";
    private static $_requiresCaptcha					= false;
    private static $_userClass                          = null;
    /**
     *
     * @var ISignonHandler
     */
    private static $_signonHandlers                     = [];
    private static $_didNewUserSignup                   = false;
    public function __construct()
    {
        parent::__construct();
        self::Set_LanguageFileClassname("\\Wafl\\Extensions\\Users\\UserAuth\\Conf\\Lang\\en_us");
        if (count(self::$_signonHandlers) == 0)
        {
            self::$_signonHandlers["Database"] = new SignonHandlers\Database(); //need to add all handlers?
            //self::$_signonHandlers["Facebook"] = new \Wafl\Extensions\Users\UserAuthentication\SignonHandlers\Facebook();
            //and so on...
        }
    }

    public static function DidNewUserSignup()
    {
        return self::$_didNewUserSignup;
    }
    public function Initialize(IApplication $app)
    {
        $this->_application = $app;
        if (AM_WEBPAGE)
        {
            foreach (self::$_signonHandlers as $signonHandler)
            {
                $signonHandler->Initialize($app);
            }

            Events::AddHandler(\DblEj\Util\SystemEvents::BEFORE_TEMPLATE_ENGINE_INITIALIZE, "LoadUser", $this);
        }
    }

    public static function AddSignonHandler($name, ISignonHandler $customHandler)
    {
        self::$_signonHandlers[$name] = $customHandler;
    }

    protected static function getAvailableSettings()
    {
        $mysettings =
        array(
            "FormField1",
            "FormField2",
            "FormField3",
            "ActionRequestVariable",
            "ActionRequestVariableNewUserValue",
            "ActionRequestVariableLogoutValue",
            "SignonHandlerIndexRequestVariable",
            "RequiresCaptcha",
            "LogoutRedirect",
            "UserClass");

        $handlerSettings = [];
        foreach (self::$_signonHandlers as $signonHandler)
        {
            $handlerSettings = array_merge($handlerSettings,$signonHandler->Get_RequiredSettings(),$signonHandler->Get_OptionalSettings());
        }

        return array_merge($mysettings,$handlerSettings);
    }

    protected function ConfirmedConfigure($settingName, $settingValue)
    {
        try
        {
            switch ($settingName)
            {
                case "RequiresCaptcha":
                    self::$_requiresCaptcha = $settingValue;
                    break;
                case "ActionRequestVariable":
                    self::$_actionRequestVariable = $settingValue;
                    break;
                case "ActionRequestVariableNewUserValue":
                    self::$_actionRequestVariableNewUserValue = $settingValue;
                    break;
                case "ActionRequestVariableLogoutValue":
                    self::$_actionRequestVariableLogoutValue = $settingValue;
                    break;
                case "FormField1":
                    self::$_formField1 = $settingValue;
                    break;
                case "FormField2":
                    self::$_formField2 = $settingValue;
                    break;
                case "FormField3":
                    self::$_formField3 = $settingValue;
                    break;
                case "LogoutRedirect":
                    self::$_logoutRedirect = $settingValue;
                    break;
                case "SignonHandlerIndexRequestVariable":
                    self::$_signonHandlerIndexFormField = $settingValue;
                    break;
                case "UserClass":
                    self::$_userClass = $settingValue;
            }

            foreach (self::$_signonHandlers as $signonHandler)
            {
                if (array_search($settingName,$signonHandler->Get_OptionalSettings())!==false || array_search($settingName,$signonHandler->Get_RequiredSettings())!==false)
                {
                    $signonHandler->Configure($settingName, $settingValue);
                }
            }
        }
        catch (\Exception $err)
        {
            throw new Exception("Could not set specified setting.  An exception occurred: " . $err->getMessage());
        }
    }

    static function Get_DatabaseInstalledTables()
    {
        return array();
    }

    public function LoadUser()
    {
        $action			= Util::GetInput(self::$_actionRequestVariable, \DblEj\Communication\Http\Request::INPUT_REQUEST);
        $proceedLogin	= true;
        $field1Value    = Util::GetInput(self::$_formField1, \DblEj\Communication\Http\Request::INPUT_REQUEST);
        $field2Value    = Util::GetInput(self::$_formField2, \DblEj\Communication\Http\Request::INPUT_REQUEST);
        $field3Value    = Util::GetInput(self::$_formField3, \DblEj\Communication\Http\Request::INPUT_REQUEST);
        $signonHandlerIdx  = Util::GetInput(self::$_signonHandlerIndexFormField);
        if ($signonHandlerIdx)
        {
            $signonHandler = self::$_signonHandlers[$signonHandlerIdx];
        } else {
            $signonHandler = reset(self::$_signonHandlers);
        }

        if ($action == self::$_actionRequestVariableLogoutValue)
        {
            $proceedLogin = false;
            $this->Logout($signonHandler);
        }
        elseif ($action == self::$_actionRequestVariableNewUserValue)
        {
            try
            {
                if (self::$_requiresCaptcha)
                {
                    $verifyCaptcha = Util::GetInput("VerifyCaptcha");
                    if (!Captcha::Authenticate($verifyCaptcha))
                    {
                        throw new Exception("Invalid human verification string");
                    }
                }

                $signonFields = $signonHandler->Get_SignonFields();
                if (isset($signonFields[0]))
                {
                    $signonFieldValues[$signonFields[0]] = $field1Value;
                }
                if (isset($signonFields[1]))
                {
                    $signonFieldValues[$signonFields[1]] = $field2Value;
                }
                if (isset($signonFields[2]))
                {
                    $signonFieldValues[$signonFields[2]] = $field3Value;
                }
                if (!$signonHandler->SignUp($signonFieldValues))
                {
                    throw new \Exception("The sign-on handlers rejected the new account.");
                } else {
                    self::$_didNewUserSignup = true;
                }
            }
            catch (\Exception $err)
            {
                UserFeedback::AppendError("There was an error creating the new account.", $err->getMessage() , "Please correct the errors and try again.");
                $proceedLogin = false;
            }
        }
        if ($proceedLogin)
        {
            $this->Login($signonHandler,$field1Value,$field2Value,$field3Value);
        }
    }

    public static function Login(ISignonHandler $signonHandler, $field1Value, $field2Value=null, $field3Value=null)
    {

        try
        {
            $signonFields = $signonHandler->Get_SignonFields();
            $signonFieldValues=[];
            if (isset($signonFields[0]) && $field1Value)
            {
                $signonFieldValues[$signonFields[0]] = $field1Value;
            }
            if (isset($signonFields[1]) && $field2Value)
            {
                $signonFieldValues[$signonFields[1]] = $field2Value;
            }
            if (isset($signonFields[2]) && $field3Value)
            {
                $signonFieldValues[$signonFields[2]] = $field3Value;
            }
            if (count($signonFieldValues)>0)
            {
                $signonResult = $signonHandler->SignOn($signonFieldValues, session_id());
                $this->_application->StoreSessionData("CurrentSessionTokens", $signonResult);
            }
            if ($this->_application->GetSessionData("CurrentSessionTokens"))
            {
                $userId = $signonHandler->GetUserIdentifier($this->_application->GetSessionData("CurrentSessionTokens"));
                if (self::$_userClass)
                {
                    \Wafl\Core::$CURRENT_USER = new self::$_userClass($userId);
                }
            }
        }
        catch (\Exception $err)
        {
            throw new Exception("Could not login the current user due to an error with the UserAuthentication extension. See Inner Exception", null, $err);
        }
        $tokens = $this->_application->GetSessionData("CurrentSessionTokens");
        return $tokens;
    }

    public static function Logout(ISignonHandler $signonHandler)
    {
		$tokens = $this->_application->GetSessionData("CurrentSessionTokens");
        $signonHandler->SignOff($tokens);
        $this->_application->DeleteSessionData("CurrentSessionTokens");
        if (self::$_logoutRedirect)
        {
            Util::HeaderRedirect(self::$_logoutRedirect,true);
        }
    }

    public function PrepareSitePage($pagename)
    {

    }

    public function Get_RequiresInstallation()
    {
        return false;
    }

    public static function Get_Dependencies()
    {
        $depends = new DependencyCollection();
//		$depends->AddDependency("UserAuth", \DblEj\Extension\Dependency::TYPE_EXTENSION, "LoginForm",
//						  \DblEj\Extension\Dependency::TYPE_CONTROL);
//		$depends->AddDependency("UserAuth", \DblEj\Extension\Dependency::TYPE_EXTENSION, "MicroLoginForm",
//						  \DblEj\Extension\Dependency::TYPE_CONTROL);
        return $depends;
    }

    public static function Get_SitePages()
    {
        return Array();
    }

    public static function Get_GlobalStylesheets()
    {
        return array();
    }

    public static function Get_GlobalScripts()
    {
        return array();
    }

    public static function Get_DatabaseInstallScripts()
    {
        return [];
    }

    public static function Set_DatabaseInstallScripts($installScripts)
    {

    }

    public function GetRaisedEventTypes()
    {
        return array();
    }

}
?>
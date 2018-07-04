<?php

namespace Wafl\Extensions\Users\UserAuth;

use DblEj\Communication\Http\Util;
use DblEj\Extension\ExtensionBase;
use DblEj\Extension\DependencyCollection;
use Exception;
use Wafl\Core;
use Wafl\Extensions\Users\UserAuth\FunctionalModel\User;
use Wafl\Extensions\Users\UserAuth\FunctionalModel\UserGroup;
use Wafl\Util\Captcha;

class UserAuth extends ExtensionBase implements \DblEj\Authentication\Integration\IUserAuthenticatorExtension
{
	private static $_tablePrefix;
	private static $_userClass;
	private static $_userGroupClass;
	private static $_userGroupTable						 = "UserGroups";
	private static $_userGroupField						 = "UserGroupId";
	private static $_sessionClass;
	private static $_sessionIdField						 = "SessionId";
	private static $_newUserGroupId;
	private static $_allowTableOverwrite				 = false;
	private static $_requiresCaptcha					 = false;
	private static $_usernameRequestVariable			 = "EmailAddress";
	private static $_passwordRequestVariable			 = "Password";
	private static $_password2RequestVariable			 = "ConfirmPassword";
	private static $_actionRequestVariable				 = "UserAuthAction";
	private static $_actionRequestVariableNewUserValue	 = "NewAccount";
	private static $_usernameColumn						 = ["EmailAddress"];
	private static $_usernameLabel						 = "email address";
    private static $_resurrectableAccountField           = null;
    private static $_resurrectableAccountValue           = null; //accounts with _resurrectableAccountColumn=_resurrectableAccountValue can already exist and still be registered
	private static $_otherRequiredFormFields			 = array();
	private static $_initializedWithCustomUserClass		 = false;
	private static $_initializedWithCustomUserGroupClass = false;
	private static $_initializedWithCustomSessionClass	 = false;
	private static $_logoutRedirect						 = null;
    private static $_didNewUserSignup                   = false;
    private static $_autoInstall;

	public function Initialize(\DblEj\Application\IApplication $app)
	{
		if (AM_WEBPAGE)
		{
			if (self::$_userClass)
			{
				self::$_initializedWithCustomUserClass = true;
			}
			else
			{
				self::$_userClass = "\\Wafl\\Extensions\\Users\\UserAuth\\FunctionalModel\\User";
			}

            if (!method_exists(self::$_userClass, "GetGuestUser"))
            {
                throw new \Exception("Your custom user class must implement the static method ".self::$_userClass."::GetGuestUser");
            }
			if (self::$_userGroupClass)
			{
				self::$_initializedWithCustomUserGroupClass = true;
			}
			else
			{
				self::$_userGroupClass = "\\Wafl\\Extensions\\Users\\UserAuth\\FunctionalModel\\UserGroup";
			}
			if (self::$_sessionClass)
			{
				self::$_initializedWithCustomSessionClass = true;
			}
			else
			{
				self::$_sessionClass = "\\Wafl\\Users\\FunctionalModel\\Session";
			}
            self::Set_LanguageFileClassname("\\Wafl\\Extensions\\Users\\UserAuth\\Conf\\Lang\\en_us");

			if ($this->Get_RequiresInstallation())
			{ //normally this is handled by wafl, but this is special extension because it handles core functionality (user system)
				$this->InstallData(Core::$STORAGE_ENGINE);
				Core::$STORAGE_ENGINE->UpdateStorageLocations();
			}

            \DblEj\Util\SystemEvents::AddSystemHandler(\DblEj\Util\SystemEvents::AFTER_SESSION_START, array($this, "LoadUser"));
			User::SetAppVariables(self::$_usernameColumn, self::$_usernameLabel,
								  self::$_userGroupTable, self::$_userGroupField, self::$_sessionIdField, self::$_sessionClass, self::$_resurrectableAccountField, self::$_resurrectableAccountValue);
		}
	}

    public static function Set_SessionClass($sessionClass)
    {
        self::$_sessionClass = $sessionClass;
    }
    public static function DidNewUserSignup()
    {
        return self::$_didNewUserSignup;
    }

	protected static function getAvailableSettings()
	{
		return array(
			"TablePrefix",
			"UserClassName",
			"UserGroupClassName",
			"UserGroupTable",
			"UserGroupField",
			"UsernameLabel",
			"SessionClassName",
			"SessionIdField",
			"NewUserGroupId",
			"UsernameFormField",
			"PasswordFormField",
			"ConfirmPasswordFormField",
			"UsernameColumn",
			"OtherRequiredFields",
			"ActionRequestVariable",
			"ActionRequestVariableNewUserValue",
			"AllowTableOverwrite",
			"RequiresCaptcha",
			"LogoutRedirect",
            "ResurrectableAccountField",
            "ResurrectableAccountValue",
            "AutoInstall");
	}

	public static function Get_TablePrefix()
	{
		return self::$_tablePrefix;
	}

    public static function Get_LogoutRedirect()
    {
        return self::$_logoutRedirect;
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
				case "UserGroupTable":
					self::$_userGroupTable = $settingValue;
					break;
				case "UserGroupField":
					self::$_userGroupField = $settingValue;
					break;
				case "ActionRequestVariable":
					self::$_actionRequestVariable = $settingValue;
					break;
				case "ActionRequestVariableNewUserValue":
					self::$_actionRequestVariableNewUserValue = $settingValue;
					break;
				case "OtherRequiredFields":
					self::$_otherRequiredFormFields = is_array($settingValue)?$settingValue:array($settingValue);
					break;
				case "UsernameFormField":
					self::$_usernameRequestVariable = $settingValue;
					break;
				case "PasswordFormField":
					self::$_passwordRequestVariable = $settingValue;
					break;
				case "SessionIdField":
					self::$_sessionIdField = $settingValue;
					break;
				case "ConfirmPasswordFormField":
					self::$_password2RequestVariable = $settingValue;
					break;
				case "UsernameColumn":
					self::$_usernameColumn = is_array($settingValue)?$settingValue:array($settingValue);
					break;
				case "UsernameLabel":
					self::$_usernameLabel = $settingValue;
					break;
				case "TablePrefix":
					self::$_tablePrefix = $settingValue;
					break;
				case "UserClassName":
					self::$_userClass = $settingValue;
					break;
				case "UserGroupClassName":
					self::$_userGroupClass = $settingValue;
					break;
				case "SessionClassName":
					self::$_sessionClass = $settingValue;
					break;
				case "NewUserGroupId":
					self::$_newUserGroupId = $settingValue;
					break;
				case "AllowTableOverwrite":
					self::$_allowTableOverwrite = $settingValue;
					break;
				case "LogoutRedirect":
					self::$_logoutRedirect = $settingValue;
					break;
				case "ResurrectableAccountField":
					self::$_resurrectableAccountField = $settingValue;
					break;
				case "ResurrectableAccountValue":
					self::$_resurrectableAccountValue = $settingValue;
					break;
                case "AutoInstall":
                    self::$_autoInstall = $settingValue;
                    break;
			}
		}
		catch (\Exception $err)
		{
			throw new \Exception("Could not set specified setting.  An exception occurred: " . $err->getMessage());
		}
	}

	static function Get_DatabaseInstalledTables()
	{
		if (self::$_allowTableOverwrite)
		{
			return array();
		}
		else
		{
			return array(
				self::$_tablePrefix . "Users",
				self::$_tablePrefix . "Sessions",
				self::$_tablePrefix . "UserGroups");
		}
	}

	public function LoadUser()
	{
		if (Core::$STORAGE_ENGINE->IsReady())
		{
			$action			 = \Wafl\RequestVar(self::$_actionRequestVariable);
			$userClass		 = self::$_userClass;
			$proceedLogin	 = true;
			if ($action == self::$_actionRequestVariableNewUserValue)
			{
				try
				{
					if (self::$_requiresCaptcha)
					{
						$verifyCaptcha = \Wafl\RequestVar("VerifyCaptcha");
						if (!Captcha::Authenticate($verifyCaptcha))
						{
							throw new \Exception("Invalid human verification string");
						}
					}
					$email		 = \Wafl\RequestVar(self::$_usernameRequestVariable);
					$password1	 = \Wafl\RequestVar(self::$_passwordRequestVariable);
					$password2	 = \Wafl\RequestVar(self::$_password2RequestVariable);
					//check user
                    $userWhereArray = [];
                    foreach (self::$_usernameColumn as $usernameCol)
                    {
                        $userWhereArray[] = "$usernameCol='" . Core::$STORAGE_ENGINE->EscapeString($email) . "'";
                    }
                    $userWhere = implode(" or ", $userWhereArray);
					$dupEmailUser = $userClass::FilterFirst($userWhere);
					if ($dupEmailUser)
					{
                        if (self::$_resurrectableAccountField && self::$_resurrectableAccountValue)
                        {
                            $fieldMethod = "Get_".self::$_resurrectableAccountField;
                            if ($dupEmailUser->$fieldMethod() != self::$_resurrectableAccountValue)
                            {
                                $proceedLogin = false;
                                throw new \Exception("There is already a registered user with that ".self::$_usernameLabel);
                            }
                        } else {
                            $proceedLogin = false;
                            throw new \Exception("There is already a registered user with that ".self::$_usernameLabel);
                        }
					}
					if (self::$_password2RequestVariable)
					{
						if ($password1 != $password2)
						{
							$proceedLogin = false;
							throw new \Exception("The passwords do not match");
						}
					}

                    $otherFieldValues = [];
					foreach (self::$_otherRequiredFormFields as $requiredField)
					{
						if (!isset($_REQUEST[$requiredField]))
						{
							$proceedLogin = false;
							throw new \Exception("$requiredField is a required field");
						} else {
                            $otherFieldValues[$requiredField] = $_REQUEST[$requiredField];
                        }
					}

					if (self::$_newUserGroupId == null)
					{
						throw new \Exception("To use the UserAuth extension you must specify the NewUserGroupId in the extension&apos;s settings");
					}
					$newUser = $userClass::RegisterNewUser($email, $password1, self::$_newUserGroupId, $otherFieldValues);
					if ($newUser)
					{
						$userClass::Login($email, $password1, false);
                        self::$_didNewUserSignup = true;
					}
					else
					{
						$proceedLogin = false;
						throw new \Exception("Unknown error when trying to UserAuth::LoadUser");
					}
				}
				catch (\Exception $err)
				{
                    Core::$CURRENT_USER = new $userClass();
					\Wafl\UserFeedback::AppendError("There was an error creating the new account.", $err->getMessage() , "Please correct the errors and try again.");
					$proceedLogin = false;
				}
			}

			if ($proceedLogin)
			{
				$email		 = isset($_REQUEST[self::$_usernameRequestVariable]) ? $_REQUEST[self::$_usernameRequestVariable] : null;
				$password	 = isset($_REQUEST[self::$_passwordRequestVariable]) ? $_REQUEST[self::$_passwordRequestVariable] : null;
				$this->Login($email, $password);
			}
			if ($action == "Logout")
			{
				$this->Logout();
			}
			$userGroupClass	 = self::$_userGroupClass;
			$allUserGroups	 = $userGroupClass::Filter();
			$defaultGroup = null;
			foreach ($allUserGroups as $group)
			{
				if (!$defaultGroup && (($group->Get_UserGroupId() == self::$_newUserGroupId) || !self::$_newUserGroupId))
				{
					$defaultGroup = $group;
				}
				UserGroup::RegisterUserGroup($group);
			}
			if ($defaultGroup && !Core::$CURRENT_USER->Get_UserGroup())
			{
				Core::$CURRENT_USER->Set_UserGroup($defaultGroup);
			}

            \DblEj\Data\PersistableModel::SetCurrentUser(Core::$CURRENT_USER);

		}
		else
		{
			\Wafl\UserFeedback::AppendError("Login System Failure","Could not initialize login system because the data engine is not ready");
		}
	}

	public function Login($username, $password)
	{
        $userClass		 = self::$_userClass;
		try
		{
			if ($username && $password)
			{
				$loggedInUser = $userClass::Login($username, $password, true);
			}
			else
			{
				$loggedInUser = $userClass::LoginBySession();
			}

			if ($loggedInUser)
			{
				Core::$CURRENT_USER = $loggedInUser;
			}
			elseif (($username !== null) && $password !== null)
			{
				Core::$CURRENT_USER = new $userClass();
			}
			else
			{
				Core::$CURRENT_USER = new $userClass();
			}
            \DblEj\Data\PersistableModel::SetCurrentUser(Core::$CURRENT_USER);
		}
		catch (\Exception $err)
		{
            Core::$CURRENT_USER = new $userClass();
			throw new \Exception("Could not login the current user due to an error with UserAuth. See Inner Exception", null, $err);
		}
	}

	public function Logout($killSessions = true)
	{
		if (Core::$CURRENT_USER->Get_UserId())
		{
			Core::$CURRENT_USER->Logout($killSessions);
		}
	}

	public function PrepareSitePage($pagename)
	{

	}
    public static function Get_AutoInstall()
    {
        return self::$_autoInstall;
    }

	public function Get_RequiresInstallation()
	{
        if (self::$_autoInstall)
        {
            if (!Core::$STORAGE_ENGINE)
            {
                throw new \Exception("Cannot run the UserAuth extension without at least one DataStorage configured");
            }
            $returnVal = false;
            if (!self::$_initializedWithCustomUserClass)
            {
                $returnVal = !Core::$STORAGE_ENGINE->DoesLocationExist(self::$_tablePrefix . "Users");
            }
            if (!$returnVal)
            {
                if (!self::$_initializedWithCustomUserGroupClass)
                {
                    $returnVal = !Core::$STORAGE_ENGINE->DoesLocationExist(self::$_tablePrefix . "UserGroups");
                }
            }
            if (!$returnVal)
            {
                if (!self::$_initializedWithCustomSessionClass)
                {
                    $returnVal = !Core::$STORAGE_ENGINE->DoesLocationExist(self::$_tablePrefix . "Sessions");
                }
            }
        } else {
            $returnVal = false;
        }
		return $returnVal;
	}

	public static function Get_Dependencies()
	{
		$depends = new DependencyCollection();
		$depends->AddDependency("UserAuth", \DblEj\Extension\Dependency::TYPE_EXTENSION, "Sessions", \DblEj\Extension\Dependency::TYPE_EXTENSION);
		$depends->AddDependency("UserAuth", \DblEj\Extension\Dependency::TYPE_EXTENSION, "\\DblEj\\Data\\Integration\\IDatabaseServerExtension", \DblEj\Extension\Dependency::TYPE_EXTENSION_INTERFACE);
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
        if (is_a(\Wafl\Core::$STORAGE_ENGINE,"\\Wafl\\Extensions\\Storage\\SqlServer"))
        {
            return array(__DIR__."/CreateTables.mssql");
        } else {
            return array(__DIR__."/CreateTables.sql");
        }
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
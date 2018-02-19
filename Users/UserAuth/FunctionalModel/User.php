<?php

namespace Wafl\Extensions\Users\UserAuth\FunctionalModel;

abstract class User extends \Wafl\Extensions\Users\UserAuth\DataModel\User {

    private $_isloggedIn = false;

	private static $_sessionFieldName;
	private static $_emailAddressOrUsernameField;
	private static $_emailAddressOrUsernameLabel;
	private static $_userGroupsTableName;
	private static $_userGroupIdField;
	private static $_sessionClassName;
    private static $_ressurectableField;
    private static $_ressurectableVal;

	public static function SetAppVariables($emailAddressOrUsernameField=["EmailAddress"],$emailAddressOrUsernameLabel="email address",
									$userGroupsTableName="UserGroups", $userGroupIdField="UserGroupId", $sessionFieldName="SessionId", $sessionClassName="\\Wafl\\Users\\FunctionalModel\\Session", $resurrectableCol = null, $ressurectableVal = null)
	{

        if (!is_array($emailAddressOrUsernameField))
        {
            $emailAddressOrUsernameField = [$emailAddressOrUsernameField];
        }
        self::$_emailAddressOrUsernameField = $emailAddressOrUsernameField;
        self::$_emailAddressOrUsernameLabel = $emailAddressOrUsernameLabel;
        self::$_sessionFieldName = $sessionFieldName;
        self::$_userGroupIdField = $userGroupIdField;
        self::$_userGroupsTableName = $userGroupsTableName;
        self::$_sessionClassName = $sessionClassName;
        self::$_ressurectableField = $resurrectableCol;
        self::$_ressurectableVal = $ressurectableVal;
        \Wafl\Extensions\Users\UserAuth\UserAuth::Set_SessionClass($sessionClassName);
    }
    public static function RegisterNewUser($emailAddressOrUsername, $authorizationKey = null, $groupId = null, $otherFieldValues = null) {
        $subclass = get_called_class();

        $userWhereArray = [];
        foreach (self::$_emailAddressOrUsernameField as $usernameCol)
        {
            $userWhereArray[] = "$usernameCol='" . \Wafl\Core::$STORAGE_ENGINE->EscapeString($emailAddressOrUsername) . "'";
        }
        $userWhere = implode(" or ", $userWhereArray);

        $dupuser = $subclass::FilterFirst($userWhere);

        if ($dupuser)
        {
            if (self::$_ressurectableField && self::$_ressurectableVal)
            {
                $fieldMethod = "Get_".self::$_ressurectableField;
                if ($dupuser->$fieldMethod() != self::$_ressurectableVal)
                {
                    throw new \Exception("Cannot register new user because another user already exists with the same ".self::$_usernameLabel);
                }
            } else {
                throw new \Exception("Cannot register new user because another user already exists with the same ".self::$_usernameLabel);
            }
        }

        $newUser = $subclass::CreateNewUserObject($emailAddressOrUsername, $authorizationKey, $groupId, $otherFieldValues);
        $newUser->Set_EmailAddressOrUsername($emailAddressOrUsername);
        $newUser->Set_UserGroupId($groupId);
        $newUser->Set_LastLogin(time());
        $newUser->UpdatePassword($authorizationKey);
        return $newUser;
    }

    public static function CreateNewUserObject($emailAddressOrUsername, $authorizationKey = null, $groupId = null, $otherFields = null)
    {
        $subclass = get_called_class();
        return new $subclass();
    }

    public static function LoginBySession() {
        $app = \Wafl\Core::$RUNNING_APPLICATION;
        $session = $app->GetCurrentSession();

        if (!$session)
        {
            throw new \Exception("The user session could not be initialized");
        }
        $subclass = get_called_class();
        if ($session->Get_UserId()) {
            $user = new $subclass($session->Get_UserId());
        } else {
            $user = new $subclass();
        }
        self::SetCurrentLoggedInUser($user);
        return $user;
    }

    public function UpdatePassword($newPassword) {
		$this->Set_AuthorizationKey(password_hash($newPassword, PASSWORD_DEFAULT));
        $this->Save();
    }

    public function Get_Username() {
        return $this->_emailAddress;
    }

    public function Set_Username($newUsername) {
        $this->_emailAddress = $newUsername;
    }

    public function Set_AuthorizationKey($newAuthorizationKey) {
        $this->_passwordHash = $newAuthorizationKey;
    }

    /**
     * Logout the user
     * @param boolean If the session should be permanently killed and never allowed to be reused
     */
    public function Logout($killSession = true) {
        $app = \Wafl\Core::$RUNNING_APPLICATION;
        $session = $app->GetCurrentSession();
        $session->End($killSession);
        $subclass = get_called_class();
		\Wafl\Core::$CURRENT_USER = $subclass::GetInstance();
        $this->_isloggedIn = false;

        $redirect = \Wafl\Extensions\Users\UserAuth\UserAuth::Get_LogoutRedirect();
        if ($redirect)
        {
            \DblEj\Communication\Http\Util::HeaderRedirect($redirect,true);
        }
    }

    /**
     * Attempt to athenticate this user with the provided password.
     *
     * @param string $password The password to authorize
     * @return int
     * <1 = not authenticated
     * >1 = authenticated
     * Specifically:
     * -1 = not authenticated, hash is outdated (rehash recommended)
     * 0 = not authenticated, hash is up-to-date
     * 1 = authenticated, hash is up-to-date
     * 2 = authenticated, hash is outdated (rehash recommended)
     */
    public function GetIsAuthorized($password)
    {
        $isAuthorized = password_verify($password, $this->_passwordHash);
        $needsRehash = password_needs_rehash($this->_passwordHash, PASSWORD_DEFAULT);

        if ($needsRehash)
        {
            if (!$isAuthorized)
            {
                //try md5 for legacy support
                $isAuthorized = md5($password) == $this->_passwordHash;
            }

            $isAuthorized = $isAuthorized?2:-1; //return 2 if the password is good but hash needs rehash.  Return -1 if password is bad and hash needs rehash
        } else {
            $isAuthorized = $isAuthorized?1:0;
        }
        return $isAuthorized;
    }

    public function GetIsLoggedin() {
        return $this->_isloggedIn;
    }

    public static function IsHashOutDated()
    {
        return password_needs_rehash($this->_passwordHash, PASSWORD_DEFAULT);
    }
    public function Get_UserGroup() {
        $userGroup = null;
        if ($this->_userGroupId) {
            $userGroup = new UserGroup($this->_userGroupId);
        }
        return $userGroup;
    }

    public function Set_UserGroup(\DblEj\Authentication\IUserGroup $newUserGroup) {
        $this->_userGroupId = $newUserGroup->Get_UserGroupId();
    }

    public static function Login($emailAddress, $authorizationKey, $allowGuestLogin = false) {
        $subclass = get_called_class();

        $userWhereArray = [];
        foreach (self::$_emailAddressOrUsernameField as $usernameCol)
        {
            $userWhereArray[] = "$usernameCol='" . \Wafl\Core::$STORAGE_ENGINE->EscapeString($emailAddress) . "'";
        }
        $userWhere = implode(" or ", $userWhereArray);

        if ($allowGuestLogin) {
            $user = $subclass::FilterFirst($userWhere, null, null, array(self::$_userGroupsTableName => self::$_userGroupIdField), false);
        } else {
            $user = $subclass::FilterFirst("($userWhere) and ".self::$_userGroupsTableName.".".self::$_userGroupIdField." <> 6", null, null, array(self::$_userGroupsTableName => self::$_userGroupIdField), false);
        }
        $isAuthorized = $user?$user->GetIsAuthorized($authorizationKey):false;
        if ($isAuthorized == 2) //hash is using old algorithm
        {
            $user->UpdatePassword($authorizationKey);
            if ($user->Get_UserId())
            {
                $user->Save();
            }
        }
        if ($user && ($isAuthorized > 0)) {
            self::SetCurrentLoggedInUser($user);
        } else {
            self::SetCurrentLoggedInUser($subclass::GetGuestUser());
        }
        return $user;
    }

    public static function SetCurrentLoggedInUser($user) {
        \Wafl\Core::$CURRENT_USER = $user;
        if ($user->Get_UserId()) {
            $user->_isloggedIn = true;
        } else {
            $user->_isloggedIn = false;
        }

        $app = \Wafl\Core::$RUNNING_APPLICATION;
        $session = $app->GetCurrentSession();
        if (!$session->Get_UserId() && $user->Get_UserId())
        {
            $session->Set_UserId($user->Get_UserId());
            $session->Save();
        }

        return $session;
    }
    public function Get_ActorType()
    {
        return $this->Get_ResourceType();
    }
    public function Get_ResourceType()
    {
        return \DblEj\Resources\Actor::RESOURCE_TYPE_GENERAL;
    }
    public function Get_Title()
    {
        return $this->Get_Username();
    }
    public function Get_Uuid()
    {
        return $this->Get_Username();
    }
}
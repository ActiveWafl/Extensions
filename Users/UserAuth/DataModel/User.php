<?php

namespace Wafl\Extensions\Users\UserAuth\DataModel;

abstract class User extends \DblEj\Data\PersistableModel implements \DblEj\Resources\IActor, \DblEj\Authentication\IAuthorizedUser
{
    use \DblEj\Resources\ActorTrait;

    protected $_userId;
    protected $_userGroupId;
    protected $_emailAddress;
    protected $_emailAddressOrUsername;
    protected $_lastLogin;
    protected $_passwordHash;

    public function __construct($keyValue = null, array $objectData = null, \DblEj\Data\IDatabaseConnection $storageEngine = null) {
        try {
            parent::__construct($keyValue, $objectData, $storageEngine);
        } catch (\InvalidArgumentException $err) {
            throw new \InvalidArgumentException("The UserAuth extension requires a connection to a Storage Engine (usually a mysql database).  You can specify a Storage Engine by editing Config/Database/DataStorage.php.");
        }
    }

    public function Get_UserId() {
        return $this->_userId;
    }

    public function Set_UserId($userId) {
        $this->_userId = $userId;
    }

    public function Get_UserGroupId() {
        return $this->_userGroupId;
    }

    public function Set_UserGroupId($userGroupId) {
        $this->_userGroupId = $userGroupId;
    }

    public function Get_PasswordHash() {
        return $this->_passwordHash;
    }

    public function Set_PasswordHash($newHash) {
        $this->_passwordHash = $newHash;
    }

    public function Get_EmailAddressOrUsername() {
        return $this->_emailAddressOrUsername;
    }

    public function Set_EmailAddressOrUsername($emailAddressOrUsername) {
        $this->_emailAddressOrUsername = $emailAddressOrUsername;
    }

    public function Get_LastLogin() {
        return $this->_lastLogin;
    }

    public function Set_LastLogin($lastLogin) {
        $this->_lastLogin = $lastLogin;
    }

    public static function Get_KeyFieldName() {
        return "UserId";
    }

    public static function Get_KeyValueIsGeneratedByEngine() {
        return true;
    }

    public static function Get_StorageLocation() {
        return \Wafl\Extensions\Users\UserAuth\UserAuth::Get_TablePrefix() . "Users";
    }

	public function Get_ActorTypeId() {
		return \DblEj\Resources\Resource::RESOURCE_TYPE_PERSON;
	}

	public function Get_ActorId() {
		return $this->_userId;
	}

	public function Get_DisplayName() {
		return $this->Get_EmailAddressOrUsername();
	}

}

?>
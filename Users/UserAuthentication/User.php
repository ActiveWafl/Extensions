<?php

namespace Wafl\Extensions\Users\UserAuthentication;

abstract class User Implements \DblEj\Authentication\IUser
{
    private $_userId;
    private $_username;
    private $_password;

    public function Set_AuthorizationKey($newAuthorizationKey)
    {
        $this->_password = $newAuthorizationKey;
    }

    public function Set_Username($newUsername)
    {
        $this->_username = $newUsername;
    }
    
    public function Get_ActorId()
    {
        return $this->_userId;
    }

    public function Get_ActorTypeId()
    {
        return \DblEj\Resources\Resource::RESOURCE_TYPE_PERSON;
    }

    public function Get_DisplayName()
    {
        return $this->_username;
    }

    public function Get_UserId()
    {
        return $this->_userId;
    }

    public function Get_Username()
    {
        return $this->_username;
    }
}
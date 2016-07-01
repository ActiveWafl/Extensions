<?php
namespace Wafl\Extensions\Forums\Lib;
class Thread extends \DblEj\Data\PersistableModel
{
    private $_threadId;
    private $_title;
    private $_parentForumId;
    private $_minimumPrivilegeLevel=0;
    private $_dateCreated;
    private $_userId;
    private $_postCount=0;
    private $_lastPostId;
    private $_isSticky=false;
    private $_isLocked=false;
    private $_isApproved=true;
    public function Get_ThreadId()
    {
        return $this->_threadId;
    }
    public function Set_ThreadId($ThreadId)
    {
        $this->_threadId = $ThreadId;
    }

    public function Get_Title()
    {
        return $this->_title;
    }
    public function Set_Title($Title)
    {
        $this->_title = $Title;
    }

    public function Get_ParentForumId()
    {
        return $this->_parentForumId;
    }
    public function Set_ParentForumId($ParentForumId)
    {
        $this->_parentForumId = $ParentForumId;
    }

    public function Get_MinimumPrivilegeLevel()
    {
        return $this->_minimumPrivilegeLevel;
    }
    public function Set_MinimumPrivilegeLevel($MinimumPrivilegeLevel)
    {
        $this->_minimumPrivilegeLevel = $MinimumPrivilegeLevel;
    }

    public function Get_DateCreated()
    {
        return $this->_dateCreated;
    }
    public function Set_DateCreated($DateCreated)
    {
        $this->_dateCreated = $DateCreated;
    }

    public function Get_UserId()
    {
        return $this->_userId;
    }
    public function Set_UserId($UserId)
    {
        $this->_userId = $UserId;
    }

    public function Get_PostCount()
    {
        return $this->_postCount;
    }
    public function Set_PostCount($PostCount)
    {
        $this->_postCount = $PostCount;
    }

    public function Get_LastPostId()
    {
        return $this->_lastPostId;
    }
    public function Set_LastPostId($LastPostId)
    {
        $this->_lastPostId = $LastPostId;
    }

    public function Get_IsSticky()
    {
        return $this->_isSticky;
    }
    public function Set_IsSticky($IsSticky)
    {
        $this->_isSticky = $IsSticky;
    }

    public function Get_IsLocked()
    {
        return $this->_isLocked;
    }
    public function Set_IsLocked($IsLocked)
    {
        $this->_isLocked = $IsLocked;
    }

    public function Get_IsApproved()
    {
        return $this->_isApproved;
    }
    public function Set_IsApproved($IsApproved)
    {
        $this->_isApproved = $IsApproved;
    }


    public static function Get_StorageLocation()
    {
        return \Wafl\Extensions\Forums\Forums::$TablePrefix."Threads";
    }
    public static function Get_FieldNames()
    {
        return array("ThreadId", "Title", "ParentForumId",
                     "MinimumPrivilegeLevel", "DateCreated",
                     "UserId", "PostCount",
                     "LastPostId", "IsSticky", "IsLocked",
                     "IsApproved");
    }
    public static function Get_KeyFieldName()
    {
        return "ThreadId";
    }
    public static function Get_KeyValueIsGeneratedByEngine()
    {
        return true;
    }
}
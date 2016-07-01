<?php
namespace Wafl\Extensions\Forums\Lib;
class Forum extends \DblEj\Data\PersistableModel
{
    private $_forumId;
    private $_title;
    private $_description;
    private $_parentForumId;
    private $_minimumPrivilegeLevel;
    private $_dateCreated;
    private $_userId;
    private $_threadCount;
    private $_lastThreadId;
    private $_postCount;
    private $_lastPostId;

    public function Get_ForumId()
    {
        return $this->_forumId;
    }
    public function Set_ForumId($ForumId)
    {
        $this->_forumId = $ForumId;
    }

    public function Get_Title()
    {
        return $this->_title;
    }
    public function Set_Title($Title)
    {
        $this->_title = $Title;
    }

    public function Get_Description()
    {
        return $this->_description;
    }
    public function Set_Description($Description)
    {
        $this->_description = $Description;
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

    public function Get_ThreadCount()
    {
        return $this->_threadCount;
    }
    public function Set_ThreadCount($ThreadCount)
    {
        $this->_threadCount = $ThreadCount;
    }

    public function Get_LastThreadId()
    {
        return $this->_lastThreadId;
    }
    public function Set_LastThreadId($LastThreadId)
    {
        $this->_lastThreadId = $LastThreadId;
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
    public function Get_DisplayOrder()
    {
        return $this->_displayOrder;
    }
    public function Set_DisplayOrder($DisplayOrder)
    {
        $this->_displayOrder = $DisplayOrder;
    }

    public function GetChildForums()
    {
        return Forum::LoadAll($this->_storageEngine,"ParentForumId=".$this->_forumId, "DisplayOrder");
    }
    public function GetChildThreads()
    {
        return Thread::LoadAll($this->_storageEngine,"ParentForumId=".$this->_forumId, "DateCreated desc");
    }
    public function GetPageLink()
    {
        return \Wafl\Util\Extensions::GetExtensionPageLink("Forums","Forum")."&ForumId=$this->_forumId";
    }
    public function GetNewThreadLink()
    {
        return \Wafl\Util\Extensions::GetExtensionPageLink("Forums","NewPost")."&ForumId=$this->_forumId";
    }

    public static function Get_StorageLocation()
    {
        return \Wafl\Extensions\Forums\Forums::$TablePrefix."Forums";
    }
    public static function Get_FieldNames()
    {
        return array("ForumId", "Title", "Description", "ParentForumId",
                     "MinimumPrivilegeLevel", "DateCreated", "UserId", "ThreadCount",
                     "LastThreadId", "PostCount", "LastPostId", "DisplayOrder");
    }
    public static function Get_KeyFieldName()
    {
        return "ForumId";
    }
    public static function Get_KeyValueIsGeneratedByEngine()
    {
        return true;
    }
}
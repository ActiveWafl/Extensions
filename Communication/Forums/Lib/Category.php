<?php
namespace Wafl\Extensions\Forums\Lib;
class Category extends \DblEj\Data\PersistableModel
{
    private $_categoryId;
    private $_title;
    private $_description;
    public function Get_CategoryId()
    {
        return $this->_categoryId;
    }
    public function Set_CategoryId($CategoryId)
    {
        $this->_categoryId = $CategoryId;
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
        return Forum::LoadAll(self::getStorageEngine(),"ParentCategoryId=".$this->_categoryId, "DisplayOrder");
    }
    public static function Get_StorageLocation()
    {
        return \Wafl\Extensions\Forums\Forums::$TablePrefix."Categories";
    }
    public static function Get_FieldNames()
    {
        return array("CategoryId", "Title", "Description", "DisplayOrder");
    }
    public static function Get_KeyFieldName()
    {
        return "CategoryId";
    }
    public static function Get_KeyValueIsGeneratedByEngine()
    {
        return true;
    }
}
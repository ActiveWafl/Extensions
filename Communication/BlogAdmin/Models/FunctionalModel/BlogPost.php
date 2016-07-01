<?php
namespace Wafl\Extensions\Communication\BlogAdmin\Models\FunctionalModel;

/**
 * BlogPost
 * Represents a row in the database table BlogPosts
 */
class BlogPost extends \Wafl\Extensions\Communication\BlogAdmin\Models\DataModel\BlogPost
{
    public function __construct($keyValue = null, array $objectData = null, \DblEj\Data\IDatabaseConnection $storageEngine = null, $dataGroup = null)
    {
        parent::__construct($keyValue, $objectData, $storageEngine, $dataGroup);
        $this->_urlTitle = $this->GetUrlTitle();
    }
    public function Set_Title($title)
    {
        parent::Set_Title($title);
        $this->_urlTitle = $this->GetUrlTitle();
    }
    public function GetUrlTitle()
    {
        $urlTitle = trim($this->_title);
        $urlTitle = \DblEj\Util\Strings::CollapseWhitespace($urlTitle, "-");
        $urlTitle = preg_replace("/[^A-Za-z0-9\-]/", "", $urlTitle);
        $urlTitle = \DblEj\Util\Strings::CollapseChars($urlTitle, "-", "-");
        return $urlTitle;
    }
}
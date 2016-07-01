<?php
namespace Wafl\Extensions\Communication\Blog\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;
class Home extends \DblEj\Extension\ExtensionControllerBase
{
	public function DefaultAction(Request $request, IMvcWebApplication $app)
	{
        $extension = $this->_extension;
        $sidePostsCount = $this->Get_Extension()->GetSettingValue("SidePostsCount");
        $sidePostsCategoryListString = $this->Get_Extension()->GetSettingValue("SidePostsCategories");

        $sidePostsCategoryList = explode(",", $sidePostsCategoryListString);

        if (count($sidePostsCategoryList))
        {
            $sidePostsCategorySql = "'".implode("','", $sidePostsCategoryList)."'";
            $posts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter("BlogCategories.Title in ($sidePostsCategorySql)","BlogPosts.PostDate desc", $sidePostsCount, null, array("BlogCategories"=>"BlogCategoryId"));
        } else {
            $posts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter(null,"PostDate desc", $sidePostsCount);
        }

        $tagModel = $extension->GetSettingValue("TagModel");
        $tags = $tagModel::Filter("BlogPosts.BlogPostId = BlogPostTags.BlogPostId", null, null, null, ["BlogPosts"=>null, "BlogPostTags"=>"TagId"]);
        $allCategories = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogCategory::Filter(null, "Title");

        return $this->createResponseFromRequest($request, $app, new \DblEj\Data\ArrayModel(["ALL_CATEGORIES"=>$allCategories,"ALL_TAGS"=>$tags,"POSTS"=>$posts]));
	}
}
?>
<?php
namespace Wafl\Extensions\Communication\Blog\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;
class Category extends \DblEj\Extension\ExtensionControllerBase
{
	public function DefaultAction(Request $request, IMvcWebApplication $app)
	{
        $extension = $this->Get_Extension();
        $allposts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter(null, "PostDate desc", 30);
        $tagModel = $extension->GetSettingValue("TagModel");
        $catId = $request->GetInput("CategoryId");
        $category = new \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogCategory($catId);
        $allCategories = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogCategory::Filter(null, "Title");
        if (!$category)
        {
            throw new \Exception("Invalid category");
        }
        $posts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter("BlogCategories.BlogCategoryId = $catId","BlogPosts.PostDate desc", 100, null, array("BlogCategories"=>"BlogCategoryId"));
        $allposts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter("BlogCategories.BlogCategoryId = $catId","BlogPosts.PostDate desc", 100, null, array("BlogCategories"=>"BlogCategoryId"));
        $tags = $tagModel::Filter("BlogPosts.BlogPostId = BlogPostTags.BlogPostId", null, null, null, ["BlogPosts"=>null, "BlogPostTags"=>"TagId"]);
        return $this->createResponseFromRequest($request, $app, new \DblEj\Data\ArrayModel(["ALL_CATEGORIES"=>$allCategories,"ALL_TAGS"=>$tags,"BLOG_CATEGORY"=>$category,"POSTS"=>$posts, "ALL_POSTS"=>$allposts]));
	}
}
?>
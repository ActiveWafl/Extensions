<?php
namespace Wafl\Extensions\Communication\Blog\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;
class BlogPost extends \DblEj\Extension\ExtensionControllerBase
{
	public function DefaultAction(Request $request, IMvcWebApplication $app) 
	{
        $postId = $request->GetInput("BlogPostId");
        if (!$postId)
        {
            throw new \Exception("Invalid Blog post");
        }

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
        $post = new \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost($postId);
        return $this->createResponseFromRequest($request, $app, new \DblEj\Data\ArrayModel(["BLOG_POST"=>$post, "POSTS"=>$posts]));
	}
}
?>
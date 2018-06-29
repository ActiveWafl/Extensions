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
            throw new \DblEj\Communication\Http\Exception($request->Get_RequestUrl(), "Invalid blog post", 404);
        }

        $sidePostsCount = $this->Get_Extension()->GetSettingValue("SidePostsCount");
        $sidePostsCategoryListString = $this->Get_Extension()->GetSettingValue("SidePostsCategories");

        if ($sidePostsCategoryListString)
        {
            $sidePostsCategoryList = explode(",", $sidePostsCategoryListString);
        } else {
            $sidePostsCategoryList = null;
        }
        
        if (count($sidePostsCategoryList))
        {
            $sidePostsCategorySql = "'".implode("','", $sidePostsCategoryList)."'";
            $posts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter("BlogCategories.Title in ($sidePostsCategorySql) and BlogPosts.IsPublished = 1","BlogPosts.PostDate desc", $sidePostsCount, null, array("BlogCategories"=>"BlogCategoryId"));
        } else {
            $posts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter("BlogPosts.IsPublished = 1","PostDate desc", $sidePostsCount);
        }
        $post = new \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost($postId);
        if (!$post->Get_IsPublished())
        {
            throw new \DblEj\Communication\Http\Exception($request->Get_RequestUrl(), "The specified blog post does not exit", 404);
        }
        return $this->createResponseFromRequest($request, $app, new \DblEj\Data\ArrayModel(["BLOG_POST"=>$post, "POSTS"=>$posts]));
	}
}
?>
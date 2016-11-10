<?php
namespace Wafl\Extensions\Communication\Blog\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;
class Search extends \DblEj\Extension\ExtensionControllerBase
{
	public function DefaultAction(Request $request, IMvcWebApplication $app)
	{
        $search = $request->GetInputString("Search");
        $searchError="";
        $extension = $this->Get_Extension();
        $tagsTable = $extension->GetSettingValue("TagsTable");
        $tagKey = $extension->GetSettingValue("TagKey");
        $tagField = $extension->GetSettingValue("TagField");
        $allposts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter(null, "PostDate desc", 30);
        $tagModelClass = $extension->GetSettingValue("TagModel");
        $tagRows = $tagModelClass::Filter();
        $allCategories = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogCategory::Filter(null, "Title");

        $tags = [];
        foreach ($tagRows as $tag)
        {
            $tags[$tag->GetFieldValue($tagField)] = $tag;
        }
        if (strlen($search)>2)
        {
            $sqlSearch = \Wafl\Core::$STORAGE_ENGINE->EscapeString($search);
            $posts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter("IsPublished=1 and (($tagsTable.$tagKey = BlogPostTags.TagId and BlogPostTags.BlogPostId = BlogPosts.BlogPostId and $tagsTable.$tagField like '$sqlSearch') or (BlogPosts.Title like '%$sqlSearch%'))","BlogPosts.PostDate desc", null, null, array($tagsTable=>null,"BlogPostTags"=>null));
            $termMatches = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter("Title like '%$sqlSearch%' and IsPublished = 1","PostDate desc", 100);
            $posts = array_merge($posts, $termMatches);
        } else {
            $posts=[];
            $searchError = "Search term must be at least three characters long.";
        }
        return $this->createResponseFromRequest($request, $app, new \DblEj\Data\ArrayModel(["ALL_CATEGORIES"=>$allCategories,"SEARCH_ERROR"=>$searchError,"ALL_TAGS"=>$tags,"SEARCH"=>$search,"POSTS"=>$posts, "ALL_POSTS"=>$allposts]));
	}
}
?>
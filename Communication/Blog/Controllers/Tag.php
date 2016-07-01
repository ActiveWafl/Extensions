<?php
namespace Wafl\Extensions\Communication\Blog\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;
class Tag extends \DblEj\Extension\ExtensionControllerBase
{
	public function DefaultAction(Request $request, IMvcWebApplication $app)
	{
        $search = $request->GetInput("Tag");
        $extension = $this->Get_Extension();
        $tagsTable = $extension->GetSettingValue("TagsTable");
        $tagField = $extension->GetSettingValue("TagField");
        $tagKey = $extension->GetSettingValue("TagKey");
        $posts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter("$tagsTable.$tagKey = BlogPostTags.TagId and $tagsTable.$tagField like '$search'","BlogPosts.PostDate desc", null, null, array($tagsTable=>null,"BlogPostTags"=>"BlogPostId"));
        $allposts = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogPost::Filter(null,"PostDate desc", 30);
        $tagModelClass = $extension->GetSettingValue("TagModel");
        $tagRows = $tagModelClass::Filter();
        $allCategories = \Wafl\Extensions\Communication\Blog\Models\FunctionalModel\BlogCategory::Filter(null, "Title");

        $tags = [];
        foreach ($tagRows as $tag)
        {
            $tags[$tag->GetFieldValue($tagField)] = $tag;
        }
        return $this->createResponseFromRequest($request, $app, new \DblEj\Data\ArrayModel(["ALL_CATEGORIES"=>$allCategories,"ALL_TAGS"=>$tags,"SEARCH"=>$search,"POSTS"=>$posts, "ALL_POSTS"=>$allposts]));
	}
}
?>
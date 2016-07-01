<?php

namespace Wafl\Extensions\Communication\BlogAdmin\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;

class BlogPostEdit
extends \DblEj\Extension\ExtensionControllerBase
{
    public function DefaultAction(Request $request, IMvcWebApplication $app)
    {
        $blogPostId = $request->GetInput("BlogPostId");
        $blogPost = new \Wafl\Extensions\Communication\BlogAdmin\Models\FunctionalModel\BlogPost($blogPostId);
        return $this->createResponseFromRequest
        (
            $request, $app, new \DblEj\Data\ArrayModel
            (
                [
                    "EDIT_BLOG_POST" => $blogPost,
                    "BLOG_CATEGORIES" => \Wafl\Extensions\Communication\BlogAdmin\Models\FunctionalModel\BlogCategory::Filter(null, "Title")
                ]
            )
        );
    }

    public function SavePost(Request $request, IMvcWebApplication $app)
    {
        $blogPostId = $request->GetInput("BlogPostId");
        $blogPost = new \Wafl\Extensions\Communication\BlogAdmin\Models\FunctionalModel\BlogPost($blogPostId);
        if (!$blogPost->Get_BlogPostId())
        {
            $blogPost->Set_PostDate(time());
            $blogPost->Set_UserId(\Wafl\Core::$CURRENT_USER->Get_UserId());

            $dupCount = \Wafl\Extensions\Communication\BlogAdmin\Models\FunctionalModel\BlogPost::Count("Title = '" . addslashes($request->GetInput("BlogPostTitle")) . "' and BlogCategoryId = " . $request->GetInput("BlogPostCategory"));
            if ($dupCount)
            {
                \Wafl\UserFeedback::AppendError("Duplicate Post", "You already have a blog post with this title in this category.", "You cannot have multiple posts with the same title unless they are in different categories.");
                return $this->DefaultAction($request, $app);
            }
        }
        $blogPost->Set_Title($request->GetInput("BlogPostTitle"));
        $blogPost->Set_Contents($request->GetInput("BlogPostContents"));
        $blogPost->Set_BlogCategoryId($request->GetInput("BlogPostCategory"));
        $blogPost->Set_IsPublished($request->GetInput("BlogPostIsPublished")?true:false);
        $blogPost->Save();
        $blogPostId = $blogPost->Get_BlogPostId();
        $request->SetInput("BlogPostId", $blogPostId, Request::INPUT_POST);
        \Wafl\UserFeedback::AppendInfo("The blog post has been saved");
        return $this->DefaultAction($request, $app);
    }
}
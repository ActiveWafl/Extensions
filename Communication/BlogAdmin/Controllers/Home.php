<?php

namespace Wafl\Extensions\Communication\BlogAdmin\Controllers;

use DblEj\Application\IMvcWebApplication;
use DblEj\Communication\Http\Request;

class Home
extends \DblEj\Extension\ExtensionControllerBase
{
    public function DefaultAction(Request $request, IMvcWebApplication $app)
    {
        return $this->createResponseFromRequest
        (
            $request, $app, new \DblEj\Data\ArrayModel
            (
                [
                    "BLOG_POSTS"=> \Wafl\Extensions\Communication\BlogAdmin\Models\FunctionalModel\BlogPost::Filter("", "PostDate desc", 20)
                ]
            )
        );
    }
}
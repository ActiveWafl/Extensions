<?php
namespace Wafl\Extensions\Visualization\Reports\Routers;

use DblEj\Application\IApplication,
    DblEj\Application\IWebApplication,
    DblEj\Communication\Http\Request,
    DblEj\Communication\Http\Routing\IInternalRouter,
    DblEj\Communication\Http\Routing\IRouter as IHttpRouter,
    DblEj\Communication\Http\Routing\Route,
    DblEj\Communication\IRequest,
    DblEj\Communication\IRouter;

class Report
implements IInternalRouter
{
    public function GetRoute(IRequest $request, IApplication $app = null, IRouter &$usedRouter = null)
    {
        return $this->GetHttpRoute($request, $app, $usedRouter);
    }

    public function GetHttpRoute(Request $request, IWebApplication $app = null, IHttpRouter &$usedRouter = null)
    {
        if ($app)
        {
            $requestFile = $request->Get_RequestUrl();
            if (strpos($requestFile, "?"))
            {
                $requestFile = substr($requestFile, 0, strpos($requestFile, "?"));
            }
            if ($requestFile == "/Reports/Report")
            {
                $controllerFullPath = realpath(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."Controllers/Report.php");
                require_once($controllerFullPath);
                $controllerClass = new \Wafl\Extensions\Visualization\Reports\Controllers\Report();
                $returnRoute     = new Route
                (
                    $request,
                    array
                    (
                        $controllerClass,
                        "CallAction"
                    ),
                    array
                    (
                        "DefaultAction",
                        $request,
                        $app
                    )
                );
                $usedRouter = $this;

                return $returnRoute;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
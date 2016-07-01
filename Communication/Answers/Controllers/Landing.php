<?php
namespace Wafl\Extensions\Communication\Answers\Controllers;

use DblEj\Application\IMvcWebApplication,
	DblEj\Communication\Http\Request,
	DblEj\Communication\Http\Util,
	DblEj\Data\ArrayModel,
	DblEj\Mvc\ControllerBase,
	Wafl\Extensions\Communication\Answers\Models\FunctionalModel\Question;

class Landing extends \DblEj\Extension\ExtensionControllerBase
{
	public function DefaultAction(Request $request, IMvcWebApplication $app) 
	{
		$dataArray = array(
				"SearchString"=>null,
				"Questions"=>Question::Select("IsApproved=1","DateAsked desc")
			);
		$model = new ArrayModel($dataArray);
		$response = $this->createResponseFromRequest($request, $app, $model);
		return $response;
	}

	public function Search(Request $request, IMvcWebApplication $app) 
	{
		$search = $request->GetInput("SearchText");
		$dataArray = array(
				"SearchString"=>$search,
				"Questions"=>Question::Select("IsApproved=1 and match(Question) against ('$search')")
			);
		$model = new ArrayModel($dataArray);
		return $this->createResponseFromRequest($request, $app, $model);
	}
}
?>
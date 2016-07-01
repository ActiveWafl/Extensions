<?php

namespace Wafl\Extensions\Visualization\Reports\Controllers;

use DblEj\Application\IMvcWebApplication,
    DblEj\Communication\Http\Request,
    DblEj\Mvc\ControllerBase,
    DblEj\Presentation\RenderOptions,
    Exception;

class Report
extends ControllerBase
{
    private $_extraTemplateVariables = [];
    public function DefaultAction(Request $request, IMvcWebApplication $app)
    {
        $allInputs = $request->GetAllInputs();

        $reportId = $request->GetInput("ReportId");
        $format = $request->GetInput("Format");
        $options = RenderOptions::GetDefaultInstance();
        $reportExtension = \Wafl\Util\Extensions::GetExtension("Reports");
        $cacheId = md5(serialize($request));
        $compileId = md5($reportExtension->GetSettingValue("LayoutTemplate"));
        $options = new RenderOptions(true, false, $cacheId, $compileId);
        $queryString = $_SERVER["QUERY_STRING"];
        if ($reportId)
        {
            $report = $reportExtension->GetReport($reportId);
            $inputsSet = true;
            foreach (array_keys($report->GetAllInputs()) as $reportInputId)
            {
                if (!isset($allInputs[$reportInputId]))
                {
                    $inputsSet = false;
                    break;
                }
            }

            if ($inputsSet)
            {
                $sitePage = $app->GetSitePage("Reports.Report");
                $options->AddToken(["REPORT_TEMPLATE"=>$report->GetTemplatePath()]);
                $options->AddToken(["REPORT_CSS"=>$report->GetTemplateCssPath()]);
                $options->AddToken(["LAYOUT_FILE"=>$reportExtension->GetSettingValue("LayoutTemplate")]);
                $options->AddToken($allInputs);
                $options->AddToken(["REPORT_DATASET"=>$report->GetReportData($allInputs)]);
                $options->AddToken(["REPORT_FIELDS"=>$report->GetFields()]);
                $options->AddToken(["REPORT_FIELD_INFO"=>$report->GetFieldInfo()]);
                $options->AddToken(["QUERY_STRING"=>$queryString]);
            } else {
                $sitePage = $app->GetSitePage("Reports.ReportInput");
                $options->AddToken(["LAYOUT_FILE"=>$reportExtension->GetSettingValue("LayoutTemplate")]);
                $options->AddToken($allInputs);
                $options->AddToken(["REPORT_INPUTS"=>$report->GetAllInputs()]);
                $options->AddToken(["REPORT_INPUT_DEPENDENCIES"=>$report->GetInputDependencies()]);
                $options->AddToken(["SET_REPORT_INPUTS"=>$allInputs]);
                $options->AddToken(["REPORT"=>$report]);

            }
        } else {
            $sitePage = $app->GetSitePage("Reports.ReportDirectory");
            $options->AddToken(["LAYOUT_FILE"=>$reportExtension->GetSettingValue("LayoutTemplate")]);
            $options->AddToken($allInputs);
            $options->AddToken(["REPORTS"=>$reportExtension->GetAllReports()]);
        }
        foreach ($this->_extraTemplateVariables as $extraVarName=>$extraVarVal)
        {
            $sitePage->SetModelData($extraVarName, $extraVarVal);
        }

        if ($format == "csv")
        {
            $reportData = $report->GetReportData($allInputs);
            $csv = implode("\t",$report->GetFields())."\n";
            $csv .= $this->_arrayToCsv($reportData);
            $headers = new \DblEj\Communication\Http\Headers("text/csv");
            $headers->Set_ForceDownload(true, $report->Get_Title().".csv");
            return new \DblEj\Communication\Http\Response($csv, \DblEj\Communication\Http\Response::HTTP_OK_200, $headers, \DblEj\Communication\Http\Response::CONTENT_TYPE_PRINTABLE_OUTPUT);
        } else {
            return $this->createResponseFromSitePage($sitePage, $options);
        }
    }

    private function _arrayToCsv(array $array, $skipFields = 0)
    {
        $csv = "";

        $firstElement = reset($array);
        $skipFrontString = "";
        if ($skipFields)
        {
            for ($skipFieldIdx = 1; $skipFieldIdx <= $skipFields; $skipFieldIdx++)
            {
                $skipFrontString .= "\t";
            }
        }
        if (is_array($firstElement))
        {
            foreach ($array as $blockLabel=>$subArray)
            {
                $subArrayFirstElement = reset($subArray);
                if (is_array($subArrayFirstElement))
                {
                    $csv .= $skipFrontString.$blockLabel."\n";
                    $skipFields += 1;
                }
                $csv .= $this->_arrayToCsv($subArray, $skipFields);
            }
        } else {
            $csv .= $skipFrontString.implode("\t", $array)."\n";
        }
        return $csv;
    }
    public function AddDefaultActionTemplateVariables($varName, $varVal)
    {
        $this->_extraTemplateVariables[$varName] = $varVal;
    }
}
<?php
namespace Wafl\Extensions\Visualization\Reports;

interface IReport
{
    public function Get_Title();
    public function GetAllInputs();
    public function GetInputAllowedValues($inputId, $setInputValues = null);
    public function GetReportData($inputValues);
    public function GetTemplatePath();
    public function GetTemplateCssPath();
    public function GetInputDependencies();
}
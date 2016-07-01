<?php
namespace Wafl\Extensions\Visualization\Reports;

class GenericReport extends \Wafl\Extensions\Visualization\Reports\TableReport
{
    private $_allInputs = [];
    private $_inputAllowedValues = [];
    private $_title = "Untitled Report";
    private $_reportData = [];

    public function __construct($title)
    {
        $this->_title = $title;
    }
    public function Get_Title()
    {
        return $this->_title;
    }
    public function GetAllInputs()
    {
        return $this->_allInputs;
    }

    public function GetInputAllowedValues($inputId, $otherInputValue = null)
    {
        return $this->_inputAllowedValues;
    }

    public function GetReportData($inputValues)
    {
        return $this->_reportData;
    }

    public function AddReportSection($sectionName)
    {
        $this->_reportData[$sectionName] = [];
    }

    public function AddData($sectionName, $dataRow)
    {
        if (!isset($this->_reportData[$sectionName]))
        {
            $this->AddReportSection($sectionName);
        }
        $this->_reportData[$sectionName][] = $dataRow;
    }
}
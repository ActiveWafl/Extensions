<?php
namespace Wafl\Extensions\Visualization\Reports;

abstract class TableReport
implements IReport
{
    public function GetTemplatePath()
    {
        return "./TableReport.tpl";
    }
    public function GetTemplateCssPath()
    {
        return "./TableReport.css";
    }

    public function GetInputDependencies()
    {
        return [];
    }

    public abstract function GetFields();
    public function GetFieldInfo()
    {
        $fieldInfo = [];
        foreach ($this->GetFields() as $fieldName)
        {
            $fieldInfo[$fieldName] = ["Type"=>"String", "Label"=>$fieldName];
        }
        return $fieldInfo;
    }

    public function GetField($fieldName)
    {
        $fieldInfo = $this->GetFieldInfo();
        if (!isset($fieldInfo[$fieldName]))
        {
            throw new \Exception("Invalid field specified: $fieldName");
        }
        return $fieldInfo[$fieldName];
    }
    
    public function GetFieldDataType($fieldName)
    {
        return $this->GetFieldInfo($fieldName)["Type"];
    }

    protected function returnIndexedData($nonIndexedData)
    {
        $indexedData = [];
        foreach ($nonIndexedData as $rowIdx => $nonIndexedDataRow)
        {
            $indexedData[$rowIdx] = [];
            foreach ($nonIndexedDataRow as $nonIndexedDataCols)
            {

            }
        }
    }
}
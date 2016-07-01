<?php

function smarty_function_property_input($params, $template)
{
    if (isset($params["Model"]))
    {
        $params["model"] = $params["Model"];
    }
    if (isset($params["Property"]))
    {
        $params["property"] = $params["Property"];
    }
    if (isset($params["Default"]))
    {
        $params["default"] = $params["Default"];
    }
    if (isset($params["Label"]))
    {
        $params["label"] = $params["Label"];
    }
    if (isset($params["InputTag"]))
    {
        $params["inputtag"] = $params["InputTag"];
    }
    if (isset($params["Field_Name"]))
    {
        $params["field_name"] = $params["Field_Name"];
    }
    if (isset($params["FieldName"]))
    {
        $params["field_name"] = $params["FieldName"];
    }
    if (isset($params["Required"]))
    {
        $params["required"] = $params["Required"];
    }


	if (!isset($params["property"]))
	{
		throw new \Exception("Must pass \"Property\" to show property input");
	}
	if (!isset($params["model"]))
	{
		throw new \Exception("Must pass \"Model\" to show property input");
	}
    
	$model			 = $params["model"];
	$propertyName	 = $params["property"];
	$defaultValue	 = isset($params["default"]) ? $params["default"] : null;
	$valueChoices    = isset($params["OptionList"])?$params["OptionList"]:null; //associative array key=option value, element=option label
    if (!$valueChoices)
    {
        $optionObjects = isset($params["OptionObjects"])?$params["OptionObjects"]:null; //alternate to an OptionList, you can pass in an array of objects and then specify which properties are used for the label and value
        if ($optionObjects)
        {
            if (!isset($params["ValueProperty"]))
            {
                throw new \Exception("If using OptionObjects with a property input, you must also specify ValueProperty");
            }
            if (!isset($params["LabelProperty"]))
            {
                throw new \Exception("If using OptionObjects with a property input, you must also specify LabelProperty");
            }
            $valueChoices = array();
            foreach ($optionObjects as $optionObject)
            {
                $valueChoices[$optionObject->GetFieldValue($params["ValueProperty"])] = $optionObject->GetFieldValue($params["LabelProperty"]);
            }
            $params["OptionList"] = $valueChoices;
        }
    }
	if (!$model->HasFieldValue($propertyName))
	{
		$templateName = is_string($template->template_resource) ? $template->template_resource : "Template Resource";
		throw new \Exception("Invalid property <mark>$propertyName</mark> passed to the property_input control in template <mark>$templateName</mark>.  Property does not exist.");
	}
	$propertyValue = $model->GetFieldValue($propertyName);
	if ($propertyValue === null)
	{
		$propertyValue = $defaultValue;
	}
	if (isset($params["label"]) && $params["label"] === true)
	{
		$params["label"] = $propertyName;
	}	
	if (!isset($params["inputtag"]))
	{
		switch ($model->GetFieldDataType($propertyName))
		{
			case \DblEj\Data\Field::DATA_TYPE_BOOL:
				$inputType	 = "checkbox";
				break;
			case \DblEj\Data\Field::DATA_TYPE_STRING:
			default:
                if ($valueChoices)
                {
                    $inputType	 = "select";
                } else {
                    $inputType	 = "text";
                }
				break;
		}
		$params["inputtag"] = $inputType;
	}
	$params["field_name"] = isset($params["field_name"])?$params["field_name"]:$propertyName;
	$params["field_value"] = $propertyValue;
    require_once(__DIR__."/function.form_input.php");
	return smarty_function_form_input($params, $template);
}

?>
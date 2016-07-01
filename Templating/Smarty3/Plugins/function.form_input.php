<?php

function smarty_function_form_input($params, $template)
{
	$propertyName		 = isset($params["field_name"])?$params["field_name"]:$params["label"];
	$propertyValue		 = $params["field_value"];
	$required			 = isset($params["required"]) ? $params["required"] : false;
	$readOnly			 = isset($params["readonly"]) ? $params["readonly"] : false;
	$elementId			 = isset($params["id"]) ? $params["id"] : $propertyName;
    $placeholder         = isset($params["placeholder"]) ? $params["placeholder"] : "";
    
	$labelElemArgs	 = "";
	$inputElemArgs	 = "";
	foreach ($params as $param => $paramVal)
	{
		if (substr($param, 0, 6) == "label_")
		{
			$labelElemArgs .= $param . substring(6) . "=\"$paramVal\" ";
		}
		else if (substr($param, 0, 6) == "input_")
		{
			$inputElemArgs .= substr($param, 6) . "=\"$paramVal\" ";
		}
	}

	$returnString	 = "";
	$labelString	 = "";
	if (isset($params["label"]) && $params["label"] != "")
	{
		$labelString = $params["label"];
	}    
	if (isset($params["validation"]))
	{
		$minLength	 = isset($params["MinLength"]) ? $params["MinLength"] : 0;
		$maxLength	 = isset($params["MaxLength"]) ? $params["MaxLength"] : 99999;

		require_once(__DIR__ . DIRECTORY_SEPARATOR . "function.validator.php");
		$validatorHtml = smarty_function_validator(array(
			"ElementId"	 => $elementId,
			"Type"		 => $params["validation"],
			"DataLabel"	 => $labelString,
			"MinLength"	 => $minLength,
			"MaxLength"	 => $maxLength), $template);
	}
	else
	{
		$validatorHtml = "";
	}
	if (isset($params["label"]) && $params["label"] != "")
	{
		$returnString .= "<label $labelElemArgs>" . $params["label"] . "&nbsp;$validatorHtml</label>";
	}
	else
	{
		$returnString .= $validatorHtml;
	}
	$requiredAttribute	 = $required ? "required " : "";
	$readOnlyAttribute	 = $readOnly ? "readonly " : "";
    $checkedAttribute    = $propertyValue ? "checked" : "";
	if (isset($params["inputtag"]))
	{
		$inputType = $params["inputtag"];
	}
	else
	{
		$inputType	 = "text";
	}
    $escapedValue = htmlentities($propertyValue, ENT_COMPAT|ENT_HTML5);
	if ($inputType == "text")
	{
		$returnString .= "<input type=\"$inputType\" name=\"$propertyName\" placeholder=\"$placeholder\" id=\"$elementId\" value=\"$escapedValue\" {$inputElemArgs}{$requiredAttribute}{$readOnlyAttribute}/>";
	}
	elseif ($inputType == "checkbox")
	{
        $returnString .= "<input type=\"$inputType\" name=\"$propertyName\" placeholder=\"$placeholder\" id=\"$elementId\" value=\"1\" {$inputElemArgs}{$requiredAttribute}{$readOnlyAttribute}{$checkedAttribute}/>";
	}
	elseif ($inputType == "textarea")
	{
		$returnString .= "<textarea name=\"$propertyName\" id=\"$elementId\" placeholder=\"$placeholder\" {$inputElemArgs}{$requiredAttribute}{$readOnlyAttribute}>$propertyValue</textarea>";
	}
	elseif ($inputType == "select")
	{
        $label = isset($params["label"])?$params["label"]:"item";
        if
            (\DblEj\Util\Strings::StartsWith($label, "a") ||
             \DblEj\Util\Strings::StartsWith($label, "e") ||
             \DblEj\Util\Strings::StartsWith($label, "i") ||
             \DblEj\Util\Strings::StartsWith($label, "o") ||
             \DblEj\Util\Strings::StartsWith($label, "u")
            )
        {
            $pronoun = "an";
        } else {
            $pronoun = "a";
        }

        $optionHtml="<option value=''>Choose $pronoun $label from the list</option>";
        $options = isset($params["OptionList"])?$params["OptionList"]:array();
        foreach ($options as $optionVal=>$optionName)
        {
            if ($optionVal==$propertyValue)
            {
                $optionHtml .= "<option value=\"$optionVal\" selected>$optionName</option>";
            } else {
                $optionHtml .= "<option value=\"$optionVal\">$optionName</option>";
            }
        }
		$returnString .= "<select name=\"$propertyName\" id=\"$elementId\" placeholder=\"$placeholder\" {$inputElemArgs}{$requiredAttribute}{$readOnlyAttribute}>$optionHtml</select>";
	}
	return $returnString;
}

?>
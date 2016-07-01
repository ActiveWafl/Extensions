<?php

function smarty_block_uiblock($params, $content, $template, &$repeat) {
    if (!$repeat) {
		$controlInstanceId = \DblEj\Util\Strings::GenerateRandomString(32);
        if (!isset($params["name"])) {
            throw new \ErrorException("Template contains a reference to a unspecified control", 0, E_WARNING, __FILE__, __LINE__);
        }
        if (!isset($params["namespace"])) {
            $params["namespace"] = "\\Wafl\\Controls";
        }
        if (!isset($params["Version"])) {
            $params["Version"] = "0.0.1";
        }
        if (!isset($params["Id"])) {
            $params["Id"] = $params["name"] . "_$controlInstanceId";
        }

        $controlName = $params["name"];
        $controlNameSpace = $params["namespace"];
        $version = $params["Version"];

        $control = \Wafl\Util\Controls::GetControl($controlName, $controlNameSpace, null, $controlInstanceId, null, $version);
        if (!$control) {
            //if its nested, then the parent control namespace can be tried
            if (count($template->_tag_stack) > 1) {
                foreach ($template->_tag_stack[0][1] as $parentArgName => $parentArg) {
                    if (strtolower($parentArgName) == "name") {
                        $control = \Wafl\Util\Controls::GetControl($controlName, "\\Wafl\\Controls", $parentArg, $controlInstanceId, null, $version);
                        break;
                    }
                }
                if ($control) {
                    foreach ($template->_tag_stack[0][1] as $parentArgName => $parentArg) {
                        if (strtolower($parentArgName) == "width") {
                            $control->SetTemplateVariable("PARENT_WIDTH", $parentArg);
                        }
                        if (strtolower($parentArgName) == "height") {
                            $control->SetTemplateVariable("PARENT_HEIGHT", $parentArg);
                        }
                    }
                }
            }
        }

        if (!$control) {
            throw new \ErrorException("Template contains a reference to a control ($controlName) that is improperly nested, is spelled incorrectly, or is not installed.<br>This is commonly caused by typos in the spelling including incorrect letter-case.", 0, E_WARNING, __FILE__, __LINE__);
        }
        $width = isset($params["width"]) ? $params["width"] : null;
        $height = isset($params["height"]) ? $params["height"] : null;
        $control->Initialize($width, $height, $params, $template);
        foreach ($control->GetRequiredParams() as $requiredParamName) {
            if (!isset($params[$requiredParamName])) {
                $params[$requiredParamName] = $control->GetParamDefaultValue($requiredParamName);
            }
        }
        foreach ($control->GetOptionalParams() as $optionalParamName) {
            if (!isset($params[$optionalParamName])) {
                $params[$optionalParamName] = $control->GetParamDefaultValue($optionalParamName);
            }
        }
        $control->SetTemplateVariable("PARAMS", $params);
        if ($width) {
            $control->SetTemplateVariable("WIDTH", $width);
        }
        if ($height) {
            $control->SetTemplateVariable("HEIGHT", $height);
        }

        $control->SetTemplateVariable("CONTROL_CONTENTS", $content);
        return $control->GetHtml();
    }
}

?>
<?php
function smarty_function_form_value($params, $template)
{
    if (!array_key_exists('name', $params) || trim($params['name']) == '')
    {
        throw new \ErrorException("Must pass \"name\" to show form value", 0, E_WARNING, __FILE__, __LINE__);
    }
    $formVariableName = $params["name"];
    $defaultValue = isset($params["Default"])?$params["Default"]:null;
    return isset($_REQUEST[$formVariableName])?$_REQUEST[$formVariableName]:(isset($_COOKIE[$formVariableName])?$_COOKIE[$formVariableName]:$defaultValue);
}
?>
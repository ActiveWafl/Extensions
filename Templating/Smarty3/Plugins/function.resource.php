<?php

function smarty_function_resource($params, $template) {
    if (!isset($params["name"])) {
	throw new \ErrorException("Template contains a reference to a unspecified resource", 0, E_WARNING, __FILE__, __LINE__);
    }
    if (!isset($params["type"])) {
	$params["type"] = "Image";
    }
    if (!isset($params["id"])) {
	$params["id"] = null;
    }

    $resourceName = $params["name"];
    $resourceType = $params["type"];
    $resourceId = isset($params["id"])?$params["id"]:  uniqid();

    $width = isset($params["width"]) ? $params["width"] : null;
    $height = isset($params["height"]) ? $params["height"] : null;
    $style = isset($params["style"]) ? $params["style"] : "";
    $class = isset($params["class"]) ? $params["class"] : "Resource";
    $alt = isset($params["alt"]) ? $params["alt"] : "Resource";

    $returnString = "";
    switch (strtolower($resourceType)) {
	case "image":
	    if ($height || $width || $style) {
            if ($height) {
                $style .= "height: $height;";
            }
            if ($width) {
                $style .= "width: $width;";
            }
            $returnString = "<img alt=\"$alt\" class=\"$class\" id=\"$resourceId\" style=\"$style\" src=\"" . \Wafl\Core::$RUNNING_APPLICATION->Get_Settings()->Get_Web()->Get_WebUrlRelative() . "Resources/Images/$resourceName\" />";
	    } else {
            $returnString = "<img alt=\"$alt\" class=\"$class\" id=\"$resourceId\" src=\"" . \Wafl\Core::$RUNNING_APPLICATION->Get_Settings()->Get_Web()->Get_WebUrlRelative() . "Resources/Images/$resourceName\" />";
	    }
	    break;
	case "video":
	    break;
	case "audio":
	    break;
	default:
	    break;
    }
    return $returnString;
}

?>
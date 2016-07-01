<?php

function smarty_function_navigate($params, $template) {
    $destination = isset($params["Destination"]) ? $params["Destination"] : null;
    if (!$destination) {
        throw new InvalidArgumentException("Destination must be passed in the Navigate control");
    }
	
    $args = isset($params["ControllerArgs"]) ? $params["ControllerArgs"] : null;
    $sitePage = \Wafl\Core::$RUNNING_APPLICATION->GetSitePageByFilename($destination);
	if ($sitePage)
	{
		$caption = isset($params["Caption"]) ? $params["Caption"] : $sitePage->Get_FullTitle();
	} else {
		$caption = isset($params["Caption"]) ? $params["Caption"] : "!Invalid Page";
	}
    if ($args) {
        return "<a href=\"" . \Wafl\Core::$RUNNING_APPLICATION->Get_Settings()->Get_Web()->Get_WebUrlRelative() . $destination . "?$args" . "\">$caption</a>";
    } else {
        return "<a href=\"" . \Wafl\Core::$RUNNING_APPLICATION->Get_Settings()->Get_Web()->Get_WebUrlRelative() . $destination . "\">$caption</a>";
    }
}

?>
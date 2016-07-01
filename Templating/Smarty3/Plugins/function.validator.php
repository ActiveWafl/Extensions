<?php

function smarty_function_validator($params, $template) {
	$params["name"] = "FormValidator"; //pass the name of the control to the control/ui function
	require_once(__DIR__."/function.ui.php");
	return smarty_function_ui($params, $template);
}

?>
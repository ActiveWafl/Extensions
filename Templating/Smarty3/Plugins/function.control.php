<?php
/**
* @version 1.0
* @param array $params parameters
* Input:<br>
*          - name = the guid name of the control (required)
*          - height = image height (optional, default actual height)
*          - width = image width (optional, default actual width)
*          - ...any number of control specific parameters
* @param object $template template object
* @example {control name="WissyWig" width="200" height="300" showAlignmentControls="true" showFontControls="false"}
* @return string|null if the assign parameter is passed, Smarty assigns the
*                     result to a template variable
*/
function smarty_function_control($params, $template)
{
	 require_once(__DIR__."/function.ui.php");
	 return smarty_function_ui($params, $template);
}

?>
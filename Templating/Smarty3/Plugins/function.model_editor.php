<?php
function smarty_function_model_editor($params, $template)
{
	 $params["name"]="ModelEditor";
    require_once("function.ui.php");
	 return smarty_function_ui($params, $template);
}
?>
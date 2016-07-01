<?php
/**
* @version 1.0
* @param array $params parameters
* @param object $template template object
* @return string|null
*/
/**
 * alias for the control function
*/
function smarty_function_ui($params, $template)
{
    $repeat = false; //used to pass byref arg to uiblock
    require_once("block.uiblock.php");
    return smarty_block_uiblock($params,"",$template,$repeat);
}
?>
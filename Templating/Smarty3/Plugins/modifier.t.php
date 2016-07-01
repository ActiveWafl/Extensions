<?php 
function smarty_modifier_t($stringToTranslate)
{
    try
    {
        $returnString = \Wafl\Core::$RUNNING_APPLICATION->GetTranslatedText($stringToTranslate);
    } catch (\Exception $err)
    {
        $returnString = "#ERROR#";
    }
    return $returnString;
}
?>
<?php 
function smarty_modifier_localtime_format($timestamp)
{
    $app = \Wafl\CORE::$RUNNING_APPLICATION;
    $tzOffsetSecs = $app->Get_ClientGmtOffset();
    $localTimeString = strftime("%l:%M&nbsp;%p", $timestamp+$tzOffsetSecs);
	 if (trim($localTimeString) == "") //windows doesnt support %e or %l
	 {
		$localTimeString = strftime("%I:%M&nbsp;%p", $timestamp+$tzOffsetSecs);
	 }
	 return $localTimeString;
}
?>
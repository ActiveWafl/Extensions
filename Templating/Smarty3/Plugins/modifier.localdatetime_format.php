<?php 
function smarty_modifier_localdatetime_format($timestamp, $datetimeSeparator = " ", $spaceSeparator = "&nbsp;")
{
    $app = \Wafl\CORE::$RUNNING_APPLICATION;
    $tzOffsetSecs = $app->Get_ClientGmtOffset();
    $localTimeString = strftime("%b$spaceSeparator%e,$spaceSeparator%Y$datetimeSeparator%l:%M$spaceSeparator%p", $timestamp+$tzOffsetSecs);
	 if (trim($localTimeString) == "") //windows doesnt support %e or %l
	 {
		$localTimeString = strftime("%b$spaceSeparator%d,$spaceSeparator%Y$datetimeSeparator%I:%M$spaceSeparator%p", $timestamp+$tzOffsetSecs);
	 }
	 return $localTimeString;
}
?>
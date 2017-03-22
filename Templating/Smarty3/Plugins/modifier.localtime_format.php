<?php
function smarty_modifier_localtime_format($timestamp, $includeSeconds = false)
{
    $app = \Wafl\CORE::$RUNNING_APPLICATION;
    $tzOffsetSecs = $app->Get_ClientGmtOffset();
    if ($includeSeconds)
    {
        $localTimeString = strftime("%l:%M:%S&nbsp;%p", $timestamp+$tzOffsetSecs);
    } else {
        $localTimeString = strftime("%l:%M&nbsp;%p", $timestamp+$tzOffsetSecs);
    }
    if (trim($localTimeString) == "") //windows doesnt support %e or %l
    {
        if ($includeSeconds)
        {
            $localTimeString = strftime("%I:%M:%S&nbsp;%p", $timestamp+$tzOffsetSecs);
        } else {
            $localTimeString = strftime("%I:%M&nbsp;%p", $timestamp+$tzOffsetSecs);
        }
    }
    return $localTimeString;
}
?>
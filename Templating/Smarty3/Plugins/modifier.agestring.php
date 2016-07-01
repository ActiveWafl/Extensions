<?php 
function smarty_modifier_agestring($timestamp)
{
    $durationInSeconds = time() - $timestamp;
    return \DblEj\Util\Time::GetDurationString($durationInSeconds, "days", "hours", "minutes", "seconds", " ", " ", false, true)." ago";
}
?>
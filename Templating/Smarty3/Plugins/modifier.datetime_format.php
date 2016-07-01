<?php 
function smarty_modifier_datetime_format($timestamp, $format = "%b %e, %Y &nbsp; %l:%M %p")
{
    $localTimeString = strftime($format, $timestamp);
	 if (trim($localTimeString) == "") //windows doesnt support %e or %l
	 {
		$localTimeString = strftime("%b %d, %Y &nbsp; %I:%M %p", $timestamp);
	 }
	 return $localTimeString;
}
?>
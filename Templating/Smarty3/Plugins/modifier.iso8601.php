<?php 
function smarty_modifier_iso8601($timestamp)
{
    return strftime("%Y-%m-%dT%H:%M", $timestamp);
}
?>
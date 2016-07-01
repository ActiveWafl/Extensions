<?php
function smarty_modifier_collapse($string)
{
    return DblEj\Util\Strings::CollapseWhitespace($string, "");
}
?>
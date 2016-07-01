<?php
function smarty_modifier_alphanumeric($string, $nonAlphaReplacement = "", $allowDecimals = false, $collapseWhitespace = false)
{
    if ($collapseWhitespace)
    {
        if ($nonAlphaReplacement)
        {
            //if we dont do this we end up with non-collapsed non-alpha-replacements such as ---
            $string = str_replace($nonAlphaReplacement, " ", $string);
        }
        $string = DblEj\Util\Strings::CollapseWhitespace($string);
    }
    if ($allowDecimals)
    {
        $string = preg_replace("/[^A-Za-z0-9\.{$nonAlphaReplacement}]/", $nonAlphaReplacement, $string);
    } else {
        $string = preg_replace("/[^A-Za-z0-9{$nonAlphaReplacement}]/", $nonAlphaReplacement, $string);
    }
    return  $string;
}
?>
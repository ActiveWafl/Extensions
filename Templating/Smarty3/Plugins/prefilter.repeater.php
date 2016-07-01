<?php
 function smarty_prefilter_repeater($source, Smarty_Internal_Template $template)
 {
     $returnString = str_replace('{repeater ', '{foreach ', $source);
     $returnString = str_replace('{/repeater}', '{/foreach}', $returnString);
     $returnString = str_replace('{nodata}', '{foreachelse}', $returnString);
     return $returnString;
 }
?>
<?php
 function smarty_prefilter_attribute_values($source, Smarty_Internal_Template $template)
 {
     $returnString = preg_replace('/\s+?value="(\[.+?\])"/', ' value="$1" data-attribute-value="$1"', $source);
     return $returnString;
 }
?>

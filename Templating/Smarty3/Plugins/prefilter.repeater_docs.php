<?php
 function smarty_prefilter_repeater_docs($source, Smarty_Internal_Template $template)
 {
     $returnString = str_replace('{repeater_docs ', '{repeater ', $source);
     $returnString = str_replace('{/repeater_docs}', '{/repeater}', $returnString);
     $returnString = str_replace('{nodata_docs}', '{nodata}', $returnString);
     return $returnString;
 }
?>
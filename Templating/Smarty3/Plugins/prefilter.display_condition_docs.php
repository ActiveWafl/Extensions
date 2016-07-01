<?php
 function smarty_prefilter_display_condition_docs($source, Smarty_Internal_Template $template)
 {
     $returnString = str_replace('{display_condition_docs ', '{display_condition ', $source);
     $returnString = str_replace('{/display_condition_docs}', '{/display_condition}', $returnString);
     $returnString = str_replace('{display_alternative_docs ', '{display_alternative ', $returnString);
     $returnString = str_replace('{display_otherwise_docs}', '{display_otherwise}', $returnString);
     return $returnString;
 }
?>
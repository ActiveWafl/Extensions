<?php
 function smarty_prefilter_display_condition($source, Smarty_Internal_Template $template)
 {
     $returnString = str_replace('{display_condition ', '{if ', $source);
     $returnString = str_replace('{/display_condition}', '{/if}', $returnString);
     $returnString = str_replace('{display_alternative ', '{elseif ', $returnString);
     $returnString = str_replace('{display_otherwise}', '{else}', $returnString);
     return $returnString;
 }
?>
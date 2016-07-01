<?php
function smarty_modifier_currency($number)
{
    if (is_numeric($number))
    {
        return number_format($number, 2);
    } else {
        return $number;
    }
}
?>
<?php

function smarty_block_layoutgrid($params, $content, $template, &$repeat) {

    $modelName= isset($params["AssignItem"])?$params["AssignItem"]:"MODEL";
    static $items;
    $spans = intval($params["Spans"]);
    $returnString = "";

    if ($repeat && $content===null)
    {
        $items = $params["CellItems"];
        $returnString .= '<div class="Auto Grid Layout"><div class="Row">';
        $currentItem = reset($items);
        $iteration = 1;
    } else {
        $currentItem = next($items);
        $indexedKeys = array_keys($items);
        $itemKey = key($items);
        $iteration = array_search($itemKey,$indexedKeys);
        if ($iteration !==false)
        {
            $iteration++; //makes 1-based
        }
    }
    $template->assign($modelName, $currentItem);
    if ($content)
    {
        $returnString .= '<div class="Spans'.$spans.' FullHeight">';
        $returnString .= $content;
        $returnString .= '</div>';
    }

    if ($iteration !==false)
    {
        if ((($iteration-1) % (12/$spans)) === 0)
        {
            $returnString .= '</div><div class="Row">';
        }
    }

    if ($iteration == count($items)) {
        $returnString .= '</div></div>';
        $repeat = false;
    } else {
        $repeat = true;
    }
    return $returnString;
}

?>
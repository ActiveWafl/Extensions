<?php
function smarty_modifier_tojson($jsonObject, $reindexArrays = false)
{
    return DblEj\Communication\JsonUtil::EncodeJson($jsonObject, true, $reindexArrays);
}
?>
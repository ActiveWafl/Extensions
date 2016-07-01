<?php

function smarty_block_nominify($params, $content, $template, &$repeat) {
    if (!$repeat) {
        if (isset($content)) {
            return "<!--NOMINIFY-->$content<!--ENDNOMINIFY-->";
        }
    }
}

?>
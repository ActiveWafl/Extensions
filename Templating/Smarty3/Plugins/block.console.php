<?php

function smarty_block_console($params, $content, $template, &$repeat) {
    if (!$repeat) {
        try
        {
            $geshiPath = realpath(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."Transformers");
            require_once("phar://".$geshiPath."/Geshi/GeSHi.phar/geshi.php");
			$header = isset($params["Header"])?$params["Header"]:null;
			$footer = isset($params["Footer"])?$params["Footer"]:null;
			$language = isset($params["Language"])?$params["Language"]:"text";
			if ($header)
			{
				$header = "<header>$header</header>";
			}
			if ($footer)
			{
				$footer = "<footer>$footer</footer>";
			}			
			$geshi = new GeSHi($content, strtolower($language), null);
            $geshi->set_header_type(\GESHI_HEADER_NONE);
			$geshi->set_overall_style("color: #ffffff;");
			$geshi->set_code_style("color: #ffffff;");
            $parsedCode = '<samp>' . $geshi->parse_code() . '</samp>';
            if ($geshi->error()) {
                throw new \DblEj\Extension\ExtensionException("Error while highlighting code: ".$geshi->error(), E_WARNING, $ex);
            }
            return "<div class=\"Console\">$header<div style=\"padding: 1%\">$parsedCode</div>$footer</div>";
            return $parsedCode;
        } 
        catch (\Exception $ex) 
        {
            throw new \DblEj\Extension\ExtensionException("Error while highlighting code", E_WARNING, $ex);
        }
    }
}

?>
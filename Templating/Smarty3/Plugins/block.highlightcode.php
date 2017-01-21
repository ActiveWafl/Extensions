<?php

function smarty_block_highlightcode($params, $content, $template, &$repeat) {
    if (!$repeat) {
        try
        {
			$content = trim($content);
			$language = isset($params["Language"])?$params["Language"]:"php";
			$inline = isset($params["Inline"])?$params["Inline"]:false;
			$header = isset($params["Header"])?$params["Header"]:null;
			$headerSideNote = isset($params["HeaderSideNote"])?$params["HeaderSideNote"]:null;
			$footer = isset($params["Footer"])?$params["Footer"]:null;
			$bgColor1 = isset($params["LineBgColor"])?$params["LineBgColor"]:"#fcfcfc";
			$bgColor2 = isset($params["LineBgColorAlt"])?$params["LineBgColorAlt"]:"#f0f0f0";
			$tabWidth = isset($params["TabWidth"])?$params["TabWidth"]:3;
			$lineNumbers = isset($params["LineNumbers"])?$params["LineNumbers"]:true;
			
			if ($inline) { $lineNumbers = false; }
			$requestedLanguage = $language;
			if (strtolower($language) == "javascriptsignature")
			{
				$language="Actionscript";
			}
            require_once("phar://".str_replace("\\","/",__DIR__)."/../../../Transformers/GeSHi/GeSHi.phar/geshi.php");
            $geshi = new GeSHi($content, $language, null);
			
			if (strtolower($requestedLanguage) == "javascriptsignature")
			{
				$geshi->remove_keyword(2,"function");
				$geshi->remove_keyword(3,"call");
				$geshi->add_keyword(3,"function");			
			}
            $geshi->set_header_type(GESHI_HEADER_NONE);
			$geshi->set_tab_width($tabWidth);
			if ($lineNumbers)
			{
				$geshi->enable_line_numbers(\GESHI_FANCY_LINE_NUMBERS,2);
				$geshi->set_line_style("color: #999999; background: $bgColor1;", "color: #999999; background: $bgColor2;");
				$geshi->set_code_style("color: #000000;");
			}
            $parsedCode = @$geshi->parse_code();
            if ($geshi->error()) {
                throw new \DblEj\Extension\ExtensionException("Error while highlighting code: ".$geshi->error(), E_WARNING);
            }
			
			if ($inline)
			{
				return "<code>$parsedCode</code>";
			} else {
				if ($headerSideNote)
				{
					$headerSideNote = "<small class=\"Float Right\">$headerSideNote</small>";
				}
				if ($header)
				{
					$header = "<header>$header{$headerSideNote}</header>";
				}
				if ($footer)
				{
					$footer = "<footer>$footer</footer>";
				}
				return "<section class=\"CodeContainer\">$header<code>$parsedCode</code>$footer</section>";
			}
        } 
        catch (\Exception $ex) 
        {
            return "<section class=\"CodeContainer\">".$ex->getMessage()."</section>";
        }
    }
}

?>
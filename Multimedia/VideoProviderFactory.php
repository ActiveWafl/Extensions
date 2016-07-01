<?php

namespace Wafl\Extensions\Multimedia;

/**
 * A factory for getting a video provider based on the specified URL or applet code snippet.
 * This factory currently supports: youtube.com and vimeo.com.
 *
 * @TODO: The implementations for YouTube and Vimeo no longer live here.
 */
class VideoProviderFactory
{

    public static function GetProviderFromUrlOrAppletCode($urlOrAppletCode)
    {
        $provider = null;
        if (stristr($urlOrAppletCode, "youtu.be") || stristr($urlOrAppletCode, "youtube.com"))
        {
            $provider = new YouTube();
        }
        else if (stristr($urlOrAppletCode, "vimeo.com"))
        {
            $provider = new Vimeo();
        }
        return $provider;
    }
}
?>
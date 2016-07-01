<?php
namespace Wafl\Extensions\Multimedia\YouTube;

class YouTube
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Multimedia\Integration\IVideoProviderExtension
{
    public function Initialize(\DblEj\Application\IApplication $app)
    {

    }
    function ResolveUrl($url)
    {
         return $url;
    }
    function GetInfoFromId($videoCode)
    {
         $videoInfo = new \DblEj\Multimedia\VideoInfo();
         $videoInfoJson = \DblEj\Communication\Http\Util::SendRequest("http://gdata.youtube.com/feeds/api/videos/$videoCode?v=2&alt=json");
         if ($videoInfoJson)
         {
               $videoInfoArray = \DblEj\Communication\JsonUtil::DecodeJson($videoInfoJson);
               if (isset($videoInfoArray["entry"]) && isset($videoInfoArray["entry"]["media\$group"]))
               {
                   $videoInfo->Set_Title($videoInfoArray["entry"]["title"]["\$t"]);
                   $videoInfo->Set_Description($videoInfoArray["entry"]["media\$group"]["media\$description"]["\$t"]);
                   $defaultThumb = $videoInfoArray["entry"]["media\$group"]["media\$thumbnail"][0]["url"];
                   $defaultThumb = str_replace("default.jpg", "0.jpg", $defaultThumb);
                   $videoInfo->Set_ThumbnailImage($defaultThumb);
               }
         }
         return $videoInfo;
    }
    function GetThumbnailFromVideoId($videoId)
    {
         return "http://img.youtube.com/vi/$videoId/2.jpg";
    }
    function GetVideoIdFromUrl($url,$resolveRedirects=false)
    {
         $videoCode = str_replace("http://", "", $url);
         $videoCode = str_replace("https://", "", $videoCode);
         $videoCode = str_replace("www.", "", $videoCode);
         $videoCode = str_replace("youtu.be/", "", $videoCode);
         $videoCode = str_replace("youtube.com/", "", $videoCode);
         return $videoCode;
    }
    function GetVideoIdFromEmbedCode($embedCode)
    {
         $videoCode = $embedCode;
         $codeStart = stripos($videoCode, "embed/");
         $videoCode = substr($videoCode, $codeStart+6);
         $videoCode = trim($videoCode);
         $codeEnd = stripos($videoCode, "\"");
         $videoCode	= substr($videoCode,0,$codeEnd);
         return $videoCode;
    }
    function GetUrlFromVideoId($videoid)
    {
         return "http://youtu.be/$videoid";
    }
    function RequiresResolve301()
    {
         return false;
    }
    function GetEmbedCodeFromVideoId($videoId)
    {
         return "<iframe width=\"420\" height=\"315\" src=\"http://www.youtube.com/embed/$videoId\" frameborder=\"0\" allowfullscreen></iframe>";
    }
}
?>
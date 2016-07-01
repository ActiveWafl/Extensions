<?php
namespace Wafl\Extensions\Multimedia\Vimeo;
class Vimeo
extends \DblEj\Extension\ExtensionBase
implements \DblEj\Multimedia\Integration\IVideoProviderExtension
{
    public function Initialize(\DblEj\Application\IApplication $app)
    {
    }
    function ResolveUrl($url)
    {
         $videoId = $this->GetVideoIdFromUrl($url);
         if (!is_numeric($videoId))
         {
               $headers = get_headers($url);
               foreach ($headers as $header)
               {
                    if (stristr($header,"Location:"))
                    {
                         $videoCode = str_ireplace("Location:", "", $header);
                         $videoCode=trim($videoCode);
                         $videoCode= str_replace("/", "", $videoCode);
                         $url = "http://vimeo.com/$videoCode";
                    }
               }
         }
         return $url;
    }
    function GetInfoFromId($videoCode)
    {
         $videoInfo = new \DblEj\Multimedia\VideoInfo();
         $videoInfoXml = \DblEj\Communication\Http\Util::SendRequest("http://vimeo.com/api/v2/video/$videoCode.xml");
         if ($videoInfoXml)
         {
               $videoInfoXml = new \SimpleXMLElement($videoInfoXml);
               $videoInfoArray = (array)$videoInfoXml;
               if (isset($videoInfoArray["video"]))
               {
                   $videoInfoArray = (array)$videoInfoArray["video"];
                   $videoInfo->Set_Title($videoInfoArray["title"]);
                   $videoInfo->Set_Description($videoInfoArray["description"]);
                   $videoInfo->Set_ThumbnailImage($videoInfoArray["thumbnail_medium"]);
               }
         }
         return $videoInfo;
    }
    function GetThumbnailFromUrl($url)
    {
         $videoId = $this->GetVideoIdFromUrl($url,true);
         return $this->GetThumbnailFromVideoId($videoId);
    }
    function GetThumbnailFromVideoId($videoId)
    {
         $videoInfo = $this->GetInfoFromId($videoId);
         return $videoInfo->Get_ThumbnailImage();
    }
    function GetUrlFromVideoId($videoid)
    {
         return "http://vimeo.com/$videoid";
    }
    function GetVideoIdFromUrl($url,$resolveRedirects=false)
    {
         if ($resolveRedirects)
         {
               $url = $this->ResolveUrl($url);
         }
         $videoCode = str_replace("http://", "", $url);
         $videoCode = str_replace("https://", "", $videoCode);
         $videoCode = str_replace("www.", "", $videoCode);
         $videoCode = str_replace("vimeo.com/", "", $videoCode);
         return $videoCode;
    }
    function GetVideoIdFromEmbedCode($embedCode)
    {
         $videoCode = $embedCode;
         $codeStart = stripos($videoCode, "video/");
         $videoCode = substr($videoCode, $codeStart+6);
         $videoCode = trim($videoCode);
         $codeEnd = stripos($videoCode, "?");
         if ($codeEnd > -1)
         {
               $videoCode	= substr($videoCode,0,$codeEnd);
         }

         $codeEnd = stripos($videoCode, "\"");
         if ($codeEnd > -1)
         {
               $videoCode	= substr($videoCode,0,$codeEnd);
         }

         return $videoCode;
    }
    function RequiresResolve301()
    {
         return true;
    }
    function GetEmbedCodeFromVideoId($videoId)
    {
         return "<iframe src=\"http://player.vimeo.com/video/$videoId?portrait=0&amp;badge=0&amp;color=ffffff\" width=\"500\" height=\"281\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>";
    }
}
?>
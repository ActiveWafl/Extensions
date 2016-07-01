<?php
namespace Wafl\Extensions\Commerce;
class GoogleQr
{
	 public static function GetImageLink($width,$height,$encodedString,$encoding="UTF-8")
	 {
		  return "https://chart.googleapis.com/chart?chs=".$width."x".$height."&cht=qr&chl=$encodedString&choe=$encoding";
	 }
}
<?php

function debug()
{
  $args = func_get_args();
  return forward_static_call_array(["\App\Debug",'dd'], $args);
}

function slug( $str )
{
    return \Illuminate\Support\Str::slug( $str, '_');
}

function numberAbbreviation($number) {
    $abbrevs = array(12 => "T", 9 => "B", 6 => "M", 3 => "K", 0 => "");
    foreach($abbrevs as $exponent => $abbrev) {
        if($number >= pow(10, $exponent)) {
            $display_num = $number / pow(10, $exponent);
            $decimals = ($exponent >= 3 && round($display_num) < 100) ? 1 : 0;
            return number_format($display_num,$decimals) . $abbrev;
        }
    }
    return $number;
}

function search_imagen( $entidad )
{
    $fileSystemPrefix = ROOT . DS . 'public';
    $fileSearch = $entidad->imagen;

    $fallBackImage = '/static/only-logo.png';

    if( $fileSearch != '' && file_exists( $fileSystemPrefix . $fileSearch ) ) {
        return $fileSearch;
    }
    return $fallBackImage;
}

function isLogged()
{
    return isset( $_SESSION['user'] );
}

function url_to_domain($url)
{
    $host = @parse_url($url, PHP_URL_HOST);
    // If the URL can't be parsed, use the original URL
    // Change to "return false" if you don't want that
    if (!$host)
        $host = $url;
    // The "www." prefix isn't really needed if you're just using
    // this to display the domain to the user
    if (substr($host, 0, 4) == "www.")
        $host = substr($host, 4);
    // You might also want to limit the length if screen space is limited
    if (strlen($host) > 50)
        $host = substr($host, 0, 47) . '...';
    return $host;
}

function timeago($date) {
	   $timestamp = strtotime($date);

	   $strTime = array("segundo", "minuto", "hora", "dia", "mes", "año");
	   $length = array("60","60","24","30","12","10");

	   $currentTime = time();
	   if($currentTime >= $timestamp) {
			$diff     = time()- $timestamp;
			for($i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++) {
			$diff = $diff / $length[$i];
			}

			$diff = round($diff);
			return $diff . " " . $strTime[$i] . "s";
	   }
	}

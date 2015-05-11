<?php
$libraries = json_decode(file_get_contents("library.json"), true);
function recognition($str) {
	global $libraries;
	$distances = [];
	foreach($libraries as $number => $library) {
		$distance = [];
		foreach($library as $item) {
			$distance[] = levenshtein($str, $item);
		}
		asort($distance);
		$distances[$number] = $distance[0];
	}
	asort($distances);
	$distances = array_keys($distances);
	return $distances[0];
}
$filename = "http://202.204.105.195:8800/include/function/chekcode.php?43"; 
$im = imagecreatefrompng($filename); 
$origin = mt_rand();
imagepng($im, $origin.".png");
list($w,$h) = getimagesize($filename); 
$colors = []; 
for($x=0;$x<$w;$x++) { 
    for($y=0;$y<$h;$y++) { 
        $i = imagecolorat($im, $x, $y); 
        $c = imagecolorsforindex($im, $i); 
        $colors[$x][$y] = array_values($c); 
    } 
} 
  
$white = [255,255,255,0]; 
$res = []; 
foreach($colors as $cx)  
    foreach($cx as $cy)  
        $res[] = implode(",", $cy); 
$res = array_count_values($res); 
$res = array_keys($res); 
$res = explode(",", $res[1]); 
for($x=0;$x<$w;$x++) { 
    for($y=0;$y<$h;$y++) { 
        $i = imagecolorat($im, $x, $y); 
        $c = $colors[$x][$y]; 
        $c = $res == $c ? $white : $c; 
        $colors[$x][$y] = $c; 
        imagecolorset($im, $i, $c[0], $c[1], $c[2]); 
    } 
} 
  
/** scan line **/
$line = []; 
for($x=0;$x<$w;$x++) { 
    $wh = 1; 
    for($y=0;$y<$h;$y++) { 
        if($colors[$x][$y] != $white) { 
            $wh = 0; 
            break; 
        } 
    } 
    $line[$x] = $wh; 
} 
$res = []; 
$start = 0; 
preg_match_all('/1(0+)1?/', implode("", $line), $m); 
for($i=0, $number=count($m[1]);$i<$number;$i++) { 
    $res[$i] = []; 
    for($x=$start;$x<$w;$x++) { 
        if($line[$x]) continue; 
        $res[$i][] = $x; 
        if($line[$x+1]) { 
            $start = $x+1; 
            break; 
        } 
    } 
} 

$result = '';
foreach($res as $key => $item) { 
	$str = "";
    //$single = imagecreatetruecolor(count($item), $h); 
    foreach($item as $x => $ox) { 
        for($y=0;$y<$h;$y++) { 
            $c = $colors[$ox][$y]; 
            $str .= $c!=$white ? '1' : '0';
            //$c = imagecolorallocate($single, $c[0], $c[1], $c[2]); 
            //imagefilledrectangle($single, $x, $y, $x, $y, $c); 
        } 
    } 
    $result .= recognition($str);
    //imagepng($single, "libs/".mt_rand().".png"); 
    // imagedestroy($single); 
} 

header("content-type:application/json");
echo $_GET['callback'].'('.json_encode([$origin, $result]).')';
<?php 
$url = "http://202.204.105.195:8800/include/function/chekcode.php?43"; 
$im = imagecreatefrompng($url); 
imagepng($im, "origin.png");
list($w,$h) = getimagesize($url); 
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
foreach($res as $key => $item) { 
    $single = imagecreatetruecolor(count($item), $h); 
    foreach($item as $x => $ox) { 
        for($y=0;$y<$h;$y++) { 
            $c = $colors[$ox][$y]; 
            $c = imagecolorallocate($single, $c[0], $c[1], $c[2]); 
            imagefilledrectangle($single, $x, $y, $x, $y, $c); 
        } 
    } 
    imagepng($single, mt_rand().".png"); 
    imagedestroy($single); 
} 
imagepng($im, "handle.png"); 
imagedestroy($im); 
  
  
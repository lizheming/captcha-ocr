<?php
$white = [255,255,255,0];
$result = [];
for($i=1;$i<10;$i++) {
	$res = [];
	foreach(glob("$i/*") as $item) {
		$im = imagecreatefrompng($item);
		list($w, $h) = getimagesize($item);
		$str = "";
		for($x=0;$x<$w;$x++) {
			for($y=0;$y<$h;$y++) {
				$c = array_values(imagecolorsforindex($im, imagecolorat($im, $x, $y)));
				$str .= $c!=$white ? '1' : '0';
			}
		}
		$res[] = $str;
	}
	$result[$i] = $res;
}
file_put_contents("library.json", json_encode($result));
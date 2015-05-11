## 网关中心自服务登陆验证码识别

新更新的[网关中心自服务](http://202.204.105.195/services.php)（Srun 3000 3.00rc14.17.5）登陆增加了验证码的支持，这是本方案的初衷。本教程采用PHP(PHP>=5.4)语言编写，根据思路你完全可以使用其他语言编写，欢迎将其他语言版本pull request进本项目。

### 1. 建立字模库

做到程序自动识别验证码的第一步是要先创建一个字模库。首先我们来看一下它的验证码有什么特点：

![](https://git.cugbteam.org/lizheming/captcha-ocr/raw/master/example/origin.png)

可以很明显的发现这是一个由4位颜色不同的数组成的，底色为白色，夹杂混淆杂色块的验证码。然后我们来说一下原理，第一步就是先把混淆色去除掉，然后对其进行切割，人工对大量切割好的验证码进行分类并最终得到字模库。

#### 1.1 读取图像

由于验证码的地址非常温馨都是一个固定网址，这为我们创建字模库减少了不少麻烦。

	$url = "http://202.204.105.195:8800/include/function/chekcode.php"; 
	$im = imagecreatefrompng($url); 
	list($w,$h) = getimagesize($url); 
	$colors = []; 
	for($x=0;$x<$w;$x++) { 
	    for($y=0;$y<$h;$y++) { 
	        $i = imagecolorat($im, $x, $y); 
	        $c = imagecolorsforindex($im, $i); 
	        $colors[$x][$y] = array_values($c); 
	    } 
	} 

代码中用到的函数：

- [imagecreatefrompng()](http://cn2.php.net/manual/zh/function.imagecreatefrompng.php)
- [getimagesize()](http://cn2.php.net/manual/zh/function.getimagesize.php)
- [imagecolorat()](http://cn2.php.net/manual/zh/function.imagecolorat.php)
- [imagecolorsforindex()](http://cn2.php.net/manual/zh/function.imagecolorsforindex.php)

代码返回的结果：

- `$colors`：横坐标和纵坐标作为索引，对应坐标下的像素的RGBA颜色值的一个三维数组。

#### 1.2 去除混淆色

去除混淆色有很多种方法，这里给两个思路：

1. 由于混淆色一般都是单个色块孤立存在，所以可以考虑检查当前色块的旁边8个位置是否基本都是背景色块，如果是则基本可以判定为是混淆色。
2. 统计图片内颜色的出现次数后我发现，混淆色的数量总是比验证码的颜色出现的次数要多，当然最多的肯定是背景色了。所以我统计完之后直接得到混淆色的颜色并加以去除。（不能保证100%的正确率）

第一种方法比较靠谱，不过我这里使用的是第二种方法。

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

代码中用到的函数：

- [implode()](http://cn2.php.net/manual/zh/function.implode.php)
- [array_count_values()](http://cn2.php.net/manual/zh/function.array-count-values.php)
- [array_keys()](http://cn2.php.net/manual/zh/function.array-keys.php)
- [explode()](http://cn2.php.net/manual/zh/function.explode.php)
- [imagecolorat()](http://cn2.php.net/manual/zh/function.imagecolorat.php)
- [imagecolorset()](http://cn2.php.net/manual/zh/function.imagecolorset.php)

代码返回的结果：

- `$res`: 杂色的RGBA值  
- `$colors`:去除了杂色之后的图片颜色索引数组。

图片处理后的效果：

![](http://git.cugbteam.org/lizheming/veritifycodeai/raw/master/example/handle.png)

### 1.3 切片取模

去除杂色之后就可以进行切片了。切片这里用的是暴力的扫线法，以横坐标做为基准循环扫纵坐标，如果当前横坐标下所有的纵坐标的点都为背景色则可判定为分割线，如果有色块则证明是需要读取内容。

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

代码中用到的函数：

- [preg_match_all()](http://cn2.php.net/manual/zh/function.preg-match-all.php)
- [imagecreatetruecolor()](http://cn2.php.net/manual/zh/function.imagecreatetruecolor.php)
- [imagecolorallocate()](http://cn2.php.net/manual/zh/function.imagecolorallocate.php)
- [imagefilledrectangle()](http://cn2.php.net/manual/zh/function.imagefilledrectangle.php)
- [mt_rand()](http://cn2.php.net/manual/zh/function.mt-rand.php)
- [imagedestory()](http://cn2.php.net/manual/zh/function.imagedestroy.php)

代码返回的结果：

- `$line`:以横坐标为索引，当前索引下的横坐标是否全为背景色为值的数组  
- `$res`:验证码数值区域范围的二维数组。使用`preg_match_all`这个小技巧是为了防止有不是固定数字的验证码存在。
- `$single`:切片后的单个验证码数值

图片处理后的效果：
![](http://git.cugbteam.org/lizheming/veritifycodeai/raw/master/example/554298580.png)
![](http://git.cugbteam.org/lizheming/veritifycodeai/raw/master/example/1242362580.png)
![](http://git.cugbteam.org/lizheming/veritifycodeai/raw/master/example/1990418714.png)
![](http://git.cugbteam.org/lizheming/veritifycodeai/raw/master/example/217019501.png)
### 1.4 建立字模库

这一步非常简单，重复以上步骤获得大量的基本数据（本代码中附带的库大概是每组数字35个样本得到的）后人工对其进行分类，建立了名称分别为1-9的文件夹存储它们。分类好后我们将图片字符化后与正确数值建立索引关系。字符化简单的来说就是将有颜色的地方标记上得到一个样子的“模型”。至于为什么要字符化稍后我们在验证码识别步骤中解说。

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

代码中使用到的函数：

- [array_values()](http://cn2.php.net/manual/zh/function.array-values.php)
- [file_put_contents()](http://cn2.php.net/manual/zh/function.file-put-contents.php)

代码返回的结果：

- `$result`: 单个验证码数值取模字符串化后与正确数值对应建立的一个二维数组
- `library.json`:将`$result`数组进行JSON对象化后存储成的文件，方便以后调用库

## 2. 验证码的识别

有了这个字模库之后我们应该怎么做呢？验证码切片我们已经会了，但是切片之后我们怎么将其与库对应并正确识别出来呢？我的第一反应就是通过图片的相似程度，相似程度越高则两者越是接近。图片的相似度并不好直接计算，所以我对其进行了字符串化，并使用了[Levenshtein距离](http://zh.wikipedia.org/zh-cn/%E7%B7%A8%E8%BC%AF%E8%B7%9D%E9%9B%A2)算法计算字符串之间的相似程度，进而获得最终的数值。Levenshtein距离又称为编辑距离，如果一个字符串只要通过较少的步骤变成另外一个字符串，我们就可以称他们有高相似度。通过与字模库中的每个字模进行编辑距离的对比，所有数字中那个编辑距离最小则我们就可以定义其为这个数字。

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

代码中使用到的函数:

- [json_decode()](http://cn2.php.net/manual/zh/function.json-decode.php)
- [file_get_contents()](http://cn2.php.net/manual/zh/function.file-get-contents.php)
- [levenshtein()](http://cn2.php.net/manual/zh/function.levenshtein.php)
- [asort](http://cn2.php.net/manual/zh/function.asort.php)

## 3. 结语与效果预览

至此我们的验证码识别就完美落幕了，总的来说验证码识别只要得到一套基于该验证码下的字模库就好说了。根据验证码的复杂度，制作字模库的难易程度也不一样，有些会增加背景色，有些也会增加混淆线，这个时候我们就要对它们进行相应的处理。

最终效果预览：

![](http://git.cugbteam.org/lizheming/veritifycodeai/raw/master/example/result.jpg)

## LICENSE  
MIT, see [LICENSE](https://git.cugbteam.org/lizheming/captcha-ocr/blob/master/LICENSE).
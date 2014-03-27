<?php
define('USE_HOWSET','');
define('USE_WIDTH','w');
define('USE_HEIGHT','h');
define('USE_AUTO','a');
/*
	IMG_FILTER_NEGATE: Reverses all colors of the image. 
	IMG_FILTER_GRAYSCALE: Converts the image into grayscale. 
	IMG_FILTER_BRIGHTNESS: Changes the brightness of the image. Use arg1 to set the level of brightness. 
	IMG_FILTER_CONTRAST: Changes the contrast of the image. Use arg1 to set the level of contrast. 
	IMG_FILTER_COLORIZE: Like IMG_FILTER_GRAYSCALE, except you can specify the color. Use arg1, arg2 and arg3 in the form of red, green, blue and arg4 for the alpha channel. The range for each color is 0 to 255. 
	IMG_FILTER_EDGEDETECT: Uses edge detection to highlight the edges in the image. 
	IMG_FILTER_EMBOSS: Embosses the image. 
	IMG_FILTER_GAUSSIAN_BLUR: Blurs the image using the Gaussian method. 
	IMG_FILTER_SELECTIVE_BLUR: Blurs the image. 
	IMG_FILTER_MEAN_REMOVAL: Uses mean removal to achieve a "sketchy" effect. 
	IMG_FILTER_SMOOTH: Makes the image smoother. Use arg1 to set the level of smoothness. 
	IMG_FILTER_PIXELATE: Applies pixelation effect to the image, use arg1 to set the block size and arg2 to set the pixelation effect mode
*/
class jImage{
static public $jpeg_quality = 80;
static public $image_replace = false;
static private $mem_types = array('','gif','jpeg','png');
static private function blank( $width,$height,$use_alpha = false ){
	$image = imagecreatetruecolor($width, $height);
	if( $use_alpha ){
		imagesavealpha($image, true);
		$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
		imagefill($image, 0, 0, $transparent);
	}
	return $image;
}
static public function createFrom( $ext,$file ){
	$func = 'imagecreatefrom'.$ext;
	if( !is_callable($func) ){ 
		throw new Exception( 'gb functon '.$func.' no exists' );
		return '';
	}
	return $func($file);
}
static public function saveImage( $img,$file_output,$ext='jpeg' ){
	if ( $ext == 'jpeg' ) 
		return imagejpeg($img,$file_output,self::jpeg_quality);
	$func = 'image'.$ext;
	return $func($img,$file_output);
}
static function joinAll( $path,$file_output,$size,$org = USE_HEIGHT,$filter_add = false,$filter_use = false ){
	if( file_exists( $path ) and ( self::image_replace or !file_exists( $file_output ) ) ){
		self::size = 0;
		self::size1 = $size;
		self::org = $org;
		self::each($path,function($Photo,$_this){
			list($w_i, $h_i, $type) = getimagesize($Photo);
			$_this->size+= ($_this->org == USE_HEIGHT)?round($w_i*($_this->size1/$h_i)):round($h_i*($_this->size1/$w_i));
		});
		$w = (self::org == USE_HEIGHT)?self::size:self::size1;
		$h = (self::org != USE_HEIGHT)?self::size:self::size1;
		self::img_o = self::blank( $w, $h );
		self::x = 0;
		self::y = 0;
		self::each($path,function($Photo,$_this){
			$h = 0;
			$w = $_this->size1;
			$src = $_this->resize($Photo,$w,$h,$_this->org);
			imagecopy($_this->img_o, $src, $_this->x, $_this->y, 0, 0, $w,$h);
			imagedestroy($src);
			$_this->x+=($_this->org == USE_HEIGHT)?$w:0;
			$_this->y+=($_this->org != USE_HEIGHT)?$h:0;
		});
		if( $filter_use!==false ){
			imagefilter(self::img_o, $filter_use);
		}
		if( $filter_add!==false ){
			$img_o1 = self::blank( self::org == USE_HEIGHT?self::size:self::size1*2, self::org != USE_HEIGHT?self::size:self::size1*2 );
			imagecopy($img_o1, self::img_o, 0, 0, 0, 0, self::org == USE_HEIGHT?self::size:self::size1*2, self::org != USE_HEIGHT?self::size:self::size1*2);
			imagefilter(self::img_o, $filter_add);//IMG_FILTER_GRAYSCALE);
			imagecopy($img_o1, self::img_o, self::org == USE_HEIGHT?0:self::size1, self::org != USE_HEIGHT?0:self::size1, 0, 0, self::org == USE_HEIGHT?self::size:self::size1*2, self::org != USE_HEIGHT?self::size:self::size1*2);
			imagedestroy(self::img_o);
			return self::saveImage($img_o1,$file_output);
		}
		return self::saveImage(self::img_o,$file_output);
	}
}
static function resize( $file,&$width,&$height=false,$org=USE_AUTO,&$type='jpeg' ) {
	if( self::isImage($file,$w_i,$h_i,$type) ){
		if( $org == USE_AUTO ){
			if($w_i > $h_i )
				$height = ($width/$w_i) * $h_i;
			else 
				$width = ($height/$h_i) * $w_i;
			if( $w_i<=$width )$width = $w_i;
			if( $h_i<=$height )$height = $h_i;
		}else if( $org==USE_WIDTH ){
			$height = ( $width/$w_i ) * $h_i;
		}else if( $org==USE_HEIGHT ){
			$height = $width;
			$width = ( $height/$h_i ) * $w_i;
		}
		$ext = $type;
		$img = self::createFrom($ext,$file);
		$img_o = self::blank($width, $height);
		imagecopyresampled($img_o, $img, 0, 0, 0, 0, $width, $height, $w_i, $h_i);
		imagedestroy($img);
		return $img_o;
	}
	return null;
}
/**
 * Обрезка изображения
 *
 * Функция работает с PNG, GIF и JPEG изображениями.
 * Обрезка идёт как с указанием абсоютной длины, так и относительной (отрицательной).
 *
 * @param string Расположение исходного файла
 * @param string Расположение конечного файла
 * @param array Координаты обрезки, по умолчанию делает квадрат по меньгей из сторон
 * @return bool
 */
static public function crop($file_input, $file_output, $crop = 'square') {
	if( !self::isImage( $file_input,$w_i, $h_i, $ext ) )return false;
	if ( $crop == 'square' ) {
		$min = $w_i;
		if ($w_i > $h_i) $min = $h_i;
		$w_o = $h_o = $min;
	} else {
		list($x_o, $y_o, $w_o, $h_o) = $crop;
		if ($percent) {
			$w_o *= $w_i / 100;
			$h_o *= $h_i / 100;
			$x_o *= $w_i / 100;
			$y_o *= $h_i / 100;
		}
    	if ($w_o < 0) $w_o += $w_i;
	    $w_o -= $x_o;
	   	if ($h_o < 0) $h_o += $h_i;
		$h_o -= $y_o;
	}
	$img = self::createFrom($ext,$file_input);
	$img_o = self::blank($w_o, $h_o);
	imagecopy($img_o, $img, 0, 0, $x_o, $y_o, $w_o, $h_o);
	self::saveImage( $img_o,$file_output,$ext );
	return true;
}
static public function clearDir ($directory,&$countfile=0,&$countdir=0){
	$dir = opendir($directory);
	while(($file = readdir($dir))){
		if ( is_file ($directory."/".$file)){
			unlink ($directory."/".$file);
			$countfile++;
		}else if ( is_dir ($directory."/".$file) && ($file != ".") && ($file != "..")){
			clearDir ($directory."/".$file,$countfile,$countdir); 
			unlink($directory."/".$file);			
			$countdir++;
		}
	}
	closedir ($dir);
}
static public function each($path,$callback,$ext = 'jpg', $open_dir = false){
	if( file_exists( $path ) and is_dir($path) and is_callable($callback) ){
		$dir = opendir($path);
		while(($file = readdir($dir))){
			if ( is_file ($path."/".$file)){
				$fi = pathinfo($path."/".$file);
				$obj = isset($fi["extension"])?strtolower($fi["extension"]):'';
				if( in_array($obj,explode(',',$ext)) )
					$callback( $path."/".$file,$this,$obj,$fi );
			}else if ( $open_dir and is_dir($path."/".$file) and ($file != ".") and ($file != "..") ){
				self::each($path."/".$file,$callback,$ext,true); 
			}
		}
	}else throw new Exception( 'path '.$path.' no exists' );
}
static public function isImage( $filename,&$w=0,&$h=0,&$type='' ){
	if(!is_file($filename))return false;
	list($w, $h, $type1) = getimagesize($filename);
	$type = @self::mem_types[$type1];
	return ( !empty($type) and $w and $h and isset(self::mem_types[$type1]) );
}
static  public function filter($file,$filter=false, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null){
	if( self::isImage($file,$w,$h,$type) ){
		$func = 'imagecreatefrom'.$type;
		$img = $func($file);
		if( is_callable($filter) ){
			$img = $filter($img,$file,$type,$w,$h);
		}else if( $filter!==false ){
			imagefilter($img, $filter,$arg1,$arg2,$arg3,$arg4);
		}
		self::saveImage($img,$file.'.'.$type);
		if(self::image_replace){
			unlink($file);
			rename($file.'.'.$type,$file);
		}
	}
	
}
static  public function thumb( $file,$thumb,$width,$height=false,$org=USE_AUTO ){
	if(self::image_replace or !file_exists( $thumb ) and self::isImage($file)){
		$img = self::resize($file,$width,$height,$org,$ext);
		self::saveImage( $img,$thumb,$ext );
		imagedestroy($img);
	}
	return $thumb;
}

static  public function _thumb( $file,$width,$height=false,$org=USE_HOWSET ){
	$out = 'files/thumb/'.$width.'x'.$height.'x'.basename(ROOT.$file);
	if(!file_exists(ROOT.$out))
		self::thumb(ROOT.$file,ROOT.$out,$width,$height,$org );
	return '/'.$out;
}
}

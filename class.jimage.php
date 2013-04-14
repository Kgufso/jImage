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
error_reporting(E_ERROR);
class jImage extends main{
public $jpeg_quality = 80;
public $image_replace = false;
private $mem_types = array('','gif','jpeg','png');
private function blank( $width,$height,$use_alpha = false ){
	$image = imagecreatetruecolor($width, $height);
	if( $use_alpha ){
		imagesavealpha($image, true);
		$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
		imagefill($image, 0, 0, $transparent);
	}
	return $image;
}
private function saveImage( $img,$file_output,$ext='jpeg' ){
	if ( $ext == 'jpeg' ) 
		return imagejpeg($img,$file_output,$this->jpeg_quality);
	$func = 'image'.$ext;
	return $func($img,$file_output);
}
function joinAll( $path,$file_output,$size,$org = USE_HEIGHT,$filter_add = false,$filter_use = false ){
	if( file_exists( $path ) and ($this->image_replace or !file_exists( $file_output )) ){
		$this->size = 0;
		$this->size1 = $size;
		$this->org = $org;
		$this->each($path,function($Photo,$_this){
			list($w_i, $h_i, $type) = getimagesize($Photo);
			$_this->size+= ($_this->org == USE_HEIGHT)?round($w_i*($_this->size1/$h_i)):round($h_i*($_this->size1/$w_i));
		});
		$w = ($this->org == USE_HEIGHT)?$this->size:$this->size1;
		$h = ($this->org != USE_HEIGHT)?$this->size:$this->size1;
		$this->img_o = $this->blank( $w, $h );
		$this->x = 0;
		$this->y = 0;
		$this->each($path,function($Photo,$_this){
			$h = 0;
			$w = $_this->size1;
			$src = $_this->resize($Photo,$w,$h,$_this->org);
			imagecopy($_this->img_o, $src, $_this->x, $_this->y, 0, 0, $w,$h);
			imagedestroy($src);
			$_this->x+=($_this->org == USE_HEIGHT)?$w:0;
			$_this->y+=($_this->org != USE_HEIGHT)?$h:0;
		});
		if( $filter_use!==false ){
			imagefilter($this->img_o, $filter_use);
		}
		if( $filter_add!==false ){
			$img_o1 = $this->blank( $this->org == USE_HEIGHT?$this->size:$this->size1*2, $this->org != USE_HEIGHT?$this->size:$this->size1*2 );
			imagecopy($img_o1, $this->img_o, 0, 0, 0, 0, $this->org == USE_HEIGHT?$this->size:$this->size1*2, $this->org != USE_HEIGHT?$this->size:$this->size1*2);
			imagefilter($this->img_o, $filter_add);//IMG_FILTER_GRAYSCALE);
			imagecopy($img_o1, $this->img_o, $this->org == USE_HEIGHT?0:$this->size1, $this->org != USE_HEIGHT?0:$this->size1, 0, 0, $this->org == USE_HEIGHT?$this->size:$this->size1*2, $this->org != USE_HEIGHT?$this->size:$this->size1*2);
			imagedestroy($this->img_o);
			return $this->saveImage($img_o1,$file_output);
		}
		return $this->saveImage($this->img_o,$file_output);
	}
}
function resize( $file,&$width,&$height=false,$org=USE_HOWSET,&$type='jpeg' ) {
	if( $this->isImage($file,$w_i,$h_i,$type) ){
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
		$func = 'imagecreatefrom'.$ext;
		if( !is_callable($func) ){ 
			throw new Exception( 'gb functon '.$func.' no exists' );
			return '';
		}
		$img = $func($file);
		$img_o = $this->blank($width, $height);
		imagecopyresampled($img_o, $img, 0, 0, 0, 0, $width, $height, $w_i, $h_i);
		imagedestroy($img);
		return $img_o;
	}
	return null;
}
function each($path,$callback,$ext = 'jpg'){
	if( file_exists( $path ) and is_callable($callback) ){
		$Handler = glob ($path . '*.'.$ext);
		foreach ($Handler as $file){
			$fi = pathinfo($file);
			$obj = isset($fi["extension"])?strtolower($fi["extension"]):'';
			$callback( $file,$this,$obj,$fi );
		}
	}else throw new Exception( 'path '.$path.' no exists' );
}
public function isImage( $filename,&$w=0,&$h=0,&$type='' ){
	if(!is_file($filename))return false;
	list($w, $h, $type1) = getimagesize($filename);
	$type = @$this->mem_types[$type1];
	return ( !empty($type) and $w and $h and isset($this->mem_types[$type1]) );
}
public function filter($file,$filter=false){
	if($this->isImage($file,$w,$h,$type)){
		$func = 'imagecreatefrom'.$type;
		$img = $func($file);
		if( is_callable($filter) ){
			$img = $filter($img,$file,$type,$w,$h);
		}else if( $filter!==false ){
			imagefilter($img, $filter);
		}
		$this->saveImage($img,$file.'.'.$type);
		if($this->image_replace){
			unlink($file);
			rename($file.'.'.$type,$file);
		}
	}
	
}
function thumb( $file,$thumb,$width,$height=false,$org=USE_HOWSET ){
	if($this->image_replace or !file_exists( $thumb ) and $this->isImage($file)){
		$img = $this->resize($file,$width,$height,$org,$ext);
		$this->saveImage( $img,$thumb,$ext );
		imagedestroy($img);
	}
	return $thumb;
}
function _thumb( $file,$width,$height=false,$org=USE_HOWSET ){
	$out = 'files/thumb/'.$width.'x'.$height.'x'.basename(ROOT.$file);
	if(!file_exists(ROOT.$out))
		$this->thumb(ROOT.$file,ROOT.$out,$width,$height,$org );
	return '/'.$out;
}
}

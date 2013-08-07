jImage
======
Class fot packet process image

snippets

Low quality for all jpg images in folder (last line 'true' means - include all subfolders)

$w>1000 - only image with width > 1000px

filemtime($file)<time()-3600 - only image which change < 1 hours

    include 'class.jimage.php';
    set_time_limit(0);
    $ji = new jImage();
    $ji->jpeg_quality = 50;
    $ji->image_replace = true;
    $ji->each(dirname(__FILE__),function( $file,$_this,$ext ){
      if( $_this->isImage($file,$w,$h,$mrype)and $w>1000 and filemtime($file)<time()-3600 ){
        echo $file,'<br>';
        $_this->saveImage($_this->createFrom($mrype,$file),$file,$mrype);
      }
    },'jpg',true);

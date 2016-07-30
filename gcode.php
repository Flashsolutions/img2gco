<?php

function ImageFlip ( $imgsrc, $mode )
{

    $width                        =    imagesx ( $imgsrc );
    $height                       =    imagesy ( $imgsrc );

    $src_x                        =    0;
    $src_y                        =    0;
    $src_width                    =    $width;
    $src_height                   =    $height;

    switch ( $mode )
    {

        case '1': //vertical
            $src_y                =    $height -1;
            $src_height           =    -$height;
        break;

        case '2': //horizontal
            $src_x                =    $width -1;
            $src_width            =    -$width;
        break;

        case '3': //both
            $src_x                =    $width -1;
            $src_y                =    $height -1;
            $src_width            =    -$width;
            $src_height           =    -$height;
        break;

        default:
            return $imgsrc;

    }

    $imgdest                    =    imagecreatetruecolor ( $width, $height );

    if ( imagecopyresampled ( $imgdest, $imgsrc, 0, 0, $src_x, $src_y , $width, $height, $src_width, $src_height ) )
    {
        return $imgdest;
    }

    return $imgsrc;

}
function map($value, $fromLow, $fromHigh, $toLow, $toHigh) {
    $fromRange = $fromHigh - $fromLow;
    $toRange = $toHigh - $toLow;
    $scaleFactor = $toRange / $fromRange;

    // Re-zero the value within the from range
    $tmpValue = $value - $fromLow;
    // Rescale the value to the to range
    $tmpValue *= $scaleFactor;
    // Re-zero back to the to range
    return $tmpValue + $toLow;
}

set_time_limit(300);

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$now = new DateTime();
$name = $now->getTimestamp();           // Unix Timestamp -- Since PHP 5.3;
$ext = "jpg";
if (isset($_FILES['image']['name']))
{
    $saveto = "$name.$ext";
    //print ";$saveto\n";
    //move_uploaded_file($_FILES['image']['tmp_name'], $saveto);
    $typeok = TRUE;
    switch($_FILES['image']['type'])
    {
        //case "image/gif": $src = imagecreatefromgif($saveto); break;
        //case "image/jpeg": // Both regular and progressive jpegs
        //case "image/pjpeg": $src = imagecreatefromjpeg($saveto); break;
        //case "image/png": $src = imagecreatefrompng($saveto); break;
        
        case "image/gif": $src = imagecreatefromgif($_FILES['image']['tmp_name']); 
            $src = imagecreatefromjpeg($_FILES['image']['tmp_name']); 
            $imgtype = "Content-Type: image/gif";
            break;
        case "image/jpeg": // Both regular and progressive jpegs
        case "image/jpg": 
            $src = imagecreatefromjpeg($_FILES['image']['tmp_name']); 
            $imgtype = "Content-Type: image/jpg";
            break;
        case "image/png": $src = imagecreatefrompng($_FILES['image']['tmp_name']); 
            $imgtype = "Content-Type: image/png";
            break;
        case "image/bmp": $src = imagecreatefromwbmp($_FILES['image']['tmp_name']); 
            $imgtype = "Content-Type: image/bmp";
            break;
        default: 
            $typeok = FALSE; 
            break;
    }
    if ($typeok)
    { 
       list($w, $h) = getimagesize($_FILES['image']['tmp_name']);  
       // delete artifacts (line under and to right of the image)
       $h -= 2;
       $w -= 2;     
    }
    else
      exit();
}
else
   exit();

if(!isset($_POST['sizeY']) || $_POST['sizeY'] == 0)
   { 
   print("No image height defined :(\n");
   exit();
   }

//header('Content-Type: text/plain; charset=utf-8');


$laserMax=$_POST['LaserMax'];//$laserMax=65; //out of 255
$laserMin=$_POST['LaserMin']; //$laserMin=20; //out of 255
$laserOff=$_POST['LaserOff'];//$laserOff=13; //out of 255
$laserCMD=$_POST['LaserCmd'];//$laserCMD=M42 P4 S or laserCMD=M106 S for example
$whiteLevel=$_POST['whiteLevel'];

$feedRate = $_POST['feedRate'];//$feedRate = 800; //in mm/sec
$travelRate = $_POST['travelRate'];//$travelRate = 3000;

$overScan = $_POST['overScan'];//$overScan = 3;

$offsetY=$_POST['offsetY'];//$offsetY=10;
$sizeY=$_POST['sizeY'];//$sizeY=40;
$scanGap=$_POST['scanGap'];//$scanGap=.1;

$offsetX=$_POST['offsetX'];//$offsetX=5;
$sizeX=$sizeY*$w/$h; //SET A HEIGHT AND CALC WIDTH (this should be customizable)
$resX=$_POST['resX'];//$resX=.1;

$passCnt=$_POST['passCnt']; //$passCnt 1 or greater

$pixelsX = round($sizeX/$resX);
$pixelsY = round($sizeY/$scanGap);

$tmp = imagecreatetruecolor($pixelsX, $pixelsY);      
imagecopyresampled($tmp, $src, 0, 0, 0, 0, $pixelsX, $pixelsY, $w, $h);

$fliped = $tmp;
$tmp = ImageFlip($tmp,3);
if($_POST['flip'] != 1) 
  {
    $tmp = ImageFlip($tmp,2);
  }


imagefilter($tmp,IMG_FILTER_GRAYSCALE);
imagefilter($fliped,IMG_FILTER_GRAYSCALE);

if($_POST['preview'] == 1)
   {
   header($imgtype); //do this to display following image
   if($_POST['flip'] == 1) 
   {
      imagejpeg($tmp); //show image
   } else {
      imagejpeg($fliped); //show image
   }
   imagedestroy($fliped);
   imagedestroy($tmp);
   imagedestroy($src);        
   exit(); //exit if above
   }


header("Content-Disposition: attachment; filename=".$_FILES['image']['name'].".gcode");

print(";Created using Nebarnix's IMG2GCO program Ver 1.0 with modifications by Larry Fortna\n");
print(";http://gcode.flashsolutions.us/img2gcode/index.html\n");
print(";Image Filename:".$_FILES['image']['name'] );
print(";Size in pixels X=$pixelsX, Y=$pixelsY\n");
$cmdRate = round(($feedRate/$resX)*2/60);
print(";Speed is $feedRate mm/min, $resX mm/pix => $cmdRate lines/min\n");
print(";Power is $laserMin to $laserMax (". round($laserMin/255*100,1) ."%-". round($laserMax/255*100,1) ."%)\n");
print(";Pass count value input is $passCnt\n");

print("G21\n");
print("$laserCMD$laserOff; Turn laser off\n");
print("G1 F$feedRate\n");

$lineIndex=0;
print("G0 X$offsetX Y$offsetY F$travelRate\n");

for($pass=1; $pass<($passCnt+1); $pass++) 
{
  print(";PASS $pass\n");
  for($line=$offsetY; $line<($sizeY+$offsetY); $line+=$scanGap)
  {  
     //analyze the row and find first and last nonwhite pixels
     $pixelIndex=0;
     $firstX = 0;
     $lastX = 0;
     for($pixelIndex=0; $pixelIndex < $pixelsX; $pixelIndex++)
     {
        $rgb = imagecolorat($tmp,$pixelIndex,$lineIndex);
        $value = ($rgb >> 16) & 0xFF;
        if($value < $whiteLevel) //Nonwhite image parts
        {
           if($firstX == 0)
              $firstX = $pixelIndex;
           
           $lastX = $pixelIndex;         
        }
     }
        
     $pixelIndex=$firstX;
     for($pixel=($offsetX+$firstX*$resX); $pixel < ($sizeX+$offsetX); $pixel+=$resX)
     {
          if($pixelIndex == $lastX) {
             print(";<---\n"); 
             break;
          }
          if($pixelIndex == $firstX)
             {            
             print("G1 X".round($pixel+$overScan,4)." Y".round($line,4)." F$travelRate\n");
             print("G1 F$feedRate\n");
             print("G1 X".round($pixel,4)." Y".round($line,4)."\n");
             }
          else
             print("G1 X".round($pixel,4)."\n");

          $rgb = imagecolorat($tmp,$pixelIndex,$lineIndex);
          $value = ($rgb >> 16) & 0xFF;
          $value = round(map($value,255,0,$laserMin,$laserMax),4);
          print("$laserCMD$value\n");
          $pixelIndex++;
      }
      print("$laserCMD$laserOff ; Laser OFF\n");      
      $lineIndex++;
  }
  
}
$lineIndex--;

print("$laserCMD$laserOff ;Turn laser off\n");
print("G0 X$offsetX Y$offsetY F$travelRate ;Go home\n");
print("G28");
imagedestroy($tmp);
imagedestroy($flip);
?>

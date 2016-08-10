<?php

function ImageFliper ( $imgsrc, $mode )
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

set_time_limit(3000);   // Do we need this?  What if the job takes longer than this to print?

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
date_default_timezone_set('America/New_York');
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


$laserMax=$_POST['LaserMax'];         //$laserMax=65; //out of 255
$laserMin=$_POST['LaserMin'];         //$laserMin=20; //out of 255
$laserOFFPwr=$_POST['laserOFFPwr'];   //$laserOff=13; //out of 255
$laserONCmd=$_POST['LaserONCmd'];     //$laserCMD=M42 P4 S or laserCMD=M106 S or M3 S for example
$laserOFFCmd=$_POST['LaserOFFCmd'];   // M5 or M3 S for example
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
$passDepth=$_POST['passDepth']; // passDepth 1 mm

$pixelsX = round($sizeX/$resX);
$pixelsY = round($sizeY/$scanGap);

$tmp = imagecreatetruecolor($pixelsX, $pixelsY);      
imagecopyresampled($tmp, $src, 0, 0, 0, 0, $pixelsX, $pixelsY, $w, $h);

$fliped = $tmp;
$tmp = ImageFliper($tmp,3);
if($_POST['flip'] != 1) 
  {
    $tmp = ImageFliper($tmp,2);
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
// Let's make sure the Laser is off before we get started!!
print("$laserOFFCmd; Turn laser off\n");

print(";Created using Nebarnix's IMG2GCO program Ver 1.0 with modifications by Larry Fortna\n");
print(";http://gcode.flashsolutions.us/img2gcode/V2/index.html\n");
print(";Image Filename:".$_FILES['image']['name'] );
print(";Size in pixels X=$pixelsX, Y=$pixelsY\n");
$cmdRate = round(($feedRate/$resX)*2/60);
print(";Speed is $feedRate mm/min, $resX mm/pix => $cmdRate lines/min\n");
print(";Power is $laserMin to $laserMax (". round($laserMin/100,1) ."%-". round($laserMax/100,1) ."%)\n");
print(";Pass count value input is $passCnt\n");

print("G21 ;Set Units to MM\n");
print("G90 ;Absolute positioning\n");  // use absolute positioning 
print("G28 ;Go HOME\n");
print("G1 F$feedRate\n");


print("G0 X$offsetX Y$offsetY F$travelRate\n");

for($pass=1; $pass<($passCnt+1); $pass++) 
{
  $lineIndex=0;
  print(";PASS $pass\n");
  for($line=$offsetY; $line<($sizeY+$offsetY); $line+=$scanGap)
  {  
     //analyze the row and find first and last nonwhite pixels
     $pixelIndex=0;
     $firstX=0;
     $lastX=0;

     for($pixelIndex=0; $pixelIndex<$pixelsX; $pixelIndex++)
     {
        $rgb = imagecolorat($tmp,$pixelIndex,$lineIndex);
        $value = ($rgb >> 16) & 0xFF;
        if($value < $whiteLevel) //Nonwhite image parts
        {
           if($firstX==0) {
              $firstX=$pixelIndex;
           }
           if($firstX>0) {
              $lastX = $pixelIndex; 
           }        
        }
     }
 
     
        
     $pixelIndex=$firstX;
     $skipDwell = TRUE;
     $workDone = FALSE;
     for($pixel=($offsetX+$firstX*$resX); $pixel < ($sizeX+$offsetX); $pixel+=$resX)
     {
          if($pixelIndex == $lastX) {
             //print(";<---\n"); 
             $skipDwell = TRUE;
             break;
          }

          $rgb = imagecolorat($tmp,$pixelIndex,$lineIndex);
          $value = ($rgb >> 16) & 0xFF;
          $value = round(map($value,255,0,$laserMin,$laserMax),4);

          if($pixelIndex == $firstX)
             {  
             $workDone = TRUE;
             print("$laserOFFCmd$laserOFFPwr\n");                    
             print("G0 X".round($pixel+$overScan,4)." Y".round($line,4)." F$travelRate\n");
             print("$laserONCmd$value\n"); 
             print("G1 F$feedRate\n");
             print("G1 X".round($pixel,4)." Y".round($line,4)."\n");
             }
          else
             {
             print("$laserONCmd$value\n");
             print("G1 X".round($pixel,4)."\n");
             }

          $pixelIndex++;
      }
      print("$laserOFFCmd$laserOFFPwr ; LASER OFF\n");
      if ($skipDwell == TRUE && $workDone == TRUE) {
      	print("G4 P8 ; Dwell on it\n");      
      }
      $lineIndex++;
     
  }
  print("G91 ;relative positioning\n");  // change to relative positioning for the Z axis
  print("G1 Z-$passDepth\n");
  print("G90 ;absolute positioning\n");  // put back to absolute positioning for Z axis
  //print("M117 End of PASS $pass\n");
  print("$laserOFFCmd$laserOFFPwr; MAKE SURE LASER IS OFF!\n");
  print("G4 P2000 ;Pause for 2 seconds\n");  // set Dwell to 2 seconds to allow Laser time to settle off
}
$lineIndex--;

print("G0 X$offsetX Y$offsetY F$travelRate ;Go home\n");
print("G28");
imagedestroy($tmp);
imagedestroy($fliped);
?>

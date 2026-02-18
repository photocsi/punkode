<?php

namespace Punkode;

trait UPLOAD_PK
{

  static function pk_save_file($files, string $path, array $type = array('jpeg', 'png', 'gif', 'pdf'), int $compression = 90)
  {


    $files_ord = self::pk_ord_file($files);
    self::pk_copy_img($files_ord, $path, $type, $compression);
  }

  static function pk_ord_file($files)
  {
    $newFiles = array();
    foreach ($files as $key => $file) {
      foreach ($file as $keyfile => $itemfile) {
        for ($i = 0; $i < count($itemfile); $i++) {
          $newFiles[$i][$keyfile] = $itemfile[$i];
        }
      }
    }
    return $newFiles;
  }

  static function pk_make_dir($path)
  {
    if (is_dir($path) != TRUE) {
      mkdir($path, 0777, TRUE);
    }
  }

  static function pk_copy_img(array $files, string $path, array $type = array('jpeg', 'png', 'gif', 'pdf'), int $compression = 90)
  {
    $length = count($files);
    for ($i = 0; $i < $length; $i++) {
      if ($files[$i]['type'] === 'image/jpeg' && in_array('jpeg', $type)) {
        $img = imagecreatefromjpeg($files[$i]['tmp_name']);
        imagejpeg($img, $path . '/' . $files[$i]['name'], $compression);
      } else if ($files[$i]['type'] === 'image/png' && in_array('png', $type)) {
        $img = imagecreatefrompng($files[$i]['tmp_name']);
        imagepng($img, $path . '/' . $files[$i]['name'], -1, -1);
      } else if ($files[$i]['type'] === 'image/gif' && in_array('gif', $type)) {
        $img = imagecreatefromgif($files[$i]['tmp_name']);
        imagegif($img, $path . '/' . $files[$i]['name']);
      } else if ($files[$i]['type'] === 'image/pdf' && in_array('pdf', $type)) {
        copy($files[$i]['tmp_name'], $path . '/' . $files[$i]['name']); //da controllare probabilmente sbagliato per i pdf
      } else {
        echo $files[$i]['name'] . ' - Non è stato compiato perchè il formato non è consentito';
      };
    }
  }

  static function pk_save_jpg(array $files, string $path, int $compression = 90)
  {

    $length = count($files);
    for ($i = 0; $i < $length; $i++) {
      $img = imagecreatefromjpeg($files[$i]['tmp_name']);
      imagejpeg($img, $path . '/' . $files[$i]['name'], $compression);
    }
  }

  static function save_scale_pk(array $files, int $dimensione, string $path, int $compression)
  {
    //funzione per ridurre e salvare l'immagine
    //parametri: il tmp_name dell'immagine, la dimensione finale in px, il path di destinazione e la compressione HO aggiunto un bollean vero o falso per capire se e verticale oppure no (non so se lo utilizzero)
   $caricati =0;
   
    $newFiles = array();
    foreach ($files as $key => $file) {
      foreach ($file as $keyfile => $itemfile) {
        for ($i = 0; $i < count($itemfile); $i++) {
          $newFiles[$i][$keyfile] = $itemfile[$i];
        }
      }
    }
    $files_ord = $newFiles;
    $length = count($files_ord);
    for ($i = 0; $i < $length; $i++) {
      $img = imagecreatefromjpeg($files_ord[$i]['tmp_name']);
 $exif = exif_read_data($files_ord[$i]['tmp_name'], 'IFDO');
      if (isset($exif['Orientation'])) {
        $ort = $exif['Orientation'];

        if ($ort == 6 || $ort == 5) {
          $img = imagerotate($img, 270, 0);
        } else if ($ort == 3 || $ort == 4) {
          $img = imagerotate($img, 180, 0);
        } else if ($ort == 8 || $ort == 7) {
          $img = imagerotate($img, 90, 0);
        }
          
          /* else if ($ort == 5 || $ort == 4 || $ort == 7)
          imageflip($img, IMG_FLIP_HORIZONTAL); */
      }
      $immagine = imagescale($img, $dimensione);

     if(imagejpeg($immagine, $path . '/' . $files_ord[$i]['name'], $compression)){
      $caricati++;
     }
    }

    return $caricati;
  }

  static function save_watermark_pk($files, $path_destinazione, $path_watermark)
  {
    $length = count($files['tmp_name']);
    for ($i = 0; $i < $length; $i++) {
      $dimensioni_watermark = getimagesize($path_watermark); // prendo le dimensioni del watermark png
      $dimensioni_foto = getimagesize($files['tmp_name'][$i]); // prendo le dimensioni della foto jpg
      if ($dimensioni_foto[0] < $dimensioni_foto[1]) { //controllo se e verticale faccio cosi
        $distanza_sinistra = 50; //distanza fra bordo sx e logo
        $distanza_alto = 200; //distanza fra bordo alto e lgo
        $larghezza = $dimensioni_watermark[0];
        $altezza = $dimensioni_watermark[1];
        $grandezza = 600; //questa e la grandezza in px dell'immagine finale
      } else if ($dimensioni_foto[0] > $dimensioni_foto[1]) { //se e orizzontale cosi
        $distanza_sinistra = 200;
        $distanza_alto = 100;
        $larghezza = $dimensioni_watermark[0];
        $altezza = $dimensioni_watermark[1];
        $grandezza = 1000;
      } else {  // se e quadrata faccio cosi
        $distanza_sinistra = 150;
        $distanza_alto = 100;
        $larghezza = $dimensioni_watermark[0];
        $altezza = $dimensioni_watermark[1];
        $grandezza = 900;
      }
      $sorgente = imagecreatefromjpeg($files['tmp_name'][$i]);
      $immagine = imagescale($sorgente, $grandezza);
      $watermark = imagecreatefrompng($path_watermark);
      imagecopy($immagine, $watermark, $distanza_sinistra, $distanza_alto, 0, 0, $larghezza, $altezza);
      /* imagecopymerge($immagine, $watermark,$larghezza,$altezza,0,0,imagesx($watermark),imagesy($watermark),40); */
      imagejpeg($immagine, $path_destinazione, 75);
    }
  }

  static function pk_rate($img)
  {
    $dimensioni_foto = getimagesize($img); // prendo le dimensioni della foto jpg
   
    if ($dimensioni_foto[0] < $dimensioni_foto[1]) { //controllo se e verticale faccio cosi
      return 'v';
    } else if ($dimensioni_foto[0] > $dimensioni_foto[1]) { //se e orizzontale cosi
      return 'h';
    } else {
      return 'q';
    }
  }

  static function control_file($dir)
  {
    $tutti_file = scandir($dir . '/hd/nuove');
    $length = count($tutti_file);
    for ($i = 2; $i < $length; $i++) {
      $image = imagecreatefromjpeg($dir . '/hd/nuove/' . $tutti_file[$i]);

      $tmp = imagescale($image, 500);

      imagejpeg($tmp, $dir . '/small/nuove/' . $tutti_file[$i], 50);
    }
  }
}

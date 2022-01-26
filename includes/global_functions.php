<?php


function respond($code, $html = '', $headers = [])
{
    $default_headers = ['content-type' => 'text/html; charset=utf-8'];
    $headers = $headers + $default_headers;
    http_response_code($code);
    foreach ($headers as $key => $value) {
        header($key . ': ' . $value);
    }
    echo $html;
    exit();
}


function createThumbnail($filename, $source_directory, $thumbs_directory, $max_width, $max_height)
{
  global $translator;
  $path_to_source_file = $source_directory . '/' . $filename;
  $path_to_thumb_file = $thumbs_directory . '/thumb-' . $filename;
  $source_filetype = exif_imagetype($path_to_source_file);

  switch ($source_filetype) {
    case IMAGETYPE_JPEG:
    case IMAGETYPE_PNG:
    case IMAGETYPE_GIF:
    case IMAGETYPE_WEBP:
      break;

    default:
      return false;
  }

  if (file_exists($thumbs_directory) !== true) {
    if (!mkdir($thumbs_directory, 0775, true)) {
      echo $translator->string('Error: The thumbnails directory could not be created.');
      exit();
    } else {
      // On some hosts, we need to change permissions of the directory using chmod
      // after creating the directory
      chmod($thumbs_directory, 0775);
    }
  }
  // Create the thumbnail ----->>>>
  list($orig_width, $orig_height) = getimagesize($path_to_source_file);
  $width = $orig_width;
  $height = $orig_height;

  if ($height > $max_height) { // taller
    $width = ($max_height / $height) * $width;
    $height = $max_height;
  }
  if ($width > $max_width) { // wider
    $height = ($max_width / $width) * $height;
    $width = $max_width;
  }
  $image_p = imagecreatetruecolor($width, $height);

  switch ($source_filetype) {
    case IMAGETYPE_JPEG:
      $image = imagecreatefromjpeg($path_to_source_file);
      imagecopyresampled(
        $image_p,
        $image,
        0,
        0,
        0,
        0,
        $width,
        $height,
        $orig_width,
        $orig_height
      );
      imagejpeg($image_p, $path_to_thumb_file);
      break;
    case IMAGETYPE_PNG:
      $image = imagecreatefrompng($path_to_source_file);
      imagecopyresampled(
        $image_p,
        $image,
        0,
        0,
        0,
        0,
        $width,
        $height,
        $orig_width,
        $orig_height
      );
      imagepng($image_p, $path_to_thumb_file);
      break;
    case IMAGETYPE_GIF:
      $image = imagecreatefromgif($path_to_source_file);
      imagecopyresampled(
        $image_p,
        $image,
        0,
        0,
        0,
        0,
        $width,
        $height,
        $orig_width,
        $orig_height
      );
      imagegif($image_p, $path_to_thumb_file);
      break;

    case IMAGETYPE_WEBP:
      $image = imagecreatefromwebp($path_to_source_file);
      imagecopyresampled(
        $image_p,
        $image,
        0,
        0,
        0,
        0,
        $width,
        $height,
        $orig_width,
        $orig_height
      );
      imagewebp($image_p, $path_to_thumb_file);
      break;


    default:
      continue;
      echo $translator->string('Unknown filetype. Supported filetypes are: JPG, PNG og GIF.');
      exit();
  }
}

function list_files($settings)
{
  $directory = BASE_PATH . 'gallery/' . $_GET['category'];
  $thumbs_directory = BASE_PATH . 'thumbnails/' . $_GET['category'];
  $item_arr = array_diff(scandir($directory), array('..', '.'));
  foreach ($item_arr as $key => $value) {
    if (is_dir($directory . '/' . $value)) {
      unset($item_arr["$key"]);
    } else {
      $path_to_file = $thumbs_directory . '/thumb-' . $value;
      if (file_exists($path_to_file) !== true) {
        if( createThumbnail($value, $directory, $thumbs_directory, 400, 400) === false )
        {
          unset($item_arr["$key"]);
        }
      }
    }
  }
  return $item_arr;
}
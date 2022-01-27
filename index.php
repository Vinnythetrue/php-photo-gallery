<?php
// Remove trailing slashes (if present), and add one manually.
// Note: This avoids a problem where some servers might add a trailing slash, and others not..
define('BASE_PATH', rtrim(realpath(dirname(__FILE__)), "/") . '/');
require BASE_PATH . 'includes/global_functions.php';

$settingfilename = BASE_PATH . 'includes/settings.php';
$distsettingfilename = $settingfilename . '.dist';
if( !is_file($settingfilename) )
{
  copy( $distsettingfilename, $settingfilename);
}

require $settingfilename; // Note. Include a file in same directory without slash in front of it!
require BASE_PATH . 'lib/translator_class.php';

$translator = new translator($settings['lang']);

require BASE_PATH . 'includes/dependency_checker.php';

if (session_status() == PHP_SESSION_NONE) {
  session_cache_limiter("private_no_expire");
  session_start();
}
// <<<<<<<<<<<<<<<<<<<<
// Validate the _GET category input for security and error handling
// >>>>>>>>>>>>>>>>>>>>
$HTML_navigation = '<li><a href="' . $settings['home_url'] . '">' . $translator->string('Home') . '</a></li>';

if (isset($_GET['category'])) {
  $HTML_navigation .= '<li><a href="index.php">' . $translator->string('Categories') . '</a></li>';
  if (preg_match("/^[a-zæøåÆØÅ-]+$/i", $_GET['category'])) {
    $requested_category = $_GET['category'];
    // <<<<<<<<<<<<<<<<<<<<
    // Fetch the files in the category, and include them in an HTML ul list
    // >>>>>>>>>>>>>>>>>>>>
    $files = list_files($settings);
    if (count($files) >= 1) {
      $HTML_cup = '<ul id="images">';
      foreach ($files as &$file_name) {
        if (isset($_SESSION["password"])) {
          $delete_control = '<a href="admin.php?delete=' . $requested_category . '/' . $file_name . '" class="delete"><img src="delete.png" alt="delete" style="width:30px;height:30px;"></a>';
          $category_preview_control = '<a href="admin.php?category=' . $requested_category . '&set_preview_image=' . $file_name . '" class="preview"><img src="preview.png" alt="set preview image" style="width:30px;height:30px;"></a>';
        } else {
          $delete_control = '';
          $category_preview_control = '';
        }
        $thumb_file_location = 'thumbnails/' . $requested_category . '/thumb-' . rawurlencode($file_name);
        $source_file_location = 'gallery/' . $requested_category . '/' . $file_name;
        $HTML_cup .= '<li><a href="viewer.php?category=' . $requested_category . '&filename=' . $file_name . '"><img src="' . $thumb_file_location . '" alt="' . $file_name . '"></a>' . $delete_control . $category_preview_control . '</li>';
      }
      $HTML_cup .= '</ul>';
    } else {
      $HTML_cup = '<p>' . $translator->string('There are no files in:') . ' <b>' . space_or_dash('-', $requested_category) . '</b></p>';
    }
  } else {
    header("HTTP/1.0 500 Internal Server Error");
    echo '<!doctype html><html><head></head><body><h1>Error</h1><p>Invalid category</p></body></html>';
    exit();
  }
} else { // If no category was requested
  // <<<<<<<<<<<<<<<<<<<<
  // Fetch categories, and include them in a HTML ul list
  // >>>>>>>>>>>>>>>>>>>>
  $requested_category = $translator->string('Categories');
  $categories = list_directories();
  if (count($categories) >= 1) {
    $HTML_cup = '<ul id="categories">';
    foreach ($categories as &$category_name) {
      if (isset($_SESSION["password"])) {
        $delete_control = '<a href="admin.php?delete=' . $category_name . '" class="delete"><img src="delete.png" alt="delete" style="width:30px;height:30px;"></a>';
      } else {
        $delete_control = '';
      }
      $category_preview_images = category_previews($category_name, $category_json_file);
      // echo 'cats:'.$category_preview_images; // Testing category views
      $HTML_cup .= '<li><div class="preview_images">' . $category_preview_images . '</div><div class="category"><a href="index.php?category=' . $category_name . '" class=""><span>' . space_or_dash('-', $category_name) . '</span></a></div>' . $delete_control . '</li>';
    }
    $HTML_cup .= '</ul>';
  } else {
    $HTML_cup = '<p>' . $translator->string('There are no categories yet...') . '</p>';
  }
}
$HTML_navigation = '<ol class="flexbox">' . $HTML_navigation . '</ol>';

// ====================
// Functions
// ====================
function space_or_dash($replace_this = '-', $in_this)
{
  if ($replace_this == '-') {
    return preg_replace('/([-]+)/', ' ', $in_this);
  } elseif ($replace_this == ' ') {
    return preg_replace('/([ ]+)/', '-', $in_this);
  }
}
function category_previews($category, $category_json_file)
{
  $thumbs_directory = BASE_PATH . 'thumbnails/' . $category;
  $previews_html = '';

  if (file_exists($thumbs_directory)) {

    if (file_exists($thumbs_directory . '/' . $category_json_file)) {
      $category_data = json_decode(file_get_contents($thumbs_directory . '/' . $category_json_file), true);

      $previews_html = '<div style="background:url(thumbnails/' . $category . '/' . rawurlencode($category_data['preview_image']) . ');" class="category_preview_img"></div>';
    } else {
      // Automatically try to select preview image if none was choosen
      $item_arr = array_diff(scandir($thumbs_directory), array('..', '.'));
      foreach ($item_arr as $key => $value) {
        $previews_html = '<div style="background:url(thumbnails/' . $category . '/' . rawurlencode($item_arr["$key"]) . ');" class="category_preview_img"></div>'; // add a dot in front of = to return all images
        break;
      }
      $category_data = json_encode(array('preview_image' => $item_arr["$key"]));
      file_put_contents($thumbs_directory . '/' . $category_json_file, $category_data);
    }
  }
  return $previews_html;
}
function list_directories()
{
  $item_arr = array_diff(scandir(BASE_PATH . 'gallery/'), array('..', '.'));
  foreach ($item_arr as $key => $value) {
    if (is_dir(BASE_PATH . 'gallery/' . $value) === false) {
      unset($item_arr["$key"]);
    }
  }
  return $item_arr;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require BASE_PATH . 'templates/' . $template . '/category_template.php';

<?php
/*
Plugin Name: RSS Feed Importer
Description: Imports posts from an RSS feed and sets the featured image.
*/

function rss_feed_importer_menu() {
  add_menu_page(
    'RSS Feed Importer',
    'RSS Feed Importer',
    'manage_options',
    'rss-feed-importer',
    'rss_feed_importer_options'
  );
}
add_action('admin_menu', 'rss_feed_importer_menu');

function rss_feed_importer_options() {
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  // Display the plugin options form
  ?>
  <div class="wrap">
    <h1>RSS Feed Importer</h1>
    <form method="post" action="options.php">
      <?php settings_fields('rss_feed_importer_options'); ?>
      <?php do_settings_sections('rss-feed-importer'); ?>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">RSS Feed URL</th>
          <td>
            <input type="text" name="rss_feed_url" value="<?php echo esc_attr(get_option('rss_feed_url')); ?>" />
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">Post Category</th>
          <td>
            <?php
            $categories = get_categories();
            echo '<select name="rss_feed_category">';
            foreach ($categories as $category) {
              $selected = (get_option('rss_feed_category') == $category->term_id) ? 'selected' : '';
              echo '<option value="' . $category->term_id . '" ' . $selected . '>' . $category->name . '</option>';
            }
            echo '</select>';
            ?>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">Post Status</th>
          <td>
            <select name="rss_feed_status">
              <option value="publish" <?php selected(get_option('rss_feed_status'), 'publish'); ?>>Publish</option>
              <option value="draft" <?php selected(get_option('rss_feed_status'), 'draft'); ?>>Draft</option>
            </select>
          </td>
        </tr>
      </table>
      <?php submit_button(); ?>
    </form>
  </div>
  <?php
}

function rss_feed_importer_settings() {
  register_setting('rss_feed_importer_options', 'rss_feed_url');
register_setting('rss_feed_importer_options', 'rss_feed_category');
register_setting('rss_feed_importer_options', 'rss_feed_status');
}
add_action('admin_init', 'rss_feed_importer_settings');

function rss_feed_importer_button() {
if (!current_user_can('manage_options')) {
return;
}

?>

  <div class="wrap">
    <h1>RSS Feed Importer</h1>
    <form method="post" action="">
      <input type="hidden" name="rss_feed_import" value="1" />
      <?php submit_button('Import RSS Feed'); ?>
    </form>
  </div>
  <?php
}
function rss_feed_importer_import() {
if (!current_user_can('manage_options')) {
return;
}

if (isset($_POST['rss_feed_import'])) {
// Get the RSS feed URL
$rss_feed_url = get_option('rss_feed_url');// Get the selected category
$rss_feed_category = get_option('rss_feed_category');

// Get the selected post status
$rss_feed_status = get_option('rss_feed_status');

// Fetch the RSS feed
$feed = fetch_feed($rss_feed_url);
$maxitems = $feed->get_item_quantity();
$items = $feed->get_items(0, $maxitems);

// Loop through each item in the feed
foreach ($items as $item) {
  // Get the post title
  $title = $item->get_title();

  // Get the post content
  $content = $item->get_content();

  // Get the post date
  $date = $item->get_date('Y-m-d H:i:s');

  // Get the post URL
  $url = $item->get_permalink();

  // Check if the post already exists
  $post_id = post_exists($title);
  if (!$post_id) {
    // Create a new post
    $post_id = wp_insert_post(array(
      'post_title' => $title,
      'post_content' => $content,
      'post_date' => $date,
      'post_status' => $rss_feed_status,
      'post_category' => array($rss_feed_category)
    ));

    // Set the post thumbnail (featured image)
    $image_url = rss_feed_importer_get_image($content);
    if ($image_url) {
      rss_feed_importer_set_thumbnail($post_id, $image_url);
    }
  }
}
}
}
add_action('admin_init', 'rss_feed_importer_import');

function rss_feed_importer_get_image($content) {
// Extract the first image from the post content
$first_image = '';
$output = preg_match_all('// Return the image URL
return $first_image;
}

function rss_feed_importer_set_thumbnail($post_id, $image_url) {
// Check if the post already has a featured image
if (get_post_thumbnail_id($post_id)) {
return;
}

// Download the image to the WordPress uploads directory
$upload_dir = wp_upload_dir();
$image_data = file_get_contents($image_url);
$filename = basename($image_url);
if (wp_mkdir_p($upload_dir['path'])) {
  $file = $upload_dir['path'] . '/' . $filename;
} else {
  $file = $upload_dir['basedir'] . '/' . $filename;
}

file_put_contents($file, $image_data);

// Check the type of file. We'll use this as the 'post_mime_type'.
$filetype = wp_check_filetype($filename, null);

// Get the path to the upload directory.
$wp_upload_dir = wp_upload_dir();

// Prepare an array of post data for the attachment.
$attachment = array(
'guid' => $wp_upload_dir['url'] . '/' . $filename,
'post_mime_type' => $filetype['type'],
'post_title' => preg_replace('/.[^.]+$/', '', $filename),
'post_content' => '',
'post_status' => 'inherit'
);

// Insert the attachment.
$attach_id = wp_insert_attachment($attachment, $file, $post_id);

// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
require_once ABSPATH . 'wp-admin/includes/image.php';

// Generate the metadata for the attachment, and update the database record.
$attach_data = wp_generate_attachment_metadata($attach_id, $file);
wp_update_attachment_metadata($attach_id, $attach_data);

// Set the post thumbnail (featured image).
set_post_thumbnail($post_id, $attach_id);
}



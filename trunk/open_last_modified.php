<?php
/*
 * Plugin Name: WP Last Modified
 * Plugin URI: www.dogan-ucar.de/wp-open-last-modified
 * Description: WP Last Modified adds the ‚last_modified_date‘ shortcut to your WordPress installation. This shortcut shows the last timestamp of your post/page. Simply use the „format“ attribute for custom date formats (it uses PHP’s date() function). The "description" attribute enables a brief description which changes has been made with the last modification. This plugin shows also the actual revision of your post/page. You can customize the text which will be shown under each post/page under settings -> WP Last Modified Settings.
 * Version: 1.4.6
 * Author: Dogan Ucar
 * Author URI: www.dogan-ucar.de
 * License: GNU General Public License v3.0
 * Text Domain: wp-open-last-modified
 * Domain Path: /languages/
 *
 * Copyright (C) <2016> <Dogan Ucar>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * For further questions please visit www.dogan-ucar.de/wp-open-last-modified
 *
 */
defined('ABSPATH') or die ('No script kiddies please!');
require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once("classes/Util.php");
require_once("classes/Options.php");

$pluginDir = plugin_dir_url(__FILE__);
add_action('plugins_loaded', 'loadPluginTextdomain');
add_action('admin_menu', 'add_wp_open_last_modified_to_options');
add_shortcode("last_modified_date", "addTimestamp");

$option = new Options();
$updated = 0;
$idUpdated = 0;

if (isset ($_POST ['update_wpolm_settings_name']) && wp_verify_nonce($_POST ['update_wpolm_settings_name'], 'update_wpolm_settings_action')) {
    if (isset ($_POST ['wpolm_text'])) {
        $text = $_POST ['wpolm_text'];
        $text = trim($text);

        if (!Util::stringEmpty($text)) {
            $value = sanitize_text_field($text);
            $option->update("wpolm_text", $value);
            $updated = 1;
        } else {
            $updated = 2;
        }
    }
    if (isset ($_POST ['update_wpolm_settings_name']) && wp_verify_nonce($_POST ['update_wpolm_settings_name'], 'update_wpolm_settings_action')) {
        $pageIds = $_POST["exclude-ids"];
        $notEmpty = trim($pageIds) !== "";
        $pageIds = sanitize_text_field($pageIds);
        $pageIds = str_replace(" ", "", $pageIds);
        $pageIds = explode(",", $pageIds);
        foreach ($pageIds as $key => $page_id) {
            if (false === get_post_status($page_id)) {
                unset($pageIds[$key]);
            }
        }
        $pageIds = array_unique($pageIds);
        sort($pageIds);
        if (count($pageIds) > 0) {
            $idUpdated = 1;
            $option->delete("wpolm_page_ids");
            $option->update("wpolm_page_ids", json_encode($pageIds));
        } else if (true === $notEmpty) {
            $idUpdated = 2;
        }
    }
}

/**
 * is executed whenever a page uses the shortcode
 *
 * @param $atts
 *
 * @return mixed
 */
function addTimestamp($atts) {
    global $post;
    global $option;
    $ids = $option->read("wpolm_page_ids");
    $ids = is_string($ids) ? $ids : "";
    $text = $option->read('wpolm_text');
    $array = json_decode($ids, true);

    if (null !== $array && in_array($post->ID, $array)) {
        return "";
    }

    $a = shortcode_atts([
        'format' => 'm/d/y H:i:s',
        'description' => '',
    ], $atts);
    $format = $a ['format'];
    $description = $a ['description'];

    if (Util::stringEmpty($format)) {
        $format = "m/d/y";
    }
    $lastModifiedDateTime = $post->post_modified;
    $_lastModifiedDateTime = date($format, strtotime($lastModifiedDateTime));

    $lastModifiedDateTimeGmt = $post->post_modified_gmt;
    $_lastModifiedDateTimeGmt = date($format, strtotime($lastModifiedDateTimeGmt));

    $creationDateTime = $post->post_date;
    $_creationDateTime = date($format, strtotime($creationDateTime));

    $creationDateTimeGmt = $post->post_date_gmt;
    $_creationDateTimeGmt = date($format, strtotime($creationDateTimeGmt));

    $revisions = wp_get_post_revisions($post->ID);
    $revisionCount = count($revisions);

    $text = str_replace("*timestamp*", $_lastModifiedDateTime, $text);
    $text = str_replace("*last_modified_timestamp*", $_lastModifiedDateTime, $text);
    $text = str_replace("*last_modified_timestamp_gmt*", $_lastModifiedDateTimeGmt, $text);
    $text = str_replace("*publication_timestamp*", $_creationDateTime, $text);
    $text = str_replace("*publication_timestamp_gmt*", $_creationDateTimeGmt, $text);
    $text = str_replace("*revision_count*", $revisionCount, $text);
    $text = str_replace("*description*", $description, $text);

    $text = strip_tags($text);

    $legacy = '
    <!-- WP Last Modified by Dogan Ucar (https://www.dogan-ucar.de). -->
    <!-- This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;-->
    <!-- 2016 - ' . date("Y") . ' Dogan Ucar. -->';

    return $legacy . $text;
}

/**
 * adds the menu page
 */
function add_wp_open_last_modified_to_options() {
    $menuTitle = __("WP Last Modified Settings", 'wp-open-last-modified');
    $siteTitle = $menuTitle;
    $permission = 'manage_options';
    $slug = __FILE__;
    $callback = 'add_wpolm_settings_page';
    add_options_page($menuTitle, $siteTitle, $permission, $slug, $callback);
}

/**
 * loads the settings page
 */
function add_wpolm_settings_page() {
    global $updated;
    global $idUpdated;
    global $option;
    $text = $option->read('wpolm_text');
    $ids = $option->read('wpolm_page_ids');
    $ids = is_string($ids) ? $ids : "";
    echo "<div id='theme-options-wrap'>";

    // Header1: Plugin-Ueberschrift
    echo "<h1>";
    echo getTranslatedText("WP Last Modified Settings");
    echo "</h1>";

    // Header3: zweite Ueberschrift
    echo "<h3>";
    echo getTranslatedText("Define the settings by overriding the defaults with your own preferences");
    echo "</h3>";

    if (isset ($updated)) {
        if ($updated == 1) {
            echo "<div class=updated notice>";
            echo "<p>";
            echo getTranslatedText("settings saved");
            echo "</p>";
            echo "</div>";
        }

        if ($updated == 2) {
            echo "<div class=error notice>";
            echo "<p>";
            echo getTranslatedText("Error occured. Please enter a message");
            echo "</p>";
            echo "</div>";
        }
    }
    if (isset ($idUpdated)) {
        if ($updated == 1) {
            echo "<div class=updated notice>";
            echo "<p>";
            echo getTranslatedText("IDs updated");
            echo "</p>";
            echo "</div>";
        }

        if ($idUpdated == 2) {
            echo "<div class=error notice>";
            echo "<p>";
            echo getTranslatedText("Error occured. Please try again updating IDs.");
            echo "</p>";
            echo "</div>";
        }
    }
    // End of Headers

    echo '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- wordpress-wpolm -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-4632643185412075"
     data-ad-slot="8151786874"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>';

    // Begin descriptions
    echo "<p>";
    echo "<h3>";
    echo getTranslatedText("Usage");
    echo "</h3>";
    echo getTranslatedText("Just use the *timestamp* or *last_modified_timestamp* shortcuts for inserting the modification date/time, *publication_timestamp* for the publication date/time and *revision_count* for the number of revisions. There are also options for GMT timezones (*last_modified_timestamp_gmt* and *publication_timestamp_gmt*).");
    echo "</p>";
    echo "<p>";
    echo getTranslatedText("If you don't need one of the values, just remove them from your text!");
    echo "</p>";

    echo "<p>";
    echo getTranslatedText("Notice: you can configure the date format via the shortcut attribute 'format'.");
    echo "</p>";

    echo "<p>";
    echo getTranslatedText("Notice: you should use the *last_modified_timestamp* instead of *timestamp* because *timestamp* will be removed in a future release.");
    echo "</p>";

    echo "<p>";
    echo getTranslatedText("Sample usage: [last_modified_date format=\"Y-m-d H:i:s\" description=\"This was only a minor change\"]");
    echo "</p>";

    echo "<h3>";
    echo getTranslatedText("Excluding Pages/Posts");
    echo "</h3>";

    echo "<p>";
    echo getTranslatedText("You can exclude page or posts by passing the corresponding ID to the text fields bellow. Please provide valid (available) page/post numeric identifier in order to hide the information on this pages.");
    echo "</p>";

    echo "<p>";
    echo getTranslatedText("Sample input: 1, 2, 3, 4");
    echo "</p>";


    // PayPal Donation
    echo "<form action=https://www.paypal.com/cgi-bin/webscr method=post target=_top>";
    echo "<input type=hidden name=cmd value=_s-xclick>";
    echo "<input type=hidden name=hosted_button_id value=Z8CV4GQF83XU4>";
    echo "<input type=image src=https://www.paypalobjects.com/en_US/DE/i/btn/btn_donateCC_LG.gif border=0 name=submit alt=PayPal - The safer, easier way to pay online!>";
    echo "<img alt= border=0 src=https://www.paypalobjects.com/en_US/i/scr/pixel.gif width=1 height=1>";
    echo "</form>";

    echo '<form method="post" action="">';
    wp_nonce_field('update_wpolm_settings_action', 'update_wpolm_settings_name');
    echo "<p class=\"submit\">";
    echo "<textarea name=\"wpolm_text\" id=\"wpolm_text\" rows=\"7\" cols=\"120\" placeholder=\"Type in your text here...\">";
    echo esc_textarea($text);
    echo "</textarea>";
    echo "<br>";

    echo '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- wordpress-wpolm2 -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-4632643185412075"
     data-ad-slot="7055818315"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>';

    //excluding pages
    echo "<p>";
    echo "<h3>";
    echo getTranslatedText("Define the page/post IDs you want to exclude");
    echo "<br>";
    echo "</h3>";
    echo getTranslatedText("Page/Post IDs (comma seperated): ");
    echo "<input type='text' name='exclude-ids' id='exclude-ids' class='regular-text' value='";
    $array = \json_decode($ids, true);
    if (null !== $array) {
        sort($array);
        echo esc_html(implode(", ", $array));
    }
    echo "'>";
    echo "</p>";
    echo '<input class="button-primary" type="Submit" name="Submit" value="';
    echo getTranslatedText("Save Changes");
    echo '"></input><br>';
    echo "</form>";
    echo "</div>";
}

/**
 * loads the plugin specific text domain.
 * wrapper function for WordPress function 'load_plugin_textdomain'.
 */
function loadPluginTextdomain() {
    load_plugin_textdomain("wp-open-last-modified", false, dirname(plugin_basename(__FILE__)) . '/languages');
}

function getTranslatedText($text) {
    return __($text, 'wp-open-last-modified');
}

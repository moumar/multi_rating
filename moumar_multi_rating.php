<?php
/*
Plugin Name: Moumar Multi Rating
Plugin URI: https://github.com/moumar/multi_rating
Description: Multi rating
Author: moumar
Version: 0.1
Author URI: http://moumar.net/
*/

$MMR_RATING_CATEGORIES = array(
  'hebergement'         => 'Hébergement',
  'gastronomie'         => 'Gastronomie',
  'accueil'             => 'Accueil & hospitalité',
  'meteo'               => 'Météo',
  'night-life'          => 'Bars/boîtes/night life',
  'paysages'            => 'Paysages',
  'culture'             => 'Culture/Monuments/musées',
  'activites'           => 'Activités/loisirs/sport',
  'shopping'            => 'Shopping',
  'sante'               => 'Hygiène/Santé',
  'securite'            => 'Sécurité',
  'gay'                 => 'Gay-Friendly',
  'photo'               => 'Intérêt photographique',
  'deplacements'        => 'Déplacements intérieurs'
);

if (__FILE__ == realpath($_SERVER["SCRIPT_FILENAME"])) {
  $base_path = str_replace("/wp-content/plugins/moumar_multi_rating", "", dirname(__FILE__));
  session_start();
  
  require($base_path . '/wp-load.php');

  $post_id = $_POST["post_id"];
  $rating_category = $_POST["rating_category"];
  $rating = $_POST["rating"];
  $session_key = "moumar-multi-rating-$post_id-$rating_category";

  $previous_rating = $_SESSION[$session_key] ;
  //echo "rating: '$rating', previous '$previous_rating'";

  if ($previous_rating) {
    //mmr_rate_post($post_id, $rating_category, -1 * $previous_rating) ;
    echo "vous avez déjà voté!";
  } else {
    mmr_rate_post($post_id, $rating_category, $rating);
    $_SESSION[$session_key] = $_POST["rating"];
    echo "Merci pour votre vote.";
  }

  exit();
}

function mmr_rate_post($post_id, $rating_category, $rating) {
  $ratings_for_country = get_post_meta($post_id, "moumar_multi_rating", true);
  
  if (!$ratings_for_country) {
    $ratings_for_country = array();
  }

  if (!$ratings_for_country[$rating_category]) {
    $ratings_for_country[$rating_category] = array("count" => 0, "total" => 0);
  }

  $ratings_for_country[$rating_category]["count"] += ($rating < 0 ? -1 : 1);
  $ratings_for_country[$rating_category]["total"] += $rating;
  //echo( "count: " . $ratings_for_country[$rating_category]["count"] . " total : " . $ratings_for_country[$rating_category]["total"]);

  add_post_meta($post_id, "moumar_multi_rating", $ratings_for_country, true) or update_post_meta($post_id, "moumar_multi_rating", $ratings_for_country);
}

function mmr_pluralize($root, $count) {
  $s = "$count $root";
  if ($count != 1) {
    $s .= "s";
  }
  return $s;
}

function mmr_show($rating_category = null) {
  global $post, $MMR_RATING_CATEGORIES;
  if ($rating_category) {
    $category_full_name = $MMR_RATING_CATEGORIES[$rating_category];
    if (!$category_full_name) {
      echo "<big> la catégorie " . htmlspecialchars($rating_category) . " n'existe pas!</big>";
      return;
    }

    $ratings_for_country = get_post_meta($post->ID, 'moumar_multi_rating', true);
    $count = 0;
    $total = 0;

    if ($ratings_for_country[$rating_category]) {
      $count = $ratings_for_country[$rating_category]["count"] or 0;
      $total = $ratings_for_country[$rating_category]["total"] or 0;
    }
    if ($total > 0)
      $note = round($total/$count, 1);
    else
      $note = 0;

    mmr_show_rating($note, htmlspecialchars($category_full_name), ", " . mmr_pluralize("vote", $count), array("post_id" => $post->ID, "rating_category" => $rating_category), true);
  } else {
    foreach($MMR_RATING_CATEGORIES as $key => $value) {
      mmr_show($key);
    }
  }
}

function mmr_show_rating($note, $title, $suffix, $datas, $is_editable) {
  $editable_class = $is_editable ? "editable" : "";
  $post_url = plugins_url(basename(__FILE__), __FILE__);
  $data_string = "data-post_url=\"" . $post_url . "\"";
  foreach($datas as $k => $v) {
    $data_string .= " data-$k=\"$v\" ";
  }

  echo "<div class=\"mmr-rating $editable_class\" $data_string >";
  echo "<span class=\"title\">" . $title ."</span>";
  $image_path = plugin_dir_url(__FILE__ ) . 'images';
  for($i = 0; $i < 5; $i++) {
    if ((int)$note > $i)
      $base = "rating_on";
    else if ($note > $i and fmod($note, 1) > 0.1)
      $base = "rating_half";
    else
      $base = "rating_off";
    $src = "$image_path/$base.gif";

    echo "<img src=\"$src\" data-index=\"$i\" data-original_src=\"$src\" />";
  }
  echo mmr_pluralize("étoile", $note) . $suffix;
  echo ' <span class="text"></span>';
  echo '</div>';
}

function mmr_clear_all() {
  foreach(get_posts() as $post) {
    setup_postdata($post);
    delete_post_meta($post->ID, "moumar_multi_rating"); 
  }
}

function mmr_top_overall($limit = 10) {
  $ratings = array();

  foreach(get_posts() as $post) {
    setup_postdata($post);
    $count = 0;
    $total = 0;
    $ratings_for_country = get_post_meta($post->ID, "moumar_multi_rating", true);
    if (!$ratings_for_country)
      continue;
    foreach($ratings_for_country as $rating) {
      $count += $rating["count"];
      $total += $rating["total"];
    }
    $ratings[$post->ID] = 0;
    if ($count > 0) {
      $ratings[$post->ID] = $total / $count;
    }
  }
  
  mmr_internal_show_ratings($ratings, $limit, "global");
}

function mmr_top($rating_category, $limit = 10) {
  global $MMR_RATING_CATEGORIES;

  if (is_numeric($rating_category)) {
    $limit = $rating_category;
    foreach($MMR_RATING_CATEGORIES as $key => $value) {
      mmr_top($key, $limit);
    }
  } else {
    if (!$MMR_RATING_CATEGORIES[$rating_category]) {
      echo "<big> la catégorie " . htmlspecialchars($rating_category) . " n'existe pas!</big>";
      return;
    }
    #$args = array('category' => -9); // exclude category 9
    #$custom_posts = get_posts($args);
    $custom_posts = get_posts();
    $ratings = array();
    foreach($custom_posts as $post) {
      setup_postdata($post);
      $ratings_for_country = get_post_meta($post->ID, "moumar_multi_rating", true);
      if ($ratings_for_country and $ratings_for_country[$rating_category]) {
        $count = $ratings_for_country[$rating_category]["count"];
        if ($count) 
          $ratings[$post->ID] = $ratings_for_country[$rating_category]["total"] / $count;
      }
    }
    mmr_internal_show_ratings($ratings, $limit, $MMR_RATING_CATEGORIES[$rating_category]);
  }
}

function mmr_internal_show_ratings($ratings, $limit, $title) {
  arsort($ratings);
  $ratings = array_slice($ratings, 0, $limit);
  //print_r($ratings);

  echo "<div class=\"mmr-rating-top\"><h3>Top " . htmlspecialchars($limit). " " . htmlspecialchars($title) . "</h3>";
  echo "<ol>";
  foreach ($ratings as $post_id => $note) {
    if ($note == 0)
      continue;
    echo "<li>";
    $permalink = htmlspecialchars(get_permalink($post_id));
    $title = htmlspecialchars(get_the_title($post_id));
    $html = "<a href=\"$permalink\" title=\"$title\">$title</a>";
    
    mmr_show_rating(round($note, 1), $html, "", array(), false);
    echo "</li>";
  }
  echo "</ol>";
  echo "</div>";
}

function mmr_init() {
  wp_enqueue_script( 'jquery' );
  wp_enqueue_script( 'moumar_multi_rating', plugins_url('moumar_multi_rating/moumar_multi_rating.js') );
  wp_enqueue_style( 'moumar_multi_rating', plugins_url('moumar_multi_rating/moumar_multi_rating.css') );
}

add_action('init', 'mmr_init');
?>

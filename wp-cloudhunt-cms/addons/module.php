<?php

add_action('init', 'app_change_post_object');
function app_change_post_object()
{
  $get_post_type = get_post_type_object('post');
  $get_post_type->menu_icon = 'dashicons-heart';
  $labels = $get_post_type->labels;
  foreach ($labels as $key => $label) {
    $label = str_replace('Post', 'Module', $label);
    $label = str_replace('post', 'module', $label);
    $labels->{$key} = $label;
  }
}

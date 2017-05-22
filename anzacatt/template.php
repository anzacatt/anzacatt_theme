<?php

/**
 * @file
 * template.php
 */

/**
 * Implements hook_html_head_alter().
 */
function anzacatt_html_head_alter(&$head_elements) {
  // Mobile Viewport.
  $head_elements['viewport'] = array(
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => array(
      'name' => 'viewport',
      'content' => 'width=device-width, initial-scale=1',
    ),
  );
  // IE Latest Browser.
  $head_elements['ie_view'] = array(
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => array(
      'http-equiv' => 'x-ua-compatible',
      'content' => 'ie=edge',
    ),
  );
}

/**
 * Implements hook_js_alter().
 */
function anzacatt_js_alter(&$javascript) {
  $javascript['misc/jquery.js']['data'] = drupal_get_path('theme', 'anzacatt') . '/vendor/jquery/jquery-3.1.1.min.js';
}

/**
 * Implements hook_preprocess_html().
 */
function anzacatt_preprocess_html(&$variables) {
  // Use the HTML hook to deny access to non-members.
  $restrict_url = array(
    'search',
  );

  if (user_is_anonymous()) {
    $access = TRUE;
    $menu_object = menu_get_object();
    if (!empty($menu_object->path['alias'])) {
      $menu_object_alias = explode("/", $menu_object->path['alias']);
    }

    if (in_array(arg(0), $restrict_url) || (!empty($menu_object_alias[0]) && in_array($menu_object_alias[0], $restrict_url))) {
      $access = FALSE;
    }

    // Fallback if nothing matched by URL, check if the field members only
    // requires private access.
    if (!empty($menu_object->field_member_only_access) && $menu_object->field_member_only_access[$menu_object->language][0]['value'] == 'membersonly') {
      $access = FALSE;
    }

    if (!$access) {
      drupal_goto('member-only');
    }
  }

  // Adding rotating images to all pages.
  $parliament_images = _anzacatt_prepare_parliament_images_array();
  drupal_add_js($parliament_images, array('type' => 'setting'));

  drupal_add_js("(function(h) {h.className = h.className.replace('no-js', '') })(document.documentElement);", array(
    'type' => 'inline',
    'scope' => 'header',
  ));
  drupal_add_js('jQuery.extend(Drupal.settings, { "pathToTheme": "' . path_to_theme() . '" });', 'inline');
  // Drupal forms.js does not support new jQuery. Migrate library needed.
  drupal_add_js(drupal_get_path('theme', 'anzacatt') . '/vendor/jquery/jquery-migrate-1.2.1.min.js');
}

function _anzacatt_prepare_parliament_images_array() {
  $images = ['anzacatt' => ['parliament_images' => []]];
  $results = views_get_view_result('parliamentary_image_urls', 'block');
  foreach ($results as $result) {
    $images['anzacatt']['parliament_images'][$result->field_field_parliament[0]['rendered']['#markup']][] = image_style_url('govcms_ui_kit_banner', $result->file_managed_uri);
  }

  return $images;
}

/**
 * Implements hook_preprocess_field().
 */
function anzacatt_preprocess_field(&$variables) {
  // Member email addresses converted to links.
  if ($variables['element']['#field_name'] === 'field_member_email_address') {
    if (!empty($variables['items'][0]['#markup'])) {
      $address = $variables['items'][0]['#markup'];
      // Test email is valid.
      if (valid_email_address($address)) {
        $variables['items'][0]['#markup'] = l($address, 'mailto:' . $address);
      }
    }
  }
  // Bean 'Image and Text' field 'Link To' to show 'Read [title]' text.
  if ($variables['element']['#field_name'] === 'field_link_to' && $variables['element']['#bundle'] === 'image_and_text') {
    if (!empty($variables['items'][0]) && !empty($variables['element']['#object']->title)) {
      // This only applies if field has a non-configurable title.
      if ($variables['items'][0]['#field']['settings']['title'] === 'none') {
        $variables['items'][0]['#element']['title'] = t('Read !title', array('!title' => $variables['element']['#object']->title));
      }
    }
  }
  if (theme_get_setting('anzacatt_override_image_styles') == 1) {
    // Define custom image style for image banners on home page.
    if ($variables['element']['#field_name'] === 'field_slide_image') {
      if ($variables['items'][0]['#image_style'] === 'feature_article') {
        $variables['items'][0]['#image_style'] = 'govcms_ui_kit_banner';
      }
    }
    // Define custom image style for thumbnails on news / blogs / etc.
    elseif ($variables['element']['#field_name'] === 'field_thumbnail') {
      $image_style = $variables['items'][0]['#image_style'];
      if ($image_style === 'medium' || $image_style === 'thumbnail') {
        $variables['items'][0]['#image_style'] = 'govcms_ui_kit_thumbnail';
      }
    }
    // Define custom image style for views.
    elseif ($variables['element']['#field_name'] === 'field_image') {
      if ($variables['items'][0]['#image_style'] === 'medium') {
        $variables['items'][0]['#image_style'] = 'govcms_ui_kit_thumbnail';
      }
    }
  }
}

/**
 * Implements hook_views_pre_render().
 */
function anzacatt_views_pre_render(&$variables) {
  if (theme_get_setting('anzacatt_override_image_styles') == 1) {
    if ($variables->name === 'footer_teaser') {
      $len = count($variables->result);
      for ($i = 0; $i < $len; $i++) {
        if (!empty($variables->result[$i]->field_field_image)) {
          // Define custom image style for thumbnails on footer_teaser.
          if ($variables->result[$i]->field_field_image[0]['rendered']['#image_style'] == 'blog_teaser_thumbnail') {
            $variables->result[$i]->field_field_image[0]['rendered']['#image_style'] = 'govcms_ui_kit_thumbnail';
          }
        }
      }
    }
  }
}

/**
 * Implements hook_image_styles_alter().
 */
function anzacatt_image_styles_alter(&$styles) {
  if (theme_get_setting('anzacatt_override_image_styles') == 1) {
    $styles['govcms_ui_kit_banner'] = array(
      'label' => 'govCMS UI-KIT - Banner',
      'name' => 'govcms_ui_kit_banner',
      'storage' => IMAGE_STORAGE_NORMAL,
      'effects' => array(
        array(
          'label' => 'Scale and crop',
          'name' => 'image_scale_and_crop',
          'data' => array(
            'width' => 1650,
            'height' => 440,
            'upscale' => 1,
          ),
          'effect callback' => 'image_scale_and_crop_effect',
          'dimensions callback' => 'image_resize_dimensions',
          'form callback' => 'image_resize_form',
          'summary theme' => 'image_resize_summary',
          'module' => 'image',
          'weight' => 0,
        ),
      ),
    );
    $styles['govcms_ui_kit_thumbnail'] = array(
      'label' => 'govCMS UI-KIT - Thumbnail',
      'name' => 'govcms_ui_kit_thumbnail',
      'storage' => IMAGE_STORAGE_NORMAL,
      'effects' => array(
        array(
          'label' => 'Scale and crop',
          'name' => 'image_scale_and_crop',
          'data' => array(
            'width' => 370,
            'height' => 275,
            'upscale' => 1,
          ),
          'effect callback' => 'image_scale_and_crop_effect',
          'dimensions callback' => 'image_resize_dimensions',
          'form callback' => 'image_resize_form',
          'summary theme' => 'image_resize_summary',
          'module' => 'image',
          'weight' => 0,
        ),
      ),
    );
  }
  return $styles;
}

/**
 * Implements hook_preprocess_node().
 */
function anzacatt_preprocess_node(&$variables) {
  // Adding theme suggestions for various view modes and content types.
  $view_mode = $variables['view_mode'];
  $content_type = $variables['type'];
  $variables['theme_hook_suggestions'][] = 'node__' . $view_mode;
  $variables['theme_hook_suggestions'][] = 'node__' . $view_mode . '_' . $content_type;

  // Adding preprocess function suggestions for view mode and content type.
  $view_mode_preprocess = 'anzacatt_preprocess_node_' . $view_mode . '_' . $content_type;
  if (function_exists($view_mode_preprocess)) {
    $view_mode_preprocess($variables);
  }

  $view_mode_preprocess = 'anzacatt_preprocess_node_' . $view_mode;
  if (function_exists($view_mode_preprocess)) {
    $view_mode_preprocess($variables);
  }

  if ($variables['view_mode'] === 'teaser' || $variables['view_mode'] === 'compact') {
    // Apply thumbnail class to node teaser view if image exists.
    $has_thumb = !empty($variables['content']['field_thumbnail']);
    $has_image = !empty($variables['content']['field_image']);
    $has_featured_image = !empty($variables['content']['field_feature_image']);
    if ($has_thumb || $has_image || $has_featured_image) {
      $variables['classes_array'][] = 'has-thumbnail';
    }
  }

  if ($variables['type'] === 'webform') {
    // Hide submitted date on webforms.
    $variables['display_submitted'] = FALSE;
  }
}

/**
 * Implements theme_breadcrumb().
 */
function anzacatt_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];
  $output = '';

  if (!empty($breadcrumb)) {
    // Build the breadcrumb trail.
    $output = '<nav class="breadcrumbs--inverted" role="navigation" aria-label="breadcrumb">';
    $output .= '<ul><li>' . implode('</li><li>', $breadcrumb) . '</li></ul>';
    $output .= '</nav>';
  }

  return $output;
}

/**
 * Implements hook_form_alter().
 */
function anzacatt_form_alter(&$form, &$form_state, $form_id) {
  if ($form_id === 'search_api_page_search_form_default_search') {
    // Global header form.
    $form['keys_1']['#attributes']['placeholder'] = t('Type search term here');
    $form['keys_1']['#title'] = t('Search field');
  }
  elseif ($form_id === 'search_api_page_search_form') {
    // Search page (above results) form.
    $form['form']['keys_1']['#title'] = t('Type search term here');
  }
  if ($form_id === 'search_form') {
    // Search form on page not found (404 page).
    $form['basic']['keys']['#title'] = t('Type search term here');
  }
}

/**
 * Implements theme_preprocess_search_api_page_result().
 */
function anzacatt_preprocess_search_api_page_result(&$variables) {
  // Strip out HTML tags from search results.
  $variables['snippet'] = strip_tags($variables['snippet']);
  // Remove the author / date from the result display.
  $variables['info'] = '';
}

/**
 * Implements theme_preprocess_search_result().
 */
function anzacatt_preprocess_search_result(&$variables) {
  // Strip out HTML tags from search results (404 page).
  $variables['snippet'] = strip_tags($variables['snippet']);
  // Remove the author / date from the result display (404 page).
  $variables['info'] = '';
}

/**
 * Implements hook_entity_info_alter().
 */
function anzacatt_entity_info_alter(&$entity_info) {
  $entity_info['node']['view modes']['listing'] = array(
    'label' => t('Listing'),
    'custom settings' => TRUE,
  );
}

/**
 * Template preprocess for Event node type Listing view mode.
 */
function anzacatt_preprocess_node_listing_event(&$variables) {
  $variables['view_more_link'] = l(t('Read more'), 'node/' . $variables['nid']);
}

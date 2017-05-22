<?php

/**
 * @file
 * template.php
 */

/**
 * Implements hook_form_alter().
 */
function anzacatt_admin_form_alter(&$form, &$form_state, $form_id) {
  if (!empty($form['#node_edit_form'])) {
    $form['#submit'][] = 'anzacatt_admin_node_form_submit';
  }
  if ($form_id === 'member_profile_node_form') {
    // Hide the node title field on the Member Profile add/edit form. It will be
    // auto-populated by concatenating the values of the name_title, first_name
    // and last_name fields.
    $form['title']['#access'] = FALSE;
  }
}

/**
 * Submit handler for node forms.
 */
function anzacatt_admin_node_form_submit($form, &$form_state) {
  if ($form_state['values']['type'] === 'member_profile') {
    $lang = $form_state['node']->language;
    $name_title = $form_state['values']['field_name_title'][$lang]['0']['tid'];
    $first_name = $form_state['values']['field_first_name'][$lang]['0']['value'];
    $last_name = $form_state['values']['field_last_name'][$lang]['0']['value'];
    $post_nominals = $form_state['values']['field_post_nominals'][$lang]['0']['value'];
    // The node title field for Member Profiles is hidden from the form.
    // Construct it by concatenating the first_name and last_name fields.
    $full_name =  $first_name . " " . $last_name;
    if ($name_title) {
      // Prepend name_title if it was provided (optional field).
      $term = taxonomy_term_load($name_title);
      $name_title = $term->name;
      $full_name = $name_title . " " . $full_name;
    }
    if ($post_nominals) {
      // Append post_nominals if there are any (optional field).
      $full_name = $full_name . " " . $post_nominals;
    }
    $form_state['values']['title'] = $full_name;
  }
}


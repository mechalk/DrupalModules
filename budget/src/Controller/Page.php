<?php

namespace Drupal\budget\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;

/**
 * Simple page controller for drupal.
 */
class Page extends ControllerBase {

  /**
   * Lists the examples provided by form_example.
   */
  public function description() {
    // These libraries are required to facilitate the ajax modal form demo.
    $content['intro'] = [
      '#markup' => '<p>' . $this->t('Form examples to demonstrate common UI solutions using the Drupal Form API.') . '</p>',
    ];

    // Create a list of links to the form examples.
    $content['links'] = [
      '#theme' => 'item_list',
      '#items' => [
        Link::createFromRoute($this->t('Account Form'), 'budget.account_form'),
      ],
    ];

    return $content;
  }

}

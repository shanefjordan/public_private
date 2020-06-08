<?php
/**
 * @file
 * Contains \Drupal\public_private\Form\PublicPrivateSettingsForm.
 */

namespace Drupal\public_private\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures forms module settings.
 */
class PublicPrivateSettingsForm  extends ConfigFormBase {

  /**
   * Array of content types.
   *
   * @var array
   */
  protected $content_type_option;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $content_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
      $this->content_type_option['_none'] = $this->t('--Select--');
    foreach ($content_types as $content_type) {
      $this->content_type_option[$content_type->id()] = $content_type->label();
    }
  }

  

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'public_private_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['public_private.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('public_private.settings');
    //dpm($config);
    
    $config_list = array();
    if (!empty($config->get('list'))) {
      $config_list = $config->get('list');
    }
    
    $num_values = $form_state->get('num_values');
    if(is_null($num_values)) {
      $num_values = count($config_list);
      $form_state->set('num_values', $num_values);
    }

    // State that the form needs to allow for a hierarchy (ie, multiple names with our names key).
    $form['#tree'] = TRUE;

    // Initial number of names.
    $form['#prefix'] = '<div id="ajax-settings-wizard-wrapper">';
    $form['#suffix'] = '</div>';

    $form['public_private'] = array(
      '#type' => 'details',
      '#title' => $this->t('Public Private File settings'),
      '#open' => TRUE,
    );

    for ( $i = 0; $i < $num_values; $i++) {
      $form['public_private']['file_fields'][] = [
        'content_type' => [
          '#type' => 'select',
          '#title' => $this->t('Content Type'),
          '#default_value' => $config_list[$i]['content_type'],
          '#options' => $this->content_type_option,
          //'#required' => true,
        ],
        'file_field' => [
          '#type' => 'textfield',
          '#description' => $this->t('Use comma (,) in case of multiple file field names in a content type.'),
          '#title' => $this->t('File Field Name'),
          '#default_value' =>$config_list[$i]['file_field'],
          '#size' => 100,
          //'#required' => true,
        ],
      ];
    }
    $form['public_private']['file_fields'][] = [
      'content_type' => [
        '#type' => 'select',
        '#title' => $this->t('Content Type'),
        '#default_value' => NULL,
        '#options' => $this->content_type_option,
        //'#required' => true,
      ],
      'file_field' => [
        '#type' => 'textfield',
        '#description' => $this->t('Use comma (,) in case of multiple file field names in a content type.'),
        '#title' => $this->t('File Field Name'),
        '#default_value' => NULL,
        '#size' => 100,
        //'#required' => true,
      ],
    ];

    // Button to add more set of content type and file field names.
    $form['public_private']['addname'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add more'),
      '#submit' => array('::addNewFields'),
      '#ajax' => [
        'callback' => '::rebuildForm',
        'wrapper' => 'ajax-settings-wizard-wrapper',
        'disable-refocus' => FALSE,
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    // Button to add more set of content type and file field names.
    $form['public_private']['removename'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove one'),
      '#submit' => array('::removeOneField'),
      '#ajax' => [
        'callback' => '::rebuildForm',
        'wrapper' => 'ajax-settings-wizard-wrapper',
        'disable-refocus' => FALSE,
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValues()['op'] == 'submit') {
      parent::validateForm($form, $form_state);
    }
    else {
      return TRUE;
    }
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $public_private = array();
    foreach ($form_state->getValues()['public_private']['file_fields'] as $key => $item) {
       
      if($item['content_type'] != '_none' && !is_null($item['file_field']) ) {
        $public_private[] = $item;
      }
    }

    $this->config('public_private.settings')
      ->set('list', $public_private)
      ->save();

  }

  /**
   * Handle adding new.
   */
  public function addNewFields(array &$form, FormStateInterface &$form_state) {
    $num_values = $form_state->get('num_values');
    $add_button = $num_values + 1;
    $form_state->set('num_values', $add_button);

    $form_state->setRebuild();
    // Rebuild the form.
    //return $form;
  }

  /**
   * Handle adding new.
   */
  public function removeOneField(array &$form, FormStateInterface &$form_state) {

    $num_values = $form_state->get('num_values');
    if ($num_values > 0) {
      $remove_button = $num_values - 1;
      $form_state->set('num_values', $remove_button);
    }
    // Rebuild the form.
    $form_state->setRebuild();
    //return $form;
  }

  /**
   * Handle adding new.
   */
  public function rebuildForm(array &$form, FormStateInterface &$form_state) {

   
    return $form;
  }
}
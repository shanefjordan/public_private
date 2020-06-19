<?php

namespace Drupal\public_private\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures forms module settings.
 */
class PublicPrivateSettingsForm extends ConfigFormBase {

  /**
   * Array of content types.
   *
   * @var array
   */
  protected $contentTypeOptions;
  /**
   * The request context.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory, $entity_type_manager);

    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $content_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    $this->contentTypeOptions['_none'] = $this->t('--Select--');
    foreach ($content_types as $content_type) {
      $this->contentTypeOptions[$content_type->id()] = $content_type->label();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')
    );
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
    // dpm($config);
    $config_list = [];
    if (!empty($config->get('list'))) {
      $config_list = $config->get('list');
    }

    $num_values = $form_state->get('num_values');
    if (is_null($num_values)) {
      $num_values = count($config_list);
      $form_state->set('num_values', $num_values);
    }

    // State that the form needs to allow for a hierarchy
    // (ie, multiple names with our names key).
    $form['#tree'] = TRUE;

    // Initial number of names.
    $form['#prefix'] = '<div id="ajax-settings-wizard-wrapper">';
    $form['#suffix'] = '</div>';

    $form['public_private'] = [
      '#type' => 'details',
      '#title' => $this->t('Public Private File settings'),
      '#open' => TRUE,
    ];

    for ($i = 0; $i < $num_values; $i++) {
      $form['public_private']['file_fields'][] = [
        'content_type' => [
          '#type' => 'select',
          '#title' => $this->t('Content Type'),
          '#default_value' => $config_list[$i]['content_type'],
          '#options' => $this->contentTypeOptions,
          // '#required' => true,
        ],
        'file_field' => [
          '#type' => 'textfield',
          '#description' => $this->t('Use comma (,) in case of multiple file field names in a content type.'),
          '#title' => $this->t('File Field Name'),
          '#default_value' => $config_list[$i]['file_field'],
          '#size' => 100,
          // '#required' => true,
        ],
      ];
    }
    $form['public_private']['file_fields'][] = [
      'content_type' => [
        '#type' => 'select',
        '#title' => $this->t('Content Type'),
        '#default_value' => NULL,
        '#options' => $this->contentTypeOptions,
        // '#required' => true,
      ],
      'file_field' => [
        '#type' => 'textfield',
        '#description' => $this->t('Use comma (,) in case of multiple file field names in a content type.'),
        '#title' => $this->t('File Field Name'),
        '#default_value' => NULL,
        '#size' => 100,
        // '#required' => true,
      ],
    ];

    // Button to add more set of content type and file field names.
    $form['public_private']['addname'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add more'),
      '#submit' => ['::addNewFields'],
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
      '#submit' => ['::removeOneField'],
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
    $public_private = [];
    foreach ($form_state->getValues()['public_private']['file_fields'] as $item) {

      if ($item['content_type'] != '_none' && !is_null($item['file_field'])) {
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
    // return $form;.
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
    // Return $form;.
  }

  /**
   * Handle adding new.
   */
  public function rebuildForm(array &$form, FormStateInterface &$form_state) {

    return $form;
  }

}

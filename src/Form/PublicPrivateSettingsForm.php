<?php
/**
 * @file
 * Contains \Drupal\public_private\Form\PublicPrivateSettingsForm.
 */

namespace Drupal\public_private\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class PublicPrivateSettingsForm  extends ConfigFormBase {

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

    // State that the form needs to allow for a hierarchy (ie, multiple names with our names key).
    $form['#tree'] = TRUE;

    // Initial number of names.
    if (!$form_state->get('num_names')) {
      $count = count($config->get('content_type'));
      if ($count == 0) {
        $count = 1;
      }
      $form_state->set('num_names', $count);
    }

    $form['public_private'] = array(
      '#type' => 'details',
      '#title' => $this->t('Public Private File settings'),
      '#open' => TRUE,
    );

    $content_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($content_types as $content_type) {
      $content_type_option[$content_type->id()] = $content_type->label();
    }

    for ($i = 0; $i < $form_state->get('num_names'); $i++) {
      $form['public_private']['container_box'][$i] = [
        '#type' => 'container',
      ];
      $form['public_private']['container_box'][$i]['content_type'][$i] = array(
        '#type' => 'select',
        '#title' => $this->t('Content Type'),
        '#default_value' => $config->get('content_type')[$i],
        '#options' => $content_type_option,
        '#required' => true,
      );
      $form['public_private']['container_box'][$i]['file_field'][$i] = array(
        '#type' => 'textfield',
        '#description' => $this->t('Use comma (,) in case of multiple file field names in a content type.'),
        '#title' => $this->t('File Field Name'),
        '#default_value' => $config->get('file_field')[$i],
        '#size' => 100,
        '#required' => true,
      );
    }

    // Button to add more set of content type and file field names.
    $form['public_private']['addname'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add more'),
    ];

    // Button to add more set of content type and file field names.
    $form['public_private']['removename'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove one'),
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
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Decide what action to take based on which button the user clicked.
    switch ($values['op']) {
      case 'Add more':
        $this->addNewFields($form, $form_state);
        break;
      case 'Remove one':
        $this->removeOneField($form, $form_state);
        break;

      default:
        $this->finalSubmit($form, $form_state);
    }

  }

  /**
   * Handle adding new.
   */
  private function addNewFields(array &$form, FormStateInterface &$form_state) {

    // Add 1 to the number of names.
    $num_names = $form_state->get('num_names');
    $form_state->set('num_names', ($num_names + 1));

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Handle adding new.
   */
  private function removeOneField(array &$form, FormStateInterface &$form_state) {

    // Add 1 to the number of names.
    $num_names = $form_state->get('num_names');
    $form_state->set('num_names', ($num_names - 1));

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Handle submit.
   */
  private function finalSubmit(array &$form, FormStateInterface &$form_state) {
    foreach ($form_state->getValues() as $key => $val) {
      if ($key === 'public_private') {
        foreach ($val['container_box'] as $value) {
          if (isset($value['content_type'])) {
            $content_types[] = array_values(array_filter($value['content_type']))[0];
          }
          if (isset($value['file_field'])) {
            $file_field[] = array_values(array_filter($value['file_field']))[0];
          }
        }
      }
    }

    $this->config('public_private.settings')
      ->set('content_type', $content_types)
      ->set('file_field', $file_field)
      ->save();

    parent::submitForm($form, $form_state);
  }

}

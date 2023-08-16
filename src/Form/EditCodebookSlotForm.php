<?php

namespace Drupal\sir\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sir\Utils;

class EditCodebookSlotForm extends FormBase {

  protected $codebookSlotUri;

  protected $codebookSlot;

  public function getCodebookSlotUri() {
    return $this->codebookSlotUri;
  }

  public function setCodebookSlotUri($uri) {
    return $this->codebookSlotUri = $uri; 
  }

  public function getCodebookSlot() {
    return $this->codebookSlot;
  }

  public function setCodebookSlot($obj) {
    return $this->codebookSlot = $obj; 
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_codebookslot_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $codebooksloturi = NULL) {
    $uri=$codebooksloturi;
    $uri_decode=base64_decode($uri);
    $this->setCodebookSlotUri($uri_decode);

    $fusekiAPIservice = \Drupal::service('sir.api_connector');
    $rawresponse = $fusekiAPIservice->getUri($this->getCodebookSlotUri());
    $obj = json_decode($rawresponse);

    $content = "";
    if ($obj->isSuccessful) {
      $this->setCodebookSlot($obj->body);
      if ($this->getCodebookSlot()->responseOption != NULL) {
        $content = $this->getCodebookSlot()->responseOption->hasContent . ' [' . $this->getCodebookSlot()->hasResponseOption . ']';
      }
        //dpm($this->getCodebookSlot());
    } else {
      \Drupal::messenger()->addMessage(t("Failed to retrieve Codebook Slot."));
      $url = Url::fromRoute('sir.manage_experiments');
      $form_state->setRedirectUrl($url);
    }

    $form['codebook_slot_uri'] = [
      '#type' => 'textfield',
      '#title' => t('Codebook Slot URI'),
      '#value' => $this->getCodebookSlotUri(),
      '#disabled' => TRUE,
    ];
    $form['codebook_slot_priority'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Priority'),
      '#default_value' => $this->getCodebookSlot()->hasPriority,
      '#disabled' => TRUE,
    ];
    $form['codebook_slot_response_option'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Response Option"),
      '#default_value' => $content,
      '#autocomplete_route_name' => 'sir.codebook_slot_response_option_autocomplete',
    ];
    $form['new_response_option_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('New Response Option'),
      '#name' => 'new_response_option',
    ];
    $form['reset_response_option_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset Codebook Slot'),
      '#name' => 'reset_response_option',
    ];
    $form['update_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#name' => 'save',
    ];
    $form['cancel_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#name' => 'back',
    ];
    $form['bottom_space'] = [
      '#type' => 'item',
      '#title' => t('<br><br>'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    if ($button_name != 'back') {
      if(strlen($form_state->getValue('codebook_slot_priority')) < 1) {
        $form_state->setErrorByName('codebook_slot_priority', $this->t('Please enter a valid priority value'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    if ($button_name === 'back') {
      $url = Url::fromRoute('sir.manage_codebook_slots');
      $url->setRouteParameter('experienceuri', base64_encode($this->getCodebookSlot()->belongsTo));
      $form_state->setRedirectUrl($url);
      return;
    } 

    if ($button_name === 'new_response_option') {
      $url = Url::fromRoute('sir.add_response_option');
      $url->setRouteParameter('codebooksloturi', base64_encode($this->getCodebookSlotUri())); 
      $form_state->setRedirectUrl($url);
      return;
    } 

    if ($button_name === 'reset_response_option') {
      // RESET responseOption
      if ($this->getCodebookSlotUri() != NULL) {
        $fusekiAPIservice = \Drupal::service('sir.api_connector');
        $fusekiAPIservice->codebookSlotReset($this->getCodebookSlotUri());
      } 

      $url = Url::fromRoute('sir.manage_codebook_slots');
      $url->setRouteParameter('experienceuri', base64_encode($this->getCodebookSlot()->belongsTo));
      $form_state->setRedirectUrl($url);
      return;
    } 

    try {
      // UPDATE responseOption
      if ($this->getCodebookSlotUri() != NULL) {
        $fusekiAPIservice = \Drupal::service('sir.api_connector');
        $fusekiAPIservice->responseOptionAttach(Utils::uriFromAutocomplete($form_state->getValue('codebook_slot_response_option')),$this->getCodebookSlotUri());
      } 

      \Drupal::messenger()->addMessage(t("Codebook Slot has been updated successfully."));
      $url = Url::fromRoute('sir.manage_codebook_slots');
      $url->setRouteParameter('experienceuri', base64_encode($this->getCodebookSlot()->belongsTo));
      $form_state->setRedirectUrl($url);

    } catch(\Exception $e) {
      \Drupal::messenger()->addMessage(t("An error occurred while updating the Codebook Slot: ".$e->getMessage()));
      $url = Url::fromRoute('sir.manage_codebook_slots');
      $url->setRouteParameter('experienceuri', base64_encode($this->getCodebookSlot()->belongsTo));
      $form_state->setRedirectUrl($url);
    }

  }

}
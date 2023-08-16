<?php

namespace Drupal\sir\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sir\Utils;

class EditAttachmentForm extends FormBase {

  protected $attachmentUri;

  protected $attachment;

  public function getAttachmentUri() {
    return $this->attachmentUri;
  }

  public function setAttachmentUri($uri) {
    return $this->attachmentUri = $uri; 
  }

  public function getAttachment() {
    return $this->attachment;
  }

  public function setAttachment($obj) {
    return $this->attachment = $obj; 
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_attachment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $attachmenturi = NULL) {
    $uri=$attachmenturi;
    $uri_decode=base64_decode($uri);
    $this->setAttachmentUri($uri_decode);

    $fusekiAPIservice = \Drupal::service('sir.api_connector');
    $rawresponse = $fusekiAPIservice->getUri($this->getAttachmentUri());
    $obj = json_decode($rawresponse);

    $content = "";
    if ($obj->isSuccessful) {
      $this->setAttachment($obj->body);
      if ($this->getAttachment()->detector != NULL) {
        $content = $this->getAttachment()->detector->hasContent . ' [' . $this->getAttachment()->hasDetector . ']';
      }
        //dpm($this->getAttachment());
    } else {
      \Drupal::messenger()->addMessage(t("Failed to retrieve Attachment."));
      $url = Url::fromRoute('sir.manage_instruments');
      $form_state->setRedirectUrl($url);
    }

    $form['attachment_uri'] = [
      '#type' => 'textfield',
      '#title' => t('Attachment URI'),
      '#value' => $this->getAttachmentUri(),
      '#disabled' => TRUE,
    ];
    //$form['attachment_instrument'] = [
    //  '#type' => 'textfield',
    //  '#title' => t('Instrument URI'),
    //  '#value' => $this->getAttachment()->belongsTo,
    //  '#disabled' => TRUE,
    //];
    $form['attachment_priority'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Priority'),
      '#default_value' => $this->getAttachment()->hasPriority,
      '#disabled' => TRUE,
    ];
    $form['attachment_detector'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Item"),
      '#default_value' => $content,
      '#autocomplete_route_name' => 'sir.attachment_detector_autocomplete',
      '#maxlength' => NULL,

    ];
    //$form['attachment_detector_uri'] = [
    //  '#type' => 'textfield',
    //  '#title' => $this->t("Item Uri"),
    //  '#default_value' => $this->getAttachment()->hasDetector,
    //  '#disabled' => TRUE,
    //];
    $form['new_detector_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('New Item'),
      '#name' => 'new_detector',
    ];
    $form['reset_detector_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset Item'),
      '#name' => 'reset_detector',
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
    $submitted_values = $form_state->cleanValues()->getValues();
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    if ($button_name != 'back') {
      if(strlen($form_state->getValue('attachment_priority')) < 1) {
        $form_state->setErrorByName('attachment_priority', $this->t('Please enter a valid priority value'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submitted_values = $form_state->cleanValues()->getValues();
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];

    $uid = \Drupal::currentUser()->id();
    $uemail = \Drupal::currentUser()->getEmail();

    if ($button_name === 'back') {
      $url = Url::fromRoute('sir.manage_attachments');
      $url->setRouteParameter('instrumenturi', base64_encode($this->getAttachment()->belongsTo));
      $form_state->setRedirectUrl($url);
      return;
    } 

    if ($button_name === 'new_detector') {
      $url = Url::fromRoute('sir.add_detector');
      $url->setRouteParameter('sourcedetectoruri', 'EMPTY'); 
      $url->setRouteParameter('attachmenturi', base64_encode($this->getAttachmentUri())); 
      $form_state->setRedirectUrl($url);
      return;
    } 

    if ($button_name === 'reset_detector') {
      // RESET DETECTOR
      if ($this->getAttachmentUri() != NULL) {
        $fusekiAPIservice = \Drupal::service('sir.api_connector');
        $fusekiAPIservice->attachmentReset($this->getAttachmentUri());
      } 

      $url = Url::fromRoute('sir.manage_attachments');
      $url->setRouteParameter('instrumenturi', base64_encode($this->getAttachment()->belongsTo));
      $form_state->setRedirectUrl($url);
      return;
    } 

    try{
      // UPDATE DETECTOR
      if ($this->getAttachmentUri() != NULL) {
        $fusekiAPIservice = \Drupal::service('sir.api_connector');
        $fusekiAPIservice->detectorAttach(Utils::uriFromAutocomplete($form_state->getValue('attachment_detector')),$this->getAttachmentUri());
      } 

      \Drupal::messenger()->addMessage(t("Attachment has been updated successfully."));
      $url = Url::fromRoute('sir.manage_attachments');
      $url->setRouteParameter('instrumenturi', base64_encode($this->getAttachment()->belongsTo));
      $form_state->setRedirectUrl($url);

    }catch(\Exception $e){
      \Drupal::messenger()->addMessage(t("An error occurred while updating the attachment: ".$e->getMessage()));
      $url = Url::fromRoute('sir.manage_attachments');
      $url->setRouteParameter('instrumenturi', base64_encode($this->getAttachment()->belongsTo));
      $form_state->setRedirectUrl($url);
    }

  }

}
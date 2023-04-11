<?php

namespace Drupal\sir\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;

class ManageExperiencesForm extends FormBase {

    /**
   * Settings Variable.
   */
  Const CONFIGNAME = "sir.settings";

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_experiences_form';
  }

  /**
     * {@inheritdoc}
     */

     protected function getEditableConfigNames() {
      return [
          static::CONFIGNAME,
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    # GET CONTENT

    $config = $this->config(static::CONFIGNAME);           
    $api_url = $config->get("api_url");
    $uemail = \Drupal::currentUser()->getEmail();
    $uid = \Drupal::currentUser()->id();
    $user = \Drupal\user\Entity\User::load($uid);
    $name = $user->name->value;
    $endpoint = "/sirapi/api/experience/maintaineremail/".rawurlencode($uemail);

    $fusekiAPIservice = \Drupal::service('sir.api_connector');
    $experience_list = $fusekiAPIservice->experiencesList($api_url,$endpoint);
    $obj = json_decode($experience_list);
    $experiences = [];
    if ($obj->isSuccessful) {
      $experiences = $obj->body;
    }
    #dpm($obj);

    # BUILD HEADER

    $header = [
      'experience_name' => t('Name'),
      'experience_language' => t('Language'),
      'experience_version' => t('Version'),
    ];

    # POPULATE DATA

    $output = array();
    foreach ($experiences as $experience) {
      $output[$experience->uri] = [
        'experience_name' => $experience->label,     
        'experience_language' => $experience->hasLanguage,
        'experience_version' => $experience->hasVersion,
      ];
    }

    # PUT FORM TOGETHER

    $form['subtitle'] = [
      '#type' => 'item',
      '#title' => t('<h3>Experiences maintained by <font color="DarkGreen">' . $name . ' (' . $uemail . ')</font></h3>'),
    ];
    $form['add_experience'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Experience'),
      '#name' => 'add_experience',
      '#disabled' => FALSE,      
    ];
    $form['delete_selected_experiences'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Selected Experiences'),
      '#name' => 'delete_experience',
      '#disabled' => TRUE,
    ];
    $form['edit_selected_experience'] = [
      '#type' => 'submit',
      '#value' => $this->t('Edit Selected Experience'),
      '#name' => 'edit_experience',
      '#disabled' => TRUE,      
    ];
    $form['manage_response_options'] = [
      '#type' => 'submit',
      '#value' => $this->t('Manage Response Options of Selected Experience'),
      '#name' => 'manage_response_options',
      '#disabled' => TRUE,
    ];
    $form['experience_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#js_select' => FALSE,
      '#empty' => t('No experiences found'),
      '#ajax' => [
        'callback' => '::experienceAjaxCallback', 
        'disable-refocus' => FALSE, 
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying entry...'),
        ],
      ]    
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#name' => 'back',
    ];
    $form['bottom_space'] = [
      '#type' => 'item',
      '#title' => t('<br><br>'),
    ];

    return $form;
  }

  public function experienceAjaxCallback(array &$form, FormStateInterface $form_state) {
    $selected_rows = $form_state->getValue('experience_table');
    $rows = [];
    foreach ($selected_rows as $index => $selected) {
      if ($selected) {
        $rows[$index] = $index;
      }
    }
    $selected_size = sizeof($rows);
    $response = new AjaxResponse();
    if ($selected_size === 0) {
      $response->addCommand(new InvokeCommand('#edit-edit-selected-experience', 'attr', array('disabled', 'disabled')));
      $response->addCommand(new InvokeCommand('#edit-manage-response-options', 'attr', array('disabled', 'disabled')));
      $response->addCommand(new InvokeCommand('#edit-delete-selected-experiences', 'attr', array('disabled', 'disabled')));
    } else if ($selected_size === 1) {
      $response->addCommand(new InvokeCommand('#edit-edit-selected-experience', 'removeAttr', array('disabled')));
      $response->addCommand(new InvokeCommand('#edit-manage-response-options', 'removeAttr', array('disabled')));
      $response->addCommand(new InvokeCommand('#edit-delete-selected-experiences', 'removeAttr', array('disabled')));
    } else if ($selected_size > 1) {
      $response->addCommand(new InvokeCommand('#edit-edit-selected-experience', 'attr', array('disabled', 'disabled')));
      $response->addCommand(new InvokeCommand('#edit-manage-response-options', 'attr', array('disabled', 'disabled')));
      $response->addCommand(new InvokeCommand('#edit-delete-selected-experiences', 'removeAttr', array('disabled')));
    }
    return $response;
      
      #$form['edit_selected_experience']['#attributes'] = [];
      #$form['edit_selected_experience']['#disabled'] = FALSE;
    
    #return $form['edit_selected_experience'];
    #$form_state->setRebuild(TRUE);
    // Attach the javascript library for the dialog box command
    // in the same way you would attach your custom JS scripts.
      #$dialogText['#attached']['library'][] = 'core/drupal.dialog.ajax';
    // Prepare the text for our dialogbox.
      #$dialogText['#markup'] = "You selected: ". $selected_size;

    // If we want to execute AJAX commands our callback needs to return
    // an AjaxResponse object. let's create it and add our commands.
      #$response = new AjaxResponse();
    // Show the dialog box.
      #$response->addCommand(new OpenModalDialogCommand('My title', $dialogText, ['width' => '300']));

    // Finally return the AjaxResponse object.
      #return $response;
    #}
    #dpm($rows);
    #if(sizeof($rows) === 1) {
    #  \Drupal::messenger()->addMessage(t("1 selected"));
    #} else if (sizeof($rows) > 1) {
    #  \Drupal::messenger()->addMessage(t("more than 1 selected"));
    #} else {
    #  \Drupal::messenger()->addMessage(t("zero selected"));
    #};
  }

  /** 
   * public function validateForm(array &$form, FormStateInterface $form_state) {
   * }
   */

  /**
   * {@inheritdoc}
   */   
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // RETRIEVE TRIGGERING BUTTON
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];
  
    // RETRIEVE SELECTED ROWS, IF ANY
    $selected_rows = $form_state->getValue('experience_table');
    $rows = [];
    foreach ($selected_rows as $index => $selected) {
      if ($selected) {
        $rows[$index] = $index;
      }
    }

    #dpm($rows);

    $config = $this->config(static::CONFIGNAME);     
    $api_url = $config->get("api_url");

    // ADD EXPERIENCE
    if ($button_name === 'add_experience') {
      $url = Url::fromRoute('sir.add_experience');
      $form_state->setRedirectUrl($url);
    }  

    // EDIT EXPERIENCE
    if ($button_name === 'edit_experience') {
      if (sizeof($rows) < 1) {
        \Drupal::messenger()->addMessage(t("Select the exact experience to be edited."));      
      } else if ((sizeof($rows) > 1)) {
        \Drupal::messenger()->addMessage(t("No more than one experience can be edited at once."));      
      } else {
        $first = array_shift($rows);
        $url = Url::fromRoute('sir.edit_experience', ['experienceuri' => base64_encode($first)]);
        $form_state->setRedirectUrl($url);
      } 
    }

    // DELETE EXPERIENCE
    if ($button_name === 'delete_experience') {
      if (sizeof($rows) <= 0) {
        \Drupal::messenger()->addMessage(t("At least one experience needs to be selected to be deleted."));      
      } else {
        foreach($rows as $uri) {
          $uriEncoded = rawurlencode($uri);
          $this->deleteExperience($api_url,"/sirapi/api/experience/delete/".$uriEncoded,[]);  
        }
        \Drupal::messenger()->addMessage(t("Selected experience(s) has/have been deleted successfully."));      
      }
    }  

    // MANAGE RESPONSE OPTIONS
    if ($button_name === 'manage_response_options') {
      if (sizeof($rows) < 1) {
        \Drupal::messenger()->addMessage(t("Select the exact experience which response options are going to be managed."));      
      } else if ((sizeof($rows) > 1)) {
        \Drupal::messenger()->addMessage(t("The response options of no more than one experience can be managed at once."));      
      } else {
        $first = array_shift($rows);
        $url = Url::fromRoute('sir.manage_response_options', ['experienceuri' => base64_encode($first)]);
        $form_state->setRedirectUrl($url);
      } 
    }

    // BACK TO MAIN PAGE
    if ($button_name === 'back') {
      $url = Url::fromRoute('sir.index');
      $form_state->setRedirectUrl($url);
    }  
  }

  public function deleteExperience($api_url,$endpoint,$data){
    $fusekiAPIservice = \Drupal::service('sir.api_connector');
    $newExperience = $fusekiAPIservice->experienceDel($api_url,$endpoint,$data);
    return true;
  }
 
}
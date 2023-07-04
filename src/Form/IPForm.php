<?php

/**
* @file
* Contains the settings for admninistering the SIR Module
*/

namespace Drupal\sir\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\Exception;
use Drupal\Core\Url;

class IPForm extends ConfigFormBase {

    /**
     * Settings Variable.
     */
    Const CONFIGNAME = "sir.settings";

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return "sir_ip_form";
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

     public function buildForm(array $form, FormStateInterface $form_state){
        $config = $this->config(static::CONFIGNAME);

        $form['api_url'] = [
            '#type' => 'textfield',
            '#title' => 'SIR API Base URL',
            '#default_value' => $config->get("api_url"),
        ];
        return Parent::buildForm($form, $form_state);


     }

    public function validateForm(array &$form, FormStateInterface $form_state) {
    }
     
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        // SET SERVICES
        $messenger = \Drupal::service('messenger');
        $fusekiAPIservice = \Drupal::service('sir.api_connector');

        //retrieve Repository configuration
        try {
            $repo = $fusekiAPIservice->repoInfoNewIp($form_state->getValue('api_url'));
            $obj = json_decode($repo);
            if ($obj->isSuccessful) {
                $repoObj = $obj->body;
                $label = $repoObj->label;
                $name = $repoObj->title;
                $domainUrl = $repoObj->hasDefaultNamespaceURL;
                $domainNamespace = $repoObj->hasDefaultNamespaceAbbreviation;
                $description = $repoObj->comment;

                $config = $this->config(static::CONFIGNAME);

                //save confs
                $config->set("site_label", $label);
                $config->set("site_name", $name);
                $config->set("repository_domain_url", $domainUrl);
                $config->set("repository_domain_namespace", $domainNamespace);
                $config->set("repository_description", $description);
                $config->set("api_url", $form_state->getValue('api_url'));
                $config->save();
                
                //site name
                $configdrupal = \Drupal::service('config.factory')->getEditable('system.site');
                $configdrupal->set('name', $name); 
                $configdrupal->save();
            
                $url = Url::fromRoute('sir.repo_info');
                $form_state->setRedirectUrl($url);

            } else {

                $messenger->addMessage($this->t('SIR configuration WAS NOT recovered'));
    
            }
        } catch (Exception $e) {
            $error_message = "Site IP may be incorrect. Error message:" . $e->getMessage();
            $messenger->addMessage($error_message);
        }
        
    }
}
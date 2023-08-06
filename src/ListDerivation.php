<?php

namespace Drupal\sir;

use Drupal\sir\Utils;
use Drupal\sir\Entity\Tables;
use Drupal\sir\Vocabulary\SIRAPI;

class ListDerivation {

  public static function exec($uri) {
    if ($uri == NULL) {
        $resp = array();
        return $resp;
    }
    $api = \Drupal::service('sir.api_connector');
    $elements = $api->parseObjectResponse($api->getDerivation($uri));
    if ($elements == NULL) {
      $elements = array();
      return $elements;
    }
    return $elements;
  }

  public static function fromDetectorToHtml($detectors) {
    $tables = new Tables;
    $generationActivities = $tables->getGenerationActivities();
      $html = "<ul>";
    if (sizeof($detectors) <= 0) {
      $html .= "<li>NONE</li>";
    } else {
      foreach ($detectors as $detector) {
        if ($detector != NULL) {
          //dpm($detector);
          $content = "(EMPTY)";
          $generation = "(UNKNOWN)";
          if ($detector->hasContent != NULL && $detector->hasContent != "") {
            $content = $detector->hasContent;
          }
          if ($detector->wasGeneratedBy != NULL && $detector->wasGeneratedBy != "") {
            $generation = $generationActivities[$detector->wasGeneratedBy];
          }
          $html .= "<li>Detector " . Utils::sirUriLink($detector->uri) . " (" . $content . ") generated by " . $generation . "</li>"; 
        }
      }     
    }
    $html .= "</ul>";
    return $html;
  }

}

?>
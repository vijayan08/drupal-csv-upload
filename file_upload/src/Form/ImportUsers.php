<?php
namespace Drupal\file_upload\Form;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ImportUsers {
  /**
   * @param \Drupal\file\Entity\File $file
   * @param array $context
   */
  public static function import(File $file, array &$context) {

    if (!isset($context['sandbox']['offset'])) {
      $context['sandbox']['offset'] = 0;
      $context['sandbox']['records'] = 0;
      $context['results'] = 0;
    }

    $uri = $file->getFileUri();

    $fp = fopen($uri, 'r');

    if ($fp === FALSE) {
      $context['finished'] = TRUE;
      return;
    }

    $ret = fseek($fp, $context['sandbox']['offset']);

    if ( $ret != 0 ) {
      $context['finished'] = TRUE;
      return;
    }

    // Maximum number of rows to process at a time
    $limit = 20;
    $done = FALSE;

    for ($i = 0; $i < $limit; $i++) {
      $line = fgetcsv($fp);

      if ($line === FALSE ) {
        $done = TRUE;
        // No more records to process
        break;
      }
      else {
        $record = $context['sandbox']['records'];
        $context['sandbox']['records']++;
        $context['sandbox']['offset'] = ftell($fp);

        //Skip File Header
        if ($record == 0) {
          continue;
        }
        else {
          $context['results']++;
        }

        if (is_array($line) && !empty($line)) {
          $firstname = isset($line[0]) ? $line[0] : '';
          $lastname = isset($line[1]) ? $line[1] : '';
          \Drupal::database()->insert('file_upload')
              ->fields(array(
                  'firstname' => $firstname,
                  'lastname' => $lastname
              ))
              ->execute();
        }
      }
    }

    $eof = feof($fp);

    if ($eof)  {
      $context['success'] = TRUE;
    }
    $processed = $context['sandbox']['records'] - 1;
    $context['message'] = t(
      "Processed @processed records",
      ['@processed' => $processed]
    );

    $context['finished'] = ( $eof || $done ) ? 1 : 0;

  }

  public static function finishImport($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $message = \Drupal::translation()
        ->formatPlural($results, 'One record processed.', '@count record processed.');
      $messenger->addMessage($message);
    }
    else {
      $messenger->addMessage( t('Finished with an error.'), $messenger::TYPE_ERROR);
    }

    return new RedirectResponse('fileupload/example');
  }

}

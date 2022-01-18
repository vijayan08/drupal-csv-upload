<?php
namespace Drupal\file_upload\Form;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;


class FileUploadForm extends FormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'user-upload-form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import_csv'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('User Upload'),
      '#upload_location' => 'public://constant-file',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#required' => TRUE,
      '#description' => $this->t('Please upload only CSV file.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Data'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $csv_file = $form_state->getValue('import_csv');
    if(empty($csv_file)){
      $form_state->setErrorByName('import_csv', 'Please upload CSV');
    }
    else {
      /* Load the object of the file by it's fid */
      $file = File::load($csv_file[0]);
      $form_state->set('uploadedFile', $file);
    }
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
    $file = $form_state->get('uploadedFile');
    $batch_builder = (new BatchBuilder())
      ->setTitle(t('Importing Users'))
      ->setFinishCallback('\Drupal\file_upload\Form\ImportUsers::finishImport')
      ->setInitMessage(t('Starting import of Users'));
    $batch_builder
      ->addOperation('\Drupal\file_upload\Form\ImportUsers::import', [
        $file
      ]);

    batch_set($batch_builder
      ->toArray());
  }

}

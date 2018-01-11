<?php

/**
 * @file
 * Contains \Drupal\import_json\Form\BaseUrlForm.
 */

namespace Drupal\import_json\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

/**
 * Class BaseUrlForm.
 */
class BaseUrlForm extends ConfigFormBase {

  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($config_factory);
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'import_json.baseurl',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'base_url_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('import_json.baseurl');
    $form['baseurl'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('baseurl'),
      '#description' => $this->t(''),
      '#default_value' => $config->get('baseurl'),
    );
    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);

    $this->config('import_json.baseurl')
      ->set('baseurl', $form_state->getValue('baseurl'))
      ->save();

    $baseurl = $this->config('import_json.baseurl')->get('baseurl');

    try {
      $response = \Drupal::httpClient()->get($baseurl, array(
        'headers' => array(
          'Accept' => 'text/plain'
        )
      ));
      $data = $response->getBody();

      if (empty($data)) {
        drupal_set_message('Empty response.');
      }
      else {
        $this->createNews($data);
      }
    }
    catch (RequestException $e) {
      watchdog_exception('import_json', $e);
    }
  }

  /**
 * Create nodes from JSON feed.
 */
protected function createNews(string $json) {
  $jsonout = json_decode($json, TRUE);

  foreach ($jsonout as $news) {
    $node = Node::create(array(
      'type' => 'fantasy_news',
      'langcode' => 'en',
      'uid' => '1',
      'status' => 1,
      'title' => $news['title'],
      'field_image_reference' => $news['image'],
      'field_league' => $news['league'],
      'field_link' => $news['link'],
      'field_date' => $news['date'],
      'field_news_source' => t('Fantasy Labs'),
    ));

    $node->save();
  }
}

}

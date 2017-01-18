<?php

/**
 * @file
 * Contains \Drupal\cors\Form\CorsAdminForm.
 */

namespace Drupal\cors\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for CORS settings.
 */
class CorsAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(){
    return 'cors_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cors.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cors_domains = '';
    $domains = $this->config('cors.settings')->get('domains');
    foreach ($domains as $path => $domain) {
      $cors_domains .= $path . '|' . $domain . "\n";
    }

    $form['cors_domains'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Domains'),
      '#description' => $this->t('A list of paths and corresponding domains to enable for CORS. Multiple entries should be separated by a comma. Enter one value per line separated by a pipe, in this order:
        <ul>
         <li>Internal path</li>
         <li>Access-Control-Allow-Origin. Use &lt;mirror&gt; to echo back the Origin header.</li>
         <li>Access-Control-Allow-Methods</li>
         <li>Access-Control-Allow-Headers</li>
         <li>Access-Control-Allow-Credentials</li>
        </ul>
        Examples:
        <ul>
          <li>*|http://example.com</li>
          <li>api|http://example.com:8080 http://example.com</li>
          <li>api/*|&lt;mirror&gt;,https://example.com</li>
          <li>api/*|&lt;mirror&gt;|POST|Content-Type,Authorization|true</li>
          <li>http://example.com|POST,GET|Content-type,Authorization|true</li>
        </ul>'),
      '#default_value' => $cors_domains,
      '#rows' => 10,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $domains = explode("\n",  $form_state->getValue('cors_domains'), 2);
    $settings = array();
    foreach ($domains as $domain) {
      $domain = explode("|", $domain, 2);
      if (count($domain) === 2) {
        $settings[$domain[0]] = (isset($settings[$domain[0]])) ? $settings[$domain[0]] . ' ' : '';
        $settings[$domain[0]] .= trim($domain[1]);
      }
    }
    $this->config('cors.settings')->set('domains', $settings)->save();
    parent::submitForm($form, $form_state);
  }

}

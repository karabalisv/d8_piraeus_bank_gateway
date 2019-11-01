<?php

namespace Drupal\commerce_piraeus_gateway\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\dblog\Plugin\views\wizard\Watchdog;
use \SoapClient;

/**
 * Provides the piraeus WInbank offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "piraeus_redirect_checkout",
 *   label = @Translation("piraeus Gateway (Redirect to piraeus)"),
 *   display_label = @Translation("piraeus"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_piraeus_gateway\PluginForm\RedirectCheckoutForm",
 *   },
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase
{
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_id'       => '',
        'currencycode'      =>  '978',
        'username'          =>  '',
        'password'          =>  '',
        'posid'             => '',
        'acquirerid'        => '14',
        'order_prefix'      =>  '0000',
        'RequestType'       =>  '00',
        'ExpirePreauth'     =>  '0',
        'parameters'        => 'POST',
        'Installments'      =>  '0',
        'Bnpl'              =>  '0',
        'language'          => 'el-GR',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#description' => $this->t('This is the Merchant ID from the piraeus manager.'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

      $form['username'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Username'),
          '#description' => $this->t('This is the Username from the piraeus manager.'),
          '#default_value' => $this->configuration['username'],
          '#required' => TRUE,
      ];

      $form['password'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Password'),
          '#description' => $this->t('This is the Password from the piraeus manager.'),
          '#default_value' => $this->configuration['password'],
          '#required' => TRUE,
      ];


    $form['posid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PosId'),
      '#description' => $this->t('This is the PosId from the piraeus manager.'),
      '#default_value' => $this->configuration['posid'],
      '#required' => TRUE,
    ];


      $form['acquirerid'] = [
          '#type' => 'textfield',
          '#title' => $this->t('AcquirerId'),
          '#description' => $this->t('This is the AcquirerId from the piraeus manager.'),
          '#default_value' => $this->configuration['acquirerid'],
          '#required' => TRUE,
      ];


    $form['parameters'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parameters'),
      '#description' => $this->t('The Parameters for the Request POST or GET Method Allowd'),
      '#default_value' => $this->configuration['parameters'],
      '#required' => TRUE,
    ];


    $languages = $this->getLanguages() ;
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#description' => $this->t('The language for the credit card form.'),
      '#options' => $languages,
      '#default_value' => $this->configuration['language'],
    ];

      $currencys = $this->getCurrency() ;
      $form['currencycode'] = [
          '#type' => 'select',
          '#title' => $this->t('Currency'),
          '#description' => $this->t('The Currency for the credit card form.'),
          '#options' => $currencys,
          '#default_value' => $this->configuration['currencycode'],
      ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['username'] = $values['username'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['posid'] = $values['posid'];
      $this->configuration['acquirerid'] = $values['acquirerid'];
      $this->configuration['parameters'] = $values['parameters'];
      $this->configuration['language'] = $values['language'];
      $this->configuration['currencycode'] = $values['currencycode'];
    }
  }

  /**
   * Returns an array of languages supported by piraeus Gateway.
   *
   * @return array
   *   Array with key being language codes, and value being names.
   */
  protected function getLanguages()
  {
    return [

      'el-GR' => $this->t('Greece'),
      'en-US' => $this->t('English'),
      'ru-RU' => $this->t('Russian'),
      'de-DE' => $this->t('German'),

    ];
  }

    /**
     * Returns an array of Currency supported by piraeus Gateway.
     *
     * @return array
     *   Array with key being currency codes, and value being names.
     */
    protected function getCurrency()
    {
        return [

            '978' => $this->t('Euro'),
            '840' => $this->t('Dolar'),
        ];
    }

}

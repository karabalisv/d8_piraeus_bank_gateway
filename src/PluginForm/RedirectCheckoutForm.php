<?php

namespace Drupal\commerce_piraeus_gateway\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_piraeus_gateway\CurrencyCalculator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\payment\Response\Response;
use \SoapClient;

use Symfony\Component\DependencyInjection\ContainerInterface;

class RedirectCheckoutForm extends PaymentOffsiteForm implements ContainerInjectionInterface {
  /**
   * @var CurrencyCalculator
   */
  protected $currency_calculator;

  function __construct(CurrencyCalculator $currency_calculator) {
    $this->currency_calculator = $currency_calculator;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_piraeus_gateway.currency_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();

    /** get the ticket * */
    $this->GetTicket();
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order_id         = $payment->getOrderId();


    $data['AcquirerId']         = (int)$configuration['acquirerid'];
    $data['MerchantId']         = (int)$configuration['merchant_id'];
    $data['PosId']              = (int)$configuration['posid'];
    $data['User']               = $configuration['username'];
    $data['LanguageCode']       = $configuration['language'];
    $data['MerchantReference']  = $order_id;
    $data['ParamBackLink']      = 'p1=/checkout/'.$order_id.'/review/&p2=/checkout/'.$order_id.'/review/';



    return $this->buildRedirectForm(
      $form,
      $form_state,
      'https://paycenter.piraeusbank.gr/redirection/pay.aspx',
      $data,
      PaymentOffsiteForm::REDIRECT_POST
    );

  }

  /**
   * Build the order id taking order prefix into account.
   *
   * @return string
   */
  private function createOrderId() {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $configuration = $this->getConfiguration();
    $order_id = $payment->getOrderId();

    // Ensure that Order number is at least 4 characters otherwise QuickPay will reject the request.
    if (strlen($order_id) < 4) {
      $order_id = substr('000' . $order_id, -4);
    }

    if ($configuration['order_prefix']) {
      $order_id = $configuration['order_prefix'] . $order_id;
    }

    return $order_id;
  }

  /**
   * Get available payment methods.
   *
   * @return string
   */
  private function getPaymentMethods() {
    $configuration = $this->getConfiguration();

    if ($configuration['payment_method'] !== 'selected') {
      return $configuration['payment_method'];
    }

    // Filter out all cards not selected.
    $cards = array_filter($configuration['accepted_cards'], function ($is_selected) {
      return $is_selected;
    }, ARRAY_FILTER_USE_BOTH);

    return implode(',', $cards);
  }

  /**
   * Calculate the md5checksum for the request.
   * not used
   *
   * @inheritdoc
   */
  private function getChecksum(array $data) {
    $configuration = $this->getConfiguration();
    ksort($data);
    $base = implode(' ', $data);
    return hash_hmac('sha256', $base, $configuration['password']);
  }


  /**
  *  Soap Requst to take ticket Response
  *
  * @inheritdoc
  */
  private function GetTicket()
  {

      try{


          /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
          $payment = $this->entity;

          $order_id         = $payment->getOrderId();
          $configuration    = $this->getConfiguration();

          $soap = new SoapClient("https://paycenter.piraeusbank.gr/services/tickets/issuer.asmx?WSDL");

          $ticketRequest = array(

              'Username'                =>  $configuration['username'],
              'Password'                =>  md5($configuration['password']),
              'MerchantId'              =>  (int)$configuration['merchant_id'],
              'PosId'                   =>  (int)$configuration['posid'],
              'AcquirerId'              =>  (int)$configuration['acquirerid'],
              'MerchantReference'       =>  $order_id,
              'RequestType'             =>  '02',
              'ExpirePreauth'           =>  '0',
              'Amount'                  =>  (float)$payment->getAmount()->getNumber(),
              'CurrencyCode'            =>  (int)$configuration['currencycode'],
              'Installments'            =>  '0',
              'Bnpl'                    =>  '0',
              'Parameters'              =>  $this->createOrderId(),
          );

          $xml = array(
              'Request' => $ticketRequest
          );
          $oResult = $soap->IssueNewTicket($xml);


      }catch (\SoapFault $fault)
      {

          trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);

      }


      $result = array(
          'order_id' => $order_id,
          'result_code' => $oResult->IssueNewTicketResult->ResultCode,
          'result_description' => $oResult->IssueNewTicketResult->ResultDescription,
          'trans_ticket' => $oResult->IssueNewTicketResult->TranTicket,
          'timestamp' => $oResult->IssueNewTicketResult->Timestamp,
          'redirect_url'  =>  '/checkout/'.$order_id.'/review/',
          'minutes_to_expiration' => $oResult->IssueNewTicketResult->MinutesToExpiration,
      );
      $_SESSION['piraeus_gateway']['ticket_response'] = $result;

      return $oResult;
  }

  /**
   * @return array
   */
  private function getConfiguration() {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_piraeus_gateway\Plugin\Commerce\PaymentGateway\RedirectCheckout $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    return $payment_gateway_plugin->getConfiguration();
  }
}

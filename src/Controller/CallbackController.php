<?php

namespace Drupal\commerce_piraeus_gateway\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\Core\Url;

/**
 * Endpoints for the routes defined.
 */
class CallbackController extends ControllerBase {
  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;



  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {


    return new static($container->get('entity_type.manager'));

  }

  /**
   * Callback action.
   *
   * Listen for callbacks from piraeus and creates any payment specified.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function callback(Request $request) {

    try {


      $response_data = $this->getSuccessTransaction($request);
      $content = json_decode($response_data);

      $order = Order::load($content->MerchantReference);
      $payment_gateway = $order->get('payment_gateway')->first()->entity;

      $entityStorage = $this->entityTypeManager->getStorage('commerce_payment');

      $payment = $entityStorage->create([
        'state' => $this->change_statement($content->StatusFlag),
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $payment_gateway->id(),
        'order_id' => $order->id(),
        'remote_id' => $content->TransactionId,
        'remote_state' =>  $content->ResponseDescription,
      ]);
      $payment->save();


    } catch (PaymentGatewayException $exception) {

      drupal_set_message($exception,'error');
      throw new NeedsRedirectException(Url::fromRoute('commerce_checkout.form', 302,[
        'commerce_order' => $order->id(),
        'step' => 'review',
      ])->toString());

    }


    throw new NeedsRedirectException(Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $order->id(),
      'step' => 'complete',
    ])->toString());

  }

  /**
   * NOT USED
   * Get the state from the transaction.
   *
   * @param object $content
   *   The request data from Winbak Paycenter.
   *
   * @return string
   */
  private function getRemoteState($content) {
    $latest_operation = end($content->operations);
    return $latest_operation->qp_status_msg;
  }


  /**
   * Get data from the transaction.
   *
   * @param object request
   *   The request data from Winbak Paycenter.
   *
   * @return JSON_OBJECT_AS_ARRAY
   */
  private function getSuccessTransaction($request)
  {
    $response = array(

      'SupportReferenceID'   =>   $request->get('SupportReferenceID'),
      'ResultCode'           =>   $request->get('ResultCode'),
      'ResultDescription'    =>   $request->get('ResultDescription'),
      'StatusFlag'           =>   $request->get('StatusFlag'),
      'ResponseCode'         =>   $request->get('ResponseCode'),
      'ResponseDescription'  =>   $request->get('ResponseDescription'),
      'LanguageCode'         =>   $request->get('LanguageCode'),
      'MerchantReference'    =>   $request->get('MerchantReference'),
      'TransactionDateTime'  =>   $request->get('TransactionDateTime'),
      'TransactionId'        =>   $request->get('TransactionId'),
      'CardType'             =>   $request->get('CardType'),
      'PackageNo'            =>   $request->get('PackageNo'),
      'ApprovalCode'         =>   $request->get('ApprovalCode'),
      'RetrievalRef'         =>   $request->get('RetrievalRef'),
      'AuthStatus'           =>   $request->get('AuthStatus'),
      'Parameters'           =>   $request->get('Parameters'),
      'HashKey'              =>   $request->get('HashKey'),
      'PaymentMethod'        =>   $request->get('PaymentMethod'),
    );

    return json_encode($response,JSON_OBJECT_AS_ARRAY);

  }


  private function change_statement($statusflag)
  {
    if($statusflag == "Success") { return "completed"; }else{ return "pending"; }
  }
}

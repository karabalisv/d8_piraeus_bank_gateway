<?php

namespace Drupal\commerce_piraeus_gateway\Access;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Psr\Log\LoggerInterface;

/**
 * Checks access for the payment callback from piraeus.
 */
class CallbackAccess implements AccessInterface {
  /**
   * Logger interface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * QuickpayIntegrationCallbackAccessCheck constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger interface.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Access callback to check that the callback hasn't been tampered with.
   *
   * @param Request $request
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Request $request) {

    /**
     * if Input Get state=“success or state=success
     */
    if($request->get('state') == "“success" or $request->get('state') == "success")
    {
      $content = $this->getSuccessTransaction($request);
      return AccessResult::allowed();
    }

    if($request->get('state') == "“failed" or $request->get('state') == "failed")
    {
      $url = $_SESSION['piraeus_gateway']['ticket_response']['redirect_url'];
      if($url)
      {
        $message =t("Couldn't load payment information from Winbak");
        drupal_set_message($message,'error');
        $response = new RedirectResponse($url);
        $response->send();
        exit;

      }else{


        $this->logger->error("Couldn't load payment information from Winbak");
        return AccessResult::forbidden();

      }

    }
  }



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

    $data = json_encode($response,JSON_OBJECT_AS_ARRAY);
    return $data;

  }

}

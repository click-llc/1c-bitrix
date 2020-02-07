<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Request;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Main\Web\Json;
use Bitrix\Sale\PaySystem\Manager;
use Bitrix\Sale\PaySystem\ServiceHandler;
use Bitrix\Sale\PaySystem\ServiceResult;
use Bitrix\Sale\Registry;
use CEventLog;
use CSaleOrder;

Loc::loadMessages( __FILE__ );

/**
 * Handler for QIWI payment gateway.
 *
 * @package Sale\Handlers\PaySystem
 */
class Click_UzbekistanHandler extends ServiceHandler {
    private $table = 'click_transactions';

    private $debug = false;
    /**
     * Needs for pay systems with test modes. Qiwi has not this mode.
     *
     * @param Payment $payment
     *
     * @return bool
     */
    protected function isTestMode( Payment $payment = null ) {
        return false;
    }


    /**
     * Initiate pay handler.
     *
     * @param Payment $payment
     * @param Request|null $request
     *
     * @return ServiceResult     
     * @throws \Exception
     */
    public function initiatePay( Payment $payment, Request $request = null ) {
        global $APPLICATION;
        $result = new ServiceResult();
        if ( ! $payment->isPaid() ) {
            /** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
            $paymentCollection = $payment->getCollection();
            if ( $paymentCollection ) {
                /** @var \Bitrix\Sale\Order $order */
                $order = $paymentCollection->getOrder();
                
            }
        }
        if ( $result->isSuccess() ) {
            return $this->showTemplate( $payment, 'template' );
        } else {
            return $result;
        }
    }

    /**
     * Identifies paysystem by GET parameter.
     *
     * @return array
     */
    public static function getIndicativeFields() {
        return [ 'click_uz' ];
    }

    /**
     * Gets order id by URL GET parameter - 'merchant_trans_id'
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getPaymentIdFromRequest( Request $request ) {
        $pid = $request->get( 'merchant_trans_id' );
        if ( $pid ) {
            $params = array(
                'select' => array('ID', 'ORDER_ID')
            );

            if (intval($pid).'|' == $pid.'|')
            {
                $params['filter']['ORDER_ID'] = $pid;
            }
            else
            {
                $params['filter']['ACCOUNT_NUMBER'] = $pid;
            }

            $registry = Registry::getInstance('ORDER');

            /** @var Payment $paymentClassName */
            $paymentClassName = $registry->getPaymentClassName();
            $result = $paymentClassName::getList($params);
            $data = $result->fetch() ?: array();

            $orderId = $data['ID'];
            $paymentId = $data['ORDER_ID'];

            if ( ! $orderId ) {

                $this->log('ERROR', $data);

                $this->sendJsonResponse( [
                    'error'      => '-5',
                    'error_note' => Loc::getMessage( 'ERROR_USER_DOES_NOT_EXISTS' )
                ] );
            }

            return $paymentId;
        } else {
            $this->sendJsonResponse( [
                'error'      => '-8',
                'error_note' => Loc::getMessage( 'ERROR_IN_REQUEST_FROM_CLICK' )
            ] );
        }

        return false;
    }

    public function getTransaction( $order_id ) {
        global $DB;

        $res = $DB->Query( "Select ID From click_transactions Where merchant_trans_id = " . $order_id );

        return $res->Fetch();
    }

    /**
     * Process request after payment.
     *
     * @param Payment $payment
     * @param Request $request
     *
     * @return ServiceResult
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\ObjectException
     * @throws \ErrorException
     */
    public function processRequest( Payment $payment, Request $request ) {
        $result = new ServiceResult();
        $action = $request->get( 'click_uz' );

        try {
            switch ( $action ) {
                case 'prepare':
                    $result = $this->processPrepareAction( $payment, $request );
                    break;
                case 'complete':
                    $result = $this->processCompleteAction( $payment, $request );
                    break;
            }
        } catch ( \Exception $ex ) {
            $this->log('ERROR', array($ex->getMessage()) );
            $result->addError( new Error( $ex->getMessage(), $ex->getCode()) );
        }

        return $result;
    }

    /**
     * @param Payment $payment
     * @param Request $request
     *
     * @throws \Exception
     */
    public function processPrepareAction( Payment $payment, Request $request ) {

        $params = array_merge( $this->getParamsBusValue( $payment ), $this->getExtraParams() );

        $result = new ServiceResult();

        if ( ! isset(
            $request['click_trans_id'],
            $request['service_id'],
            $request['merchant_trans_id'],
            $request['amount'],
            $request['action'],
            $request['sign_time'] ) ) {

            throw new \Exception(Loc::getMessage( 'ERROR_IN_REQUEST_FROM_CLICK' ), -8);
        }


        $signString = $request['click_trans_id'] .
                      $request['service_id'] .
                      $params['CLICK_UZ_SECRET_KEY'] .
                      $request['merchant_trans_id'] .
                      $request['amount'] .
                      $request['action'] .
                      $request['sign_time'];

        $signString = md5( $signString );


        if ( $signString !== $_POST['sign_string'] ) {
            throw new \Exception(Loc::getMessage( 'ERROR_SIGN_CHECK' ), -1);
        }

        if ( $payment->isPaid() ) {
            throw new \Exception(Loc::getMessage( 'ERROR_ALREADY_PAID' ), -4);
        }

        if ( abs( $payment->getSum() - (float) $request['amount'] ) > 0.01 ) {
            throw new \Exception(Loc::getMessage( 'ERROR_INCORRECT_AMOUNT' ), -2);
        }

        try {
            global $DB;

            $transaction = $this->getTransaction( $payment->getOrderId() );

            if ( ! $transaction || ! isset( $transaction['ID'] ) ) {
                $prepare_id = $DB->Insert( $this->table, array(
                    'click_trans_id'    => $request['click_trans_id'],
                    'service_id'        => $request['service_id'],
                    'click_paydoc_id'   => $request['click_paydoc_id'],
                    'merchant_trans_id' => "'" . $request['merchant_trans_id'] . "'",
                    'amount'            => $request['amount'],
                    'error'             => $request['error'],
                    'error_note'        => "'" . $request['error_note'] . "'",
                    'action'            => 0
                ) );

            } else {
                $prepare_id = $transaction['ID'];
                $DB->Update( $this->table, array(
                    'click_trans_id'    => $request['click_trans_id'],
                    'service_id'        => $request['service_id'],
                    'click_paydoc_id'   => $request['click_paydoc_id'],
                    'merchant_trans_id' => "'" . $request['merchant_trans_id'] . "'",
                    'amount'            => $request['amount'],
                    'error'             => $request['error'],
                    'error_note'        => "'" . $request['error_note'] . "'",
                    'action'            => 0
                ), 'WHERE ID = ' . $transaction['ID'] );
            }
            $result->setPsData(['PAID' => 'N', ]);
            $result->setData(array(
                'click_trans_id'      => $_POST['click_trans_id'],
                'merchant_trans_id'   => $_POST['merchant_trans_id'],
                'merchant_prepare_id' => $prepare_id,
                'error'               => '0',
                'error_note'          => Loc::getMessage( 'Success' )
            ));
        } catch ( \Exception $ex ) {
            throw new \Exception(Loc::getMessage( 'ERROR_UPDATE_FAILED' ), -7);
        }

        return $result;
    }

    /**
     * @param Payment $payment
     *
     * @return ServiceResult
     * @throws \Exception
     */
    public function processCompleteAction( Payment $payment, Request $request ) {

        $params = array_merge( $this->getParamsBusValue( $payment ), $this->getExtraParams() );

        $result = new ServiceResult();

        if ( ! isset(
            $request['click_trans_id'],
            $request['service_id'],
            $request['merchant_trans_id'],
            $request['merchant_prepare_id'],
            $request['amount'],
            $request['action'],
            $request['sign_time'] )  ) {

            throw new \Exception(Loc::getMessage( 'ERROR_IN_REQUEST_FROM_CLICK' ), -8);
        }

        $signString = $request['click_trans_id'] .
                      $request['service_id'] .
                      $params['CLICK_UZ_SECRET_KEY'] .
                      $request['merchant_trans_id'] .
                      $request['merchant_prepare_id'] .
                      $request['amount'] .
                      $request['action'] .
                      $request['sign_time'];

        $signString = md5( $signString );


        if ( $signString !== $_POST['sign_string'] ) {
            throw new \Exception(Loc::getMessage( 'ERROR_SIGN_CHECK' ), -1);
        }

        if ( $payment->isPaid() ) {
            throw new \Exception(Loc::getMessage( 'ERROR_ALREADY_PAID' ), -4);
        }

        if ( abs( $payment->getSum() - (float) $request['amount'] ) > 0.01 ) {
            throw new \Exception(Loc::getMessage( 'ERROR_INCORRECT_AMOUNT' ), -2);
        }
        $transaction = $this->getTransaction( $payment->getOrderId() );

        if ( ! $transaction || $transaction['ID'] != $request->get('merchant_prepare_id') ) {
            throw new \Exception(Loc::getMessage( 'ERROR_TRANSACTION_DOES_NOT_EXIST' ), -6);
        }

        try {
            global $DB;

            $DB->Update( $this->table, array(
                'click_trans_id'    => $request['click_trans_id'],
                'service_id'        => $request['service_id'],
                'click_paydoc_id'   => $request['click_paydoc_id'],
                'merchant_trans_id' => "'" . $request['merchant_trans_id'] . "'",
                'amount'            => $request['amount'],
                'error'             => $request['error'],
                'error_note'        => "'" . $request['error_note'] . "'",
                'action'            => 0
            ), 'WHERE ID = ' . $transaction['ID'] );

            if( $request->get('error') != 0 ) {
                $result->setOperationType(ServiceResult::MONEY_LEAVING);
                $result->setPsData(['PAID' => 'N', 'PS_STATUS_CODE' => 'N']);
            } else {
                $result->setOperationType(ServiceResult::MONEY_COMING);
                $result->setPsData(['PAID' => 'Y', 'PS_STATUS_CODE' => 'P']);
                CSaleOrder::PayOrder   ($payment->getOrderId(), 'Y');
                CSaleOrder::StatusOrder($payment->getOrderId(), "P");
            }


            $result->setData(array(
                'click_trans_id'      => $_POST['click_trans_id'],
                'merchant_trans_id'   => $_POST['merchant_trans_id'],
                'merchant_confirm_id' => $transaction['ID'],
                'error'               => '0',
                'error_note'          => Loc::getMessage( 'Success' )
            ));

        } catch ( \Exception $ex ) {
            throw new \Exception(Loc::getMessage( 'ERROR_UPDATE_FAILED' ), -7);
        }

        return $result;
    }

    /**
     * Sets header and prints json encoded data, then dies.
     *
     * @param array $data
     * @param int $code
     *
     */
    public function sendJsonResponse( $data = [], $code = 200 ) {
        http_response_code( $code );
        header( 'Content-Type: application/json' );
        header( 'Pragma: no-cache' );
        die( Json::encode( $data ) );
    }

    /**
     * Final function that sends response or redirects user to payment page.
     *
     * @param ServiceResult $result
     * @param Request $request
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    public function sendResponse( ServiceResult $result, Request $request ) {
        if( $result->isSuccess() ) {
            $this->sendJsonResponse( $result->getData() );
        } else {
            $error = $result->getErrors()[0];

            $this->sendJsonResponse( [
                'error' => $error->getCode(),
                'error_note' => $error->getMessage()
            ] );
        }

    }

    /**
     * Log event.
     *
     * @param string $type
     * @param array $desc
     */
    protected function log( $type, array $desc ) {
        if ( $this->debug ) {
            CEventLog::Add( [
                'SEVERITY'      => 'DEBUG',
                'AUDIT_TYPE_ID' => 'PAYMENT_CLICK_UZ_' . $type,
                'MODULE_ID'     => 'click.uzbekistan',
                'ITEM_ID'       => 1,
                'DESCRIPTION'   => $desc,
            ] );
        }
    }


    /**
     * Bitrix's abstract method, we don't use.
     *
     * @return void
     */

    public function getCurrencyList() {
        return;
    }

    /**
     * Bitrix's abstract method, we don't use.
     *
     * @return void
     */
    public function cancel( Payment $payment ) {
        // TODO: Implement cancel() method.
    }

    /**
     * Bitrix's abstract method, we don't use.
     *
     * @return void
     */
    public function confirm( Payment $payment ) {
        // TODO: Implement confirm() method.
    }

    /**
     * Bitrix's abstract method, we don't use.
     *
     * @return void
     */
    public function refund( Payment $payment, $refundableSum ) {
        // TODO: Implement refund() method.
    }

}

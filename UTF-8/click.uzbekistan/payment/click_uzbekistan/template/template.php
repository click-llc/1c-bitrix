<?php

use Bitrix\Main\Localization\Loc;

/** @var \Bitrix\Sale\Payment $payment */
/** @var array $params */

Loc::loadMessages( __FILE__ );

$popup = $params['CLICK_UZ_USE_POPUP'] == 'Y';

$button_title      =  Loc::getMessage( 'SALE_HANDLERS_CLICK_UZ_BUTTON_PAID' );
$merchantID        = $params['CLICK_UZ_MERCHANT_ID'];
$merchantUserID    = $params['CLICK_UZ_MERCHANT_USER_ID'];
$merchantServiceID = $params['CLICK_UZ_SERVICE_ID'];
$transID           = $payment->getOrderId();
$transAmount       = $payment->getSum();
$returnURL         = '//' . $_SERVER['SERVER_NAME'] . '/personal/order/';


?>
<div class="sale-click_uzbekistan-wrapper">
    <span class="tablebodytext">
        <?= Loc::getMessage( 'SALE_HANDLERS_CLICK_UZ_DESCRIPTION' ) ?>
        <?= SaleFormatCurrency( $payment->getField( 'SUM' ), $payment->getField( 'CURRENCY' ) ) ?>
    </span>

    <div>
        <?php if ( $popup ): ?>
            <button id="click-pay-button"><i></i><?php echo $button_title; ?></button>

            <script src="//my.click.uz/pay/checkout.js"></script>
            <script>
                window.onload = function () {
                    var linkEl = document.querySelector("#click-pay-button");
                    linkEl.addEventListener("click", function () {
                        createPaymentRequest({
                            merchant_id: <?php echo $merchantID; ?>,
                            merchant_user_id: "<?php echo $merchantUserID; ?>",
                            service_id: <?php echo $merchantServiceID; ?>,
                            transaction_param: "<?php echo $transID; ?>",
                            amount: <?php echo $transAmount; ?>
                        }, function (data) {
                            if (data && (data.status === 2 || data.status === 0)) {

                                window.location.href = '<?php echo $returnURL; ?>';
                            }
                        });
                    });
                };
            </script>
        <?php else: ?>
            <form action="https://my.click.uz/services/pay" id="click-pay-form" method="get">
                <input type="hidden" name="amount" value="<?php echo $transAmount; ?>"/>
                <input type="hidden" name="merchant_id" value="<?php echo $merchantID; ?>"/>
                <input type="hidden" name="merchant_user_id" value="<?php echo $merchantUserID; ?>"/>
                <input type="hidden" name="service_id" value="<?php echo $merchantServiceID; ?>"/>
                <input type="hidden" name="transaction_param" value="<?php echo $transID; ?>"/>
                <input type="hidden" name="return_url" value="<?php echo $returnURL; ?>"/>

                <button id="click-pay-button"><i></i><?php echo $button_title; ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<style type="text/css">
    #click-pay-button {
        width: auto;
        border: 0;
        border-radius: 4px;
        background: #00a6ff;
        margin: 10px 0 0;
        padding: 0 15px;
        height: 49px;
        font: 17px/49px Microsoft Sans Serif, Arial, Helvetica, sans-serif;
        color: #fff;
        cursor: pointer;
    }

    #click-pay-button i {
        background: url(//m.click.uz/static/img/logo.png) no-repeat center left;
        width: 30px;
        height: 49px;
        float: left;
    }
</style>

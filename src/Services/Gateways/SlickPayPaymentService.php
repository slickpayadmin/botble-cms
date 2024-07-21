<?php

namespace Botble\Slickpay\Services\Gateways;

use Exception;

use Illuminate\Support\Arr;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Supports\PaymentHelper;
use Botble\Slickpay\Gateways\Transfer;
use Botble\Slickpay\Services\Abstracts\SlickPayPaymentAbstract;

class SlickPayPaymentService extends SlickPayPaymentAbstract
{
    /**
     * Make a payment
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function makePayment(array $data)
    {
        $commission = null;
        $amount = $amount_raw = round((float)$data['amount'], $this->isSupportedDecimals() ? 2 : 0);

        $result = Transfer::calculateCommission($amount);

        if (!empty($result['response']['amount'])) {
            $commission = $result['response']['amount'] - $amount;
            $amount = $result['response']['amount'];
        }

        $currency = $data['currency'];
        $currency = strtoupper($currency);

        $order_id = reset($data['order_id']);

        session([
            'slickpay_amount'        => $amount,
            'slickpay_amount_raw'    => $amount_raw,
            'slickpay_order_id'      => $order_id,
            'slickpay_currency'      => $currency,
            'slickpay_commission'    => $commission,
            'slickpay_customer_id'   => Arr::get($data, 'customer_id'),
            'slickpay_customer_type' => Arr::get($data, 'customer_type'),
        ]);

        $queryParams = [
            'type'          => SLICKPAY_PAYMENT_METHOD_NAME,
            'order_id'      => $order_id,
            // 'amount'        => $amount,
            // 'currency'      => $currency,
            // 'customer_id'   => Arr::get($data, 'customer_id'),
            // 'customer_type' => Arr::get($data, 'customer_type'),
        ];

        return $this
            ->setReturnUrl($data['callback_url'] . '?' . http_build_query($queryParams))
            ->setCurrency($currency)
            ->setOrderId($order_id)
            ->setCustomer(Arr::get($data, 'address.email'))
            ->setItem([
                'name'     => $data['description'],
                'quantity' => 1,
                'price'    => $amount,
                'sku'      => null,
                'type'     => SLICKPAY_PAYMENT_METHOD_NAME,
            ])
            ->createPayment($data['description']);
    }

    /**
     * Use this function to perform more logic after user has made a payment
     *
     * @param array $data
     * @return mixed
     */
    public function afterMakePayment(array $data)
    {
        $status = PaymentStatusEnum::COMPLETED;

        $chargeId = session('slickpay_payment_id');

        if (!empty(session('slickpay_commission'))) {
            $data['commission'] = session('slickpay_commission');
        }

        // $orderIds = (array)Arr::get($data, 'order_id', []);

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount'          => floatval(str_replace(',', '', $data['amount'])),
            'currency'        => session('slickpay_currency'),
            'charge_id'       => $chargeId,
            'order_id'        => [session('slickpay_order_id')],
            'customer_id'     => session('slickpay_customer_id'),
            'customer_type'   => session('slickpay_customer_type'),
            'payment_channel' => SLICKPAY_PAYMENT_METHOD_NAME,
            'status'          => $status,
            'metadata'        => json_encode($data),
        ]);

        session()->forget(['slickpay_payment_id', 'slickpay_amount', 'slickpay_amount_raw', 'slickpay_customer_id', 'slickpay_customer_type', 'slickpay_order_id', 'slickpay_commission', 'slickpay_currency']);

        return $chargeId;
    }
}

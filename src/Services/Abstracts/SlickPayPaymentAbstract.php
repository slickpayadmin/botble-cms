<?php

namespace Botble\Slickpay\Services\Abstracts;

use Exception;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Slickpay\Gateways\Transfer;
use Illuminate\Support\Facades\Log;

abstract class SlickPayPaymentAbstract
{
    use PaymentErrorTrait;

    /**
     * @var array
     */
    protected $itemList;

    /**
     * @var string
     */
    protected $paymentCurrency;

    /**
     * @var int
     */
    protected $totalAmount;

    /**
     * @var float
     */
    protected $commission;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var integer
     */
    protected $orderId;

    /**
     * @var string
     */
    protected $returnUrl;

    /**
     * @var string
     */
    protected $cancelUrl;

    /**
     * @var string
     */
    protected $transactionDescription;

    /**
     * @var string
     */
    protected $customer;

    /**
     * @var bool
     */
    protected $supportRefundOnline;

    /**
     * SlickPayPaymentAbstract constructor.
     */
    public function __construct()
    {
        $this->paymentCurrency = config('plugins.payment.payment.currency');

        $this->totalAmount = 0;

        $this->supportRefundOnline = true;
    }

    /**
     * @return bool
     */
    public function getSupportRefundOnline()
    {
        return $this->supportRefundOnline;
    }

    /**
     * Set payment currency
     *
     * @param string $currency String name of currency
     * @return self
     */
    public function setCurrency($currency)
    {
        $this->paymentCurrency = $currency;

        return $this;
    }

    /**
     * Get current payment currency
     *
     * @return string Current payment currency
     */
    public function getCurrency()
    {
        return $this->paymentCurrency;
    }

    /**
     *
     * @return string
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param integer $orderId
     * @return self
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @param float $commission
     * @return self
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;

        return $this;
    }

    /**
     * @param float $amount
     * @return self
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param string $customer
     * @return self
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Add item to list
     *
     * @param array $itemData Array item data
     * @return self
     */
    public function setItem($itemData)
    {
        if (count($itemData) === count($itemData, COUNT_RECURSIVE)) {
            $itemData = [$itemData];
        }

        foreach ($itemData as $data) {
            $amount = $data['price'] * $data['quantity'];

            $item = [
                'name'        => $data['name'],
                'sku'         => $data['sku'],
                'unit_amount' => [
                    'currency_code' => $this->paymentCurrency,
                    'value'         => $amount,
                ],
                'quantity'    => $data['quantity'],
            ];

            if ($description = Arr::get($data, 'description')) {
                $item['description'] = $description;
            }

            if ($tax = Arr::get($data, 'tax')) {
                $item['tax'] = [
                    'currency_code' => $this->paymentCurrency,
                    'value'         => $tax,
                ];
            }

            if ($category = Arr::get($data, 'category')) {
                $item['category'] = $category;
            }

            $this->itemList[] = $item;
            $this->totalAmount += $amount;
        }

        // issue https://developer.slickpay.com/docs/api/orders/v2/#error-DECIMAL_PRECISION
        $this->totalAmount = round((float)$this->totalAmount, $this->isSupportedDecimals() ? 2 : 0);

        return $this;
    }

    /**
     * Get list item
     *
     * @return array
     */
    public function getItemList()
    {
        return $this->itemList;
    }

    /**
     * Get total amount
     *
     * @return mixed Total amount
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set return URL
     *
     * @param string $url Return URL for payment process complete
     * @return self
     */
    public function setReturnUrl($url)
    {
        $this->returnUrl = $url;

        return $this;
    }

    /**
     * Get return URL
     *
     * @return string Return URL
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * Set cancel URL
     *
     * @param string $url Cancel URL for payment
     * @return self
     */
    public function setCancelUrl($url)
    {
        $this->cancelUrl = $url;

        return $this;
    }

    /**
     * Get cancel URL of payment
     *
     * @return string Cancel URL
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    /**
     * Setting up the JSON request body for creating the Order. The Intent in the
     * request body should be set as "CAPTURE" for capture intent flow.
     */
    protected function buildRequestBody()
    {
        return [
            'intent'              => 'CAPTURE',
            'application_context' => [
                'return_url' => $this->returnUrl,
                'cancel_url' => $this->cancelUrl ?: $this->returnUrl,
                'brand_name' => theme_option('site_name'),
            ],
            'purchase_units'      => [
                0 => [
                    'description' => $this->transactionDescription,
                    'custom_id'   => $this->customer,
                    'amount'      => [
                        'currency_code' => $this->paymentCurrency,
                        'value'         => (string)$this->totalAmount,
                    ],
                ],
            ],
        ];
    }


    /**
     * Create payment
     *
     * @param string $transactionDescription Description for transaction
     * @return mixed SlickPay checkout URL or false
     * @throws Exception
     */
    public function createPayment($transactionDescription)
    {
        $this->transactionDescription = $transactionDescription;

        // $queryParams = $this->buildRequestBody();

        $result = Transfer::createPayment([
            'url'     => $this->returnUrl,
            // 'type'          => setting('payment_slickpay_receiver_type', 'internal'),
            'account' => setting('payment_slickpay_receiver_uuid', null),
            'amount'        => session('slickpay_amount_raw') // reset($queryParams['purchase_units'])['amount']['value']
        ]);


        if (!empty($result['response']['redirectUrl'])) {

            if (!empty($result['response']['transferId']))
                session(['slickpay_payment_id' => $result['response']['transferId']]);

            return $result['response']['redirectUrl'];
        }

        session()->forget('slickpay_payment_id');

        return null;
    }

    /**
     * Get payment status
     *
     * @param Request $request
     * @return mixed Object payment details or false
     */
    public function getPaymentStatus(Request $request)
    {
        // Log::info($request->transfer_id);
        /*if (empty($request->get('order_id')) || empty($request->get('transfer_id'))) {
            return false;
        }*/

        $paymentId = session('slickpay_payment_id');
        // Log::info($paymentId);

        $result = Transfer::paymentStatus($paymentId);
        Log::info($result);
        // $orderRequest = new OrdersCaptureRequest($paymentId);
        // $orderRequest->prefer('return=representation');

        // $response = $this->client->execute($orderRequest);
        // if ($response && $response->statusCode == 201 && $response->result->status == 'COMPLETED') {
        //     return $response->result->status;
        // }


        if ($result['success'] == 1 && $result['status'] == 'completed') {
            return $result['response'];
        } else {
            $stat = isset($result['status']) ? $result['status'] : 200;
            Log::error('Failed to make a payment charge.');
            // $this->setErrorMessageAndLogging(implode(' / ', $result['messages']), 1);
        }

        return false;
    }

    /**
     * Get payment details
     *
     * @param string $paymentId SlickPay payment Id
     * @return mixed Object payment details
     */
    public function getPaymentDetails($paymentId)
    {
        try {
            $response = $this->client->execute(new OrdersGetRequest($paymentId));
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);
            return false;
        }

        return $response;
    }

    /**
     * Function to create a refund capture request. Payload can be updated to issue partial refund.
     */
    public function buildRefundRequestBody($totalAmount)
    {
        $totalAmount = round((float) $totalAmount, 2);

        return [
            'amount' => [
                'value'         => (string) $totalAmount,
                'currency_code' => $this->paymentCurrency,
            ],
        ];
    }

    /**
     * This function can be used to preform refund on the capture.
     */
    public function refundOrder($paymentId, $totalAmount)
    {
        try {
            $detail = $this->getPaymentDetails($paymentId);
            $captureId = null;
            if ($detail) {
                $purchase = Arr::get($detail->result->purchase_units, 0);
                $capture = Arr::get($purchase->payments->captures, 0);
                $captureId = $capture->id;
            }
            if ($captureId) {
                $refundRequest = new CapturesRefundRequest($captureId);
                $refundRequest->body = $this->buildRefundRequestBody($totalAmount);
                $refundRequest->prefer('return=representation');
                $response = $this->client->execute($refundRequest);

                if ($response && $response->statusCode == 201 && $response->result->status == 'COMPLETED') {
                    return [
                        'error'  => false,
                        'status' => $response->result->status,
                        'data'   => (array) $response->result,
                    ];
                }

                return [
                    'error'   => true,
                    'status'  => $response->statusCode,
                    'message' => trans('plugins/payment::payment.status_is_not_completed')
                ];
            }
            return [
                'error'   => true,
                'message' => trans('plugins/payment::payment.cannot_found_capture_id'),
            ];
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);
            return [
                'error'   => true,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Execute main service
     *
     * @param array $data
     * @return mixed
     */
    public function execute(array $data)
    {
        try {
            return $this->makePayment($data);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    /**
     * @return bool
     */
    public function isSupportedDecimals()
    {
        return !in_array($this->getCurrency(), [
            'BIF',
            'CLP',
            'DJF',
            'GNF',
            'JPY',
            'KMF',
            'KRW',
            'MGA',
            'PYG',
            'RWF',
            'VND',
            'VUV',
            'XAF',
            'XOF',
            'XPF'
        ]);
    }

    /**
     * List currencies supported https://developer.slickpay.com/docs/api/reference/currency-codes/
     * @return string[]
     */
    public function supportedCurrencyCodes(): array
    {
        return [
            'DZD',
        ];
    }

    /**
     * Make a payment
     *
     * @param array $data
     * @return mixed
     */
    abstract public function makePayment(array $data);

    /**
     * Use this function to perform more logic after user has made a payment
     *
     * @param array $data
     *
     * @return mixed
     */
    abstract public function afterMakePayment(array $data);
}

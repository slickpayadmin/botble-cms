<?php

namespace Botble\Slickpay\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Botble\Slickpay\Gateways\Transfer;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Slickpay\Http\Requests\SlickPayPaymentCallbackRequest;
use Botble\Slickpay\Services\Gateways\SlickPayPaymentService;
use Botble\Payment\Supports\PaymentHelper;
use Log;

class SlickpayController extends Controller
{
    /**
     * @param SlickPayPaymentCallbackRequest $request
     * @param SlickPayPaymentService $slickPayPaymentService
     * @param BaseHttpResponse $response
     * @return BaseHttpResponse
     */
    public function getCallback(
        Request                $request,
        SlickPayPaymentService $slickPayPaymentService,
        BaseHttpResponse       $response
    ) {
        $status = $slickPayPaymentService->getPaymentStatus($request);
            Log::info('slick pay controller getCallback');        
            Log::info($status);        
        if ($status === false) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->withInput()
                ->setMessage(__('Payment failed!'));
        }

        $slickPayPaymentService->afterMakePayment($status);

        return $response
            ->setNextUrl(PaymentHelper::getRedirectURL())
            ->setMessage(__('Checkout successfully!'));
    }

    public function commission(Request $request)
    {
        $amount = $request->input('amount');
        $commission = 0;
        $result = Transfer::calculateCommission($request->input('amount'));

        if (!empty($result['response']['amount'])) {
            $commission = $result['response']['amount'] - $request->input('amount');
            $amount = $result['response']['amount'];
        }

        return response()->json([
            'error'      => false,
            'amount_raw' => $amount,
            'amount'     => format_price($amount),
            'commission' => format_price($commission)
        ]);
    }
}

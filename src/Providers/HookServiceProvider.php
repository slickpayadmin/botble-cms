<?php

namespace Botble\Slickpay\Providers;

use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Slickpay\Services\Gateways\SlickPayPaymentService;
use Html;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Throwable;

class HookServiceProvider extends ServiceProvider
{
    public function boot()
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, [$this, 'registerSlickpayMethod'], 2, 2);

        $this->app->booted(function () {
            add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithSlickpay'], 2, 2);
        });

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, [$this, 'addPaymentSettings'], 2);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['SLICKPAY'] = SLICKPAY_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 2, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == SLICKPAY_PAYMENT_METHOD_NAME) {
                $value = 'Slickpay';
            }

            return $value;
        }, 2, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == SLICKPAY_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )
                    ->toHtml();
            }

            return $value;
        }, 2, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == SLICKPAY_PAYMENT_METHOD_NAME) {
                $data = SlickpayPaymentService::class;
            }

            return $data;
        }, 2, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == SLICKPAY_PAYMENT_METHOD_NAME) {
                $paymentDetail = (new SlickPayPaymentService())->getPaymentDetails($payment->charge_id);
                $data = view('plugins/slickpay::detail', ['payment' => $paymentDetail])->render();
            }

            return $data;
        }, 2, 2);
    }

    /**
     * @param string|null $settings
     * @return string
     * @throws Throwable
     */
    public function addPaymentSettings(?string $settings): string
    {
        return $settings . view('plugins/slickpay::settings')->render();
    }

    /**
     * @param string|null $html
     * @param array $data
     * @return string
     */
    public function registerSlickpayMethod(?string $html, array $data): string
    {
        return $html . view('plugins/slickpay::methods', $data)->render();
    }

    /**
     * @param array $data
     * @param Request $request
     * @return array
     * @throws BindingResolutionException
     */
    public function checkoutWithSlickpay(array $data, Request $request): array
    {
        if ($request->input('payment_method') == SLICKPAY_PAYMENT_METHOD_NAME) {

            $currentCurrency = get_application_currency();

            $currencyModel = $currentCurrency->replicate();

            $slickPayService = $this->app->make(SlickPayPaymentService::class);

            $supportedCurrencies = $slickPayService->supportedCurrencyCodes();

            $currency = strtoupper($currentCurrency->title);

            $notSupportCurrency = false;

            if (!in_array($currency, $supportedCurrencies)) {
                $notSupportCurrency = true;

                if (!$currencyModel->where('title', 'DZD')->exists()) {
                    $data['error'] = true;
                    $data['message'] = __(":name doesn't support :currency. List of currencies supported by :name: :currencies.", [
                        'name'       => 'SlickPay',
                        'currency'   => $currency,
                        'currencies' => implode(', ', $supportedCurrencies),
                    ]);

                    return $data;
                }
            }

            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

            if ($notSupportCurrency) {
                $usdCurrency = $currencyModel->where('title', 'DZD')->first();

                $paymentData['currency'] = 'DZD';

                if ($currentCurrency->is_default) {
                    $paymentData['amount'] = $paymentData['amount'] * $usdCurrency->exchange_rate;
                } else {
                    $paymentData['amount'] = format_price($paymentData['amount'], $currentCurrency, true);
                }
            }

            $paymentData['callback_url'] = route('payments.slickpay.status');

            $checkoutUrl = $slickPayService->execute($paymentData);

            if ($checkoutUrl) {
                $data['checkoutUrl'] = $checkoutUrl;
            } else {
                $data['error'] = true;
                $data['message'] = $slickPayService->getErrorMessage();
            }

            return $data;
        }

        return $data;
    }
}

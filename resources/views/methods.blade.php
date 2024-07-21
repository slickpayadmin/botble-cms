@if (setting('payment_slickpay_status') == 1)
    <li class="list-group-item">
        <input class="magic-radio js_payment_method" type="radio" name="payment_method" id="payment_slickpay"
               @if ((session('selected_payment_method') ?: setting('default_payment_method')) == SLICKPAY_PAYMENT_METHOD_NAME) checked @endif
               value="slickpay" data-bs-toggle="collapse" data-bs-target=".payment_slickpay_wrap" data-toggle="collapse" data-target=".payment_slickpay_wrap" data-parent=".list_payment_method">
        <label for="payment_slickpay" class="text-start">{{ setting('payment_slickpay_name', trans('plugins/payment::payment.payment_via_slickpay')) }}</label>
        <div class="payment_slickpay_wrap payment_collapse_wrap collapse @if ((session('selected_payment_method') ?: setting('default_payment_method')) == SLICKPAY_PAYMENT_METHOD_NAME) show @endif" style="padding: 15px 0;">
            <p>{!! BaseHelper::clean(setting('payment_slickpay_description')) !!}</p>

            @php $supportedCurrencies = (new \Botble\Slickpay\Services\Gateways\SlickPayPaymentService)->supportedCurrencyCodes(); @endphp
            @if (function_exists('get_application_currency') && !in_array(get_application_currency()->title, $supportedCurrencies) && !get_application_currency()->replicate()->where('title', 'USD')->exists())
                <div class="alert alert-warning" style="margin-top: 15px;">
                    {{ __(":name doesn't support :currency. List of currencies supported by :name: :currencies.", ['name' => 'SlickPay', 'currency' => get_application_currency()->title, 'currencies' => implode(', ', $supportedCurrencies)]) }}

                    <div style="margin-top: 10px;">
                        {{ __('Learn more') }}: <a href="https://developer.slickpay.com/docs/api/reference/currency-codes" target="_blank" rel="nofollow">https://developer.slickpay.com/docs/api/reference/currency-codes</a>
                    </div>

                    @php
                        $currencies = get_all_currencies();

                        $currencies = $currencies->filter(function ($item) use ($supportedCurrencies) { return in_array($item->title, $supportedCurrencies); });
                    @endphp
                    @if (count($currencies))
                        <div style="margin-top: 10px;">{{ __('Please switch currency to any supported currency') }}:&nbsp;&nbsp;
                            @foreach ($currencies as $currency)
                                <a href="{{ route('public.change-currency', $currency->title) }}" @if (get_application_currency_id() == $currency->id) class="active" @endif><span>{{ $currency->title }}</span></a>
                                @if (!$loop->last)
                                    &nbsp; | &nbsp;
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </li>
@endif

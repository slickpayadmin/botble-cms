@php $slickPayStatus = setting('payment_slickpay_status'); @endphp
<table class="table payment-method-item">
    <tbody>
    <tr class="border-pay-row">
        <td class="border-pay-col"><i class="fa fa-theme-payments"></i></td>
        <td style="width: 20%;">
            <img class="filter-black" src="{{ url('vendor/core/plugins/slickpay/images/slickpay.png') }}" alt="SlickPay">
        </td>
        <td class="border-right">
            <ul>
                <li>
                    <a href="https://slickpay.com" target="_blank">Slick-Pay</a>
                    <p>{{ trans('plugins/payment::payment.slickpay_description') }}</p>
                </li>
            </ul>
        </td>
    </tr>
    <tr class="bg-white">
        <td colspan="3">
            <div class="float-start" style="margin-top: 5px;">
                <div class="payment-name-label-group  @if ($slickPayStatus== 0) hidden @endif">
                    <span class="payment-note v-a-t">{{ trans('plugins/payment::payment.use') }}:</span> <label class="ws-nm inline-display method-name-label">{{ setting('payment_slickpay_name') }}</label>
                </div>
            </div>
            <div class="float-end">
                <a class="btn btn-secondary toggle-payment-item edit-payment-item-btn-trigger @if ($slickPayStatus == 0) hidden @endif">{{ trans('plugins/payment::payment.edit') }}</a>

                <a class="btn btn-secondary toggle-payment-item save-payment-item-btn-trigger @if ($slickPayStatus == 1) hidden @endif">{{ trans('plugins/payment::payment.settings') }}</a>
            </div>
        </td>
    </tr>
    <tr class="slick-online-payment payment-content-item hidden">
        <td class="border-left" colspan="3">
            {!! Form::open() !!}
            {!! Form::hidden('type', SLICKPAY_PAYMENT_METHOD_NAME, ['class' => 'payment_type']) !!}
            <div class="row">
                <div class="col-sm-6">
                    <ul>
                        <li>
                            <label>{{ trans('plugins/payment::payment.configuration_instruction', ['name' => 'SlickPay']) }}</label>
                        </li>
                        <li class="payment-note">
                            <p>{{ trans('plugins/payment::payment.configuration_requirement', ['name' => 'SlickPay']) }}:</p>
                            <ul class="m-md-l" style="list-style-type:decimal">
                                <li style="list-style-type:decimal">
                                    <a href="https://www.slick-pay.com" target="_blank">
                                        {{ trans('plugins/payment::payment.service_registration', ['name' => 'Slick-Pay']) }}
                                    </a>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{{ trans('plugins/payment::payment.slickpay_after_service_registration_msg', ['name' => 'Slick-Pay']) }}</p>
                                </li>
                                <li style="list-style-type:decimal">
                                    <p>{{ trans('plugins/payment::payment.enter_public_secret_keys') }}</p>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <div class="col-sm-6">
                    <div class="well bg-white">
                        <div class="form-group mb-3">
                            <label class="text-title-field" for="slickpay_name">{{ trans('plugins/payment::payment.method_name') }}</label>

                            <input type="text" class="next-input input-name" name="payment_slickpay_name" id="slickpay_name" data-counter="400" value="{{ setting('payment_slickpay_name', trans('plugins/payment::payment.pay_online_via', ['name' => 'SlickPay'])) }}">
                        </div>

                        <div class="form-group mb-3">
                            <label class="text-title-field" for="payment_slickpay_description">{{ trans('core/base::forms.description') }}</label>

                            <textarea class="next-input" name="payment_slickpay_description" id="payment_slickpay_description">{{ get_payment_setting('description', 'slick', __('You will be redirected to SATIM to complete the payment.')) }}</textarea>
                        </div>

                        <p class="payment-note">
                            {{ trans('plugins/payment::payment.please_provide_information') }} <a target="_blank" href="//www.slick-pay.com">SlickPay</a>:
                        </p>

                        <div class="form-group mb-3">
                            <label class="text-title-field" for="slickpay_public_key">{{ trans('plugins/payment::payment.public_key') }}</label>
                            <input type="text" class="next-input"
                            placeholder="*******************************"
                            name="payment_slickpay_public_key" id="slickpay_public_key" value="{{ app()->environment('demo') ? '*******************************' :setting('payment_slickpay_public_key') }}">
                        </div>

                        <div class="form-group mb-3">
                            <label class="text-title-field" for="slickpay_receiver_type">{{ trans('plugins/payment::payment.receiver_type') }}</label>
                            <div class="ui-select-wrapper form-group">
                                <select name="payment_slickpay_receiver_type" id="slickpay_receiver_type" class="ui-select" required>
                                    <option value="USER" @if(setting('payment_slickpay_receiver_type') == 'USER') selected @endif>User</option>
                                    <option value="MERCHANT" @if(setting('payment_slickpay_receiver_type') == 'MERCHANT') selected @endif>Merchant</option>
                                </select>

                                <svg class="svg-next-icon svg-next-icon-size-16">
                                    <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#select-chevron"></use>
                                </svg>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="text-title-field" for="slickpay_receiver_uuid">{{ trans('plugins/payment::payment.receiver_uuid') }}</label>
                            <div class="input-option">
                                <input type="text" class="next-input" placeholder="*******************************" id="slickpay_receiver_uuid" name="payment_slickpay_receiver_uuid" value="{{ app()->environment('demo') ? '*******************************' : setting('payment_slickpay_receiver_uuid') }}">
                            </div>
                        </div>

                        {!! Form::hidden('payment_slickpay_mode', 1) !!}
                        <div class="form-group mb-3">
                            <label class="next-label">
                                <input type="checkbox" value="1" name="payment_slickpay_mode" @if (setting('payment_slickpay_mode', 1) == 1) checked @endif>
                                {{ trans('plugins/payment::payment.sandbox_mode') }}
                            </label>
                        </div>

                        {!! apply_filters(PAYMENT_METHOD_SETTINGS_CONTENT, null, 'slick') !!}
                    </div>
                </div>
            </div>
            <div class="col-12 bg-white text-end">
                <button class="btn btn-warning disable-payment-item @if ($slickPayStatus == 0) hidden @endif" type="button">{{ trans('plugins/payment::payment.deactivate') }}</button>

                <button class="btn btn-info save-payment-item btn-text-trigger-save @if ($slickPayStatus == 1) hidden @endif" type="button">{{ trans('plugins/payment::payment.activate') }}</button>

                <button class="btn btn-info save-payment-item btn-text-trigger-update @if ($slickPayStatus == 0) hidden @endif" type="button">{{ trans('plugins/payment::payment.update') }}</button>
            </div>
            {!! Form::close() !!}
        </td>
    </tr>
    </tbody>
</table>

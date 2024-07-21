<?php

Route::group(['namespace' => 'Botble\Slickpay\Http\Controllers', 'middleware' => ['web', 'core']], function () {

    Route::get('payment/slickpay/status', 'SlickpayController@getCallback')
        ->name('payments.slickpay.status');

    Route::get('payment/slickpay/commission', 'SlickpayController@commission')
        ->name('payments.slickpay.commission');
});

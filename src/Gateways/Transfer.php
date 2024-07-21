<?php

namespace Botble\Slickpay\Gateways;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Botble\Setting\Models\Setting;

/**
 * Transfer
 *
 * @author     Slick-Pay <contact@slick-pay.com>
 */
class Transfer
{
    /**
     * Calculate transfer commission
     *
     * @param  float $amount  Request params
     * @return array
     */
    public static function calculateCommission(float $amount): array
    {
        $public_key = setting('payment_slickpay_public_key', null);

        if (empty($public_key)) return [
            'success'  => 0,
            'error'    => 1,
            'messages' => [
                __("You have to set a public key, from your config file.")
            ],
        ];

        if (!is_numeric($amount) || $amount <= 100) return [
            'success'  => 0,
            'error'    => 1,
            'messages' => [
                __("The amount must be a valid number.")
            ],
        ];

        try {

            $cURL = curl_init();

            $domain_name = setting('payment_slickpay_mode', true)
                ? "slickpay-v2.azimutbscenter.com"
                : "slickpay-v2.azimutbscenter.com";

            curl_setopt($cURL, CURLOPT_URL, "https://{$domain_name}/api/user/v2/transfer/commission");
            curl_setopt($cURL, CURLOPT_POSTFIELDS, [
                'amount' => $amount
            ]);
            curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($cURL, CURLOPT_TIMEOUT, 20);
            curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer {$public_key}",
            ));

            $result = curl_exec($cURL);

            $status = curl_getinfo($cURL, CURLINFO_HTTP_CODE);

            curl_close($cURL);

            $result = json_decode($result, true);

            if ($status < 200 || $status >= 300) return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [$result['message']],
            ];

            elseif (isset($result['errors']) && boolval($result['errors']) == true) return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [
                    !empty($result['message']) ? $result['message'] : $result['msg']
                ],
            ];

        } catch (\Exception $e) {

            return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [
                    $e->getMessage()
                ],
            ];
        }

        return [
            'success'  => 1,
            'error'    => 0,
            'response' => [
                'amount' => $result['amount'],
                'commission'=> $result['commission']
            ]
        ];
    }

    /**
     * Initiate a new payment
     *
     * @param  array $params  Request params
     * @return array
     */
    public static function createPayment(array $params): array
    {
        $public_key = (Setting::firstWhere('key','payment_slickpay_public_key'))['value'];
        
        $user_type  = (Setting::firstWhere('key','payment_slickpay_receiver_type'))['value'];
        $mode    = Setting::where('key','payment_slickpay_mode')->first();
        
        if (empty($public_key)) return [
            'success'  => 0,
            'error'    => 1,
            'messages' => [
                __("You have to set a public key, from your config file.")
            ],
        ];

        $validator = Validator::make($params, [
            'amount'        => 'required|numeric|min:100',
            'account'          => 'required|string',
            'url'           => 'required|url',
        ]);

        if ($validator->fails()) return [
            'success'  => 0,
            'error'    => 1,
            'messages' => $validator->errors()->all(),
        ];

        //$validator['url'] = str_replace("amp;","",$validator['url']);
        try {

            $cURL = curl_init();

            $domain_name = $mode['value'] == 0
                ? "devapi.slick-pay.com"
                : "prodapi.slick-pay.com";
                
            $url = "";
                
            $url = $user_type == 'USER' ?  "https://{$domain_name}/api/v2/users/transfers" : "https://{$domain_name}/api/v2/merchants/invoices";
            $info = session()->get('booking_address');
            
            /*$params->merge([
                'name'=>$info->name,
                'address'=>$info->address,
                'items' => ['name']
            ]);*/
            
            //Log::info($url);
            //Log::info($params);

            // $params['account'] = "dae1b693-e7e5-405f-a557-67812ba746da";
            // $params['amount'] = 5000;


            curl_setopt($cURL, CURLOPT_URL, $url);
            curl_setopt($cURL, CURLOPT_POSTFIELDS,  json_encode($params));
            curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
                "Accept: application/json",
                "Content-Type: application/json",
                "Authorization: Bearer {$public_key}",
            ));
            curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);

            curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($cURL, CURLOPT_TIMEOUT, 20);
            // curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
            //     "Accept: application/json",
            //     "Content-Type: application/json",
            //     "Authorization: Bearer 6131|GHnSsG0M2NMCr6MWZnGGDpV2CYs5YTCR7vmm0A5G",
            // ));

            $res = curl_exec($cURL);
            
            $status = curl_getinfo($cURL, CURLINFO_HTTP_CODE);
            $errors = curl_error($cURL);
            curl_close($cURL);
            
            //dd($result);
            // Log::info('res '.$res);

            $result = json_decode($res, true);


            if ($status < 200 || $status >= 300) return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [
                    $result['message']
                ],
            ];

            elseif (isset($result['errors']) && boolval($result['errors']) == true) return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [
                    !empty($result['message']) ? $result['message'] : $result['msg']
                ],
            ];

        } catch (\Exception $e) {

            return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [
                    $e->getMessage()
                ],
            ];
        }

        return [
            'success'  => 1,
            'error'    => 0,
            'response' => [
                'transferId'  => $result['id'],
                'redirectUrl' => $result['url'],
                'message'     => $result['message']
            ]
        ];
    }

    /**
     * Check a payment status with it transfer_id
     *
     * @param  integer $transfer_id  The payment transfer_id provided as a return of the initiate function
     * @return array
     */
    public static function paymentStatus(int $transfer_id): array
    {
        $public_key = (Setting::firstWhere('key','payment_slickpay_public_key'))['value'];
        
        $user_type  = (Setting::firstWhere('key','payment_slickpay_receiver_type'))['value'];
        $mode    = Setting::where('key','payment_slickpay_mode')->first();
     

        if (empty($public_key)) return [
            'success'  => 0,
            'error'    => 1,
            'messages' => [
                __("You have to set a public key, from your config file.")
            ],
        ];

        try {
            
            $cURL = curl_init();

            $domain_name = $mode['value'] == 0 
                ? "devapi.slick-pay.com"
                : "prodapi.slick-pay.com";
                
            $url = "";
                
            $url = $user_type == 'USER' ?  "https://{$domain_name}/api/v2/users/transfers/{$transfer_id}" : "https://{$domain_name}/api/v2/merchants/invoices/{$transfer_id}";

            curl_setopt($cURL, CURLOPT_URL, $url);
            curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($cURL, CURLOPT_TIMEOUT, 20);
            curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer {$public_key}",
            ));

            $result = curl_exec($cURL);

            $status = curl_getinfo($cURL, CURLINFO_HTTP_CODE);

            curl_close($cURL);

            $result = json_decode($result, true);

            // if (!empty($result['msg']) && $result['msg'] == 'draft') return [
            //     'success' => 1,
            //     'error'   => 0,
            //     'status'  => "draft",
            // ];

            if ($status < 200 || $status >= 300) return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [
                    __("Error ! Please, try later")
                ],
            ];

            elseif (!$result['completed']) return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [
                    // !empty($result['message']) ? $result['message'] : $result['msg']
                    "Your Payment hasn't been completed yet, try later"
                ],
            ];

        } catch (\Exception $e) {

            return [
                'success' => 0,
                'error'   => 1,
                'messages' => [
                    $e->getMessage()
                ],
            ];
        }

        return [
            'success'  => 1,
            'error'    => 0,
            'status'   => "completed",
            'response' => $result['data']
        ];
    }

    /**
     * Get user payment history
     *
     * @param  integer  $offset  Pagination offset
     * @return array
     */
    public static function paymentHistory(int $offset = 0): array
    {
        $public_key = (Setting::firstWhere('key','payment_slickpay_public_key'))['value'];
        
        $user_type  = (Setting::firstWhere('key','payment_slickpay_receiver_type'))['value'];
        $mode    = Setting::where('key','payment_slickpay_mode')->first();

        if (empty($public_key)) return [
            'success'  => 0,
            'error'    => 1,
            'messages' => [
                __("You have to set a public key, from your config file.")
            ],
        ];

        try {

            $cURL = curl_init();

            $domain_name = $mode['value'] == 0
                ? "devapi.slick-pay.com"
                : "prodapi.slick-pay.com";

            curl_setopt($cURL, CURLOPT_URL, "https://{$domain_name}/api/v2/user/transfer?offset={$offset}");
            curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($cURL, CURLOPT_TIMEOUT, 20);
            curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer {$public_key}",
            ));

            $result = curl_exec($cURL);

            $status = curl_getinfo($cURL, CURLINFO_HTTP_CODE);

            curl_close($cURL);

            $result = json_decode($result, true);

            if ($status < 200 || $status >= 300) return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [
                    __("Error ! Please, try later")
                ],
            ];

            elseif (!empty($result['errors'])) return [
                'success'  => 0,
                'error'    => 1,
                'messages' => [
                    !empty($result['message']) ? $result['message'] : $result['msg']
                ],
            ];

        } catch (\Exception $e) {

            return [
                'success' => 0,
                'error'   => 1,
                'messages' => [
                    $e->getMessage()
                ],
            ];
        }

        return [
            'success'  => 1,
            'error'    => 0,
            'response' => $result
        ];
    }
}

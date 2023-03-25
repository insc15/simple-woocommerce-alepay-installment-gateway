<?php
    define("BASE_URL", 'https://alepay-v3-sandbox.nganluong.vn/api/v3/checkout');
    define("LOGO_URL", 'https://static-alepay.nganluong.vn/static/images/banks/');

    function rest_call($method, $url, $data = "", $contentType= false, $token = false)
    {
        $curl = curl_init();

        if($token){ //Add Bearer Token header in the request
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization: '.$token
            ));
        }

        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data){
                    if($contentType){
                        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                            'Content-Type: '.$contentType,
                            'Content-Length: ' . strlen($data),
                        ));
                    }
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);

        curl_close($curl);

        return json_decode($result, true);
    }

    function getInstallmentInfo($data, $checkSum){
        $query = http_build_query($data);
        $signature = hash_hmac('sha256', $query, $checkSum, false);

        $data['signature'] = $signature;

        $response = rest_call("POST", BASE_URL . "/get-installment-info", json_encode($data),'application/json');

        $response['data'] = array_filter($response['data'], function($item){
            return $item['bankCode'] != 'fff';
        });

        $response['data'] = array_map(function($item){
            $item['logo'] = getLogo($item['bankCode']);

            $item['paymentMethods'] = array_map(function($paymentMethod){
                $paymentMethod['logo'] = getLogo($paymentMethod['paymentMethod']);
                return $paymentMethod;
            }, $item['paymentMethods']);

            return $item;
        }, $response['data']);

        return $response;
    }

    function getLogo($bankCode){
        if(strtolower($bankCode) === 'nh tp bank') $bankCode = 'tpb';
        $bankCode = strtolower($bankCode);
        return LOGO_URL . "$bankCode.png";
    }

    function stripVN($str) {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
    
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        return $str;
    }

    function processCheckout($data, $checkSum){

        ksort($data);

        $query = '';

        foreach ($data as $key => $value) {
            $value = stripVN( $value);
            
            if($key == 'checkoutType') $value = (int) $value;
            if($key == 'installment'){ 
                $query .= $key . '=true&';
                $value = true;
            }else{
                $query .= $key . '=' . $value . '&';
            }
            $data[$key] = $value;
        }
        
        $query = rtrim($query, '&');
        
        $signature = hash_hmac('sha256', $query, $checkSum, false);

        $data['signature'] = $signature;

        $response = rest_call('POST', BASE_URL . '/request-payment', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), 'application/json');

        return $response;
    }
?>
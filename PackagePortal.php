<?php
class PackagePortal{

    private static $url = 'https://prod.pp-app-api.com/v1/graphql';

    private static function request($url, $body = null, $headers = null){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($body != null) {
            curl_setopt($ch, CURLOPT_POST, $body);
        }else{
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_VERBOSE, true);


        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private static function SetFirebaseHeader(){
        $headers = array();
        $headers[] = 'Host: www.googleapis.com';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: */*';
        $headers[] = 'X-Ios-Bundle-Identifier: com.packageportal.customerapp';
        $headers[] = 'Connection: close';
        $headers[] = 'X-Client-Version: iOS/FirebaseSDK/6.9.2/FirebaseCore-iOS';
        $headers[] = 'User-Agent: FirebaseAuth.iOS/6.9.2 com.packageportal.customerapp/1.0.9 iPhone/14.4.1 hw/iPhone11_8';
        $headers[] = 'Accept-Language: en';
        // $headers[] = 'Content-Length: 89';
        // $headers[] = 'Accept-Encoding: gzip, deflate';

        return $headers;
    }

    public static function Login($email, $password){
        $url = 'https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPassword?key=AIzaSyChXf6sDuL4PcYBZiOUdP_tbsVx3Woa_Yc';
        $body = '{"email":"' . $email . '","returnSecureToken":true,"password":"' . $password . '"}';
        $headers = self::SetFirebaseHeader();
        $request = self::request($url, $body, $headers);
        return $request;
    }

    private static function SetPackagePortalHeader($bearer){
        $headers[] = 'Host: prod.pp-app-api.com';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'User-Agent: PackagePortal/2 CFNetwork/1220.1 Darwin/20.3.0';
        $headers[] = 'Connection: close';
        $headers[] = 'Accept: */*';
        $headers[] = 'Accept-Language: en-us';
        $headers[] = 'Authorization: Bearer ' . $bearer;
        return $headers;
    }

    public static function UpdateLastLogin($bearer){
        $body = '{"operationName":"update_user","variables":{"last_logged_in":"' . date(DATE_ISO8601) . '"},"query":"mutation update_user($last_logged_in: timestamptz!) {\n  update_users(where: {}, _set: {last_logged_in: $last_logged_in}) {\n    affected_rows\n    returning {\n      id\n      __typename\n    }\n    __typename\n  }\n}\n"}';
        $headers = self::SetPackagePortalHeader($bearer);

        $request = self::request(self::$url, $body, $headers);
        return $request;
    }

    public static function GetUserIDandNames($bearer){
        $body = '{"operationName":null,"variables":{},"query":"{\n  users {\n    id\n		first_name\n    last_name\n    __typename\n  }\n}\n"}';
        $headers = self::SetPackagePortalHeader($bearer);

        $request = self::request(self::$url, $body, $headers);
        return $request;
    }

    public static function GetWallet($bearer){
        $body = '{"operationName":"getWalletAddress","variables":{},"query":"query getWalletAddress {\n  users {\n    wallet_address\n    __typename\n  }\n}\n"}';
        $headers = self::SetPackagePortalHeader($bearer);

        $request = self::request(self::$url, $body, $headers);
        return $request;
    }

    public static function UpdateWallet($bearer, $wallet_address){
        $body = '{"operationName":"update_user","variables":{"wallet_address":"' . $wallet_address . '"},"query":"mutation update_user($wallet_address: String!) {\n  update_users(where: {}, _set: {wallet_address: $wallet_address}) {\n    affected_rows\n    returning {\n      id\n      wallet_address\n      __typename\n    }\n    __typename\n  }\n}\n"}';
        $headers = self::SetPackagePortalHeader($bearer);

        $request = self::request(self::$url, $body, $headers);
        return $request;
    }

    public static function GetAllScan($bearer){
        $body = '{"operationName":null,"variables":{},"query":"{\n  scans(order_by: {created_at: desc}) {\n    id\n    tracking_number\n    created_at\n    batch_uuid\n    tracking_numbers(where: {result: {_eq: \"valid\"}}) {\n      created_at\n      transaction_key\n      __typename\n    }\n    tracking_numbers_aggregate {\n      aggregate {\n        count\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}\n"}';
        $headers = self::SetPackagePortalHeader($bearer);
        $request = self::request(self::$url, $body, $headers);
        return $request;
    }

    private static function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,
    
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,
    
            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    

    public static function InsertNewInstall($bearer, $user_id){
        $body = '{"operationName":"insert_new_install","variables":{"object":{"user_id":'.$user_id.',"install_id":"'. self::gen_uuid() .'"}},"query":"mutation insert_new_install($object: installs_insert_input!) {\n  insert_installs_one(object: $object) {\n    install_id\n    user_id\n    __typename\n  }\n}\n"}';
        $headers = self::SetPackagePortalHeader($bearer);
        $request = self::request(self::$url, $body, $headers);
        return $request;
    }

    public static function DoScan($bearer, $user_id, $tracking_number){
        $body = '{"operationName":"insert_multiple_scans","variables":{"objects":[{"user_id":"'.$user_id.'","tracking_number":"'.$tracking_number.'","longitude":'. rand(10, 99) .'.168331,"latitude":'. rand(10, 99) .'.153332,"accuracy":'. rand(65, 100) .',"batch_uuid":"'. self::gen_uuid() .'"}]},"query":"mutation insert_multiple_scans($objects: [scans_insert_input!]!) {\n  insert_scans(objects: $objects) {\n    returning {\n      id\n      __typename\n    }\n    __typename\n  }\n}\n"}';
        $headers = self::SetPackagePortalHeader($bearer);
        $request = self::request(self::$url, $body, $headers);
        return $request;
    }

    public static function DoReview($bearer, $scan_id){
        $body = '{"operationName":"add_multiple_ratings","variables":{"objects":[{"scan_id":"' . $scan_id . '","up":true,"descriptors":["Delivered with care"]}]},"query":"mutation add_multiple_ratings($objects: [ratings_insert_input!]!) {\n  insert_ratings(objects: $objects) {\n    affected_rows\n    __typename\n  }\n}\n"}';
        $headers = self::SetPackagePortalHeader($bearer);
        $request = self::request(self::$url, $body, $headers);
        return $request;
    }

    public static function CheckWallet($address){
        $url = 'https://api.viewblock.io/zilliqa/addresses/'.$address.'?txsType=tokens';
        $headers = array();
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:87.0) Gecko/20100101 Firefox/87.0';
        $headers[] = 'origin: https://viewblock.io';

        return self::request($url, null, $headers);
    }

    public static function ValidateResi($resi){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.ship24.com/api/parcels/' . $resi . '?lang=en');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"userAgent\":\"\",\"os\":\"Android\",\"browser\":\"Chrome\",\"device\":\"Android\",\"os_version\":\"unknown\",\"browser_version\":\"87.0.4280.66\",\"uL\":\"id-ID\"}");

        $headers = array();
        $headers[] = 'User-Agent: Mozilla/5.0 (Linux; Android 10; Redmi Note 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Mobile Safari/537.36';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Sec-Fetch-Mode: cors';
        $headers[] = 'Sec-Fetch-Site: same-site';
        $headers[] = 'Sec-Fetch-Dest: empty';
        $headers[] = 'Origin: https://www.ship24.com';
        $headers[] = 'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7';
        $headers[] = 'Referer: https://www.ship24.com/';
        $headers[] = 'Accept: application/json, text/plain, */*';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($result);
        if (isset($result->data)) {
            foreach ($result->data->tracking_numbers as $key => $tracking_number) {
                $data['tracking_numbers'][$key] = $tracking_number->tracking_number;
            }
            $data['origin']      = (isset($result->data->origin_country_code)) ? $result->data->origin_country_code : "";
            $data['destination'] = (isset($result->data->destination_country_code)) ? $result->data->destination_country_code : "";
        }else{
            $data['tracking_numbers'] = '';
            $data['origin'] = '';
            $data['destination'] = '';
        }

        return $data;
    }
}
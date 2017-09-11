<?php

namespace Tiny\Plugin;

use Tiny\Traits\LogTrait;

class RpcHelper
{

    use LogTrait;

    /**
     * post请求url，并返回结果
     * @param string $query_url
     * @param array $header
     * @param string $type
     * @param array $post_fields
     * @param int $base_auth
     * @param int $timeout
     * @return array
     */
    public static function curlRpc($query_url, $header = [], $type = 'GET', $post_fields = [], $base_auth = 0, $timeout = 10)
    {
        $t1 = microtime(true);
        //变量初始化
        //open connection
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        //print_r($query_url);exit();
        curl_setopt($ch, CURLOPT_URL, $query_url);
        curl_setopt($ch, CURLOPT_PORT, 80);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        if ($base_auth) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        if ($type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($post_fields) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            }
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($type));

        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        //execute post
        $response = curl_exec($ch);
        //get response code
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //close connection 
        $http_ok = $response_code == 200 || $response_code == 201 || $response_code == 204;
        $use_time = round(microtime(true) - $t1, 3) * 1000 . 'ms';
        //记录日志 参数中会有私密信息 不把私密信息存入日志
        $log_msg = " use:{$use_time}, query_url:{$query_url}, response_code:{$response_code}";
        $total = strlen($response) > 500;
        $log_msg .= $total > 500 ? ', rst:' . substr($log_msg, 0, 500) . "...total<{$total}>chars..." : ", rst:{$response}";
        if (!$http_ok) {
            $log_msg .= ', curl_error:' . curl_error($ch);
            $log_msg .= ', curl_errno:' . curl_errno($ch);
            self::error($log_msg, __METHOD__, __CLASS__, __LINE__);
        } else {
            self::debug($log_msg, __METHOD__, __CLASS__, __LINE__);
        }
        curl_close($ch);
        //return result
        if ($http_ok) {
            $data = json_decode(trim($response), true);
            return $data;
        } else {
            return ['error' => ['msg' => '调用远程接口失败', 'res' => trim($response), 'code' => $response_code], ];
        }
    }

} 
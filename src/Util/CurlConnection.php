<?php


namespace App\Util;


class CurlConnection
{

    /**
     * @param string $base_url
     * @param string $endpoint
     * @param array $params
     * @param string $delimiter
     * @param array $headers
     * @param bool $return_headers
     * @return array
     * @throws CurlConnectionException
     */
    protected static function call(string $base_url, string $endpoint, array $params = [], $delimiter = '&', array $headers = array(), bool $return_headers = true)
    {

        /**
         * Create URL
         */
        $url = $base_url . $endpoint;

        /**
         * Attach Params
         */
        if (!empty($params)) {
            $url .= '?';
            $round = 0;
            foreach ($params as $param => $value) {
                $round++;
                $url .= $round === 1 ? $param . '=' . $value : $delimiter . $param . '=' . $value;
            }
        }

        /**
         * Curl Call
         */
        $ch = curl_init($url);

        /**
         * Add Header Array
         */
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        /**
         * Other Options
         */
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, $return_headers);

        $curl = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);

        if (isset($error_msg)) {
            throw new CurlConnectionException('cURL Error: ' . $error_msg);
        }


        return array(
            'code' => $code,
            'result' => $curl,
            'url' => $url,
        );

    }

}
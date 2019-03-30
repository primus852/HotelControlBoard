<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 29.03.2019
 * Time: 18:58
 */

namespace App\Util\Competitors;


use DateTime;
use Symfony\Component\Filesystem\Filesystem;

class CompetitorBooking
{
    const BASE_URL = 'https://www.booking.com/';

    /**
     * @param string $slug
     * @param DateTime $checkin
     * @param int $pax
     * @return array
     * @throws CompetitorException
     */
    public static function crawl_hotel(string $slug, DateTime $checkin, int $pax)
    {

        $checkout = clone $checkin;
        $checkout->modify('+1 day');

        $params = array(
            'checkin' => $checkin->format('Y-m-d'),
            'checkout' => $checkout->format('Y-m-d'),
            'dest_type' => 'city',
            'dist' => '0',
            'group_adults' => $pax,
            'group_children' => '0',
            'hapos' => '1',
        );

        $headers = [
            'Host: www.booking.com',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:66.0) Gecko/20100101 Firefox/66.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: de,en-US;q=0.7,en;q=0.3',
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Pragma: no-cache',
            'Cache-Control: no-cache',
        ];

        /**
         * Get the Site
         */
        try {
            $result = self::call('hotel/de/' . $slug, $params, ';', $headers);
        } catch (CompetitorException $e) {
            throw new CompetitorException($e->getMessage());
        }

        /**
         * Check the Return
         */
        if ($result['code'] !== 200) {
            throw new CompetitorException('Invalid Return Code: ' . $result['code'] . ' Url: ' . $result['url']);
        }

        /**
         * Regex Rooms Available
         */
        preg_match('@b_rooms_available_and_soldout: (.*?)\nb_@', $result['result'], $room_str);
        if (!array_key_exists(1, $room_str)) {
            $fs = new Filesystem();
            $fs->appendToFile('tmp/temp.html', $result['result']);
            throw new CompetitorException('Could not extract Rooms');
        }

        $rooms = json_decode(substr($room_str[1], 0, -1), true);

        /**
         * Get Room Name
         */
        $roomName = $rooms[0]['b_name'];

        /**
         * Get array with max pax = $pax
         */
        $k = null;
        foreach ($rooms[0]['b_blocks'] as $key => $room) {

            if ($room['b_max_persons'] === $pax) {
                $k = $key;
                break;
            }
        }

        if($k === null){
            $k = 0;
        }

        /**
         * Get Price
         */
        if (!array_key_exists('b_raw_price', $rooms[0]['b_blocks'][$k])) {
            $price = 'booked';
            $isIncl = false;
        } else {
            $price = (float)$rooms[0]['b_blocks'][$k]['b_raw_price'];
            $isIncl = $rooms[0]['b_blocks'][$k]['b_mealplan_included_name'] === 'breakfast' ? true : false;
        }

        $maxPax = $rooms[0]['b_blocks'][$k]['b_max_persons'];


        return array(
            'price' => $price,
            'isIncl' => $isIncl,
            'roomName' => $roomName,
            'pax' => $maxPax,
        );

    }

    /**
     * @param string $endpoint
     * @param array $params
     * @param string $delimiter
     * @param array $headers
     * @param bool $return_headers
     * @return array
     * @throws CompetitorException
     */
    private static function call(string $endpoint, array $params = [], $delimiter = '&', array $headers = array(), bool $return_headers = true)
    {

        /**
         * Create URL
         */
        $url = self::BASE_URL . $endpoint;

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
            throw new CompetitorException('cURL Error: ' . $error_msg);
        }


        return array(
            'code' => $code,
            'result' => $curl,
            'url' => $url,
        );

    }


}
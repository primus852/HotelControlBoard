<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 29.03.2019
 * Time: 18:58
 */

namespace App\Util\Competitors;


use App\Util\CurlConnection;
use App\Util\CurlConnectionException;
use DateTime;
use Symfony\Component\Filesystem\Filesystem;

class CompetitorBooking extends CurlConnection
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
            $result = self::call(self::BASE_URL, 'hotel/de/' . $slug, $params, ';', $headers);
        } catch (CurlConnectionException $e) {
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
            $price = (float)$rooms[0]['b_blocks'][$k]['b_raw_price'].'&euro;';
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




}
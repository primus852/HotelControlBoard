<?php


namespace App\Util\OpenWeather;


use App\Util\CurlConnection;
use App\Util\CurlConnectionException;
use DateTime;
use Exception;

class OpenWeather extends CurlConnection
{

    const URL = 'https://api.openweathermap.org/data/2.5/';
    const CITY_ID = '2950157'; /* Berlin */

    private $appId;

    /**
     * OpenWeather constructor.
     */
    public function __construct(string $appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return array
     * @throws OpenWeatherException
     */
    public function forecast()
    {
        try {
            $data = self::call(self::URL, 'weather', array(
                'id' => self::CITY_ID,
                'appid' => $this->appId,
                'units' => 'metric'
            ), '&', array(), false);
        } catch (CurlConnectionException $e) {
            throw new OpenWeatherException($e->getMessage());
        }

        if ($data['code'] !== 200) {
            throw new OpenWeatherException('Invalid Return Code: ' . $data['code']);
        }

        $result = json_decode($data['result'], true);

        if ($result === false) {
            throw new OpenWeatherException('Invalid Response format.');
        }

        try {
            return array(
                'min' => round($result['main']['temp_min'],0),
                'max' => round($result['main']['temp_max'],0),
                'now' => round($result['main']['temp'],0),
                'icon' => $result['weather'][0]['icon'],
                'condition' => $result['weather'][0]['description'],
                'wind' => $result['wind']['speed']
            );
        } catch (Exception $e) {
            throw new OpenWeatherException('Invalid Result Array.');
        }

    }

    /**
     * @return array
     * @throws OpenWeatherException
     */
    public function hourly()
    {

        try {
            $data = self::call(self::URL, 'forecast/hourly', array(
                'id' => self::CITY_ID,
                'appid' => $this->appId,
                'units' => 'metric'
            ), '&', array(), false);
        } catch (CurlConnectionException $e) {
            throw new OpenWeatherException($e->getMessage());
        }

        if ($data['code'] !== 200) {
            throw new OpenWeatherException('Invalid Return Code: ' . $data['code']);
        }

        $result = json_decode($data['result'], true);

        if ($result === false) {
            throw new OpenWeatherException('Invalid Response format.');
        }

        $output = array();
        $counter = 0;
        foreach ($result['list'] as $list) {
            $counter++;
            if($counter <= 2){
                continue;
            }
            if($counter === 9){
                break;
            }

            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $list['dt_txt']);

            try {
                $output[] = array(
                    'temp' => round($list['main']['temp'],0),
                    'icon' => $list['weather'][0]['icon'],
                    'condition' => $list['weather'][0]['description'],
                    'time' => $dt->format('H:i'),
                );
            } catch (Exception $e) {
                throw new OpenWeatherException('Invalid Result Array.');
            }
        }

        return $output;

    }


}
<?php


namespace App\Util\DFA;


use App\Entity\Holiday;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;

class DFA
{

    const DFA = 'https://deutsche-feiertage-api.de/api/v1/';
    const TOKEN = 'dfa';

    /**
     * @param string|null $date
     * @param array $params
     * @return bool|mixed
     * @throws DFAException
     */
    public static function api(string $date = null, array $params = array())
    {

        $result = false;

        try{
            $date = $date === null ? (new DateTime())->format('Y-m-d') : $date;
        }catch (Exception $e){
            throw new DFAException('Invalid Date');
        }

        $isDate = DateTime::createFromFormat('Y-m-d', $date);
        if ($isDate === false) {
            $isDate = DateTime::createFromFormat('Y', $date);
            if ($isDate === false) {
                throw new DFAException('Invalid Date: ' . $date);
            }
        }

        $data = self::curl(self::DFA, $date, $params);

        if ($data['code'] !== 200) {
            return $result;
        }

        return $data['result'];
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param array $params
     * @return int
     * @throws DFAException
     */
    public static function calc(DateTime $start, DateTime $end, array $params = array())
    {

        $start->setTime(0, 0, 0);
        $end->setTime(0, 0, 0);
        $end->modify('+1 day');

        /**
         * Get all Dates
         */
        try {
            $period = new DatePeriod(
                $start,
                new DateInterval('P1D'),
                $end
            );
        } catch (Exception $e) {
            throw new DFAException('Invalid DateInterval');
        }


        /* @var $value DateTime */
        $takeDays = 0;
        $hasHolidays = false;

        if (iterator_count($period) === 0) {


            /**
             * Only One Day to Check
             */
            if ($start->format('N') !== '6' && $start->format('N') !== '7') {

                try {
                    $data = self::api($start->format('Y-m-d'), $params);
                } catch (DFAException $e) {
                    throw new DFAException($e);
                }

                /**
                 * Check for Holiday in Berlin
                 */
                if ($data['isHoliday'] === false) {
                    $takeDays++;
                } else {
                    $hasHolidays = true;
                }
            }
        } else {

            /**
             * Check DatePeriod
             */
            foreach ($period as $key => $value) {
                /**
                 * Check for Saturday or Sunday
                 */
                if ($value->format('N') !== '6' && $value->format('N') !== '7') {

                    try {
                        $data = self::api($value->format('Y-m-d'), $params);
                    } catch (DFAException $e) {
                        throw new DFAException($e);
                    }

                    /**
                     * Check for Holiday in Berlin
                     */
                    if ($data['isHoliday'] === false) {
                        $takeDays++;
                    } else {
                        $hasHolidays = true;
                    }
                }
            }
        }

        /**
         * Check if we only have one day selected
         */
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            $takeDays = $hasHolidays === true ? 0 : 1;
        }


        return $takeDays;

    }

    /**
     * @param DateTime $dateTime
     * @param bool $add
     * @return DateTime
     * @throws DFAException
     */
    public static function borders(DateTime $dateTime, bool $add)
    {

        $isWork = false;
        $workDay = clone $dateTime;

        /**
         * Reduce one day due to "DatePeriod" misbehaviour if $add = true
         */
        $add === true ? $workDay->modify('-1 day') : null;

        while (!$isWork) {

            $add === true ? $workDay->modify('+1 day') : $workDay->modify('-1 day');


            if ($workDay->format('N') !== '6' && $workDay->format('N') !== '7') {

                try {
                    $data = self::api($workDay->format('Y-m-d'), array('bundeslaender' => 'be', 'short' => 'true'));
                } catch (DFAException $e) {
                    throw new DFAException($e);
                }

                /**
                 * Check for Holiday in Berlin
                 */
                if ($data['isHoliday'] === false) {
                    $isWork = true;
                }
            }
        }

        return $workDay;
    }

    /**
     * @param User $user
     * @param DateTime $start
     * @param float $taken
     * @param ObjectManager $em
     * @return float|int|string
     */
    public static function rest(User $user, DateTime $start, float $taken, ObjectManager $em)
    {

        $holidays = $em->getRepository(Holiday::class)->findBy(array(
            'requestUser' => $user,
            'approved' => 2,
        ));

        $days = 0;
        /* @var $holiday Holiday */
        foreach($holidays as $holiday){
            if($holiday->getStart()->format('Y') === $start->format('Y')){
                $days += $holiday->getDaysTaken();
            }
        }

        $rest = $user->getHolidays() - $days - $taken;

        return $rest;

    }

    /**
     * @param User $user
     * @param DateTime $start
     * @param ObjectManager $em
     * @return array
     */
    public static function avail(User $user, DateTime $start, ObjectManager $em)
    {

        $holidays = $em->getRepository(Holiday::class)->findBy(array(
            'requestUser' => $user,
            'approved' => 2,
        ));

        $days = 0;
        /* @var $holiday Holiday */
        foreach($holidays as $holiday){
            if($holiday->getStart()->format('Y') === $start->format('Y')){
                $days += $holiday->getDaysTaken();
            }
        }

        $total = $user->getHolidays();
        $rest = $total - $days;

        return array(
            'total' => $total,
            'rest' => $rest
        );

    }

    /**
     * @param DateTime $first
     * @param DateTime $last
     * @return mixed
     * @throws DFAException
     */
    public static function away(DateTime $first, DateTime $last)
    {

        $f = clone $first;

        $f->modify('+1 day');

        try {
            $interval = $last->diff($f);
        } catch (Exception $e) {
            throw new DFAException('Unknown Interval: ' . $e->getMessage());
        }

        return $interval->days;

    }

    /**
     * @param string $url
     * @param string $date
     * @param array $params
     * @param bool $json
     * @return array
     */
    protected static function curl(string $url, string $date, array $params = array(), bool $json = true)
    {

        $req = $url . $date;

        $mod = '';
        if (!empty($params)) {
            $mod = '?';
            foreach ($params as $key => $param) {

                if ($param === false) {
                    $param = 'false';
                }

                if ($param === true) {
                    $param = 'true';
                }

                if (is_array($param)) {
                    foreach ($param as $p) {
                        $mod .= $key . '=' . $p . '&';
                    }
                } else {
                    $mod .= $key . '=' . $param . '&';
                }
            }
            $mod = substr($mod, 0, -1);
        }

        $ch = curl_init($req . $mod);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-DFA-Token: ' . self::TOKEN
        ));

        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array(
            'result' => $json ? json_decode($result, true) : $result,
            'code' => $code,
            'url' => $req,
        );

    }
    
}
<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 15.02.2019
 * Time: 13:45
 */

namespace App\Util\Helper;


use App\Entity\Budget;
use App\Entity\CompetitorCheck;
use App\Entity\HistoryForecast;
use App\Entity\Rateplan;
use App\Entity\Ratetype;
use App\Entity\Roomtype;
use App\Entity\User;
use App\Util\Rate\RateHandler;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\ORMException;
use Exception;
use primus852\SimpleCrypt\SimpleCrypt;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Helper
{

    /**
     * @param int $month
     * @param int $year
     * @param ObjectManager $em
     * @return string
     * @throws HelperException
     */
    public static function generate_calendar(int $month, int $year, ObjectManager $em)
    {

        try {
            $now = DateTime::createFromFormat('Y-m-d', $year . '-' . $month . '-01');
        } catch (Exception $e) {
            throw new HelperException('Could not create Datetime: ' . $e->getMessage());
        }

        /* draw table */
        $calendar = '<table class="calendar"><tr><td colspan="7" class="calendar-month">' . $now->format('M') . '</td></tr>';

        /* table headings */
        $headings = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
        $calendar .= '<tr class="calendar-row"><th class="calendar-day-head">' . implode('</th><th class="calendar-day-head">', $headings) . '</th></tr>';

        /* days and weeks vars now ... */
        $running_day = date('w', mktime(0, 0, 0, $month, 0, $year));
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $days_in_this_week = 1;
        $day_counter = 0;

        /* row for week one */
        $calendar .= '<tr class="calendar-row">';

        /* print "blank" days until the first of the current week */
        for ($x = 0; $x < $running_day; $x++) {
            $calendar .= '<td class="calendar-day-np"> </td>';
            $days_in_this_week++;
        }

        /* keep going with days.... */
        for ($list_day = 1; $list_day <= $days_in_month; $list_day++) {

            try {
                $today = DateTime::createFromFormat('Y-m-d', $year . '-' . $month . '-' . $list_day);
            } catch (Exception $e) {
                throw new HelperException('Could not create Datetime: ' . $e->getMessage());
            }

            $rate = $em->getRepository(Rateplan::class)->findOneBy(array(
                'bookDate' => $today,
            ));

            $dsp_rate = $rate === null ? '-' : number_format($rate->getPrice(), 0);

            /**
             * Color & CSS Classes
             */
            $color = array_key_exists($dsp_rate, RateHandler::RATE_COLORS) ? RateHandler::RATE_COLORS[$dsp_rate]['color'] : '#fff';
            $fontColor = array_key_exists($dsp_rate, RateHandler::RATE_COLORS) ? RateHandler::RATE_COLORS[$dsp_rate]['font'] : '#000';
            $cxl = 'cxlreg';
            if ($rate !== null) {
                if ($rate->getCxl() !== null) {
                    $cxl = 'cxl' . $rate->getCxl();
                }
            }

            $calendar .= '<td class="calendar-day" style="background: ' . $color . ';color:' . $fontColor . ';">';
            /* add in the day number */
            $calendar .= '<div class="day-number ' . $cxl . '">' . $list_day . '</div>';

            $calendar .= $dsp_rate;

            $calendar .= '</td>';
            if ($running_day == 6):
                $calendar .= '</tr>';
                if (($day_counter + 1) != $days_in_month):
                    $calendar .= '<tr class="calendar-row">';
                endif;
                $running_day = -1;
                $days_in_this_week = 0;
            endif;
            $days_in_this_week++;
            $running_day++;
            $day_counter++;
        }

        /* finish the rest of the days in the week */
        if ($days_in_this_week < 8) {
            for ($x = 1; $x <= (8 - $days_in_this_week); $x++) {
                $calendar .= '<td class="calendar-day-np"> </td>';
            }
        }

        /* final row */
        $calendar .= '</tr>';

        /* end the table */
        $calendar .= '</table>';

        /* all done, return result */
        return $calendar;
    }

    /**
     * @param ObjectManager $em
     * @return array
     */
    public static function month_list(ObjectManager $em)
    {
        /**
         * Select last Date of HF
         */
        $last = $em->getRepository(HistoryForecast::class)->findBy(
            array(),
            array('bookDate' => 'DESC'),
            1
        );

        try {
            $begin = new DateTime();
            $end = $last[0]->getBookDate();

            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);
        } catch (Exception $e) {
            throw new AccessDeniedHttpException('Error creating DateTime: ' . $e->getMessage());
        }

        $lastMonth = null;
        $months = array();

        /* @var $dt DateTime */
        foreach ($period as $dt) {

            if ($dt > $end) {
                break;
            }

            if ($lastMonth !== $dt->format('m-Y')) {
                $months[] = $dt->format('m-Y');

            }

            $lastMonth = $dt->format('m-Y');
        }

        return $months;

    }

    /**
     * @param string $entity
     * @param int $id
     * @param string $to_set
     * @param ObjectManager $em
     * @return array
     * @throws HelperException
     */
    public static function toggleActive(string $entity, int $id, string $to_set, ObjectManager $em)
    {

        /**
         * Load Entity
         */
        try {
            $entity = self::repo($entity, $em, $id);
        } catch (Exception $e) {
            throw new HelperException('Could not find Repository');
        }

        /**
         * Check if we have "isActive"
         */
        if (!method_exists($entity, 'getIsActive') && !$entity instanceof User) {
            throw new HelperException('Entity has no "getIsActive" Method');
        }

        $set = $to_set === 'activate' ? true : false;
        $set_new = $to_set === 'activate' ? 'deactivate' : 'activate';
        $html_new = $to_set === 'activate' ? '<i class="fa fa-ban"></i> Deactivate' : '<i class="fa fa-check"></i> Activate';
        $b_class = $to_set === 'activate' ? 'success' : 'danger';
        $b_html = $to_set === 'activate' ? 'active' : 'inactive';

        if (method_exists($entity, 'setIsActive')) {
            $entity->setIsActive($set);
        }else{
            $entity->setEnabled($set);
        }


        $em->persist($entity);

        try {
            $em->flush();
        } catch (Exception $e) {
            throw new HelperException('MySQL Error: ' . $e->getMessage());
        }

        return array(
            'to_set_new' => $set_new,
            'b_class' => $b_class,
            'b_html' => $b_html,
            'html_new' => $html_new,
        );

    }

    /**
     * @param string $entity
     * @param int $id
     * @param ObjectManager $em
     * @return bool
     * @throws HelperException
     */
    public static function removeEntity(string $entity, int $id, ObjectManager $em)
    {

        /**
         * Load Entity
         */
        try {
            $entity = self::repo($entity, $em, $id);
        } catch (Exception $e) {
            throw new HelperException('Could not find Repository');
        }

        /**
         * Detect which Entity and delete accordingly
         */
        try {
            switch (true) {
                case $entity instanceof Roomtype:
                case $entity instanceof Ratetype:
                case $entity instanceof CompetitorCheck:
                case $entity instanceof Budget:
                    return RemoveHelper::remove_simple($entity, $em);
                    break;
                case $entity instanceof User:
                    return RemoveHelper::remove_user($entity, $em);
                    break;
                default:
                    throw new HelperException('Missing Entity in Detection');
            }
        } catch (HelperException $e) {
            throw new HelperException($e->getMessage());
        }


    }

    /**
     * @param string $search
     * @param ObjectManager $em
     * @param int $id
     * @param string $bundle
     * @return bool|object|string|null
     * @throws HelperException
     */
    public static function repo(string $search, ObjectManager $em, int $id, string $bundle = 'App')
    {

        /**
         * Decrypt Value
         */
        $val = ucfirst(SimpleCrypt::dec($search));

        if (!$val) {
            return false;
        }

        try {
            $name = $em->getRepository($bundle . ':' . $val);
        } catch (Exception $e) {
            if ($e instanceof MappingException || $e instanceof ORMException) {
                return false;
            }
            throw new HelperException('Error loading Repository: ' . $e->getMessage());
        }

        if ($id === 0) {
            return $name->getClassName();
        }

        $repo = $em->getRepository($name->getClassName())->find($id);
        if ($repo === null) {
            return false;
        }

        return $repo;

    }

}
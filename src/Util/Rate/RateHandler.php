<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 13.02.2019
 * Time: 15:43
 */

namespace App\Util\Rate;


use App\Entity\Budget;
use App\Entity\HcbSettings;
use App\Entity\HistoryForecast;
use App\Entity\JournalBudget;
use App\Entity\Rateplan;
use App\Entity\Ratetype;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use primus852\SimpleCrypt\SimpleCrypt;

class RateHandler
{

    const RATE_STEPS = array(
        '0-3' => 150,
        '4-6' => 160,
        '7-9' => 170,
        '10-12' => 180,
        '13-15' => 190,
        '16-20' => 200,
        '21-27' => 210,
        '28-33' => 220,
        '34-39' => 240,
        '40-45' => 260,
        '46-50' => 280,
        '51-54' => 300,
    );

    const RATE_COLORS = array(
        150 => array(
            'color' => '#F7FFF6',
            'font' => '#000',
        ),
        160 => array(
            'color' => '#BCEBCB',
            'font' => '#000',
        ),
        170 => array(
            'color' => '#87D68D',
            'font' => '#000',
        ),
        180 => array(
            'color' => '#93B48B',
            'font' => '#000',
        ),
        190 => array(
            'color' => '#7C9885',
            'font' => '#000',
        ),
        200 => array(
            'color' => '#DE9151',
            'font' => '#000',
        ),
        210 => array(
            'color' => '#D64933',
            'font' => '#000',
        ),
        220 => array(
            'color' => '#8491A3',
            'font' => '#000',
        ),
        240 => array(
            'color' => '#2E86AB',
            'font' => '#000',
        ),
        260 => array(
            'color' => '#564138',
            'font' => '#fff',
        ),
        280 => array(
            'color' => '#565554',
            'font' => '#fff',
        ),
        300 => array(
            'color' => '#090909',
            'font' => '#fff',
        ),
    );

    /**
     * @param ObjectManager $em
     * @return array
     */
    public static function gather_all(ObjectManager $em)
    {
        $rates = array();

        $ratetypes = $em->getRepository(Ratetype::class)->findAll();

        foreach ($ratetypes as $ratetype) {

            $rates[] = array(
                'id' => $ratetype->getId(),
                'name' => $ratetype->getName(),
                'nameShort' => $ratetype->getNameShort(),
                'isActive' => $ratetype->getIsActive(),
                'isBase' => $ratetype->getIsBase(),
                'minStay' => $ratetype->getMinStay(),
                'daysAdvance' => $ratetype->getDaysAdvance(),
                'discountAmount' => $ratetype->getDiscountAmount(),
                'discountPercent' => $ratetype->getDiscountPercent(),
                'connections' => 0 //@todo: count connected x?
            );

        }

        return $rates;
    }

    /**
     * @param string $date_string
     * @param ObjectManager $em
     * @return array
     * @throws RateHandlerException
     */
    public static function hf_by_date(string $date_string, ObjectManager $em)
    {

        $list = array();

        $date = DateTime::createFromFormat('d-m-Y', '01-' . $date_string);

        if ($date === false) {
            throw new RateHandlerException('Could not create DateTime: ' . $date_string);
        }

        $start_date = DateTime::createFromFormat('Y-m-d', $date->format('Y') . '-' . $date->format('m') . '-01');
        $start_date->setTime(0, 0, 0);

        try {
            $end_date = new DateTime('Last day of ' . $date->format('F') . ' ' . $date->format('Y'));
            $end_date->setTime(23, 59, 59);
        } catch (Exception $e) {
            throw new RateHandlerException('Could not create EndDate: ' . $e->getMessage());
        }

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->andX(
            Criteria::expr()->gte('bookDate', $start_date),
            Criteria::expr()->lte('bookDate', $end_date)
        ));

        $hfs = $em->getRepository(HistoryForecast::class)->matching($criteria);

        /**
         * Combine them with Rateplan
         * @var $hf HistoryForecast
         */
        foreach ($hfs as $hf) {

            $rateplan = $em->getRepository(Rateplan::class)->findOneBy(array(
                'bookDate' => $hf->getBookDate()
            ));

            $price = $rateplan === null ? 0 : $rateplan->getPrice();
            $id = $rateplan === null ? 'new' : $rateplan->getId();
            $suggested = self::suggested_rate($hf->getBookedRooms());
            $cxl = $rateplan === null ? 'reg' : ($rateplan->getCxl() === null ? 'reg' : $rateplan->getCxl());
            $cxlText = $rateplan === null ? '24hrs' : ($rateplan->getCxl() === null ? '24hrs' : $rateplan->getCxl() . ' weeks');

            $list[$hf->getBookDate()->format('Y-m-d')] = array(
                'rate' => $price,
                'arrivals' => $hf->getArrivalRooms(),
                'departures' => $hf->getDepartureRooms(),
                'pax' => $hf->getPax(),
                'cxl' => $cxl,
                'cxlText' => $cxlText,
                'occ' => (100 * $hf->getBookedRooms() / $hf->getTotalRooms()),
                'avail' => $hf->getTotalRooms() - $hf->getBookedRooms(),
                'booked' => $hf->getBookedRooms(),
                'field' => SimpleCrypt::enc('Rateplan'),
                'id' => $id,
                'suggested' => array(
                    'rate' => $suggested,
                    'useClass' => self::use_class_suggested($price, $suggested)
                ),
            );

        }


        return $list;

    }

    /**
     * @param DateTime $date
     * @param ObjectManager $em
     * @return array
     * @throws RateHandlerException
     */
    public static function stats(DateTime $date, ObjectManager $em)
    {

        /**
         * Next Day
         */
        $next_date = clone $date;
        $next_date->modify('+1 day');

        /**
         * Previous Day
         */
        $prev_date = clone $date;
        $prev_date->modify('-1 day');

        /**
         * This Month
         */
        $month_start = clone $date;
        $month_end = clone $date;
        $month_start->setDate($date->format('Y'), $date->format('m'), '1')->setTime(0, 0, 0);
        $month_end->modify('Last Day of ' . $date->format('F') . ' ' . $date->format('Y'))->setTime(23, 59, 59);

        /**
         * Next Month
         */
        $month_next_start = clone $date;
        $month_next_end = clone $date;
        $month_next_start->modify('next month');
        $month_next_start->setDate($month_next_start->format('Y'), $month_next_start->format('m'), '1')->setTime(0, 0, 0);
        $month_next_end->modify('Last Day of ' . $month_next_start->format('F') . ' ' . $month_next_start->format('Y'))->setTime(23, 59, 59);

        /**
         * Get Breakfast
         */
        $bf = $em->getRepository(HcbSettings::class)->findOneBy(array(
            'name' => 'bf',
        ));
        if ($bf === null) {
            throw new RateHandlerException('No Setting for "bf"');
        }

        /**
         * Get all from this Month
         */
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->andX(
            Criteria::expr()->gte('bookDate', $month_start),
            Criteria::expr()->lte('bookDate', $month_end)
        ));
        $hf_month = $em->getRepository(HistoryForecast::class)->matching($criteria);

        /**
         * Get all from next Month
         */
        $criteriaNext = Criteria::create();
        $criteriaNext->where(Criteria::expr()->andX(
            Criteria::expr()->gte('bookDate', $month_next_start),
            Criteria::expr()->lte('bookDate', $month_next_end)
        ));
        $hf_month_next = $em->getRepository(HistoryForecast::class)->matching($criteriaNext);

        /**
         * Get all Other Revenue from this Month
         */
        $criteriaJb = Criteria::create();
        $criteriaJb->where(Criteria::expr()->andX(
            Criteria::expr()->gte('bookDate', $month_start),
            Criteria::expr()->lte('bookDate', $month_end)
        ));
        $jb_month = $em->getRepository(JournalBudget::class)->matching($criteriaJb);

        /**
         * Get all Other Revenue from next Month
         */
        $criteriaJbNext = Criteria::create();
        $criteriaJbNext->where(Criteria::expr()->andX(
            Criteria::expr()->gte('bookDate', $month_next_start),
            Criteria::expr()->lte('bookDate', $month_next_end)
        ));
        $jb_month_next = $em->getRepository(JournalBudget::class)->matching($criteriaJbNext);

        /**
         * Get total Stats for this month
         */
        $acc = 0;
        $rooms = 0;
        $pax = 0;
        $roomsTotal = 0;
        foreach ($hf_month as $item) {
            $acc += $item->getRevenue();
            $rooms += $item->getBookedRooms();
            $pax += $item->getPax();
            $roomsTotal += $item->getTotalRooms();
        }
        /**
         * Calc Avg. Rate this month
         */
        $avg_rate = number_format(($acc + ($pax * ($bf->getSetting() / 119 * 100))) / $rooms, 2);

        /**
         * Calc Occupancy this Month
         */
        $avg_occ = number_format(($rooms * 100 / $roomsTotal), 2);

        /**
         * Get total Stats for next month
         */
        $acc_next = 0;
        $rooms_next = 0;
        $pax_next = 0;
        $roomsTotal_next = 0;
        foreach ($hf_month_next as $item) {
            $acc_next += $item->getRevenue();
            $rooms_next += $item->getBookedRooms();
            $pax_next += $item->getPax();
            $roomsTotal_next += $item->getTotalRooms();
        }

        /**
         * Calc Avg. Rate next month
         */
        $avg_rate_next = number_format(($acc_next + ($pax_next * ($bf->getSetting() / 119 * 100))) / $rooms_next, 2);

        /**
         * Calc Occupancy next Month
         */
        $avg_occ_next = number_format(($rooms_next * 100 / $roomsTotal_next), 2);

        /**
         * Get total Other Rev for this month
         */
        $other_rev = 0;
        foreach($jb_month as $item){
            $other_rev += $item->getTransTotal();
        }

        /**
         * Get total Other Rev for next month
         */
        $other_rev_next = 0;
        foreach($jb_month_next as $item){
            $other_rev_next += $item->getTransTotal();
        }

        /**
         * Get Projected this month
         */
        $projected = $em->getRepository(Budget::class)->findOneBy(array(
            'month' => $month_start->format('n'),
            'year' => $month_start->format('Y')
        ));

        $acc_budget = 0;
        $other_budget = 0;
        $nights_budget = 0;
        $occ_budget = 0;
        $rate_budget = 0;
        if($projected !== null){
            $acc_budget = $projected->getAccomodation();
            $other_budget = $projected->getOtherRevenue();
            $nights_budget = $projected->getRoomNights();
            $occ_budget = $projected->getOccupancy();
            $rate_budget = $projected->getRate();
        }

        /**
         * Get Projected next month
         */
        $projected_next = $em->getRepository(Budget::class)->findOneBy(array(
            'month' => $month_next_start->format('n'),
            'year' => $month_next_start->format('Y')
        ));

        $acc_budget_next = 0;
        $other_budget_next = 0;
        $nights_budget_next = 0;
        $occ_budget_next = 0;
        $rate_budget_next = 0;
        if($projected_next !== null){
            $acc_budget_next = $projected_next->getAccomodation();
            $other_budget_next = $projected_next->getOtherRevenue();
            $nights_budget_next = $projected_next->getRoomNights();
            $occ_budget_next = $projected_next->getOccupancy();
            $rate_budget_next = $projected_next->getRate();
        }


        /**
         * Get all from today
         */
        $hf_today = $em->getRepository(HistoryForecast::class)->findOneBy(array(
            'bookDate' => $date,
        ));

        if ($hf_today === null) {
            throw new RateHandlerException('No Stats for ' . $date->format('Y-m-d'));
        }

        /**
         * Get all from yesterday
         */
        $hf_next = $em->getRepository(HistoryForecast::class)->findOneBy(array(
            'bookDate' => $next_date,
        ));

        if ($hf_next === null) {
            throw new RateHandlerException('No Stats for ' . $next_date->format('Y-m-d'));
        }

        /**
         * Get all from yesterday
         */
        $hf_prev = $em->getRepository(HistoryForecast::class)->findOneBy(array(
            'bookDate' => $prev_date,
        ));

        if ($hf_prev === null) {
            throw new RateHandlerException('No Stats for ' . $prev_date->format('Y-m-d'));
        }

        /**
         * Get missing projected
         */
        $missing_acc = ($acc_budget - $acc) > 0 ? '<span class="text-danger">-'.number_format(($acc_budget - $acc),2).'&euro;</span>' : '<span class="text-success">+'.number_format(($acc - $acc_budget),2).'&euro;</span>';
        $missing_acc_next = ($acc_budget_next - $acc_next) > 0 ? '<span class="text-danger">-'.number_format(($acc_budget_next - $acc_next),2).'&euro;</span>' : '<span class="text-success">-'.number_format(($acc_next - $acc_budget_next),2).'&euro;</span>';

        $missing_other = ($other_budget - $other_rev) > 0 ? '<span class="text-danger">-'.number_format(($other_budget - $other_rev),2).'&euro;</span>' : '<span class="text-success">+'.number_format(($other_rev - $other_budget),2).'&euro;</span>';
        $missing_other_next = ($other_budget_next - $other_rev_next) > 0 ? '<span class="text-danger">-'.number_format(($other_budget_next - $other_rev_next),2).'&euro;</span>' : '<span class="text-success">-'.number_format(($other_rev_next - $other_budget_next),2).'&euro;</span>';

        $missing_nights = ($nights_budget - $rooms) > 0 ? '<span class="text-danger">-'.number_format(($nights_budget - $rooms),0).'</span>' : '<span class="text-success">+'.number_format(($rooms - $nights_budget)).'</span>';
        $missing_nights_next = ($nights_budget_next - $rooms_next) > 0 ? '<span class="text-danger">-'.number_format(($nights_budget_next - $rooms_next),0).'</span>' : '<span class="text-success">+'.number_format(($rooms_next - $nights_budget_next)).'</span>';

        $missing_occ = ($occ_budget - $avg_occ) > 0 ? '<span class="text-danger">-'.number_format(($occ_budget - $avg_occ),2).'&percnt;</span>' : '<span class="text-success">+'.number_format(($avg_occ - $occ_budget),2).'&percnt;</span>';
        $missing_occ_next = ($occ_budget_next - $avg_occ_next) > 0 ? '<span class="text-danger">-'.number_format(($occ_budget_next - $avg_occ_next),2).'&percnt;</span>' : '<span class="text-success">-'.number_format(($avg_occ_next - $occ_budget_next),2).'&percnt;</span>';

        $missing_rate = ($rate_budget - $avg_rate) > 0 ? '<span class="text-danger">-'.number_format(($rate_budget - $avg_rate),2).'&euro;</span>' : '<span class="text-success">+'.number_format(($avg_rate - $rate_budget),2).'&euro;</span>';
        $missing_rate_next = ($rate_budget_next - $avg_rate_next) > 0 ? '<span class="text-danger">-'.number_format(($rate_budget_next - $avg_rate_next),2).'&euro;</span>' : '<span class="text-success">-'.number_format(($avg_rate_next - $rate_budget_next),2).'&euro;</span>';

        /**
         * Get Stats for Month
         */

        return array(
            'today' => array(
                'date' => $month_start,
                'projected' => array(
                    'acc' => $acc_budget,
                    'nights' => $nights_budget,
                    'occ' => $occ_budget,
                    'rate' => $rate_budget,
                    'other' => $other_budget,
                ),
                'missing' => array(
                    'acc' => $missing_acc,
                    'other' => $missing_other,
                    'rate' => $missing_rate,
                    'nights' => $missing_nights,
                    'occ' => $missing_occ,
                ),
                'accomodation' => number_format($acc, 2),
                'avg_rate' => $avg_rate,
                'avg_occ' => $avg_occ,
                'other_rev' => number_format($other_rev,2),
                'arrivals' => $hf_today->getArrivalRooms(),
                'departures' => $hf_today->getDepartureRooms(),
                'breakfasts' => $hf_prev->getPax(),
                'pax' => $hf_today->getPax(),
                'sell' => $hf_today->getTotalRooms() - $hf_today->getBookedRooms(),
                'occ' => number_format($hf_today->getBookedRooms() * 100 / $hf_today->getTotalRooms(), 2),
                'nights' => $rooms,
                'rate' => number_format(($hf_today->getRevenue() + ($hf_today->getPax() * ($bf->getSetting() / 119 * 100))) / $hf_today->getBookedRooms(), 2),
            ),
            'tomorrow' => array(
                'date' => $month_next_start,
                'projected' => array(
                    'acc' => $acc_budget_next,
                    'occ' => $occ_budget_next,
                    'nights' => $nights_budget_next,
                    'rate' => $rate_budget_next,
                    'other' => $other_budget_next,
                ),
                'missing' => array(
                    'acc' => $missing_acc_next,
                    'other' => $missing_other_next,
                    'rate' => $missing_rate_next,
                    'nights' => $missing_nights_next,
                    'occ' => $missing_occ_next,
                ),
                'accomodation' => number_format($acc_next, 2),
                'avg_rate' => $avg_rate_next,
                'avg_occ' => $avg_occ_next,
                'other_rev' => number_format($other_rev_next,2),
                'arrivals' => $hf_next->getArrivalRooms(),
                'departures' => $hf_next->getDepartureRooms(),
                'pax' => $hf_next->getPax(),
                'breakfasts' => $hf_today->getPax(),
                'sell' => $hf_next->getTotalRooms() - $hf_next->getBookedRooms(),
                'occ' => number_format($hf_next->getBookedRooms() * 100 / $hf_next->getTotalRooms(), 2),
                'nights' => $rooms_next,
                'rate' => number_format(($hf_next->getRevenue() + ($hf_next->getPax() * ($bf->getSetting() / 119 * 100))) / $hf_next->getBookedRooms(), 2),
            ),
        );

    }

    /**
     * @param float $rate
     * @param int $pax
     * @param ObjectManager $em
     * @return array
     * @throws RateHandlerException
     */
    public static function city_tax(float $rate, int $pax, ObjectManager $em)
    {

        /**
         * Get BF Settings
         */
        $settings = $em->getRepository(HcbSettings::class)->findOneBy(array(
            'name' => 'bf'
        ));

        if ($settings === null) {
            throw new RateHandlerException('Could not find BF Setting');
        }

        /**
         * Get BF
         */
        $bf = (float)$settings->getSetting();

        /**
         * Rate excluding BF
         */
        $rate_no_bf = ($rate - ($pax * $bf));

        /**
         * Rate excl. BF excl VAT
         */
        $rate_no_bf_net = $rate_no_bf / 107 * 100;

        /**
         * City Tax
         */
        $city_tax = $rate_no_bf_net / 105 * 5 / 100 * 107;

        /**
         * Final rate no Tax
         */
        $final_rate_no_tax = $rate - $city_tax;

        return array(
            'rate_no_tax' => number_format($final_rate_no_tax, 2),
            'city_tax' => number_format($city_tax, 2),
        );
    }

    /**
     * @param float $rate
     * @param float $suggested_rate
     * @return string
     */
    private static function use_class_suggested(float $rate, float $suggested_rate)
    {
        /**
         * Get absolute difference
         */
        $diff = abs($rate - $suggested_rate);

        $useClass = 'success';

        if ($diff >= 10) {
            $useClass = 'warning';
        }

        if ($diff >= 30) {
            $useClass = 'danger';
        }

        return $useClass;
    }

    /**
     * @param int $bookedRooms
     * @return bool|int|string
     */
    private static function suggested_rate(int $bookedRooms)
    {

        foreach (self::RATE_STEPS as $val => $rate) {

            $pre = explode('-', $val);
            $from = (int)$pre[0];
            $to = (int)$pre[1];

            if ($bookedRooms >= $from && $bookedRooms <= $to) {
                return $rate;
            }

        }

        return 0;

    }

}
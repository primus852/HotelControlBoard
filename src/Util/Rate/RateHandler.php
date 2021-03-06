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
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use primus852\SimpleCrypt\SimpleCrypt;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
     * @param DateTime $start
     * @param int $nights
     * @param int $pax
     * @param ObjectManager $em
     * @return array
     * @throws RateHandlerException
     */
    public static function rate_check(DateTime $start, int $nights, int $pax, ObjectManager $em)
    {

        /**
         * Get Pax Modifier
         */
        $isSingle = false;
        $isDouble = false;
        if ($pax === 1) {
            $add = 0;
            $isSingle = true;
        } elseif ($pax === 2) {
            $mod = $em->getRepository(HcbSettings::class)->findOneBy(array(
                'name' => 'add_double',
            ));
            if ($mod === null) {
                throw new RateHandlerException('No Setting for "add_double" found');
            }
            $add = $mod->getSetting();
            $isDouble = true;
        } elseif ($pax === 3) {
            $mod = $em->getRepository(HcbSettings::class)->findOneBy(array(
                'name' => 'add_triple',
            ));
            if ($mod === null) {
                throw new RateHandlerException('No Setting for "add_triple" found');
            }
            $add = $mod->getSetting();
        } else {
            throw new RateHandlerException('Invalid Pax, only 1-3 allowed');
        }

        /**
         * Check if we have a BaseRate
         */
        $baseRate = $em->getRepository(Ratetype::class)->findOneBy(array(
            'isBase' => true,
        ));

        if ($baseRate === null) {
            throw new RateHandlerException('No BaseRate defined, please set it first');
        }

        /**
         * Get end Date
         */
        $end = clone $start;
        $end->modify('+' . $nights . ' days');

        /**
         * Get Checkout Date
         */
        $checkout = clone $end;

        /**
         * Days advance
         */
        $now = new DateTime();
        $diff = $now->diff($start);
        $daysAdv = $diff->days;

        /**
         * Create Interval to get every single day
         */
        try {
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($start, $interval, $end);
        } catch (Exception $e) {
            throw new AccessDeniedHttpException('Error creating DateTime: ' . $e->getMessage());
        }

        $days = array(
            'summary' => array(),
            'checkout' => $checkout,
        );

        /* @var $dt DateTime */
        $countDay = 0;
        foreach ($period as $dt) {

            $countDay++;

            /**
             * Get todays Rate
             */
            $rate = $em->getRepository(Rateplan::class)->findOneBy(array(
                'bookDate' => $dt,
            ));

            if ($rate === null) {
                throw new RateHandlerException('Could not find rates for specified date');
            }

            /**
             * Get alle Ratetypes
             */
            $ratetypes = $em->getRepository(Ratetype::class)->findBy(array(
                'isActive' => true,
            ));

            foreach ($ratetypes as $ratetype) {

                $available = true;

                /**
                 * Get Color
                 */
                try {
                    $priceColor = self::RATE_COLORS[(int)$rate->getPrice()]['color'];
                    $priceColorFont = self::RATE_COLORS[(int)$rate->getPrice()]['font'];
                } catch (Exception $e) {
                    $priceColor = '#fff';
                    $priceColorFont = '#000';
                }

                /**
                 * Get Regular Price
                 */
                $price = $rate->getPrice() + $add;

                /**
                 * Get fixed Single
                 */
                if ($ratetype->getFixedSingle() !== null && $ratetype->getFixedSingle() > 0) {
                    if ($isSingle) {
                        $price = $ratetype->getFixedSingle();
                    }
                }

                /**
                 * Get fixed Double
                 */
                if ($ratetype->getFixedDouble() !== null && $ratetype->getFixedDouble() > 0) {
                    if ($isDouble) {
                        $price = $ratetype->getFixedDouble();
                    }
                }

                /**
                 * Get Discount
                 */
                if ($ratetype->getDiscountAmount() > 0) {

                    $discount = $ratetype->getDiscountAmount();
                    if ($ratetype->getDiscountPercent()) {
                        $discount = $price / 100 * $ratetype->getDiscountAmount();
                    }

                    $price = $price - $discount;

                }

                $priceTooltip = $price;

                /**
                 * Get Min Stay
                 */
                if ($nights < $ratetype->getMinStay()) {
                    $price = 'N/A';
                    $priceTooltip = 'min. Stay ' . $ratetype->getMinStay();
                    $priceColor = '#fff';
                    $priceColorFont = '#dc3545';
                    $available = false;
                }

                /**
                 * Check if we meet max Advance Days
                 */
                if ($ratetype->getMaxAdvance() > 0) {
                    if ($daysAdv > $ratetype->getMaxAdvance()) {
                        $price = 'N/A';
                        $priceTooltip = 'max. advance Days ' . $ratetype->getMaxAdvance() . ' (is ' . $daysAdv . ')';
                        $priceColor = '#fff';
                        $priceColorFont = '#dc3545';
                        $available = false;
                    }
                }

                /**
                 * Check if we meet min Advance Days
                 */
                if ($ratetype->getDaysAdvance() > 1) {
                    if ($daysAdv < $ratetype->getDaysAdvance()) {
                        $price = 'N/A';
                        $priceTooltip = 'min. advance Days ' . $ratetype->getDaysAdvance() . ' (is ' . $daysAdv . ')';
                        $priceColor = '#fff';
                        $priceColorFont = '#dc3545';
                        $available = false;
                    }
                }

                /**
                 * Check if we have a fair date
                 */
                if ($rate->getCxl() !== null && $ratetype->getFairsAllowed() === false) {
                    $price = 'N/A';
                    $priceTooltip = 'Fair Date: ' . $rate->getCxl() . ' weeks CXL';
                    $priceColor = '#fff';
                    $priceColorFont = '#dc3545';
                    $available = false;
                }

                /**
                 * Check if Day is allowed
                 */
                $curDay = (int)$dt->format('N');

                /**
                 * Monday
                 */
                if ($curDay === 1 && $ratetype->getAllowMon() === false) {
                    $price = 'N/A';
                    $priceTooltip = 'Day not allowed: Monday';
                    $priceColor = '#fff';
                    $priceColorFont = '#dc3545';
                    $available = false;
                }

                /**
                 * Tuesday
                 */
                if ($curDay === 2 && $ratetype->getAllowTue() === false) {
                    $price = 'N/A';
                    $priceTooltip = 'Day not allowed: Tuesday';
                    $priceColor = '#fff';
                    $priceColorFont = '#dc3545';
                    $available = false;
                }

                /**
                 * Wednesday
                 */
                if ($curDay === 3 && $ratetype->getAllowWed() === false) {
                    $price = 'N/A';
                    $priceTooltip = 'Day not allowed: Wednesday';
                    $priceColor = '#fff';
                    $priceColorFont = '#dc3545';
                    $available = false;
                }

                /**
                 * Thursday
                 */
                if ($curDay === 4 && $ratetype->getAllowThu() === false) {
                    $price = 'N/A';
                    $priceTooltip = 'Day not allowed: Thursday';
                    $priceColor = '#fff';
                    $priceColorFont = '#dc3545';
                    $available = false;
                }

                /**
                 * Friday
                 */
                if ($curDay === 5 && $ratetype->getAllowFri() === false) {
                    $price = 'N/A';
                    $priceTooltip = 'Day not allowed: Friday';
                    $priceColor = '#fff';
                    $priceColorFont = '#dc3545';
                    $available = false;
                }

                /**
                 * Saturday
                 */
                if ($curDay === 6 && $ratetype->getAllowSat() === false) {
                    $price = 'N/A';
                    $priceTooltip = 'Day not allowed: Saturday';
                    $priceColor = '#fff';
                    $priceColorFont = '#dc3545';
                    $available = false;
                }

                /**
                 * Sunday
                 */
                if ($curDay === 7 && $ratetype->getAllowSun() === false) {
                    $price = 'N/A';
                    $priceTooltip = 'Day not allowed: Sunday';
                    $priceColor = '#fff';
                    $priceColorFont = '#dc3545';
                    $available = false;
                }


                $days[$dt->format('Y-m-d')][$ratetype->getNameShort()] = array(
                    'rateType' => $ratetype->getName(),
                    'rateNote' => $ratetype->getNote(),
                    'price' => $price,
                    'tooltip' => $priceTooltip,
                    'color' => $priceColor,
                    'colorFont' => $priceColorFont,
                );

                /**
                 * Add Summary per RateCode
                 */
                if (!array_key_exists($ratetype->getNameShort(), $days['summary'])) {
                    $days['summary'][$ratetype->getNameShort()] = array(
                        'available' => true,
                        'total' => 0,
                        'avg' => 0,
                        'tooltip' => $ratetype->getNote(),
                        'name' => $ratetype->getName(),
                    );
                }
                if ($available === false) {
                    $days['summary'][$ratetype->getNameShort()]['available'] = false;
                } else {
                    $days['summary'][$ratetype->getNameShort()]['total'] += (float)$price;
                    $days['summary'][$ratetype->getNameShort()]['avg'] = $days['summary'][$ratetype->getNameShort()]['total'] / $countDay;
                }

            }

        }

        return $days;

    }

    /**
     * @param ObjectManager $em
     * @return array
     */
    public static function gather_all(ObjectManager $em)
    {
        $rates = array();

        $ratetypes = $em->getRepository(Ratetype::class)->findAll();

        foreach ($ratetypes as $ratetype) {

            $symbol = $ratetype->getDiscountPercent() ? '&percnt;' : '&euro;';
            $dcString = $ratetype->getDiscountAmount() . $symbol;
            if ($ratetype->getFixedSingle() !== null || $ratetype->getFixedDouble() !== null) {
                $dcString = number_format($ratetype->getFixedSingle(), 2) . '/' . number_format($ratetype->getFixedDouble(), 2);
            }

            $rates[] = array(
                'id' => $ratetype->getId(),
                'name' => $ratetype->getName(),
                'note' => $ratetype->getNote(),
                'nameShort' => $ratetype->getNameShort(),
                'isActive' => $ratetype->getIsActive(),
                'isBase' => $ratetype->getIsBase(),
                'minStay' => $ratetype->getMinStay(),
                'maxAdvance' => $ratetype->getMaxAdvance(),
                'fairsAllowed' => $ratetype->getFairsAllowed(),
                'daysAdvance' => $ratetype->getDaysAdvance(),
                'discount' => $dcString,
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
         * Add Breakfast net to Acc
         */
        $acc = $acc + ($pax * ($bf->getSetting() / 119 * 100));

        /**
         * Calc Avg. Rate this month
         */
        $avg_rate = $rooms > 0 ? number_format($acc / $rooms, 2) : 0;

        /**
         * Calc Occupancy this Month
         */
        $avg_occ = $roomsTotal > 0 ? number_format(($rooms * 100 / $roomsTotal), 2) : 0;

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
         * Add Breakfast net to Acc
         */
        $acc_next = $acc_next + ($pax_next * ($bf->getSetting() / 119 * 100));

        /**
         * Calc Avg. Rate this month
         */
        $avg_rate_next = $rooms_next > 0 ? number_format($acc_next / $rooms_next, 2) : 0;

        /**
         * Calc Occupancy next Month
         */
        $avg_occ_next = $roomsTotal_next > 0 ? number_format(($rooms_next * 100 / $roomsTotal_next), 2) : 0;

        /**
         * Get total Other Rev for this month
         */
        $other_rev = 0;
        foreach ($jb_month as $item) {
            $other_rev += $item->getTransTotal();
        }

        /**
         * Get total Other Rev for next month
         */
        $other_rev_next = 0;
        foreach ($jb_month_next as $item) {
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
        if ($projected !== null) {
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
        if ($projected_next !== null) {
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
        $missing_acc = ($acc_budget - $acc) > 0 ? '<span class="text-danger">-' . number_format(($acc_budget - $acc), 2) . '&euro;</span>' : '<span class="text-success">+' . number_format(($acc - $acc_budget), 2) . '&euro;</span>';
        $missing_acc_next = ($acc_budget_next - $acc_next) > 0 ? '<span class="text-danger">-' . number_format(($acc_budget_next - $acc_next), 2) . '&euro;</span>' : '<span class="text-success">-' . number_format(($acc_next - $acc_budget_next), 2) . '&euro;</span>';

        $missing_other = ($other_budget - $other_rev) > 0 ? '<span class="text-danger">-' . number_format(($other_budget - $other_rev), 2) . '&euro;</span>' : '<span class="text-success">+' . number_format(($other_rev - $other_budget), 2) . '&euro;</span>';
        $missing_other_next = ($other_budget_next - $other_rev_next) > 0 ? '<span class="text-danger">-' . number_format(($other_budget_next - $other_rev_next), 2) . '&euro;</span>' : '<span class="text-success">-' . number_format(($other_rev_next - $other_budget_next), 2) . '&euro;</span>';

        $missing_nights = ($nights_budget - $rooms) > 0 ? '<span class="text-danger">-' . number_format(($nights_budget - $rooms), 0) . '</span>' : '<span class="text-success">+' . number_format(($rooms - $nights_budget)) . '</span>';
        $missing_nights_next = ($nights_budget_next - $rooms_next) > 0 ? '<span class="text-danger">-' . number_format(($nights_budget_next - $rooms_next), 0) . '</span>' : '<span class="text-success">+' . number_format(($rooms_next - $nights_budget_next)) . '</span>';

        $missing_occ = ($occ_budget - $avg_occ) > 0 ? '<span class="text-danger">-' . number_format(($occ_budget - $avg_occ), 2) . '&percnt;</span>' : '<span class="text-success">+' . number_format(($avg_occ - $occ_budget), 2) . '&percnt;</span>';
        $missing_occ_next = ($occ_budget_next - $avg_occ_next) > 0 ? '<span class="text-danger">-' . number_format(($occ_budget_next - $avg_occ_next), 2) . '&percnt;</span>' : '<span class="text-success">-' . number_format(($avg_occ_next - $occ_budget_next), 2) . '&percnt;</span>';

        $missing_rate = ($rate_budget - $avg_rate) > 0 ? '<span class="text-danger">-' . number_format(($rate_budget - $avg_rate), 2) . '&euro;</span>' : '<span class="text-success">+' . number_format(($avg_rate - $rate_budget), 2) . '&euro;</span>';
        $missing_rate_next = ($rate_budget_next - $avg_rate_next) > 0 ? '<span class="text-danger">-' . number_format(($rate_budget_next - $avg_rate_next), 2) . '&euro;</span>' : '<span class="text-success">-' . number_format(($avg_rate_next - $rate_budget_next), 2) . '&euro;</span>';

        /**
         * Get Stats for Month
         */
        $rate_today = $hf_today->getBookedRooms() > 0 ? ($hf_today->getRevenue() + ($hf_today->getPax() * ($bf->getSetting() / 119 * 100))) / $hf_today->getBookedRooms() : 0;
        $rate_tomorrow = $hf_next->getBookedRooms() > 0 ? ($hf_next->getRevenue() + ($hf_next->getPax() * ($bf->getSetting() / 119 * 100))) / $hf_next->getBookedRooms() : 0;

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
                'other_rev' => number_format($other_rev, 2),
                'arrivals' => $hf_today->getArrivalRooms(),
                'departures' => $hf_today->getDepartureRooms(),
                'breakfasts' => $hf_prev->getPax(),
                'pax' => $hf_today->getPax(),
                'sell' => $hf_today->getTotalRooms() - $hf_today->getBookedRooms(),
                'occ' => number_format($hf_today->getBookedRooms() * 100 / $hf_today->getTotalRooms(), 2),
                'nights' => $rooms,
                'rate' => number_format($rate_today, 2),
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
                'other_rev' => number_format($other_rev_next, 2),
                'arrivals' => $hf_next->getArrivalRooms(),
                'departures' => $hf_next->getDepartureRooms(),
                'pax' => $hf_next->getPax(),
                'breakfasts' => $hf_today->getPax(),
                'sell' => $hf_next->getTotalRooms() - $hf_next->getBookedRooms(),
                'occ' => number_format($hf_next->getBookedRooms() * 100 / $hf_next->getTotalRooms(), 2),
                'nights' => $rooms_next,
                'rate' => number_format($rate_tomorrow, 2),
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

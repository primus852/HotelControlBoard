<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 13.02.2019
 * Time: 15:43
 */

namespace App\Util\Rate;


use App\Entity\HistoryForecast;
use App\Entity\Rateplan;
use App\Entity\Ratetype;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
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

    /**
     * @param ObjectManager $em
     * @return array
     */
    public static function gather_all(ObjectManager $em)
    {
        $rates = array();

        $ratetypes = $em->getRepository(Ratetype::class)->findAll();

        foreach($ratetypes as $ratetype){

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

        $date = \DateTime::createFromFormat('d-m-Y', '01-'.$date_string);

        if ($date === false) {
            throw new RateHandlerException('Could not create DateTime: ' . $date_string);
        }

        $start_date = \DateTime::createFromFormat('Y-m-d',$date->format('Y').'-'.$date->format('m').'-01');
        $start_date->setTime(0,0,0);

        try{
            $end_date = new \DateTime('Last day of '.$date->format('F').' '.$date->format('Y'));
            $end_date->setTime(23,59,59);
        }catch (\Exception $e){
            throw new RateHandlerException('Could not create EndDate: '.$e->getMessage());
        }

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->andX(
            Criteria::expr()->gte('bookDate',$start_date),
            Criteria::expr()->lte('bookDate',$end_date)
        ));

        $hfs = $em->getRepository(HistoryForecast::class)->matching($criteria);

        /**
         * Combine them with Rateplan
         * @var $hf HistoryForecast
         */
        foreach($hfs as $hf){

            $rateplan = $em->getRepository(Rateplan::class)->findOneBy(array(
                'bookDate' => $hf->getBookDate()
            ));

            $price = $rateplan === null ? 0 : $rateplan->getPrice();
            $id = $rateplan === null ? 'new' : $rateplan->getId();
            $suggested = self::suggested_rate($hf->getBookedRooms());

            $list[$hf->getBookDate()->format('Y-m-d')] = array(
                'rate' => $price,
                'arrivals' => $hf->getArrivalRooms(),
                'departures' => $hf->getDepartureRooms(),
                'pax' => $hf->getPax(),
                'occ' => (100 * $hf->getBookedRooms() / $hf->getTotalRooms()),
                'avail' => $hf->getTotalRooms() - $hf->getBookedRooms(),
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

        if($diff >= 10){
            $useClass = 'warning';
        }

        if($diff >= 30){
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

        foreach(self::RATE_STEPS as $val => $rate){

            $pre = explode('-',$val);
            $from = (int) $pre[0];
            $to = (int) $pre[1];

            if($bookedRooms >= $from && $bookedRooms <= $to){
                return $rate;
            }

        }

        return 0;

    }

}
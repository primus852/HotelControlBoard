<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 16.03.2019
 * Time: 13:52
 */

namespace App\Util\Xml;


use App\Entity\Availability;
use App\Entity\HistoryForecast;
use App\Entity\JournalBudget;
use App\Entity\Roomtype;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;

class HcbXmlReader
{

    const FOLDER = 'reports';

    /**
     * @param ObjectManager $em
     * @throws HcbXmlReaderException
     */
    public static function jb(ObjectManager $em)
    {


        try {
            $crawler = self::xml('jb');
        } catch (HcbXmlReaderException $e) {
            throw new HcbXmlReaderException($e->getMessage());
        }

        /**
         * Get Report Type
         */
        $r_type = $crawler->filterXPath('//FINJRNLBYTRANS');

        if($r_type->count() === 0){
            throw new HcbXmlReaderException('Invalid Report Type');
        }

        $days = $crawler->filterXPath('//G_TRX_CHAR_DATE');

        foreach ($days->getIterator() as $day) {

            $dayCrawler = new Crawler($day);


            /**
             * Get Date
             */
            $date = \DateTime::createFromFormat('d-M-y',$dayCrawler->filterXPath('//BUSINESS_DATE')->text());

            if($date === false){
                throw new HcbXmlReaderException('Could not convert to Date: '.$dayCrawler->filterXPath('//BUSINESS_DATE')->text());
            }

            $transNo = $dayCrawler->filterXPath('//TRX_NO')->text();
            $transDesc = $dayCrawler->filterXPath('//TRX_DESC')->text();
            $transDebit = (float) $dayCrawler->filterXPath('//CASHIER_DEBIT')->text();
            $transCredit = (float) $dayCrawler->filterXPath('//CASHIER_CREDIT')->text();
            $transTotal = $transDebit - $transCredit;

            /**
             * Try to find an entry for the same transaction number
             */
            $jb = $em->getRepository(JournalBudget::class)->findOneBy(array(
                'transNo' => $transNo,
            ));

            if($jb === null){
                $jb = new JournalBudget();
                $jb->setTransNo($transNo);
            }

            $jb->setBookDate($date);
            $jb->setTransDesc($transDesc);
            $jb->setTransTotal($transTotal);
            $em->persist($jb);

        }

        try{
            $em->flush();
        }catch (\Exception $e){
            throw new HcbXmlReaderException('MySQL Error: '.$e->getMessage());
        }

    }

            /**
     * @param ObjectManager $em
     * @throws HcbXmlReaderException
     */
    public static function hf(ObjectManager $em)
    {


        try {
            $crawler = self::xml('hf');
        } catch (HcbXmlReaderException $e) {
            throw new HcbXmlReaderException($e->getMessage());
        }

        /**
         * Get Report Type
         */
        $r_type = $crawler->filterXPath('//HISTORY_FORECAST');

        if($r_type->count() === 0){
            throw new HcbXmlReaderException('Invalid Report Type');
        }

        $days = $crawler->filterXPath('//G_CONSIDERED_DATE');

        foreach ($days->getIterator() as $day) {

            $dayCrawler = new Crawler($day);

            /**
             * Get Date
             */
            $date = \DateTime::createFromFormat('d-M-y',$dayCrawler->filterXPath('//CONSIDERED_DATE')->text());

            if($date === false){
                throw new HcbXmlReaderException('Could not convert to Date: '.$dayCrawler->filterXPath('//CONSIDERED_DATE')->text());
            }

            $bookedRooms = (int) $dayCrawler->filterXPath('//NO_ROOMS')->text();
            $totalRooms = (int) $dayCrawler->filterXPath('//INVENTORY_ROOMS')->text();
            $pax = (int) $dayCrawler->filterXPath('//NO_PERSONS')->text();
            $arrivalRooms = (int) $dayCrawler->filterXPath('//ARRIVAL_ROOMS')->text();
            $departureRoome = (int) $dayCrawler->filterXPath('//DEPARTURE_ROOMS')->text();

            /**
             * Try to find an entry for the same date
             */
            $hf = $em->getRepository(HistoryForecast::class)->findOneBy(array(
                'bookDate' => $date,
            ));

            if($hf === null){
                $hf = new HistoryForecast();
                $hf->setBookDate($date);
            }

            $hf->setBookedRooms($bookedRooms);
            $hf->setTotalRooms($totalRooms);
            $hf->setPax($pax);
            $hf->setArrivalRooms($arrivalRooms);
            $hf->setDepartureRooms($departureRoome);
            $em->persist($hf);

        }

        try{
            $em->flush();
        }catch (\Exception $e){
            throw new HcbXmlReaderException('MySQL Error: '.$e->getMessage());
        }

    }

    /**
     * @param ObjectManager $em
     * @throws HcbXmlReaderException
     */
    public static function da(ObjectManager $em)
    {


        try {
            $crawler = self::xml('da');
        } catch (HcbXmlReaderException $e) {
            throw new HcbXmlReaderException($e->getMessage());
        }

        /**
         * Get Report Type
         */
        $r_type = $crawler->filterXPath('//DETAIL_AVAIL');

        if($r_type->count() === 0){
            throw new HcbXmlReaderException('Invalid Report Type');
        }

        $days = $crawler->filterXPath('//DAY');

        foreach ($days->getIterator() as $day) {

            $dayCrawler = new Crawler($day);

            /**
             * Get Date
             */
            if($dayCrawler->filterXPath('//BUSINESS_DATE')->text() === 'Total Available'){
                continue;
            }
            $date = \DateTime::createFromFormat('d.m.y',$dayCrawler->filterXPath('//BUSINESS_DATE')->text());

            if($date === false){
                throw new HcbXmlReaderException('Could not convert to Date: '.$dayCrawler->filterXPath('//BUSINESS_DATE')->text());
            }

            /**
             * Get Rooms
             */
            $rooms = $dayCrawler->filterXPath('//ROOM_TYPE');
            foreach($rooms->getIterator() as $room){

                $roomCrawler = new Crawler($room);

                /**
                 * Get RoomType
                 */
                $short = $roomCrawler->filterXPath('//MARKET_CODE')->text();

                /**
                 * Check if we have the RoomType in the Database
                 */
                $room = $em->getRepository(Roomtype::class)->findOneBy(array(
                    'nameShort' => $short,
                ));

                if($room === null){
                    continue;
                }

                /**
                 * Get available Rooms of this Type
                 */
                $avail = $roomCrawler->filterXPath('//NO_OF_ROOMS1')->text();

                /**
                 * See if we have the Date/Room in the DB
                 */
                $available = $em->getRepository(Availability::class)->findOneBy(array(
                    'bookDate' => $date,
                    'roomType' => $room,
                ));

                if($available === null){
                    $available = new Availability();
                    $available->setBookDate($date);
                    $available->setRoomType($room);
                }

                $available->setAvailable((int)$avail);
                $em->persist($available);


            }

        }

        try{
            $em->flush();
        }catch (\Exception $e){
            throw new HcbXmlReaderException('MySQL Error: '.$e->getMessage());
        }

    }

    /**
     * @param string $name
     * @return Crawler
     * @throws HcbXmlReaderException
     */
    private static function xml(string $name)
    {

        $fs = new Filesystem();
        if (!$fs->exists(self::FOLDER . '/' . $name . '.xml')) {
            throw new HcbXmlReaderException('Could not load File: ' . $name . '.xml');
        }

        $raw = file_get_contents(self::FOLDER . '/' . $name . '.xml');

        try {
            $crawler = new Crawler($raw);
        } catch (\Exception $e) {
            throw new HcbXmlReaderException('Could not load XML to Crawler: ' . $e->getMessage());
        }

        return $crawler;


    }

}
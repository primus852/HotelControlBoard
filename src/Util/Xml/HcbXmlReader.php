<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 16.03.2019
 * Time: 13:52
 */

namespace App\Util\Xml;


use App\Entity\Roomtype;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;

class HcbXmlReader
{

    const FOLDER = 'reports';

    public static function da(ObjectManager $em)
    {


        try {
            $crawler = self::xml('da');
        } catch (HcbXmlReaderException $e) {
            throw new HcbXmlReaderException($e->getMessage());
        }


        $days = $crawler->filterXPath('//DAY');

        foreach ($days->getIterator() as $day) {

            $dayCrawler = new Crawler($day);

            /**
             * Get Date
             */
            $date = \DateTime::createFromFormat('d.m.y',$dayCrawler->filterXPath('//BUSINESS_DATE')->text());

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
                $exists = $em->getRepository(Roomtype::class)->findOneBy(array(
                    'nameShort' => $short,
                ));

                if($exists === null){
                    continue;
                }

                /**
                 * Get available Rooms of this Type
                 */
                $avail = $roomCrawler->filterXPath('//NO_OF_ROOMS1')->text();

                dump('ROOM: '.$short.' Avail: '.$avail);


            }

            dump($date);
            die;
        }
        die;

        return $raw;

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
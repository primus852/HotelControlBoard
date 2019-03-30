<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 15.02.2019
 * Time: 13:56
 */

namespace App\Util\Helper;


use App\Entity\CompetitorCheck;
use App\Entity\Ratetype;
use App\Entity\Roomtype;
use Doctrine\Common\Persistence\ObjectManager;

class RemoveHelper
{


    /**
     * @param Roomtype $roomtype
     * @param ObjectManager $em
     * @return int|null
     * @throws HelperException
     */
    public static function remove_roomtype(Roomtype $roomtype, ObjectManager $em)
    {

        /**
         * "Save" old $id
         */
        $id = $roomtype->getId();

        /**
         * Delete Roomtype
         */
        $em->remove($roomtype);

        try{
            $em->flush();
        }catch (\Exception $e){
            throw new HelperException('MySQL Error: '.$e->getMessage());
        }

        return $id;

    }

    /**
     * @param Ratetype $ratetype
     * @param ObjectManager $em
     * @return int|null
     * @throws HelperException
     */
    public static function remove_ratetype(Ratetype $ratetype, ObjectManager $em)
    {

        /**
         * "Save" old $id
         */
        $id = $ratetype->getId();

        /**
         * Delete Ratetype
         */
        $em->remove($ratetype);

        try{
            $em->flush();
        }catch (\Exception $e){
            throw new HelperException('MySQL Error: '.$e->getMessage());
        }

        return $id;

    }

    /**
     * @param CompetitorCheck $competitor
     * @param ObjectManager $em
     * @return int|null
     * @throws HelperException
     */
    public static function remove_competitor(CompetitorCheck $competitor, ObjectManager $em)
    {

        /**
         * "Save" old $id
         */
        $id = $competitor->getId();

        /**
         * Delete Competitor
         */
        $em->remove($competitor);

        try{
            $em->flush();
        }catch (\Exception $e){
            throw new HelperException('MySQL Error: '.$e->getMessage());
        }

        return $id;

    }

}
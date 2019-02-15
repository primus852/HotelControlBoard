<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 13.02.2019
 * Time: 15:43
 */

namespace App\Util\Room;


use App\Entity\Roomtype;
use Doctrine\Common\Persistence\ObjectManager;

class RoomHandler
{

    /**
     * @param ObjectManager $em
     * @return array
     */
    public static function gather_all(ObjectManager $em)
    {
        $rooms = array();

        $roomtypes = $em->getRepository(Roomtype::class)->findAll();

        foreach($roomtypes as $roomtype){

            $rooms[] = array(
                'id' => $roomtype->getId(),
                'name' => $roomtype->getName(),
                'nameShort' => $roomtype->getNameShort(),
                'isActive' => $roomtype->getIsActive(),
                'maxOcc' => $roomtype->getMaxOccupancy(),
                'connections' => 0 //@todo: count connected x?
            );

        }

        return $rooms;
    }

}
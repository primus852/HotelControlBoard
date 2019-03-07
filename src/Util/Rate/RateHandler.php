<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 13.02.2019
 * Time: 15:43
 */

namespace App\Util\Rate;


use App\Entity\Ratetype;
use Doctrine\Common\Persistence\ObjectManager;

class RateHandler
{

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
                'discountAmount' => $ratetype->getDiscountAmount(),
                'discountPercent' => $ratetype->getDiscountPercent(),
                'connections' => 0 //@todo: count connected x?
            );

        }

        return $rates;
    }

}
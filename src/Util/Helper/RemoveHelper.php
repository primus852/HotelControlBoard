<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 15.02.2019
 * Time: 13:56
 */

namespace App\Util\Helper;


use App\Entity\Budget;
use App\Entity\CompetitorCheck;
use App\Entity\Ratetype;
use App\Entity\Roomtype;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;

class RemoveHelper
{

    /**
     * @param $entity Roomtype|Ratetype|CompetitorCheck|Budget
     * @param ObjectManager $em
     * @return mixed
     * @throws HelperException
     */
    public static function remove_simple($entity, ObjectManager $em)
    {

        /**
         * "Save" old $id
         */
        $id = $entity->getId();

        /**
         * Delete Entity
         */
        $em->remove($entity);

        try {
            $em->flush();
        } catch (Exception $e) {
            throw new HelperException('MySQL Error: ' . $e->getMessage());
        }

        return $id;

    }

}
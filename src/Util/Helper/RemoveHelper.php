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
use App\Entity\User;
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

    /**
     * @param User $user
     * @param ObjectManager $em
     * @return int|null
     * @throws HelperException
     */
    public static function remove_user(User $user, ObjectManager $em)
    {
        $id = $user->getId();

        /**
         * Delete Holidays
         */
        foreach($user->getHolidays() as $holiday){
            $em->remove($holiday);
        }

        /**
         * Delete User
         */
        $em->remove($user);

        try {
            $em->flush();
        } catch (Exception $e) {
            throw new HelperException('MySQL Error: ' . $e->getMessage());
        }

        return $id;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 15.02.2019
 * Time: 13:45
 */

namespace App\Util\Helper;


use App\Entity\Ratetype;
use App\Entity\Roomtype;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\ORMException;
use primus852\SimpleCrypt\SimpleCrypt;

class Helper
{

    /**
     * @param string $entity
     * @param int $id
     * @param string $to_set
     * @param ObjectManager $em
     * @return array
     * @throws HelperException
     */
    public static function toggleActive(string $entity, int $id, string $to_set, ObjectManager $em)
    {

        /**
         * Load Entity
         */
        try {
            $entity = self::repo($entity, $em, $id);
        } catch (\Exception $e) {
            throw new HelperException('Could not find Repository');
        }

        /**
         * Check if we have "isActive"
         */
        if(!method_exists($entity, 'getIsActive')){
            throw new HelperException('Entity has no "getIsActive" Method');
        }

        $set = $to_set === 'activate' ? true : false;
        $set_new = $to_set === 'activate' ? 'deactivate' : 'activate';
        $html_new = $to_set === 'activate' ? '<i class="fa fa-ban"></i> Deactivate' : '<i class="fa fa-check"></i> Activate';
        $b_class = $to_set === 'activate' ? 'success' : 'danger';
        $b_html = $to_set === 'activate' ? 'active' : 'inactive';

        $entity->setIsActive($set);

        $em->persist($entity);

        try{
            $em->flush();
        }catch (\Exception $e){
            throw new HelperException('MySQL Error: '.$e->getMessage());
        }

        return array(
            'to_set_new' => $set_new,
            'b_class' => $b_class,
            'b_html' => $b_html,
            'html_new' => $html_new,
        );

    }

    /**
     * @param string $entity
     * @param int $id
     * @param ObjectManager $em
     * @return bool
     * @throws HelperException
     */
    public static function removeEntity(string $entity, int $id, ObjectManager $em)
    {

        /**
         * Load Entity
         */
        try {
            $entity = self::repo($entity, $em, $id);
        } catch (\Exception $e) {
            throw new HelperException('Could not find Repository');
        }

        /**
         * Detect which Entity and delete accordingly
         */
        try {
            switch (true) {
                case $entity instanceof Roomtype:
                    return RemoveHelper::remove_roomtype($entity, $em);
                    break;
                case $entity instanceof Ratetype:
                    return RemoveHelper::remove_ratetype($entity, $em);
                    break;
                default:
                    throw new HelperException('Invalid Switch');
            }
        } catch (HelperException $e) {
            throw new HelperException($e->getMessage());
        }


    }

    /**
     * @param string $search
     * @param ObjectManager $em
     * @param int $id
     * @param string $bundle
     * @return bool|object|string|null
     * @throws HelperException
     */
    public static function repo(string $search, ObjectManager $em, int $id, string $bundle = 'App')
    {

        /**
         * Decrypt Value
         */
        $val = ucfirst(SimpleCrypt::dec($search));

        if (!$val) {
            return false;
        }

        try {
            $name = $em->getRepository($bundle . ':' . $val);
        } catch (\Exception $e) {
            if ($e instanceof MappingException || $e instanceof ORMException) {
                return false;
            }
            throw new HelperException('Error loading Repository: ' . $e->getMessage());
        }

        if ($id === 0) {
            return $name->getClassName();
        }

        $repo = $em->getRepository($name->getClassName())->find($id);
        if ($repo === null) {
            return false;
        }

        return $repo;

    }

}
<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 15.02.2019
 * Time: 13:45
 */

namespace App\Util\Helper;


use App\Entity\HistoryForecast;
use App\Entity\Ratetype;
use App\Entity\Roomtype;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\ORMException;
use primus852\SimpleCrypt\SimpleCrypt;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Helper
{

    /**
     * @param string $date_string
     * @param ObjectManager $em
     * @return \Doctrine\Common\Collections\Collection
     * @throws HelperException
     */
    public static function hf_by_date(string $date_string, ObjectManager $em)
    {

        $date = \DateTime::createFromFormat('d-m-Y', '01-'.$date_string);

        if ($date === false) {
            throw new HelperException('Could not create DateTime: ' . $date_string);
        }

        $start_date = \DateTime::createFromFormat('Y-m-d',$date->format('Y').'-'.$date->format('m').'-01');
        $start_date->setTime(0,0,0);

        try{
            $end_date = new \DateTime('Last day of '.$date->format('F').' '.$date->format('Y'));
            $end_date->setTime(23,59,59);
        }catch (\Exception $e){
            throw new HelperException('Could not create EndDate: '.$e->getMessage());
        }

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->andX(
            Criteria::expr()->gte('bookDate',$start_date),
            Criteria::expr()->lte('bookDate',$end_date)
        ));

        $list = $em->getRepository(HistoryForecast::class)->matching($criteria);

        return $list;

    }

    /**
     * @param ObjectManager $em
     * @return array
     */
    public static function month_list(ObjectManager $em)
    {
        /**
         * Select last Date of HF
         */
        $last = $em->getRepository(HistoryForecast::class)->findBy(
            array(),
            array('bookDate' => 'DESC'),
            1
        );

        try {
            $begin = new \DateTime();
            $end = $last[0]->getBookDate();

            $interval = \DateInterval::createFromDateString('1 day');
            $period = new \DatePeriod($begin, $interval, $end);
        } catch (\Exception $e) {
            throw new AccessDeniedHttpException('Error creating DateTime: ' . $e->getMessage());
        }

        $lastMonth = null;
        $months = array();

        /* @var $dt \DateTime */
        foreach ($period as $dt) {

            if ($dt > $end) {
                break;
            }

            if ($lastMonth !== $dt->format('m-Y')) {
                $months[] = $dt->format('m-Y');

            }

            $lastMonth = $dt->format('m-Y');
        }

        return $months;

    }

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
        if (!method_exists($entity, 'getIsActive')) {
            throw new HelperException('Entity has no "getIsActive" Method');
        }

        $set = $to_set === 'activate' ? true : false;
        $set_new = $to_set === 'activate' ? 'deactivate' : 'activate';
        $html_new = $to_set === 'activate' ? '<i class="fa fa-ban"></i> Deactivate' : '<i class="fa fa-check"></i> Activate';
        $b_class = $to_set === 'activate' ? 'success' : 'danger';
        $b_html = $to_set === 'activate' ? 'active' : 'inactive';

        $entity->setIsActive($set);

        $em->persist($entity);

        try {
            $em->flush();
        } catch (\Exception $e) {
            throw new HelperException('MySQL Error: ' . $e->getMessage());
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
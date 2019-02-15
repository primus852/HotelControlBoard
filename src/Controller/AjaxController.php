<?php

namespace App\Controller;

use App\Entity\Ratetype;
use App\Entity\Roomtype;
use App\Util\Helper\Helper;
use App\Util\Helper\HelperException;
use Doctrine\Common\Persistence\ObjectManager;
use primus852\ShortResponse\ShortResponse;
use primus852\SimpleCrypt\SimpleCrypt;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AjaxController extends AbstractController
{

    /**
     * @Route("/_ajax/_toggleActive", name="ajaxToggleActive")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function toggleActive(Request $request, ObjectManager $em)
    {
        try{
            $state = Helper::toggleActive($request->get('type'), $request->get('id'), $request->get('to_set'), $em);
        }catch (HelperException $e){
            return ShortResponse::exception('Error deleting Entity', $e->getMessage());
        }

        return ShortResponse::success('Entry updated', $state);

    }

    /**
     * @Route("/_ajax/_removeEntity", name="ajaxRemoveEntity")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function removeEntity(Request $request, ObjectManager $em)
    {

        /**
         * Call the Remove function for the requested ID
         */
        try {
            $del_id = Helper::removeEntity($request->get('type'), $request->get('id'), $em);
        } catch (HelperException $e) {
            return ShortResponse::exception('Error deleting Entity', $e->getMessage());
        }

        return ShortResponse::success('Entry deleted', array(
            'id' => $del_id,
        ));

    }

    /**
     * @Route("/_ajax/_addRatetype", name="ajaxAddRatetype")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function addRatetype(Request $request, ObjectManager $em)
    {

        /**
         * Get Vars
         */
        $name = $request->get('name');
        $short = $request->get('nameShort');
        $isBase = $request->get('isBase') === 'yes' ? true : false;

        /**
         * Check $name
         */
        if (trim($name) === "" || $name === null) {
            return ShortResponse::error('Rate Name cannot be empty');
        }

        /**
         * Check $short
         */
        if (trim($short) === "" || $short === null) {
            return ShortResponse::error('Rate Name Short cannot be empty');
        }

        /**
         * If the BaseRate is selected, check if we have another BaseRate already
         */
        if ($isBase) {
            $ratesBase = $em->getRepository(Ratetype::class)->findOneBy(array(
                'isBase' => true
            ));

            if ($ratesBase !== null) {
                return ShortResponse::error('There is already a BaseRate in the Database: ' . $ratesBase->getName());
            }
        }

        /**
         * Check if the same $name is already available
         */
        $ratesName = $em->getRepository(Ratetype::class)->findOneBy(array(
            'name' => $name
        ));

        if ($ratesName !== null) {
            return ShortResponse::error('Ratetype with name <strong>' . $name . '</strong> already in Database');
        }

        /**
         * Check if the same $short is already available
         */
        $ratesShort = $em->getRepository(Ratetype::class)->findOneBy(array(
            'nameShort' => $short
        ));

        if ($ratesShort !== null) {
            return ShortResponse::error('Ratetype with short name <strong>' . $short . '</strong> already in Database');
        }

        $rate = new Ratetype();
        $rate->setName($name);
        $rate->setNameShort($short);
        $rate->setIsBase($isBase);
        $rate->setIsActive(true);

        try {
            $em->persist($rate);
            $em->flush();
        } catch (\Exception $e) {
            return ShortResponse::mysql();
        }

        return ShortResponse::success('Ratetype added', array(
            'id' => $rate->getId(),
            'name' => $rate->getName(),
            'nameShort' => $rate->getNameShort(),
            'isBase' => $isBase,
        ));


    }

    /**
     * @Route("/_ajax/_addRoomtype", name="ajaxAddRoomtype")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function addRoomtype(Request $request, ObjectManager $em)
    {

        /**
         * Get Vars
         */
        $name = $request->get('name');
        $short = $request->get('nameShort');
        $maxOcc = $request->get('occ');

        /**
         * Check $name
         */
        if (trim($name) === "" || $name === null) {
            return ShortResponse::error('Rate Name cannot be empty');
        }

        /**
         * Check $short
         */
        if (trim($short) === "" || $short === null) {
            return ShortResponse::error('Rate Name Short cannot be empty');
        }

        /**
         * Check $maxOcc
         */
        if (trim($maxOcc) === "" || $maxOcc === null || (int)$maxOcc > 4 || (int)$maxOcc === 0) {
            return ShortResponse::error('Please select a number between 1 and 4 for max. occupancy');
        }

        /**
         * Check if the same $name is already available
         */
        $roomsName = $em->getRepository(Roomtype::class)->findOneBy(array(
            'name' => $name
        ));

        if ($roomsName !== null) {
            return ShortResponse::error('Roomtype with name <strong>' . $name . '</strong> already in Database');
        }

        /**
         * Check if the same $short is already available
         */
        $roomsShort = $em->getRepository(Roomtype::class)->findOneBy(array(
            'nameShort' => $short
        ));

        if ($roomsShort !== null) {
            return ShortResponse::error('Roomtype with short name <strong>' . $short . '</strong> already in Database');
        }

        $room = new Roomtype();
        $room->setName($name);
        $room->setNameShort($short);
        $room->setMaxOccupancy((int)$maxOcc);
        $room->setIsActive(true);

        try {
            $em->persist($room);
            $em->flush();
        } catch (\Exception $e) {
            return ShortResponse::mysql();
        }

        return ShortResponse::success('Roomtype added', array(
            'id' => $room->getId(),
            'name' => $room->getName(),
            'nameShort' => $room->getNameShort(),
            'maxOcc' => $room->getMaxOccupancy(),
            'link' => $this->generateUrl('renderRoomtype',array(
                'id' => $room->getId(),
            )),
            'type' => SimpleCrypt::enc('Roomtype'),
        ));


    }
}

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
     * @Route("/_ajax/_updateRoomtype", name="ajaxUpdateRoomtype")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function updateRoomtype(Request $request, ObjectManager $em)
    {

        /**
         * Gather Vars
         */
        $id = $request->get('id');
        $name = $request->get('name');
        $nameShort = $request->get('nameShort');
        $maxOcc = $request->get('maxOcc');

        if ($name === null || trim($name) === '') {
            return ShortResponse::error('Name cannot be empty');
        }

        if ($nameShort === null || trim($nameShort) === '') {
            return ShortResponse::error('Name (Short) cannot be empty');
        }

        if ($maxOcc === null || trim($maxOcc) === '') {
            return ShortResponse::error('Max. Occupancy cannot be empty');
        }

        if (!is_numeric($maxOcc)) {
            return ShortResponse::error('Max. Occupancy is not a valid number');
        }

        /**
         * Get Roomtype
         */
        $room = $em->getRepository(Roomtype::class)->find($id);

        if ($room === null) {
            return ShortResponse::error('Roomtype not found');
        }

        /**
         * Check if we have the name already
         */
        $nameRoom = $em->getRepository(Roomtype::class)->findOneBy(array(
            'name' => $name,
        ));

        if ($nameRoom !== null && $nameRoom !== $room) {
            return ShortResponse::error('Same Name already exists in Database');
        }

        /**
         * Check if we have the nameShort already
         */
        $nameShortRoom = $em->getRepository(Roomtype::class)->findOneBy(array(
            'nameShort' => $nameShort,
        ));

        if ($nameShortRoom !== null && $nameShortRoom !== $room) {
            return ShortResponse::error('Same Name (Short) already exists in Database');
        }

        $room->setName($name);
        $room->setNameShort($nameShort);
        $room->setMaxOccupancy($maxOcc);

        $em->persist($room);

        try {
            $em->flush();
        } catch (\Exception $e) {
            return ShortResponse::mysql($e->getMessage());
        }

        return ShortResponse::success('Roomtype updated', array(
            'id' => $room->getId(),
            'type' => SimpleCrypt::enc('Roomtype'),
            'name' => $room->getName(),
            'nameShort' => $room->getNameShort(),
            'maxOcc' => $room->getMaxOccupancy(),
        ));

    }

    /**
     * @Route("/_ajax/_updateRatetype", name="ajaxUpdateRatetype")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function updateRatetype(Request $request, ObjectManager $em)
    {

        /**
         * Gather Vars
         */
        $id = $request->get('id');
        $name = $request->get('name');
        $nameShort = $request->get('nameShort');
        $isBaseRate = $request->get('isBase') === 'yes' ? true : false;
        $dcAmount = (float) str_replace(',','.',$request->get('dcAmount'));
        $dcType = $request->get('dcType') === 'p' ? true : false;

        if ($name === null || trim($name) === '') {
            return ShortResponse::error('Name cannot be empty');
        }

        if ($nameShort === null || trim($nameShort) === '') {
            return ShortResponse::error('Name (Short) cannot be empty');
        }

        if ($request->get('isBase') === null) {
            return ShortResponse::error('BaseRate cannot be empty');
        }

        /**
         * Get Ratetype
         */
        $rate = $em->getRepository(Ratetype::class)->find($id);

        if ($rate === null) {
            return ShortResponse::error('Ratetype not found');
        }

        /**
         * Check if we have the name already
         */
        $nameRate = $em->getRepository(Ratetype::class)->findOneBy(array(
            'name' => $name,
        ));

        if ($nameRate !== null && $nameRate !== $rate) {
            return ShortResponse::error('Same Name already exists in Database');
        }

        /**
         * Check if we have the nameShort already
         */
        $nameShortRate = $em->getRepository(Ratetype::class)->findOneBy(array(
            'nameShort' => $nameShort,
        ));

        if ($nameShortRate !== null && $nameShortRate !== $rate) {
            return ShortResponse::error('Same Name (Short) already exists in Database');
        }

        /**
         * Check if we have a BaseRate already
         */
        $baseRate = $em->getRepository(Ratetype::class)->findOneBy(array(
            'isBase' => true,
        ));

        if ($baseRate !== null && $baseRate !== $rate && $isBaseRate) {
            return ShortResponse::error('BaseRate already exists in Database');
        }

        /**
         * If this is the BaseRate
         */
        if ($isBaseRate && $dcAmount > 0) {
            return ShortResponse::error('Cannot apply discount to BaseRate');
        }

        /**
         * If there is no BaseRate yet, no discount can be applied
         */
        if($baseRate === null && $dcAmount > 0){
            return ShortResponse::error('There is no BaseRate defined. Please define a BaseRate before adding a discounted Rate');
        }

        $rate->setName($name);
        $rate->setNameShort($nameShort);
        $rate->setIsBase($isBaseRate);
        $rate->setDiscountAmount($dcAmount);
        $rate->setDiscountPercent($dcType);

        $em->persist($rate);

        try {
            $em->flush();
        } catch (\Exception $e) {
            return ShortResponse::mysql($e->getMessage());
        }

        return ShortResponse::success('Ratetype updated', array(
            'id' => $rate->getId(),
            'type' => SimpleCrypt::enc('Ratetype'),
            'name' => $rate->getName(),
            'nameShort' => $rate->getNameShort(),
            'dcAmount' => $rate->getDiscountAmount(),
            'dcType' => $rate->getDiscountPercent() ? '&percnt;' : '&euro;',
            'isBase' => $rate->getIsBase() ? '<i class="fa fa-check" id="base_' . SimpleCrypt::enc('Ratetype') . '_' . $rate->getId() . '"></i>' : '<i class="fa fa-remove" id="base_' . SimpleCrypt::enc('Ratetype') . '_' . $rate->getId() . '"></i>',
        ));


    }

    /**
     * @Route("/_ajax/_toggleActive", name="ajaxToggleActive")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function toggleActive(Request $request, ObjectManager $em)
    {
        try {
            $state = Helper::toggleActive($request->get('type'), $request->get('id'), $request->get('to_set'), $em);
        } catch (HelperException $e) {
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
        $dcAmount = (float) str_replace(',','.',$request->get('dcAmount'));
        $dcType = $request->get('dcType') === 'p' ? true : false;

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
         * Search BaseRate for BaseRate / Discount Check
         */
        $ratesBase = $em->getRepository(Ratetype::class)->findOneBy(array(
            'isBase' => true
        ));

        /**
         * If the BaseRate is selected, check if we have another BaseRate already
         */
        if ($isBase) {

            if ($ratesBase !== null) {
                return ShortResponse::error('There is already a BaseRate in the Database: ' . $ratesBase->getName());
            }

            /**
             * If the BaseRate is selected, no discount can be applied
             */
            if($dcAmount > 0){
                return ShortResponse::error('No Discount can be applied to the BaseRate');
            }
        }

        /**
         * If there is no BaseRate yet, no discount can be applied
         */
        if($ratesBase === null && $dcAmount > 0){
            return ShortResponse::error('There is no BaseRate defined. Please define a BaseRate before adding a discounted Rate');
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
        $rate->setDiscountAmount($dcAmount);
        $rate->setDiscountPercent($dcType);
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
            'dcAmount' => $rate->getDiscountAmount(),
            'dcType' => $rate->getDiscountPercent() ? '&percnt;' : '&euro;',
            'isBase' => $rate->getIsBase() ? '<i class="fa fa-check" id="base_' . SimpleCrypt::enc('Ratetype') . '_' . $rate->getId() . '"></i>' : '<i class="fa fa-remove" id="base_' . SimpleCrypt::enc('Ratetype') . '_' . $rate->getId() . '"></i>',
            'link' => $this->generateUrl('renderRatetype', array(
                'id' => $rate->getId(),
            )),
            'type' => SimpleCrypt::enc('Ratetype'),
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
            'link' => $this->generateUrl('renderRoomtype', array(
                'id' => $room->getId(),
            )),
            'type' => SimpleCrypt::enc('Roomtype'),
        ));


    }
}

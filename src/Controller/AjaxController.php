<?php

namespace App\Controller;

use App\Entity\Rateplan;
use App\Entity\Ratetype;
use App\Entity\Roomtype;
use App\Util\Helper\Helper;
use App\Util\Helper\HelperException;
use App\Util\Xml\HcbXmlReader;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use primus852\ShortResponse\ShortResponse;
use primus852\SimpleCrypt\SimpleCrypt;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AjaxController extends AbstractController
{

    /**
     * @Route("/_ajax/_editCxl", name="ajaxEditCxl")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function editCxl(Request $request, ObjectManager $em)
    {

        if($request->get('id') === null || $request->get('id') === ''){
            return ShortResponse::error('Could not find ID');
        }

        $rate = $em->getRepository(Rateplan::class)->find($request->get('id'));

        if($rate === null){
            return ShortResponse::error('Could not find Rate');
        }

        /**
         * Toggle through the Policies
         */
        if($rate->getCxl() === null){
            $rate->setCxl(2);
            $cssClass = '2';
        }elseif($rate->getCxl() === 2){
            $rate->setCxl(4);
            $cssClass = '4';
        }else{
            $rate->setCxl(null);
            $cssClass = 'reg';
        }

        $em->persist($rate);

        try{
            $em->flush();
        }catch (Exception $e){
            return ShortResponse::mysql($e->getMessage());
        }

        return ShortResponse::success('Policy updated',array(
            'cssClass' => $cssClass,
        ));


    }

    /**
     * @Route("/_ajax/_editRateplan", name="ajaxEditRateplan")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function editRateplan(Request $request, ObjectManager $em)
    {

        $value = (float) str_replace(',', '.', $request->get('value'));
        $pre = explode('_',$request->get('id'));
        $bookDate = DateTime::createFromFormat('Y-m-d', $pre[1]);
        $id = $pre[0];

        if ($bookDate === false) {
            return ShortResponse::error('Could not create Date: ' . $request->get('bookDate'));
        }

        if ($id === 'new') {
            $rateplan = new Rateplan();
            $rateplan->setBookDate($bookDate);
        } else {

            $rateplan = $em->getRepository(Rateplan::class)->find($id);

            if ($rateplan === null) {
                throw new Exception('Could not find Rateplan ID: '.$id);
            }
        }

        $rateplan->setPrice($value);
        $em->persist($rateplan);

        try{
            $em->flush();
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }

        return new Response(number_format($rateplan->getPrice(),2));

    }

    /**
     * @Route("/_ajax/_uploadReports", name="ajaxUploadReports")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function uploadReports(Request $request, ObjectManager $em)
    {

        foreach ($request->files as $files) {

            $type = $request->get('report_type');
            $filename = $type . '.xml';

            /* @var $file File */
            foreach ($files as $file) {
                try {
                    $file->move('reports', $filename);
                } catch (Exception $e) {
                    return ShortResponse::error('Could not upload file', array(
                        'error' => $e->getMessage(),
                    ));
                }

                /**
                 * Call the according XML Parser
                 */
                try {
                    HcbXmlReader::$type($em);
                } catch (Exception $e) {
                    return ShortResponse::exception('Failed to parse Report', $e->getMessage());
                }
            }
        }

        return ShortResponse::success('Upload succeeded, Report parsed', array());

    }

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
        } catch (Exception $e) {
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
        $minStay = (int)$request->get('minStay');
        $daysAdvance = (int)$request->get('daysAdvance');
        $isBaseRate = $request->get('isBase') === 'yes' ? true : false;
        $dcAmount = (float)str_replace(',', '.', $request->get('dcAmount'));
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
        if ($baseRate === null && $dcAmount > 0) {
            return ShortResponse::error('There is no BaseRate defined. Please define a BaseRate before adding a discounted Rate');
        }

        $rate->setName($name);
        $rate->setNameShort($nameShort);
        $rate->setIsBase($isBaseRate);
        $rate->setMinStay($minStay);
        $rate->setDaysAdvance($daysAdvance);
        $rate->setDiscountAmount($dcAmount);
        $rate->setDiscountPercent($dcType);

        $em->persist($rate);

        try {
            $em->flush();
        } catch (Exception $e) {
            return ShortResponse::mysql($e->getMessage());
        }

        return ShortResponse::success('Ratetype updated', array(
            'id' => $rate->getId(),
            'type' => SimpleCrypt::enc('Ratetype'),
            'name' => $rate->getName(),
            'minStay' => $rate->getMinStay(),
            'daysAdvance' => $rate->getDaysAdvance(),
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
        $dcAmount = (float)str_replace(',', '.', $request->get('dcAmount'));
        $minStay = (int)$request->get('minStay');
        $preDays = (int)$request->get('preDays');
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
            if ($dcAmount > 0) {
                return ShortResponse::error('No Discount can be applied to the BaseRate');
            }
        }

        /**
         * If there is no BaseRate yet, no discount can be applied
         */
        if ($ratesBase === null && $dcAmount > 0) {
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
        $rate->setDaysAdvance($preDays);
        $rate->setMinStay($minStay);
        $rate->setIsActive(true);

        try {
            $em->persist($rate);
            $em->flush();
        } catch (Exception $e) {
            return ShortResponse::mysql();
        }

        return ShortResponse::success('Ratetype added', array(
            'id' => $rate->getId(),
            'name' => $rate->getName(),
            'nameShort' => $rate->getNameShort(),
            'dcAmount' => $rate->getDiscountAmount(),
            'minStay' => $rate->getMinStay(),
            'preDays' => $rate->getDaysAdvance(),
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
        } catch (Exception $e) {
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

<?php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\CompetitorCheck;
use App\Entity\HcbSettings;
use App\Entity\Rateplan;
use App\Entity\Ratetype;
use App\Entity\Roomtype;
use App\Util\Competitors\CompetitorBooking;
use App\Util\Competitors\CompetitorException;
use App\Util\Helper\Helper;
use App\Util\Helper\HelperException;
use App\Util\Rate\RateHandler;
use App\Util\Rate\RateHandlerException;
use App\Util\Xml\HcbXmlReader;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use iio\libmergepdf\Merger;
use Knp\Snappy\Pdf;
use primus852\ShortResponse\ShortResponse;
use primus852\SimpleCrypt\SimpleCrypt;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AjaxController extends AbstractController
{

    /**
     * @Route("/_ajax/_calcCityTaxGeneral", name="ajaxCalcCityTaxGeneral")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     * @throws RateHandlerException
     */
    public function calcCityTaxGeneral(Request $request, ObjectManager $em)
    {

        /**
         * Get Pax + add Price
         */
        $pax = (int)$request->get('pax');

        /**
         * Get Amount
         */
        $rate = (float)str_replace(',', '.', $request->get('rate'));
        if ($rate <= 0) {
            return ShortResponse::error('Invalid Rate Amount');
        }

        /**
         * BF Setting
         */
        $settingBf = $em->getRepository(HcbSettings::class)->findOneBy(array(
            'name' => 'bf'
        ));
        if ($settingBf === null) {
            return ShortResponse::error('Invalid Breakfast Setting');
        }


        /**
         * Expedia only Discount 1
         */
        $dc1 = null;
        if ($request->get('dc1') !== 'None') {
            $dc1 = (float)$request->get('dc1');
            $rate = $rate * ((100 - $dc1) / 100);
        }

        /**
         * Expedia only Discount 2
         */
        $dc2 = null;
        if ($request->get('dc2') !== 'None') {
            $dc2 = (float)$request->get('dc2');
            $rate = $rate * ((100 - $dc2) / 100);
        }

        /**
         * Calc CityTax
         */
        $taxes = RateHandler::city_tax($rate, $pax, $em);

        /**
         * Subtract Commission Expedia only
         */
        if ($dc1 !== null || $dc2 !== null) {
            $rate = $taxes['rate_no_tax'] * 0.82;
        }


        return ShortResponse::success('CT calculated', array(
            'rate' => number_format($rate, 2),
            'rate_no_tax' => number_format($taxes['rate_no_tax'], 2),
            'citytax' => number_format($taxes['city_tax'], 2),
        ));
    }

    /**
     * @Route("/_ajax/_saveSettings", name="ajaxSaveSettings")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function saveSettings(Request $request, ObjectManager $em)
    {

        /**
         * Gather Vars
         */
        $add_double = $request->get('add_double');
        $add_triple = $request->get('add_triple');
        $add_extra = $request->get('add_extra');
        $bf = $request->get('bf');

        /**
         * Clean
         */
        $add_double = (float)str_replace(',', '.', $add_double);
        if ($add_double <= 0) {
            return ShortResponse::error('Invalid Double Room Modifier');
        }

        $add_triple = (float)str_replace(',', '.', $add_triple);
        if ($add_triple <= 0) {
            return ShortResponse::error('Invalid Triple Room Modifier');
        }

        $add_extra = (float)str_replace(',', '.', $add_extra);
        if ($add_extra <= 0) {
            return ShortResponse::error('Invalid Extra Person Modifier');
        }

        $bf = (float)str_replace(',', '.', $bf);
        if ($bf <= 0) {
            return ShortResponse::error('Invalid Breakfast Amount');
        }

        /**
         * 4 Queries (ugly)
         */
        $q_double = $em->getRepository(HcbSettings::class)->findOneBy(array(
            'name' => 'add_double',
        ));
        if ($q_double === null) {
            return ShortResponse::error('Invalid DB for "add_double"');
        }
        $q_double->setSetting($add_double);
        $em->persist($q_double);

        $q_triple = $em->getRepository(HcbSettings::class)->findOneBy(array(
            'name' => 'add_triple',
        ));
        if ($q_triple === null) {
            return ShortResponse::error('Invalid DB for "add_triple"');
        }
        $q_triple->setSetting($add_triple);
        $em->persist($q_triple);

        $q_extra = $em->getRepository(HcbSettings::class)->findOneBy(array(
            'name' => 'add_extra',
        ));
        if ($q_extra === null) {
            return ShortResponse::error('Invalid DB for "add_extra"');
        }
        $q_extra->setSetting($add_extra);
        $em->persist($q_extra);

        $q_bf = $em->getRepository(HcbSettings::class)->findOneBy(array(
            'name' => 'bf',
        ));
        if ($q_bf === null) {
            return ShortResponse::error('Invalid DB for "bf"');
        }
        $q_bf->setSetting($bf);
        $em->persist($q_bf);

        try {
            $em->flush();
        } catch (Exception $e) {
            return ShortResponse::mysql($e->getMessage());
        }

        return ShortResponse::success('Settings saved');

    }

    /**
     * @Route("/_ajax/_generateTaxForms", name="ajaxTaxForms")
     * @param Pdf $pdf
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function taxForms(Pdf $pdf, ObjectManager $em)
    {
        /**
         * Call the CT Parser
         */
        try {
            $result = HcbXmlReader::ct($em);
        } catch (Exception $e) {
            return ShortResponse::exception('Failed to parse Report', $e->getMessage());
        }

        /**
         * Remove all PDFs
         */
        $fs = new Filesystem();
        $finder = new Finder();
        $finder->files()->in('pdfs');

        if ($finder->hasResults()) {
            foreach ($finder as $fileInfo) {
                $absoluteFilePath = $fileInfo->getRealPath();
                $fs->remove($absoluteFilePath);
            }
        }

        /**
         * Merging output PDFs
         */
        $merger = new Merger();

        $id = 0;
        foreach ($result as $r) {

            $id++;

            $pdf->setOption('header-html', $this->renderView('default/pdf/pdfTaxFormsHeader.html.twig', array()));
            $pdf->setOption('footer-html', $this->renderView('default/pdf/pdfTaxFormFooter.html.twig', array()));

            $lang = $r['isGerman'] === true ? 'de' : 'en';

            $pdf->generateFromHtml($this->renderView('default/pdf/pdfTaxFormBase.' . $lang . '.html.twig', array(
                'checkin' => $r['checkin'],
                'checkout' => $r['checkout'],
                'name' => $r['guest'],
                'dob' => $r['dob'],
                'zip' => $r['zip_private'],
                'city' => $r['city_private'],
                'street' => $r['street_private'],
                'company' => $r['company']

            )), 'pdfs/taxforms.' . $id . '.pdf');

            $merger->addFile('pdfs/taxforms.' . $id . '.pdf');
        }

        /**
         * Merge the files
         */
        try {
            $pdf_merged = $merger->merge();
        } catch (Exception $e) {
            return ShortResponse::exception('Could not merge PDFs', $e->getMessage());
        }

        /**
         * Create merged Doku
         */
        $fs->dumpFile('pdfs/taxforms.pdf', $pdf_merged);


        return ShortResponse::success('TaxForms generated', array(
            'link' => $this->generateUrl('taxFormsPdf', array()),
        ));

    }

    /**
     * @Route("/_ajax/_check_rate", name="ajaxCheckRate")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function checkRate(Request $request, ObjectManager $em)
    {


    }

    /**
     * @Route("/_ajax/_make_comp_check", name="ajaxMakeCompCheck")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function makeCompCheck(Request $request, ObjectManager $em)
    {

        if ($request->get('id') === null || $request->get('id') === '') {
            return ShortResponse::error('Could not find ID');
        }

        $competition = $em->getRepository(CompetitorCheck::class)->find($request->get('id'));

        if ($competition === null) {
            return ShortResponse::error('Could not find Competitor');
        }

        /**
         * Create Date
         */
        $date = DateTime::createFromFormat('Y-m-d', $request->get('date'));
        if ($date === false) {
            return ShortResponse::error('Could not create date');
        }

        try {
            $crawl = CompetitorBooking::crawl_hotel($competition->getLink(), $date, 1);
        } catch (CompetitorException $e) {
            return ShortResponse::exception('Could not Crawl', $e->getMessage());
        }

        $price = $price = $crawl['price'] === 'booked' ? '<span class="text-danger">booked</span>' : '<span class="text-success">' . $crawl['price'] . '</span>';

        return ShortResponse::success('Hotel crawled', array(
            'room' => $crawl['roomName'],
            'incl' => $crawl['isIncl'] === true ? 'incl' : 'excl',
            'pax' => $crawl['pax'],
            'price' => $price,
        ));


    }

    /**
     * @Route("/_ajax/_editCxl", name="ajaxEditCxl")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function editCxl(Request $request, ObjectManager $em)
    {

        if ($request->get('id') === null || $request->get('id') === '') {
            return ShortResponse::error('Could not find ID');
        }

        $rate = $em->getRepository(Rateplan::class)->find($request->get('id'));

        if ($rate === null) {
            return ShortResponse::error('Could not find Rate');
        }

        /**
         * Toggle through the Policies
         */
        if ($rate->getCxl() === null) {
            $rate->setCxl(2);
            $cssClass = '2';
        } elseif ($rate->getCxl() === 2) {
            $rate->setCxl(4);
            $cssClass = '4';
        } else {
            $rate->setCxl(null);
            $cssClass = 'reg';
        }

        $em->persist($rate);

        try {
            $em->flush();
        } catch (Exception $e) {
            return ShortResponse::mysql($e->getMessage());
        }

        return ShortResponse::success('Policy updated', array(
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

        $value = (float)str_replace(',', '.', $request->get('value'));
        $pre = explode('_', $request->get('id'));
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
                throw new Exception('Could not find Rateplan ID: ' . $id);
            }
        }

        $rateplan->setPrice($value);
        $em->persist($rateplan);

        try {
            $em->flush();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return new Response(number_format($rateplan->getPrice(), 2));

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
     * @Route("/_ajax/_updateCompetitor", name="ajaxUpdateCompetitor")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function updateCompetitor(Request $request, ObjectManager $em)
    {

        /**
         * Gather Vars
         */
        $id = $request->get('id');
        $name = $request->get('name');
        $link = $request->get('link');

        if ($name === null || trim($name) === '') {
            return ShortResponse::error('Name cannot be empty');
        }

        if ($link === null || trim($link) === '') {
            return ShortResponse::error('Link cannot be empty');
        }

        /**
         * Get Competitor
         */
        $competitor = $em->getRepository(CompetitorCheck::class)->find($id);

        if ($competitor === null) {
            return ShortResponse::error('Competitor not found');
        }

        /**
         * Check if we have the name already
         */
        $nameCompetitor = $em->getRepository(CompetitorCheck::class)->findOneBy(array(
            'name' => $name,
        ));

        if ($nameCompetitor !== null && $nameCompetitor !== $competitor) {
            return ShortResponse::error('Same Name already exists in Database');
        }

        /**
         * Check if we have the nameShort already
         */
        $nameLink = $em->getRepository(CompetitorCheck::class)->findOneBy(array(
            'link' => $link,
        ));

        if ($nameLink !== null && $nameLink !== $competitor) {
            return ShortResponse::error('Same Link already exists in Database');
        }

        $competitor->setName($name);
        $competitor->setLink($link);

        $em->persist($competitor);

        try {
            $em->flush();
        } catch (Exception $e) {
            return ShortResponse::mysql($e->getMessage());
        }

        return ShortResponse::success('Competitor updated', array(
            'id' => $competitor->getId(),
            'type' => SimpleCrypt::enc('CompetitorCheck'),
            'name' => $competitor->getName(),
            'link' => $competitor->getLink(),
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
     * @Route("/_ajax/_addBudget", name="ajaxAddBudget")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     * @throws Exception
     */
    public function addBudget(Request $request, ObjectManager $em)
    {

        /**
         * Get Vars
         */
        $month = $request->get('month');
        $year = $request->get('year');
        $acc = (float)str_replace(',', '.', $request->get('acc'));
        $other = (float)str_replace(',', '.', $request->get('other'));
        $occ = (float)str_replace(',', '.', $request->get('occ'));
        $rate = (float)str_replace(',', '.', $request->get('rate'));

        $today = new DateTime();

        /**
         * Check $month
         */
        if (trim($month) === "" || $month === null || (int)$month > 12 || (int)$month < 1) {
            return ShortResponse::error('Invalid Month');
        }

        /**
         * Check $year
         */
        if (trim($year) === "" || $year === null || (int)$year < (int)$today->format('Y')) {
            return ShortResponse::error('Invalid Year');
        }

        /**
         * Check if we have the same year already
         */
        $budget = $em->getRepository(Budget::class)->findOneBy(array(
            'year' => $year,
            'month' => $month,
        ));

        if ($budget !== null) {
            return ShortResponse::error('Budget for month already in Database');
        }

        $budget = new Budget();
        $budget->setMonth($month);
        $budget->setYear($year);
        $budget->setAccomodation($acc);
        $budget->setOtherRevenue($other);
        $budget->setOccupancy($occ);
        $budget->setRate($rate);
        $em->persist($budget);

        try {
            $em->flush();
        } catch (Exception $e) {
            return ShortResponse::mysql($e->getMessage());
        }

        return ShortResponse::success('Budget saved', array(
            'id' => $budget->getId(),
            'date' => $budget->getYear() . '/' . $budget->getMonth(),
            'acc' => number_format($budget->getAccomodation(), 2),
            'occ' => number_format($budget->getOccupancy(), 2),
            'other' => number_format($budget->getOtherRevenue(), 2),
            'rate' => number_format($budget->getRate(), 2),
            'link' => $this->generateUrl('renderRatetype',array(
                'id' => $budget->getId(),
            ))
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

    /**
     * @Route("/_ajax/_addCompetitor", name="ajaxAddCompetitor")
     * @param Request $request
     * @param ObjectManager $em
     * @return JsonResponse
     */
    public function addCompetitor(Request $request, ObjectManager $em)
    {

        /**
         * Get Vars
         */
        $name = $request->get('name');
        $link = $request->get('link');

        /**
         * Check $name
         */
        if (trim($name) === "" || $name === null) {
            return ShortResponse::error('Competitor Name cannot be empty');
        }

        /**
         * Check $short
         */
        if (trim($link) === "" || $link === null) {
            return ShortResponse::error('Link cannot be empty');
        }


        /**
         * Check if the same $name is already available
         */
        $competitor = $em->getRepository(CompetitorCheck::class)->findOneBy(array(
            'name' => $name
        ));

        if ($competitor !== null) {
            return ShortResponse::error('Competitor with name <strong>' . $name . '</strong> already in Database');
        }

        /**
         * Check if the same $link is already available
         */
        $competitorLink = $em->getRepository(CompetitorCheck::class)->findOneBy(array(
            'link' => $link
        ));

        if ($competitorLink !== null) {
            return ShortResponse::error('Competitor Link<strong>' . $link . '</strong> already in Database');
        }

        $competitor = new CompetitorCheck();
        $competitor->setName($name);
        $competitor->setLink($link);
        $competitor->setIsActive(true);

        try {
            $em->persist($competitor);
            $em->flush();
        } catch (Exception $e) {
            return ShortResponse::mysql();
        }

        return ShortResponse::success('Competitor added', array(
            'id' => $competitor->getId(),
            'name' => $competitor->getName(),
            'sublink' => $competitor->getLink(),
            'link' => $this->generateUrl('renderCompetitor', array(
                'id' => $competitor->getId(),
            )),
            'type' => SimpleCrypt::enc('CompetitorCheck'),
        ));


    }
}

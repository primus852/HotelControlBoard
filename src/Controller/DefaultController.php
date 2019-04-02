<?php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\CompetitorCheck;
use App\Entity\HcbSettings;
use App\Entity\Rateplan;
use App\Entity\Ratetype;
use App\Entity\Roomtype;
use App\Util\Helper\Helper;
use App\Util\Helper\HelperException;
use App\Util\OpenWeather\OpenWeather;
use App\Util\OpenWeather\OpenWeatherException;
use App\Util\Rate\RateHandler;
use App\Util\Rate\RateHandlerException;
use App\Util\Room\RoomHandler;
use App\Util\SecurityChecker;
use App\Util\Xml\HcbXmlReader;
use App\Util\Xml\HcbXmlReaderException;
use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use Knp\Snappy\Pdf;
use Parsedown;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{

    /**
     * @Route("/tax-forms/pdf", name="taxFormsPdf")
     * @return BinaryFileResponse|RedirectResponse
     */
    public function taxFormsPdfAction()
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser(), 'ROLE_USER')) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        $response = new BinaryFileResponse('pdfs/taxforms.pdf');
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'TaxForms.pdf');
        return $response;

    }

    /**
     * @Route("/panel/_ratesheet/{date_string}", name="openRatesheet", defaults={"date_string"="0"})
     * @param string $date_string
     * @param ObjectManager $em
     * @param Pdf $pdf
     * @return BinaryFileResponse
     * @throws HelperException
     */
    public function openRatesheet(string $date_string, ObjectManager $em, Pdf $pdf)
    {

        /**
         * Delete current Ratesheet
         */
        $fs = new Filesystem();
        if ($fs->exists('pdfs/ratesheet.pdf')) {
            $fs->remove('pdfs/ratesheet.pdf');
        }

        /**
         * Get current Years Rates
         */
        try {
            $sentDate = DateTime::createFromFormat('m-Y', $date_string);
        } catch (Exception $e) {
            throw new Exception('Could not create Date from: ' . $date_string);
        }

        if ($sentDate === false) {
            $sentDate = new DateTime();
        }

        try {
            $begin = new DateTime('First Day of January ' . $sentDate->format('Y'));
            $end = new DateTime('Last Day of December ' . $sentDate->format('Y'));
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);
        } catch (Exception $e) {
            throw new Exception('Could not create Datetime' . $e->getMessage());
        }

        /**
         * Search Prices for each day (inefficient?)
         * @var $dt DateTime
         */
        $rates = array();
        foreach ($period as $dt) {

            $rate = $em->getRepository(Rateplan::class)->findOneBy(array(
                'bookDate' => $dt,
            ));

            $rates[$dt->format('Y-m-d')] = $rate === null ? '-' : number_format($rate->getPrice(), 2);

        }

        /*
        return $this->render('default/pdf/pdfHeader.html.twig', array(
            'year' => $sentDate->format('Y')
        ));
        */


        /*
        return $this->render('default/pdf:pdfFooter.html.twig', array(
            'client' => $stats->getClient(),
            'stats' => $stats,
            'base_dir' => $this->get('kernel')->getProjectDir() . '/web' . $request->getBasePath(),
        ));
        */


        /*
        return $this->render('default/pdf/pdfBase.html.twig', array(
            'calendar_jan' => Helper::generate_calendar(1,$sentDate->format('Y'), $em),
            'calendar_feb' => Helper::generate_calendar(2,$sentDate->format('Y'), $em),
            'calendar_mar' => Helper::generate_calendar(3,$sentDate->format('Y'), $em),
            'calendar_apr' => Helper::generate_calendar(4,$sentDate->format('Y'), $em),
            'calendar_may' => Helper::generate_calendar(5,$sentDate->format('Y'), $em),
            'calendar_jun' => Helper::generate_calendar(6,$sentDate->format('Y'), $em),
            'calendar_jul' => Helper::generate_calendar(7,$sentDate->format('Y'), $em),
            'calendar_aug' => Helper::generate_calendar(8,$sentDate->format('Y'), $em),
            'calendar_sep' => Helper::generate_calendar(9,$sentDate->format('Y'), $em),
            'calendar_oct' => Helper::generate_calendar(10,$sentDate->format('Y'), $em),
            'calendar_nov' => Helper::generate_calendar(11,$sentDate->format('Y'), $em),
            'calendar_dec' => Helper::generate_calendar(12,$sentDate->format('Y'), $em),
        ));
        */

        $pdf->setOption('header-html', $this->renderView('default/pdf/pdfHeader.html.twig', array(
            'year' => $sentDate->format('Y')
        )));
        $pdf->setOption('footer-html', $this->renderView('default/pdf/pdfFooter.html.twig', array(
            'ratecolors' => RateHandler::RATE_COLORS,
        )));

        $pdf->generateFromHtml($this->renderView('default/pdf/pdfBase.html.twig', array(
            'calendar_jan' => Helper::generate_calendar(1, $sentDate->format('Y'), $em),
            'calendar_feb' => Helper::generate_calendar(2, $sentDate->format('Y'), $em),
            'calendar_mar' => Helper::generate_calendar(3, $sentDate->format('Y'), $em),
            'calendar_apr' => Helper::generate_calendar(4, $sentDate->format('Y'), $em),
            'calendar_may' => Helper::generate_calendar(5, $sentDate->format('Y'), $em),
            'calendar_jun' => Helper::generate_calendar(6, $sentDate->format('Y'), $em),
            'calendar_jul' => Helper::generate_calendar(7, $sentDate->format('Y'), $em),
            'calendar_aug' => Helper::generate_calendar(8, $sentDate->format('Y'), $em),
            'calendar_sep' => Helper::generate_calendar(9, $sentDate->format('Y'), $em),
            'calendar_oct' => Helper::generate_calendar(10, $sentDate->format('Y'), $em),
            'calendar_nov' => Helper::generate_calendar(11, $sentDate->format('Y'), $em),
            'calendar_dec' => Helper::generate_calendar(12, $sentDate->format('Y'), $em),
        )), 'pdfs/ratesheet.pdf');


        /**
         * Prepare Response
         */
        $response = new BinaryFileResponse('pdfs/ratesheet.pdf');
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'RateSheet' . $sentDate->format('Y') . '.pdf');
        return $response;

    }

    /**
     * @Route("/", name="default")
     */
    public function index()
    {
        return $this->redirectToRoute('panel');
    }


    /**
     * @Route("/panel", name="panel")
     * @param ObjectManager $em
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function panel(ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser(), 'ROLE_USER')) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        /**
         * Get all added Competitors
         */
        $competitors = $em->getRepository(CompetitorCheck::class)->findBy(array(
            'isActive' => true,
        ));

        /**
         * Get all Roomtypes
         */
        $rooms = $em->getRepository(Roomtype::class)->findBy(array(
            'isActive' => true,
        ));

        /**
         * Get all RateCodes
         */
        $rates = $em->getRepository(Ratetype::class)->findBy(array(
            'isActive' => true,
        ));

        /**
         * Get latest TaxForm Date
         */
        try {
            $latest_forms = HcbXmlReader::latest_tax_forms();
        } catch (HcbXmlReaderException $e) {
            $latest_forms = 'Error';
        }

        $latest_date = 'Error';
        if ($latest_forms !== null) {
            $latest_date = $latest_forms;
        }

        /**
         * Get Daily Rates
         */
        $today = new DateTime();
        $rateplan = $em->getRepository(Rateplan::class)->findOneBy(array(
            'bookDate' => $today
        ));

        if ($rateplan === null) {
            $todaysRate = 0;
            $todaysRateDbl = 0;
        } else {
            $todaysRate = $rateplan->getPrice();

            $settingDbl = $em->getRepository(HcbSettings::class)->findOneBy(array(
                'name' => 'add_double',
            ));
            if ($settingDbl === null) {
                $todaysRateDbl = 0;
            } else {
                $todaysRateDbl = $todaysRate + $settingDbl->getSetting();
            }
        }


        /**
         * CityTax for Daily Rates
         */
        try {
            $taxesSingle = RateHandler::city_tax($todaysRate, 1, $em);
        } catch (RateHandlerException $e) {
            $taxesSingle = array(
                'rate_no_tax' => 'N/A',
                'city_tax' => 'N/A'
            );
        }

        try {
            $taxesDouble = RateHandler::city_tax($todaysRateDbl, 2, $em);
        } catch (RateHandlerException $e) {
            $taxesDouble = array(
                'rate_no_tax' => 'N/A',
                'city_tax' => 'N/A'
            );
        }

        /**
         * Get Stats
         */
        $today = new DateTime();
        $stats = RateHandler::stats($today, $em);

        return $this->render('default/index.html.twig', [
            'competitors' => $competitors,
            'rooms' => $rooms,
            'forms' => $latest_date,
            'rates' => $rates,
            'single' => number_format($todaysRate, 2),
            'double' => number_format($todaysRateDbl, 2),
            'taxesSingle' => $taxesSingle,
            'taxesDouble' => $taxesDouble,
            'stats' => $stats,
        ]);
    }

    /**
     * @Route("/panel/daily-uploads", name="dailyUploads")
     * @return RedirectResponse|Response
     */
    public function dailyUploads()
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser(), 'ROLE_USER')) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('default/settingsUpload.html.twig', array());

    }

    /**
     * @Route("/panel/settings/rateplan/{date_string}", name="settingsRateplan", defaults={"date_string"="0"})
     * @param string $date_string
     * @param ObjectManager $em
     * @return RedirectResponse|Response
     */
    public function settingsRateplan(string $date_string, ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        if ($date_string === '0') {
            try {
                $now = new DateTime();
            } catch (Exception $e) {
                throw new AccessDeniedHttpException('Could not create Datetime: ' . $e->getMessage());
            }

            $date_string = $now->format('F Y');
        } else {

            $now = DateTime::createFromFormat('m-Y', $date_string);
            if ($now === false) {
                throw new AccessDeniedHttpException('Could not create Datetime: ' . $date_string);
            }

            $date_string = $now->format('F Y');

        }

        try {
            $hfs = RateHandler::hf_by_date($now->format('m-Y'), $em);
        } catch (RateHandlerException $e) {
            throw new AccessDeniedHttpException('Helper Error: ' . $e->getMessage());
        }

        return $this->render('default/settingsRateplan.html.twig', array(
            'date_string' => $date_string,
            'months' => Helper::month_list($em),
            'currMonth' => $now->format('m-Y'),
            'hfs' => $hfs,
        ));

    }

    /**
     * @Route("/panel/settings/room-types", name="settingsRoomtypes")
     * @param ObjectManager $em
     * @return RedirectResponse|Response
     */
    public function settingsRoomtypes(ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('default/settingsRoomtypes.html.twig', array(
            'rooms' => RoomHandler::gather_all($em)
        ));
    }

    /**
     * @Route("/panel/settings/rate-types", name="settingsRatetypes")
     * @param ObjectManager $em
     * @return RedirectResponse|Response
     */
    public function settingsRatetypes(ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('default/settingsRatetypes.html.twig', array(
            'rates' => RateHandler::gather_all($em)
        ));
    }

    /**
     * @Route("/panel/settings/competitors", name="settingsCompetitors")
     * @param ObjectManager $em
     * @return RedirectResponse|Response
     */
    public function settingsCompetitors(ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        $competitors = $em->getRepository(CompetitorCheck::class)->findAll();


        return $this->render('default/settingsCompetitors.html.twig', array(
            'competitors' => $competitors,
        ));
    }

    /**
     * @Route("/panel/settings/budget", name="settingsBudget")
     * @param ObjectManager $em
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function settingsBudget(ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser(), 'ROLE_MANAGER')) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        $budgets = $em->getRepository(Budget::class)->findBy(array(),array(
            'year' => 'DESC',
            'month' => 'DESC',
        ));

        return $this->render('default/settingsBudget.html.twig', array(
            'budgets' => $budgets,
        ));

    }

    /**
     * @Route("/panel/settings/global", name="settingsGlobal")
     * @param ObjectManager $em
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function settingsGlobal(ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        /**
         * Add Settings if the dont exists
         * TODO: Move this to Fixtures
         */

        $settings = $em->getRepository(HcbSettings::class)->findAll();

        $addDouble = true;
        $addExtra = true;
        $addTriple = true;
        $addBf = true;
        foreach ($settings as $setting) {


            if ($setting->getName() === 'add_double') {
                $addDouble = false;
            }

            if ($setting->getName() === 'add_extra') {
                $addExtra = false;
            }

            if ($setting->getName() === 'add_triple') {
                $addTriple = false;
            }

            if ($setting->getName() === 'bf') {
                $addBf = false;
            }
        }


        if ($addDouble) {
            $settingDouble = new HcbSettings();
            $settingDouble->setName('add_double');
            $settingDouble->setSetting(0);
            $em->persist($settingDouble);
        }

        if ($addExtra) {
            $settingExtra = new HcbSettings();
            $settingExtra->setName('add_extra');
            $settingExtra->setSetting(0);
            $em->persist($settingExtra);
        }

        if ($addTriple) {
            $settingTriple = new HcbSettings();
            $settingTriple->setName('add_triple');
            $settingTriple->setSetting(0);
            $em->persist($settingTriple);
        }

        if ($addBf) {
            $settingBf = new HcbSettings();
            $settingBf->setName('bf');
            $settingBf->setSetting(0);
            $em->persist($settingBf);
        }

        try {
            $em->flush();
        } catch (Exception $e) {
            throw new Exception('Could not insert:' . $e->getMessage());
        }


        return $this->render('default/settingsHcb.html.twig', array(
            'add_double' => $em->getRepository(HcbSettings::class)->findOneBy(array(
                'name' => 'add_double'
            )),
            'add_triple' => $em->getRepository(HcbSettings::class)->findOneBy(array(
                'name' => 'add_triple'
            )),
            'add_extra' => $em->getRepository(HcbSettings::class)->findOneBy(array(
                'name' => 'add_extra'
            )),
            'bf' => $em->getRepository(HcbSettings::class)->findOneBy(array(
                'name' => 'bf'
            )),
        ));
    }

    /**
     * @Route("/panel/changelog", name="changelog")
     */
    public function changelog()
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser(), 'ROLE_USER')) {
            throw new AccessDeniedHttpException('Keine Berechtigung');
        }

        $Parsedown = new Parsedown();

        $log = $Parsedown->text((file_get_contents(__DIR__ . '/../../README.md')));


        return $this->render(
            'default/changelog.html.twig',
            array(
                'log' => $log,
            )
        );
    }
}

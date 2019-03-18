<?php

namespace App\Controller;

use App\Entity\HcbSettings;
use App\Util\Helper\Helper;
use App\Util\Helper\HelperException;
use App\Util\Rate\RateHandler;
use App\Util\Room\RoomHandler;
use App\Util\SecurityChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="default")
     */
    public function index()
    {
        return $this->redirectToRoute('panel');
    }

    /**
     * @Route("/panel", name="panel")
     */
    public function panel()
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser(), 'ROLE_USER')) {
            return $this->redirectToRoute('login');
        }

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }

    /**
     * @Route("/panel/daily-uploads", name="dailyUploads")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function dailyUploads()
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('login');
        }

        return $this->render('default/settingsUpload.html.twig', array());

    }

    /**
     * @Route("/panel/settings/rateplan/{date_string}", name="settingsRateplan", defaults={"date_string"="0"})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function settingsRateplan(string $date_string, ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('login');
        }

        if ($date_string === '0') {
            try {
                $now = new \DateTime();
            } catch (\Exception $e) {
                throw new AccessDeniedHttpException('Could not create Datetime: ' . $e->getMessage());
            }

            $date_string = $now->format('F Y');
        } else {

            $now = \DateTime::createFromFormat('m-Y', $date_string);
            if ($now === false) {
                throw new AccessDeniedHttpException('Could not create Datetime: ' . $date_string);
            }

            $date_string = $now->format('F Y');

        }

        try{
            $hfs = Helper::hf_by_date($now->format('m-Y'), $em);
        }catch (HelperException $e){
            throw new AccessDeniedHttpException('Helper Error: '.$e->getMessage());
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function settingsRoomtypes(ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('login');
        }

        return $this->render('default/settingsRoomtypes.html.twig', array(
            'rooms' => RoomHandler::gather_all($em)
        ));
    }

    /**
     * @Route("/panel/settings/rate-types", name="settingsRatetypes")
     * @param ObjectManager $em
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function settingsRatetypes(ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('login');
        }

        return $this->render('default/settingsRatetypes.html.twig', array(
            'rates' => RateHandler::gather_all($em)
        ));
    }

    /**
     * @Route("/panel/settings/global", name="settingsGlobal")
     * @param ObjectManager $em
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function settingsGlobal(ObjectManager $em)
    {

        /* @var $security SecurityChecker */
        $security = new SecurityChecker($this->getUser(), $this->container);

        if (!$security->hasRole($this->getUser())) {
            return $this->redirectToRoute('login');
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

        $Parsedown = new \Parsedown();

        $log = $Parsedown->text((file_get_contents(__DIR__ . '/../../README.md')));


        return $this->render(
            'default/changelog.html.twig',
            array(
                'log' => $log,
            )
        );
    }
}

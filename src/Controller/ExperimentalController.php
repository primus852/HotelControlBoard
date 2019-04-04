<?php

namespace App\Controller;

use App\Util\Rate\RateHandler;
use App\Util\Xml\HcbXmlReader;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ExperimentalController extends AbstractController
{

    /**
     * @Route("/experimental", name="experimental")
     * @param ObjectManager $em
     * @throws \App\Util\Xml\HcbXmlReaderException
     */
    public function index(ObjectManager $em)
    {
        $start = new \DateTime();
        $nights = 3;
        $pax = 1;

        RateHandler::rate_check($start, $nights, $pax, $em);
        die;
    }
}

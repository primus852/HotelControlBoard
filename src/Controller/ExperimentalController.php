<?php

namespace App\Controller;

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
        HcbXmlReader::da($em);
        die;
    }
}

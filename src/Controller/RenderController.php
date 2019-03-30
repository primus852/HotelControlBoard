<?php

namespace App\Controller;

use App\Entity\CompetitorCheck;
use App\Entity\Ratetype;
use App\Entity\Roomtype;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RenderController extends AbstractController
{
    /**
     * @Route("/_render/_version", name="renderVersion")
     * @return Response
     */
    public function renderVersionAction()
    {
        return new Response(getenv('PANEL_VERSION'));
    }

    /**
     * @Route("/panel/_render/_roomtype/{id}", name="renderRoomtype", defaults={"id"="0"})
     * @param int $id
     * @param ObjectManager $em
     * @return Response
     */
    public function renderDetailsRoomtype(int $id, ObjectManager $em)
    {
        /**
         * Find Roomtype
         */
        $room = $em->getRepository(Roomtype::class)->find($id);

        if($room === null){
            return $this->render('render/detailsNotFound.html.twig', array('id' => $id));
        }

        return $this->render('render/detailsRoomtype.html.twig', array(
            'room' => $room
        ));
    }

    /**
     * @Route("/panel/_render/_competitor/{id}", name="renderCompetitor", defaults={"id"="0"})
     * @param int $id
     * @param ObjectManager $em
     * @return Response
     */
    public function renderDetailsCompetitor(int $id, ObjectManager $em)
    {
        /**
         * Find Competitor
         */
        $competitor = $em->getRepository(CompetitorCheck::class)->find($id);

        if($competitor === null){
            return $this->render('render/detailsNotFound.html.twig', array('id' => $id));
        }

        return $this->render('render/detailsCompetitor.html.twig', array(
            'c' => $competitor
        ));
    }

    /**
     * @Route("/panel/_render/_ratetype/{id}", name="renderRatetype", defaults={"id"="0"})
     * @param int $id
     * @param ObjectManager $em
     * @return Response
     */
    public function renderDetailsRatetype(int $id, ObjectManager $em)
    {
        /**
         * Find Ratetype
         */
        $rate = $em->getRepository(Ratetype::class)->find($id);

        if($rate === null){
            return $this->render('render/detailsNotFound.html.twig', array('id' => $id));
        }

        return $this->render('render/detailsRatetype.html.twig', array(
            'rate' => $rate
        ));
    }
}

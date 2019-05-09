<?php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\CompetitorCheck;
use App\Entity\Department;
use App\Entity\Ratetype;
use App\Entity\Roomtype;
use App\Entity\User;
use App\Util\OpenWeather\OpenWeather;
use App\Util\OpenWeather\OpenWeatherException;
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
     * @Route("/_render/_partial/_weather", name="renderWeather")
     * @return Response
     */
    public function renderWeatherAction()
    {

        /**
         * Weather Forecast
         */
        $weather = new OpenWeather(getenv('OPENWEATHER_APPID'));

        try {
            $forecast = $weather->forecast();
        } catch (OpenWeatherException $e) {
            $forecast = array(
                'min' => 'N/A',
                'max' => 'N/A',
                'now' => 'N/A',
                'icon' => 'N/A',
                'condition' => 'N/A',
                'wind' => 'N/A'
            );
        }

        try {
            $hourly = $weather->hourly();
        } catch (OpenWeatherException $e) {
            $hourly = array();
        }

        return $this->render('render/partial/weather.html.twig', [
            'forecast' => $forecast,
            'hourly' => $hourly,
        ]);

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

    /**
     * @Route("/panel/_render/_budget/{id}", name="renderBudget", defaults={"id"="0"})
     * @param int $id
     * @param ObjectManager $em
     * @return Response
     */
    public function renderDetailsBudget(int $id, ObjectManager $em)
    {
        /**
         * Find Budget
         */
        $budget = $em->getRepository(Budget::class)->find($id);

        if($budget === null){
            return $this->render('render/detailsNotFound.html.twig', array('id' => $id));
        }

        return $this->render('render/detailsBudget.html.twig', array(
            'budget' => $budget
        ));
    }

    /**
     * @Route("/panel/_render/_user/{id}", name="renderUser", defaults={"id"="0"})
     * @param int $id
     * @param ObjectManager $em
     * @return Response
     */
    public function renderDetailsUser(int $id, ObjectManager $em)
    {
        /**
         * Find User
         */
        $user = $em->getRepository(User::class)->find($id);

        if($user === null){
            return $this->render('render/detailsNotFound.html.twig', array('id' => $id));
        }

        $departments = $em->getRepository(Department::class)->findAll();

        return $this->render('render/detailsUser.html.twig', array(
            'user' => $user,
            'departments' => $departments,
        ));
    }
}

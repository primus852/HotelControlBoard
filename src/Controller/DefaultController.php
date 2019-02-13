<?php

namespace App\Controller;

use App\Util\SecurityChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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

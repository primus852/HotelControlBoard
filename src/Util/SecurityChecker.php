<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 01.06.2018
 * Time: 13:16
 */

namespace App\Util;


use App\Entity\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SecurityChecker
{

    private $container;
    private $user;

    /**
     * SecurityChecker constructor.
     * @param User $user
     * @param ContainerInterface $container
     */
    public function __construct(User $user, ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->user = $user;
    }

    /**
     * @param User $checkUser
     * @return bool
     */
    public function isUser(User $checkUser)
    {

        return $checkUser !== $this->user ? false : true;

    }

    /**
     * @param User $user
     * @param string $role
     * @return bool
     */
    public function hasRole(User $user, string $role = 'ROLE_SUPER_ADMIN')
    {

        if ($this->container === null) {
            throw new AccessDeniedHttpException('Container empty but needed for "hasRole"');
        }

        if(in_array($role, $user->getRoles())){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function canEdit()
    {
        return $this->user->getAccessGroup()->getCanEdit() ? true : false;
    }

}
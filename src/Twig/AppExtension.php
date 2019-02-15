<?php
/**
 * Created by PhpStorm.
 * User: torsten
 * Date: 15.02.2019
 * Time: 10:15
 */

namespace App\Twig;


use primus852\SimpleCrypt\SimpleCrypt;

class AppExtension extends \Twig_Extension
{

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('decrypt', array($this, 'decryptFilter')),
            new \Twig_SimpleFilter('encrypt', array($this, 'encryptFilter')),
        );
    }

    public function encryptFilter($string)
    {
        return SimpleCrypt::enc($string);
    }

    public function decryptFilter($string)
    {
        return SimpleCrypt::dec($string);
    }

    public function getName()
    {
        return 'app_extension';
    }


}
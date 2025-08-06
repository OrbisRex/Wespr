<?php
/**
 * Routers for WESPR
 * @author David Ehrlich, 2013
 * @license MIT
 */

namespace App;

use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
        OrbisRex\Wespr;

/**
 * Router factory.
 */
class RouterFactory
{
    /**
     * @return \Nette\Application\IRouter
     */
    public function createRouter(Wespr\Translator $translator)
    {
            $router = new RouteList();

            $router[] = new Route('index<? \.html?|\.php|>', 'Front:Homepage:default', Route::ONE_WAY);
            $router[] = new Route('[admin/][<lang [a-z]{2}>/]registration', 'Wespr:Registration:default', Route::ONE_WAY);
            $router[] = new Route('[admin/][<lang [a-z]{2}>/]reminder', 'Wespr:Sign:reminder', Route::ONE_WAY);
            
            $router[] = new Route('[admin/]jirak/[<lang [a-z]{2}>/][<presenter>/][<action>/][<id>]', array(
                'module'    => 'Front',
                'presenter' => 'Homepage',
                'action'    => 'default',
                'lang'      => $translator->getLang()
            ), Route::ONE_WAY);
            
            $router[] = new Route('admin/[<lang [a-z]{2}>/][<presenter>/][<action>/][<id>]', array(
                'module'    => 'Wespr',
                'presenter' => 'Sign',
                'action'    => 'in',
                'lang'      => $translator->getLang()
            ));
            
            $router[] = new Route('[<lang [a-z]{2}>/][<presenter>/][<action>/][<id>]', array(
                'module'    => 'Front',
                'presenter' => 'Homepage',
                'action'    => 'default',
                'lang'      => $translator->getLang()
            ));
            
            return $router;
    }

}

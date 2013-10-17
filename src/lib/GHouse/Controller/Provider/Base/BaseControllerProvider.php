<?php // base class for controller providers
namespace GHouse\Controller\Provider\Base;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


abstract class BaseControllerProvider implements ControllerProviderInterface
{
	/**
	 * Set controller definitions in this method for access to middlewares
	 * 
	 * @param Application $app
	 * @param Array       $middlewares An array of middlewares provided by setMiddlewares()
	 */
	abstract function setControllers(Application $app, Array $middlewares);

	/**
	 * Set middlewares for controllers to access. Should return an array of middlewares.
	 * 
	 * @param Application $app [description]
	 */
	abstract function setMiddlewares(Application $app);

	/**
	 * Gives controllers access to the middlewares before connecting routes.
	 * 
	 * {@inheritdoc}
	 * 
	 * @param  Application $app
	 * @return \Silex\ControllerCollection
	 */
	public function connect(Application $app)
	{
		return $this->setControllers($app, $this->setMiddlewares($app));
	}
}
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
	abstract function setControllers(Application $app, Array $middlewares);
	abstract function setMiddlewares(Application $app);

	public function connect(Application $app)
	{
		return $this->setControllers($app, $this->setMiddlewares($app));
	}
}
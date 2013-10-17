<?php // image serving controllers (ghouse.co)
namespace GHouse\Controller\Provider;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GHouse\Controller\Provider\Base\BaseControllerProvider as Base_Controller_Provider;

class ImageControllerProvider extends Base_Controller_Provider
{
	public function setControllers(Application $app, Array $mw)
	{
		$controller = $app['controllers_factory'];


		/* <!-- Start Controller Definitions -- */

		// Artist Images
		$controller->match('/artist/{artist_slug}.{file_ext}', function (Request $request, $artist_slug, $file_ext) use ($app) {

			$filePath = $app['media_dir'] . '/uploads/artist/' . $artist_slug . '/' . $artist_slug . '.' . $file_ext;

			if (!file_exists($filePath))
				$app->abort(404, 'No such image.');

			return $app->sendFile($filePath);


		})->method('GET');


		// Artist Images - Thumbs
		$controller->match('/thumb/artist/{artist_slug}.{file_ext}', function (Request $request, $artist_slug, $file_ext) use ($app) {

			$filePath = $app['media_dir'] . '/uploads/artist/' . $artist_slug . '/' . $artist_slug . '.' . $file_ext;

			if (!file_exists($filePath))
				$app->abort(404, 'No such image.');

			$thumbPath = $app['media_dir'] . '/uploads/artist/' . $artist_slug . '/' . $artist_slug . '_thumb.' . $file_ext;

		    if (!file_exists($thumbPath))
		    {
				$image = $app['imagine']->open($filePath);
			    $transformation = new \Imagine\Filter\Transformation();
			    $transformation->thumbnail(new \Imagine\Image\Box(200, 200));
			    $image = $transformation->apply($image);
			    $image->save($thumbPath, array('quality' => 80));
		    }

		    return $app->sendFile($thumbPath);

		})->method('GET');	


		// Label Images
		$controller->match('/label/{label_slug}.{file_ext}', function (Request $request, $label_slug, $file_ext) use ($app) {

			$filePath = $app['media_dir'] . '/uploads/label/' . $label_slug . '.' . $file_ext;

			if (!file_exists($filePath))
				$app->abort(404, 'No such image.');

			return $app->sendFile($filePath);


		})->method('GET');


		// Label Images - Thumbs
		$controller->match('/thumb/label/{label_slug}.{file_ext}', function (Request $request, $label_slug, $file_ext) use ($app) {
			$filePath = $app['media_dir'] . '/uploads/label/' . $label_slug . '.' . $file_ext;

			if (!file_exists($filePath))
				$app->abort(404, 'No such image.');

			$thumbPath = $app['media_dir'] . '/uploads/label/' . $label_slug . '_thumb.' . $file_ext;

		    if (!file_exists($thumbPath))
		    {
				$image = $app['imagine']->open($filePath);
			    $transformation = new \Imagine\Filter\Transformation();
			    $transformation->thumbnail(new \Imagine\Image\Box(175, 175));
			    $image = $transformation->apply($image);
			    $image->save($thumbPath, array('quality' => 80));
		    }

		    return $app->sendFile($thumbPath);

		})->method('GET');		


		// Release Images
		$controller->match('/artist/{artist_slug}/releases/{release_slug}.{file_ext}', function (Request $request, $release_slug, $file_ext, $artist_slug) use ($app) {

			$filePath = $app['media_dir'] . '/uploads/artist/' . $artist_slug  . '/releases/' . $release_slug . '.' . $file_ext;

			if (!file_exists($filePath))
				$app->abort(404, 'No such image.');

			return $app->sendFile($filePath);


		})->method('GET');


		// Release Images - Thumbs
		$controller->match('/thumb/artist/{artist_slug}/releases/{release_slug}.{file_ext}', function (Request $request, $release_slug, $file_ext, $artist_slug) use ($app) {	
			$filePath = $app['media_dir'] . '/uploads/artist/' . $artist_slug  . '/releases/' . $release_slug . '.' . $file_ext;

			if (!file_exists($filePath))
				$app->abort(404, 'No such image.');		    

		    $thumbPath = $app['media_dir'] . '/uploads/artist/' . $artist_slug . '/releases/' . $release_slug . '_thumb.' . $file_ext;

		    if (!file_exists($thumbPath))
		    {
				$image = $app['imagine']->open($filePath);
			    $transformation = new \Imagine\Filter\Transformation();
			    $transformation->thumbnail(new \Imagine\Image\Box(200, 200));
			    $image = $transformation->apply($image);
			    $image->save($thumbPath, array('quality' => 80));
		    }

		    return $app->sendFile($thumbPath);

		})->method('GET');


		// Release Images - Twitter Card Thumbs
		$controller->match('/twitter_thumb/artist/{artist_slug}/releases/{release_slug}.{file_ext}', function (Request $request, $release_slug, $file_ext, $artist_slug) use ($app) {	
			$filePath = $app['media_dir'] . '/uploads/artist/' . $artist_slug  . '/releases/' . $release_slug . '.' . $file_ext;

			if (!file_exists($filePath))
				$app->abort(404, 'No such image.');		    

		    $thumbPath = $app['media_dir'] . '/uploads/artist/' . $artist_slug . '/releases/' . $release_slug . '_twitter_thumb.' . $file_ext;

		    if (!file_exists($thumbPath))
		    {
				$image = $app['imagine']->open($filePath);
			    $transformation = new \Imagine\Filter\Transformation();
			    $transformation->thumbnail(new \Imagine\Image\Box(263, 263));
			    $image = $transformation->apply($image);
			    $image->save($thumbPath, array('quality' => 80));
		    }

		    return $app->sendFile($thumbPath);

		})->method('GET|POST');		

		/* -- End Controller Definitions //--> */


		return $controller;
	}

	public function setMiddlewares(Application $app)
	{

		$middleware = array();


		/* <!-- Start Middleware Definitions -- */


		/* -- End Middleware Definitions //--> */


		return $middleware;
	}
}
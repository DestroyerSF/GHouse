<?php // main site controllers (ghouse.co)
namespace GHouse\Controller\Provider;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GHouse\Controller\Provider\Base\BaseControllerProvider as Base_Controller_Provider;

class MainControllerProvider extends Base_Controller_Provider
{
	public function setControllers(Application $app, Array $mw)
	{
		$controller = $app['controllers_factory'];


		/* <!-- Start Controller Definitions -- */


		// Index page
		$controller->match('/', function (Request $request) use ($app) {

			return $app->render('front/index.html.twig',
				array('page_title' => 'Music Collective', 'home' => true, 'page_script' => 'home'));

		})
		->method('GET|POST')
		->before($mw['get_spotlight_features'])
		->before($mw['get_labels'])
		->before($mw['get_artists']);


		// AJAX release portion
		$controller->match('/ajax/{artist_slug}/{release_slug}', function (Request $request) use ($app) {
			if (!$request->isXmlHttpRequest())
				return $app->abort(404, 'Wrong request method.');

			return $app->render('partials/release.html.twig', array('featured_release' => $app['release']));

		})
		->method('POST')
		->before($mw['get_artist'])
		->before($mw['get_release']);

		// Artist page
		$controller->match('/{artist_slug}', function (Request $request) use ($app) {
			return $app->render('front/artist.html.twig',
				array('page_script' => 'artist', 'page_title' => $app['artist']['name'], 'featured_release' => $app['artist']['releases'][0]));

		})
		->method('GET')
		->before($mw['get_artist']);


		// Artist release page
		$controller->match('/{artist_slug}/{release_slug}', function (Request $request) use ($app) {

			return $app->render('front/artist.html.twig',
				array('page_script' => 'artist', 'page_title' => $app['artist']['name'], 'featured_release' => $app['release']));

		})
		->method('GET')
		->before($mw['get_artist'])
		->before($mw['get_release']);

		/* -- End Controller Definitions //--> */


		return $controller;
	}

	public function setMiddlewares(Application $app)
	{

		$middleware = array();


		/* <!-- Start Middleware Definitions -- */

		$middleware['get_spotlight_features'] = function (Request $request) use ($app) {
			$spotlight_main = $app['db']->fetchAssoc('SELECT * FROM `spotlight_feature` WHERE `stack_order` = 0');
			$spotlight_sub = $app['db']->fetchAll('SELECT * FROM `spotlight_feature` WHERE `stack_order` > 0 ORDER BY `stack_order` ASC');

			if ($spotlight_main['release_id'] != null)
			{
				$release = $app['db']->fetchAssoc('SELECT * FROM `release` WHERE `id` = ?', array($spotlight_main['release_id']));
				$artist = $app['db']->fetchAssoc('SELECT * FROM `artist` WHERE `id` = ?', array($release['artist_id']));

				$spotlight_main['title'] = $artist['name'] . ' &mdash; ' . $release['name'];
				$spotlight_main['url'] = $app['base_url'] . '/' . $artist['slug'] . '/' . $release['slug'];
				$spotlight_main['image_file_url'] = $app['base_url'] . '/img/artist/' . $artist['slug'] . '/releases/' . $release['slug'] . '.' . $release['picture_ext'];
			}

			foreach ($spotlight_sub as $key => $spotlight)
			{
				if ($spotlight['release_id'] != null)
				{
					$release = $app['db']->fetchAssoc('SELECT * FROM `release` WHERE `id` = ?', array($spotlight['release_id']));
					$artist = $app['db']->fetchAssoc('SELECT * FROM `artist` WHERE `id` = ?', array($release['artist_id']));

					$spotlight_sub[$key]['title'] = $artist['name'] . ' &mdash; ' . $release['name'];
					$spotlight_sub[$key]['url'] = $app['base_url'] . '/' . $artist['slug'] . '/' . $release['slug'];
					$spotlight_sub[$key]['image_file_url'] = $app['base_url'] . '/img/artist/' . $artist['slug'] . '/releases/' . $release['slug'] . '.' . $release['picture_ext'];
				}
			}

			$app['spotlight_features'] = array('main' => $spotlight_main, 'sub_features' => $spotlight_sub);
		};


		$middleware['get_labels'] = function (Request $request) use ($app) {
			$labels = $app['db']->fetchAll('SELECT * FROM `label` ORDER BY `stack_order` ASC');

			foreach ($labels as $key => $label)
			{
				$labels[$key]['picture_url'] = $app['base_url'] . '/img/thumb/label/' . $label['slug'] . '.' . $label['picture_ext'];
			}

			$app['labels'] = $labels;
			$app['labels_width'] = $app['count_to_width'](count($app['labels']));
		};


		$middleware['get_artists'] = function (Request $request) use ($app) {
			$artists = $app['db']->fetchAll('SELECT * from `artist` ORDER BY `name` ASC');

			foreach($artists as $artist_key => $artist)
			{
				// Taken out temporarily for performance
				//$artist['shows']	= $app['songkick.get_artist_concerts']($artist['songkick_artist_id']);
				$artist['releases'] = $app['db']->fetchAll('SELECT * FROM `release` WHERE `artist_id` = ? ORDER BY `release_date` DESC', array($artist['id']));

				foreach($artist['releases'] as $key => $release)
				{
					$label = $app['db']->fetchAssoc('SELECT * FROM `label` WHERE `id` = ?', array($release['label_id']));

					$artist['labels'][$label['slug']] = $label;
					$artist['releases'][$key]['label'] = $label;
				}

				if ($artist['picture_ext'] != '' || isset($artist['releases'][0]))
				{
					// Set the artist's picture URL for use in the template
					$artist['picture_url'] = $app['base_url'] . '/img/thumb/artist/' . $artist['slug'];
					// If one isn't set for the artist, use the latest release
					$artist['picture_url'] .= $artist['picture_ext'] == '' ? '/releases/' . $artist['releases'][0]['slug'] . '.' . $artist['releases'][0]['picture_ext'] : '.' . $artist['picture_ext'];
				}
				else
				{
					$artist['picture_url'] = $app['base_url'] . '/assets/img/ghouse-logo-new.png';
				}

				$artists[$artist_key] = $artist;
			}
			

			$app['artists'] = $artists;
		};


		$middleware['get_artist'] = function (Request $request) use ($app) {
			$artist = $app['db']->fetchAssoc('SELECT * from `artist` WHERE `slug` = ?', array($request->get('artist_slug')));

			if (!$artist)
				$app->abort(404, 'No such artist.');

			
			$page_url = $app['base_url'].'/'.$artist['slug'];

			$tweet_link = "https://twitter.com/share?";

			if ($artist['smart_url'] != null) 	// if there's a smart url, set that as the main url and the page url as the count
				$tweet_link .= "url=".urlencode($artist['smart_url'])."&counturl=".urlencode($page_url);
			else 								// otherwise, just use the page url
				$tweet_link .= "url=".urlencode($page_url);

			$tweet_link .= "&via=ghouse&related=".$artist['twitter_handle']."&text=".urlencode("@".$artist['twitter_handle']);

			$booking_agent_id = $app['db']->fetchColumn('SELECT `booking_agent_id` FROM `booking_agent__artist` WHERE `artist_id` = ?', array($artist['id']));

			$artist['booking_agent']	= $booking_agent_id ? $app['db']->fetchAssoc('SELECT * FROM `booking_agent` WHERE `id` = ?', array($booking_agent_id)) : false;
			$artist['shows']			= $app['songkick.get_artist_concerts']($artist['songkick_artist_id']);
			$artist['tweet_link']		= $tweet_link;
			$artist['recent_tweet']		= ($artist['twitter_handle'] == '') ? false : $app['twitter.statuses.user_timeline']($artist['twitter_handle']);
			$artist['releases']			= $app['db']->fetchAll('SELECT * FROM `release` WHERE `artist_id` = ? ORDER BY `release_date` DESC', array($artist['id']));

			if (empty($artist['releases']))
			{
				return $app->redirect('/');
			}

			foreach($artist['releases'] as $key => $release)
			{
				if (strlen($release['name']) > 21)
				{
					$artist['releases'][$key]['name'] = trim(substr($release['name'], 0, 20))."&hellip;";
				}

				$label = $app['db']->fetchAssoc('SELECT * FROM `label` WHERE `id` = ?', array($release['label_id']));
				
				$page_url = $app['base_url'].'/'.$artist['slug'].'/'.$release['slug'];

				$tweet_link = "https://twitter.com/share?";

				if ($release['smart_url'] != null) 	// if there's a smart url, set that as the main url and the page url as the count
					$tweet_link .= "url=".urlencode($release['smart_url'])."&counturl=".urlencode($page_url);
				else 								// otherwise, just use the page url
					$tweet_link .= "url=".urlencode($page_url);

				$tweet_link .= "&via=GHouse&related=".$artist['twitter_handle']."&text=".urlencode($release['name']." by @".$artist['twitter_handle']);

				$artist['labels'][$label['slug']] = $label;
				$artist['releases'][$key]['label'] = $label;
				$artist['releases'][$key]['tweet_link'] = $tweet_link;
			}

				if ($artist['picture_ext'] != '' || isset($artist['releases'][0]))
				{
					// Set the artist's picture URL for use in the template
					$artist['picture_url'] = $app['base_url'] . '/img/thumb/artist/' . $artist['slug'];
					// If one isn't set for the artist, use the latest release
					$artist['picture_url'] .= $artist['picture_ext'] == '' ? '/releases/' . $artist['releases'][0]['slug'] . '.' . $artist['releases'][0]['picture_ext'] : '.' . $artist['picture_ext'];
				}
				else
				{
					$artist['picture_url'] = $app['base_url'] . '/assets/img/ghouse-logo-new.png';
				}

			$app['artist'] = $artist;
		};


		$middleware['get_release'] = function (Request $request) use ($app) {

			$artist = $app['artist'] ? $app['artist'] : $app['db']->fetchAssoc('SELECT * FROM `artist` WHERE `slug` = ?', array($request->get('artist_slug')));

			if (!$artist)
				$app->abort(404, 'No such artist.');

			$release = $app['db']->fetchAssoc('SELECT * FROM `release` WHERE `artist_id` = ? AND `slug` = ?', array($artist['id'], $request->get('release_slug')));

			if (!$release)
				$app->abort(404, 'No such release.');

				
			$label = $app['db']->fetchAssoc('SELECT * FROM `label` WHERE `id` = ?', array($release['label_id']));
			
			$page_url = $app['base_url'].'/'.$artist['slug'].'/'.$release['slug'];

			$tweet_link = "https://twitter.com/share?";

			if ($release['smart_url'] != null) 	// if there's a smart url, set that as the main url and the page url as the count
				$tweet_link .= "url=".urlencode($release['smart_url'])."&counturl=".urlencode($page_url);
			else 								// otherwise, just use the page url
				$tweet_link .= "url=".urlencode($page_url);

			$tweet_link .= "&via=ghouse&related=".$artist['twitter_handle']."&text=".urlencode($release['name']." by @".$artist['twitter_handle']);

			$release['tweet_link'] = $tweet_link;
			$release['label'] = $label;
			$app['release'] = $release;
		};

		/* -- End Middleware Definitions //--> */


		return $middleware;
	}
}
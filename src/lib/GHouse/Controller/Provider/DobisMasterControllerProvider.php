<?php // dobis master controllers (ghouse.co)
namespace GHouse\Controller\Provider;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use GHouse\Controller\Provider\Base\BaseControllerProvider as Base_Controller_Provider;

class DobisMasterControllerProvider extends Base_Controller_Provider
{
	public function setControllers(Application $app, Array $mw)
	{
		$controller = $app['controllers_factory'];
		$controller->before($mw['dobis_version']);


		/* <!-- Start Controller Definitions -- */

		// Index page
		$controller->match('/', function (Request $request) use ($app) {

			return $app->render('dobis/index.html.twig');

		})
		->method('GET')
		->before($mw['must_be_logged_in'])
		->before($mw['get_artists']);



		// Login page
		$controller->match('/login', function (Request $request) use ($app) {
			
			$data = array('username' => '', 'password' => '');
			$message = null;


			$form = $app['form.factory']->createNamedBuilder('login', 'form', $data)
				->add('username', 'text', array('label' => false))
				->add('password', 'password', array('label' => false))
				->getForm();

			if ($request->getMethod() == 'POST')
			{
				$form->bind($request);

				if ($form->isValid())
				{
					$data = $form->getData();

					$dobis_user = $app['db']->fetchAssoc('SELECT * FROM `dobis_user` WHERE `username` = ? and `password` = ?', array($data['username'], md5($data['password'])));

					if (!$dobis_user)
					{
						$message = array('error', 'Invalid dengus or paynuss. Please try again.');
					}
					else
					{
						$app['db']->update('dobis_user', array('last_login' => time()), array('id' => $dobis_user['id']));
						$app['session']->start();
						$user['authenticated'] = 'yes';
						$app['session']->set('dobis_user', $dobis_user);
						
						return $app->redirect('/dobismaster/');
					}
				}
				else
				{
					$message = array('error', '<strong>Error:</strong> Tengus.');
				}

			}

			return $app->render('dobis/login.html.twig', array('form' => $form->createView(), 'message' => $message));

		})->method('GET|POST')->before($mw['must_be_logged_out']);

		
		// Logout
		$controller->match('/logout', function (Request $request) use ($app) {
			$app['session']->set('dobis_user', null);
			$app['session']->clear();

			$app['session']->getFlashBag()->set('notice', 'Great Job!');

			return $app->redirect('/dobismaster/login');

		})->method('GET');


		// Booking Agent Management
		$controller->match('/booking', function (Request $request) use ($app) {

			$message = null;

			$existing_booking_agents_form = $app['form.factory']->createNamedBuilder('existing_booking_agents', 'form');

			$existing_booking_agents = $app['db']->fetchAll('SELECT * FROM `booking_agent`');

			foreach ($existing_booking_agents as $key => $booking_agent)
			{
				$existing_booking_agent_form = $app['form.factory']->createNamedBuilder($booking_agent['id'], 'form', $booking_agent)
					->add('id', 'hidden')
					->add('remove', 'hidden')
					->add('name', 'text', array('constraints' => array(new Assert\NotBlank())))
					->add('email', 'email', array('constraints' => array(new Assert\Email(), new Assert\NotBlank())));

				// Add any artists that the agent manages to the $existing_booking_agents array
				$existing_booking_agents[$key]['managed_artists'] = $app['db']->fetchAll('SELECT `baa`.`artist_id`, `a`.`name`, `a`.`slug` FROM `artist` `a`, `booking_agent__artist` `baa` WHERE `a`.`id` = `baa`.`artist_id` AND `baa`.`booking_agent_id` = ?', array($booking_agent['id']));
				
				$existing_booking_agents_form->add($existing_booking_agent_form);
			}

			$booking_agents_form = $app['form.factory']->createNamedBuilder('booking_agents_form', 'form')
				->add($existing_booking_agents_form)
				->getForm();

			// New Booking Agents Form -- Leave blank; will be filled in if need be
			$new_booking_agents_form = false;

			// handle form submisstion
			if ($request->getMethod() == 'POST')
			{
				$booking_agents_form->bind($request);

				$new_booking_agents = $request->get('new_booking_agents');
				if ($new_booking_agents)
				{
					$new_booking_agents_form = $app['form.factory']->createNamedBuilder('new_booking_agents', 'form', null, array('csrf_protection' => false));
					
					foreach($new_booking_agents as $key => $agent)
					{
						$new_booking_agent_form = $app['form.factory']->createNamedBuilder($key, 'form', null, array('csrf_protection' => false))
							->add('name', 'text', array('constraints' => array(new Assert\NotBlank())))
							->add('email', 'email', array('constraints' => array(new Assert\Email(), new Assert\NotBlank())));

						$new_booking_agents_form->add($new_booking_agent_form);
					}

					$new_booking_agents_form = $new_booking_agents_form->getForm()->bind($request);
					$new_booking_agents_valid = $new_booking_agents_form->isValid();
				}
				else
				{					
					$new_booking_agents_valid = true;
				}

				// If everything is valid...
				if ($booking_agents_form->isValid() && $new_booking_agents_valid)
				{
					$bookingAgentsData = $booking_agents_form->getData();

					foreach($bookingAgentsData['existing_booking_agents'] as $agent)
					{
						if ($agent['remove'] == '1')
						{
							$app['db']->delete('booking_agent', array('id' => $agent['id']));
							$app['db']->delete('booking_agent__artist', array('booking_agent_id' => $agent['id']));
							
						}
						else
						{
							$app['db']->update('booking_agent', array(
								'name' => $agent['name'],
								'email' => $agent['email']
							), array('id' => $agent['id']));
						}
					}

					if ($new_booking_agents_form)
					{
						$newAgentsData = $new_booking_agents_form->getData();

						foreach ($newAgentsData as $new_agent)
						{
							$app['db']->insert('booking_agent', array(
								'name' => $new_agent['name'],
								'email' => $new_agent['email']
							));
						}
					}

					$app['session']->getFlashBag()->set('notice', 'Great Job!');

					return $app->redirect('/dobismaster/booking');
					
				}

				$message = "There were errors with your entry.<br>Please scroll down and correct the listed errors.";
			}

			// If we've got new booking agents added with errors, create the view now...
			if ($new_booking_agents_form)
				$new_booking_agents_form = $new_booking_agents_form->createView();

			return $app->render('dobis/booking.html.twig', array('page_script' => 'dobis-booking', 'new_booking_agents_form' => $new_booking_agents_form, 'booking_agents_form' => $booking_agents_form->createView(), 'existing_booking_agents' => $existing_booking_agents, 'message' => $message));

		})->method('GET|POST')->before($mw['must_be_logged_in']);


		// Edit Artist / New Artist
		$controller->match('/artists/{artist_slug}', function (Request $request, $artist_slug) use ($app) {

			$message = null;

			if ($artist_slug == 'new')
			{
				$artist = array();
				$releases = array();
			}
			else
			{
				$artist = $app['db']->fetchAssoc('SELECT * FROM `artist` WHERE `slug` = ?', array($artist_slug));
				
				if (!$artist)
					return $app->abort(404, 'No such artist.');

				// Grab the booking agent if there is one
				$booking_agent = $app['db']->fetchColumn('SELECT `booking_agent_id` FROM `booking_agent__artist` WHERE `artist_id` = ?', array($artist['id']));
				$artist['booking_agent'] = $booking_agent ? $booking_agent : 'false';
				

				$releases = $app['db']->fetchAll('SELECT * FROM `release` WHERE `artist_id` = ? ORDER BY `release_date` DESC', array($artist['id']));
				
				if ($artist['picture_ext'] != '' || isset($releases[0]))
				{
					// Set the artist's picture URL for use in the template
					$artist['picture_url'] = $app['base_url'] . '/img/thumb/artist/' . $artist['slug'];
					// If one isn't set for the artist, use the latest release
					$artist['picture_url'] .= $artist['picture_ext'] == '' ? '/releases/' . $releases[0]['slug'] . '.' . $releases[0]['picture_ext'] : '.' . $artist['picture_ext'];
				}
				else
				{
					$artist['picture_url'] = $app['base_url'] . '/assets/img/ghouse-logo-new.png';
				}
			}

			// Labels Dropdown
			$labels = $app['db']->fetchAll('SELECT * FROM `label`');
			$labels_dropdown = array();
			foreach ($labels as $label)
				$labels_dropdown[$label['id']] = $label['name'];


			// Booking Agents Dropdown
			$booking_agents = $app['db']->fetchAll('SELECT * FROM `booking_agent` ORDER BY `name` ASC');
			$booking_agents_dropdown = array();
			$booking_agents_dropdown['false'] = 'No Agent';
			foreach ($booking_agents as $booking_agent)
				$booking_agents_dropdown[$booking_agent['id']] = $booking_agent['name'] . ' [' . $booking_agent['email'] . ']';

			$artist['remove_picture'] = 'false';
			
			// Artist Form
			$artist_form = $app['form.factory']->createNamedBuilder('artist', 'form', $artist)
				->add('id', 'hidden')
				->add('name', 'text', array('constraints' => array(new Assert\NotBlank())))
				->add('location', 'text', array('constraints' => array(new Assert\NotBlank())))
				->add('new_picture', 'file', array('constraints' => array(
					new Assert\File(array(
						'mimeTypes' => array('image/png', 'image/jpeg', 'image/gif'),
						'mimeTypesMessage' => 'File must be a png, jpeg, or gif.'
					)),
					new Assert\Image(array(
						'minHeight' => 200,
						'minHeightMessage' => 'Image must be at least {{ min_height }}px by {{ min_height }}px.',
						'minWidth' => 200,
						'minWidthMessage' => 'Image must be at least {{ min_width }}px by {{ min_width }}px.'
					))
				)))
				->add('remove_picture', 'hidden')
				->add('twitter_handle', 'text', array('constraints' => array(new Assert\NotBlank())))
				->add('twitter_card_description', 'textarea', array('constraints' => array(new Assert\Length(array('max' => 200)))))
				->add('songkick_artist_id')
				->add('smart_url')
				->add('booking_agent', 'choice', array('choices' => $booking_agents_dropdown));

			// Set up the releases form...
			$releases_form = $app['form.factory']->createNamedBuilder('releases', 'form');


			// ...and construct it accordingly
			if(!empty($releases))
			{
				$existing_releases_form = $app['form.factory']->createNamedBuilder('existing_releases', 'form');

				foreach($releases as $release)
				{
					$release['release_date'] = new \DateTime($release['release_date']);

					$release_form = $app['form.factory']->createNamedBuilder($release['id'], 'form', $release)
						->add('id', 'hidden')
						->add('name', 'text', array('constraints' => array(new Assert\NotBlank())))
						->add('slug', 'hidden')
						->add('label_id', 'choice', array('choices' => $labels_dropdown))
						->add('spotify_uri')
						->add('bandcamp_album_id')
						->add('itunes_url', 'text')
						->add('release_date', 'date', array('constraints' => array(new Assert\NotBlank())))
						->add('new_picture', 'file', array('constraints' => array(
							new Assert\File(array(
								'mimeTypes' => array('image/png', 'image/jpeg', 'image/gif'),
								'mimeTypesMessage' => 'File must be a png, jpeg, or gif.'
							)),
							new Assert\Image(array(
								'minHeight' => 200,
								'minHeightMessage' => 'Image must be at least {{ min_height }}px by {{ min_height }}px.',
								'minWidth' => 200,
								'minWidthMessage' => 'Image must be at least {{ min_width }}px by {{ min_width }}px.'
							))
						)))
						->add('twitter_card_description', 'textarea', array('constraints' => array(new Assert\Length(array('max' => 200)))))
						->add('smart_url')
						->add('remove', 'hidden');

					$existing_releases_form->add($release_form);
				}

				// Releases Form
				$releases_form->add($existing_releases_form);
			}

			// Parent form
			$parent_form = $app['form.factory']->createNamedBuilder('dobis_form', 'form')
				->add($artist_form)
				->add($releases_form)
				->getForm();

			// New Releases Form -- Leave blank; will be filled in if need be
			$new_releases_form = false;

			// Process submission
			if ($request->getMethod() == 'POST')
			{
				$parent_form->bind($request);

				$new_releases = $request->get('new_releases');
				if ($new_releases)
				{
					$new_releases_form = $app['form.factory']->createNamedBuilder('new_releases', 'form', null, array('csrf_protection' => false));
					
					foreach($new_releases as $key => $release)
					{
						$release_form = $app['form.factory']->createNamedBuilder($key, 'form', null, array('csrf_protection' => false))
							->add('name', 'text', array('constraints' => array(new Assert\NotBlank())))
							->add('label_id', 'choice', array('choices' => $labels_dropdown))
							->add('twitter_card_description', 'textarea', array('constraints' => array(new Assert\Length(array('max' => 200)))))
							->add('spotify_uri')
							->add('bandcamp_album_id')
							->add('itunes_url', 'text')
							->add('release_date', 'date', array('constraints' => array(new Assert\NotBlank())))
							->add('new_picture', 'file', array('constraints' => array(
								new Assert\NotBlank(),
								new Assert\File(array(
									'mimeTypes' => array('image/png', 'image/jpeg', 'image/gif'),
									'mimeTypesMessage' => 'File must be a png, jpeg, or gif.'
								)),
								new Assert\Image(array(
									'minHeight' => 200,
									'minHeightMessage' => 'Image must be at least {{ min_height }}px by {{ min_height }}px.',
									'minWidth' => 200,
									'minWidthMessage' => 'Image must be at least {{ min_width }}px by {{ min_width }}px.'
								))
							)))
							->add('smart_url');

						$new_releases_form->add($release_form);
					}

					$new_releases_form = $new_releases_form->getForm()->bind($request);
					$new_releases_valid = $new_releases_form->isValid();
				}
				else
				{					
					$new_releases_valid = true;
				}

				// If everything is valid...
				if ($parent_form->isValid() && $new_releases_valid)
				{
					$dobisData = $parent_form->getData();


					// get the artist info ready for the database
					$artistData = $dobisData['artist'];
					$artistData['slug'] = $app['slugify']($artistData['name']);


					// make sure the artist's media folders are set up
					$artist_media_dir = $app['media_dir'].'/uploads/artist/'.$artistData['slug'];
					$release_media_dir = $artist_media_dir.'/releases';

					if (!is_dir($artist_media_dir))
					{
						mkdir($artist_media_dir);
						chmod($artist_media_dir, 0777);
						mkdir($release_media_dir);
						chmod($release_media_dir, 0777);
					}

					// upload picture
					if (!is_null($artistData['new_picture']))
					{
						$artistData['picture_ext'] = $artistData['new_picture']->guessExtension();
						$filename = $artistData['slug'].'.'.$artistData['picture_ext'];
						$thumb_filename = $artistData['slug'].'_thumb.'.$artistData['picture_ext'];
						$fullsizePath = $artist_media_dir . '/' . $filename;
						$thumbPath = $artist_media_dir . '/' . $thumb_filename;

						$artistData['new_picture']->move($artist_media_dir, $filename);
						chmod($fullsizePath, 0777);

						$image = $app['imagine']->open($fullsizePath);
					    $transformation = new \Imagine\Filter\Transformation();
					    $transformation->thumbnail(new \Imagine\Image\Box(200, 200));
					    $image = $transformation->apply($image);
					    $image->save($thumbPath, array('quality' => 80));
					    chmod($thumbPath, 0777);
					}
					// no new picture uploaded
					else
					{
						// artist exists
						if (isset($artist['id']))
						{
							// remove artist image if necessary
							if ($artistData['remove_picture'] == 'true')
							{
								// delete both the full-size and thumbnail pictures
								$fullsizePath = $artist_media_dir . '/' . $artistData['slug'] . '.' . $artistData['picture_ext'];
								$thumbPath = $artist_media_dir . '/' . $artistData['slug'] . '_thumb.' . $artistData['picture_ext'];
								unlink($fullsizePath);
								unlink($thumbPath);
								$artistData['picture_ext'] = null;
							}
							// otherwise, keep it the same
							else
							{
								$artistData['picture_ext'] = $artist['picture_ext'];	
							}
						}
						// artist doesn't exist
						else
						{
							$artistData['picture_ext'] = null;
						}
							
					}


					if ($artist_slug == 'new')
					{
						$app['db']->insert('artist', array(
							'name' => $artistData['name'],
							'slug' => $artistData['slug'],
							'location' => $artistData['location'],
							'twitter_handle' => $artistData['twitter_handle'],
							'twitter_card_description' => $artistData['twitter_card_description'],
							'songkick_artist_id' => $artistData['songkick_artist_id'],
							'picture_ext' => $artistData['picture_ext'],
							'smart_url' => $artistData['smart_url']
						));

						$artist_id = $app['db']->lastInsertId();
						$artist = $app['db']->fetchAssoc('SELECT * FROM `artist` WHERE `id` = ?', array($artist_id));

						// add the booking contact if there is one
						if ($artistData['booking_agent'] != 'false')
						{
							$app['db']->insert('booking_agent__artist', array(
								'booking_agent_id' => $artistData['booking_agent'],
								'artist_id' => $artist['id']
							));
						}
					}
					else
					{
						$app['db']->update('artist', array(
							'name' => $artistData['name'],
							'slug' => $artistData['slug'],
							'location' => $artistData['location'],
							'twitter_handle' => $artistData['twitter_handle'],
							'twitter_card_description' => $artistData['twitter_card_description'],
							'songkick_artist_id' => $artistData['songkick_artist_id'],
							'picture_ext' => $artistData['picture_ext'],
							'smart_url' => $artistData['smart_url']),
							array('id' => $artistData['id']));

						$artist = $app['db']->fetchAssoc('SELECT * FROM `artist` WHERE `id` = ?', array($artistData['id']));

						$booking_contact= $app['db']->fetchAssoc('SELECT `id`, `booking_agent_id` FROM `booking_agent__artist` WHERE `artist_id` = ?', array($artist['id']));

						if ($artistData['booking_agent'] == 'false' && $booking_contact)
							$app['db']->delete('booking_agent__artist', array('id' => $booking_contact['id']));
						elseif ($booking_contact)
							$app['db']->update('booking_agent__artist', array('booking_agent_id' => $artistData['booking_agent']), array('id' => $booking_contact['id']));
						else
							$app['db']->insert('booking_agent__artist', array('booking_agent_id' => $artistData['booking_agent'], 'artist_id' => $artist['id']));

					}

					// Existing Releases?
					$existingReleasesDobis = isset($dobisData['releases']['existing_releases']) ? $dobisData['releases']['existing_releases'] : false;

					if ($existingReleasesDobis)
					{
						foreach($existingReleasesDobis as $existing_release)
						{
							// should we delete it?
							if (!is_null($existing_release['remove']))
							{
								$app['db']->delete('`release`', array('id' => $existing_release['id']));
							}
							else
							{
								$old_slug = $existing_release['slug'];
								$existing_release['slug'] = $app['slugify']($existing_release['name']);


								// upload a new picture if necessary
								if ($existing_release['new_picture'] != null)
								{
									$existing_release['picture_ext'] = $existing_release['new_picture']->guessExtension();
									$filename = $existing_release['slug'].'.'.$existing_release['picture_ext'];
									$existing_release['new_picture']->move($release_media_dir, $filename);
									chmod($release_media_dir . '/' . $filename, 0777);
									
									// update generated thumbnail
									$thumb_filename = $release_media_dir . '/' . $existing_release['slug'].'_thumb.'.$existing_release['picture_ext'];
									$image = $app['imagine']->open($release_media_dir . '/' . $filename);
								    $transformation = new \Imagine\Filter\Transformation();
								    $transformation->thumbnail(new \Imagine\Image\Box(200, 200));
								    $image = $transformation->apply($image);
								    $image->save($thumb_filename, array('quality' => 80));
								    chmod($thumb_filename, 0777);
								}
								// or rename the picture if need be
								else
								{
									if ($old_slug != $existing_release['slug'])
									{
										$old_file = $release_media_dir.'/'.$old_slug.'.'.$existing_release['picture_ext'];
										$new_file = $release_media_dir.'/'.$existing_release['slug'].'.'.$existing_release['picture_ext'];
										rename($old_file, $new_file);
									}

								}

								$release_update = array(
									'label_id' => $existing_release['label_id'],
									'name' => $existing_release['name'],
									'slug' => $existing_release['slug'],
									'release_date' => $existing_release['release_date']->format('Y-m-d'),
									'spotify_uri' => $existing_release['spotify_uri'],
									'bandcamp_album_id' => $existing_release['bandcamp_album_id'],
									'itunes_url' => $existing_release['itunes_url'],
									'smart_url' => $existing_release['smart_url'],								
									'picture_ext' => $existing_release['picture_ext'],
									'twitter_card_description' => $existing_release['twitter_card_description']
								);

								$app['db']->update('`release`', $release_update, array('id' => $existing_release['id']));								
							}
						}
					}
					

					// New Releases?
					if ($new_releases_form)
					{
						$newReleasesDobis = $new_releases_form->getData();

						foreach($newReleasesDobis as $new_release)
						{
							$new_release['artist_id'] = $artist['id'];
							$new_release['slug'] = $app['slugify']($new_release['name']);
							$new_release['picture_ext'] = $new_release['new_picture']->guessExtension();

							// upload picture
							$filename = $new_release['slug'].'.'.$new_release['picture_ext'];
							$new_release['new_picture']->move($release_media_dir, $filename);
							chmod($release_media_dir . '/' . $filename, 0777);


							// generate thumbnail
							$thumb_filename = $release_media_dir . '/' . $new_release['slug'].'_thumb.'.$new_release['picture_ext'];
							$image = $app['imagine']->open($release_media_dir . '/' . $filename);
						    $transformation = new \Imagine\Filter\Transformation();
						    $transformation->thumbnail(new \Imagine\Image\Box(200, 200));
						    $image = $transformation->apply($image);
						    $image->save($thumb_filename, array('quality' => 80));
						    chmod($thumb_filename, 0777);


							$release_insert = array(
								'artist_id' => intval($new_release['artist_id']),
								'label_id' => $new_release['label_id'],
								'name' => $new_release['name'],
								'slug' => $new_release['slug'],
								'release_date' => $new_release['release_date']->format('Y-m-d'),
								'spotify_uri' => $new_release['spotify_uri'],
								'itunes_url' => $new_release['itunes_url'],
								'smart_url' => $new_release['smart_url'],								
								'picture_ext' => $new_release['picture_ext'],
								'bandcamp_album_id' => $new_release['bandcamp_album_id'],
								'twitter_card_description' => $new_release['twitter_card_description']
							);

							// save to db
							$app['db']->insert('`release`', $release_insert);

						}
					}
					

					$app['session']->getFlashBag()->set('notice', 'Great Job!');

					return $app->redirect('/dobismaster/artists/'.$artist['slug']);
					
				}

				$message = "There were errors with your entry. Please scroll down and correct the listed errors.";
			}

			// If we've got new releases added with errors, create the view now...
			if ($new_releases_form)
				$new_releases_form = $new_releases_form->createView();

			return $app->render('dobis/artist.html.twig', array('page_script' => 'dobis-artist', 'new_releases_form' => $new_releases_form, 'parent_form' => $parent_form->createView(), 'artist' => $artist, 'releases' => $releases, 'message' => $message));

		})->method('GET|POST')->before($mw['must_be_logged_in']);


		// Delete Artist (confirm)
		$controller->get('/artists/{artist_slug}/delete', function (Request $request, $artist_slug) use ($app) {
			
			$artist = $app['db']->fetchAssoc('SELECT * FROM `artist` WHERE `slug` = ?', array($artist_slug));			
				
			if (!$artist)
				return $app->abort(404, 'No such artist.');

			$artist['release_count'] = count($app['db']->fetchAll('SELECT * FROM `release` WHERE `artist_id` = ?', array($artist['id'])));

			return $app->render('/dobis/confirm-artist-delete.html.twig', array('artist' => $artist));

		})->before($mw['must_be_logged_in']);


		// Delete Artist (confirmed)
		$controller->get('/artists/{artist_slug}/delete/confirm', function (Request $request, $artist_slug) use ($app) {

			$artist = $app['db']->fetchAssoc('SELECT * FROM `artist` WHERE `slug` = ?', array($artist_slug));			
				
			if (!$artist)
				return $app->abort(404, 'No such artist.');

			$app['db']->delete('artist', array('id' => $artist['id']));
			$app['db']->delete('`release`', array('artist_id' => $artist['id']));

			$app['session']->getFlashBag()->set('notice', 'Great Job!');

			return $app->redirect('/dobismaster/');

		})->before($mw['must_be_logged_in']);



		// Account settings
		$controller->match('/account', function (Request $request) use ($app) {

			$data = array(
				'id' => $app['dobis_user']['id'],
				'username' => $app['dobis_user']['username'],
				'password' => null,
				'name' => $app['dobis_user']['name'],
				'email' => $app['dobis_user']['email']
			);

			$account_form = $app['form.factory']->createNamedBuilder('account', 'form', $data)
				->add('id', 'hidden')
				->add('username', 'text', array('attr' => array('autocomplete' => 'off'), 'constraints' => array(new Assert\NotBlank())))
				->add('password', 'password', array('attr' => array('autocomplete' => 'off')))
				->add('name', 'text', array('constraints' => array(new Assert\NotBlank())))
				->add('email', 'email', array('constraints' => array(new Assert\NotBlank())))
				->getForm();

			if ($request->getMethod() == 'POST')
			{
				$account_form->bind($request);

				if ($account_form->isValid())
				{
					$data = $request->get('account');
					$data['password'] = trim($data['password']) == '' ? $app['dobis_user']['password'] : md5($data['password']);

					$app['db']->update('dobis_user', array(
						'name' => $data['name'],
						'username' => $data['username'],
						'password' => $data['password'],
						'email' => $data['email']
					), array('id' => $app['dobis_user']['id']));

					$dobis_user = $app['db']->fetchAssoc('SELECT * FROM `dobis_user` WHERE `id` = ?', array($app['dobis_user']['id']));
					$app['session']->set('dobis_user', $dobis_user);


					$app['session']->getFlashBag()->set('notice', 'Great Job!');

					return $app->redirect('/dobismaster/account');					
				}

				$message = "There were errors with your entry. Please scroll down and correct the listed errors.";
			}


			return $app->render('dobis/account.html.twig', array('page_script' => 'dobis-account', 'form' => $account_form->createView()));

		})->method('GET|POST')->before($mw['must_be_logged_in']);



		// Labels
		$controller->match('/labels', function (Request $request) use ($app) {


			$data = array();

			foreach ($app['labels'] as $label)
			{
				$data[$label['stack_order']] = $label['slug'];
			}


			$labels_form = $app['form.factory']->createNamedBuilder('stack', 'form', $data);


			foreach ($app['labels'] as $label)
			{
				$labels_form->add($label['stack_order'], 'hidden');
			}


			$labels_form = $labels_form->getForm();


			if ($request->getMethod() == 'POST')
			{
				$labels_form->bind($request);

				if ($labels_form->isValid())
				{
					$data = $request->get('stack');

					$new_stacks = array_flip($data);

					foreach ($new_stacks as $slug => $stack_order)
					{
						$app['db']->update('label', array('stack_order' => $stack_order), array('slug' => $slug));
					}

					$app['session']->getFlashBag()->set('notice', 'Great Job!');

					return $app->redirect('/dobismaster/labels');					
				}

				$message = "Something weird happened...please try again.";
			}


			return $app->render('dobis/labels.html.twig', array('form' => $labels_form->createView()));

		})
		->method('GET|POST')
		->before($mw['must_be_logged_in'])
		->before($mw['get_labels']);


		// Labels -- Add/Edit
		$controller->match('/labels/{label_slug}', function (Request $request, $label_slug) use ($app) { 

			if ($label_slug == 'new')
			{
				$label = array();
				$label['stack_order'] = ($app['db']->fetchColumn('SELECT `stack_order` FROM `label` ORDER BY `stack_order` DESC') + 1);
			}
			else
			{
				$label = $app['db']->fetchAssoc('SELECT * FROM `label` WHERE `slug` = ?', array($label_slug));
				$label['picture_url'] = $app['base_url'] . '/img/label/' . $label['slug'] . '.' . $label['picture_ext'];
			}

			$label_form = $app['form.factory']->createNamedBuilder('label', 'form', $label)
				->add('name', 'text', array('constraints' => array(new Assert\NotBlank())))
				->add('stack_order', 'hidden');
			
			if ($label_slug == 'new')
				$label_form->add('new_picture', 'file', array('constraints' => array(new Assert\NotBlank())));
			else
				$label_form->add('new_picture', 'file', array('required' => false));

			$label_form = $label_form->getForm();


			if ($request->getMethod() == 'POST')
			{
				$label_form->bind($request);

				if ($label_form->isValid())
				{
					$labelData = $label_form->getData();
					$label = array_merge($label, $labelData);

					if ($label_slug != 'new')
					{
						$label['old_slug'] = $label['slug'];
					}

					$label['slug'] = $app['slugify.old']($label['name']);

					// make sure the label's media folders are set up
					$label_media_dir = $app['media_dir'].'/uploads/label';


					// rename old picture if necessary
					if (isset($label['old_slug']) && $label['old_slug'] != $label['slug'])
					{
						rename($label_media_dir . '/' . $label['old_slug'] . '.' . $label['picture_ext'], $label_media_dir . '/' . $label['slug'] . '.' . $label['picture_ext']);
					}


					// upload picture if necessary
					if (isset($label['new_picture']) && !is_null($label['new_picture']))
					{

						if (file_exists($label_media_dir . '/' . $label['slug'] . '.' . $label['picture_ext']))
							unlink($label_media_dir . '/' . $label['slug'] . '.' . $label['picture_ext']);

						$label['picture_ext'] = $label['new_picture']->guessExtension();
						$filename = $label['slug'].'.'.$label['picture_ext'];

						$label['new_picture']->move($label_media_dir, $filename);

						$image = $app['imagine']->open($label_media_dir . '/' . $filename);
					    $transformation = new \Imagine\Filter\Transformation();
					    $transformation->thumbnail(new \Imagine\Image\Box(300, 300));
					    $image = $transformation->apply($image);
					    $image->save($label_media_dir . '/' . $filename, array('quality' => 100));
					}


					if ($label_slug == 'new')
					{
						$app['db']->insert('label', array(
							'name' => $label['name'],
							'slug' => $label['slug'],
							'stack_order' => $label['stack_order'],
							'picture_ext' => $label['picture_ext']
						));
					}
					else
					{
						$app['db']->update('label', array(
							'name' => $label['name'],
							'slug' => $label['slug'],
							'stack_order' => $label['stack_order'],
							'picture_ext' => $label['picture_ext']
						), array('id' => $label['id']));
					}

					$app['session']->getFlashBag()->set('notice', 'Great Job!');

					return $app->redirect('/dobismaster/labels');
				}

				$message = "There were errors with your entry. Please try again.";

			}

			return $app->render('dobis/label.html.twig', array('form' => $label_form->createView(), 'label' => $label));

		})
		->method('GET|POST')
		->before($mw['must_be_logged_in']);


		// Labels -- Add/Edit
		$controller->match('/labels/{label_slug}/delete', function (Request $request, $label_slug) use ($app) { 
			$label = $app['db']->fetchAssoc('SELECT * FROM `label` WHERE `slug` = ?', array($label_slug));

			if (!$label)
			{
				return $app->abort(404);
			}

			$releases = $app['db']->fetchAll('SELECT * FROM `release` WHERE `label_id` = ?', array($label['id']));

			if (empty($releases) || !$releases)
			{
				$delete = true;
			}
			else
			{
				$delete = false;

				$links = array();

				foreach($releases as $release)
				{
					$artist = $app['db']->fetchAssoc('SELECT * FROM `artist` WHERE `id` = ?', array($release['artist_id']));
					
					if (!isset($links[$artist['slug']]))
					{
						$links[$artist['slug']] = $artist;
						$links[$artist['slug']]['releases'] = array();
						$links[$artist['slug']]['releases'][] = $release;
					}
					else
					{
						$links[$artist['slug']]['releases'][] = $release;
					}
				}
			}

			if ($delete)
			{
				$app['db']->delete('label', array('slug' => $label['slug']));

				unlink($app['base_url'] . '/img/label/' . $label['slug'] . '.' . $label['picture_ext']);

				$app['session']->getFlashBag()->set('notice', "Good Job!");

				return $app->redirect('/dobismaster/labels');	
			}
			else
			{
				$linksText = "This label is still associated with active releases.<br>Please switch the following albums to another label before trying again.<br><br>";

				foreach ($links as $link)
				{
					foreach ($link['releases'] as $key => $release)
					{
						$linksText .= "<b>" . $release['name'] . "</b>";

						if (($key + 1) < count($link['releases']))
						{
							if (($key+2) == count($link['releases']))
							{
								$linksText .= ", and ";
							}
							else
							{
								$linksText .= ", ";
							}
						}
					}

					$linksText .= " by <a href=\"" . $app['base_url'] . "/" . $link['slug'] . "\" target=\"_blank\">" . $link['name'] . "</a>.<br><br>";
				}

				$app['session']->getFlashBag()->set('error', $linksText);

				return $app->redirect('/dobismaster/labels/'.$label['slug']);				
			}


		})
		->before($mw['must_be_logged_in']);


		// Header block management
		$controller->match('/header-blocks', function (Request $request) use ($app) {

			$artists = $app['db']->fetchAll('SELECT * FROM `artist` ORDER BY `name` ASC');

			$releases_dropdown = array();

			foreach($artists as $artist)
			{
				$releases = $app['db']->fetchAll('SELECT * FROM `release` WHERE `artist_id` = ? ORDER BY `name` ASC', array($artist['id']));

				foreach($releases as $release)
				{
					$releases_dropdown[$release['id']] = $artist['name'] . ' — ' . $release['name'];
				}
			}


			$spotlight_main = $app['db']->fetchAssoc('SELECT * FROM `spotlight_feature` WHERE `stack_order` = 0');
			$spotlight_sub = $app['db']->fetchAll('SELECT * FROM `spotlight_feature` WHERE `stack_order` > 0 ORDER BY `stack_order` ASC');


			$data = array(
				'main' => $spotlight_main['release_id'],
				'sub_1' => $spotlight_sub[0]['release_id'],
				'sub_2' => $spotlight_sub[1]['release_id'],
				'sub_3' => $spotlight_sub[2]['release_id'],
				'sub_4' => $spotlight_sub[3]['release_id']
			);

			$header_block_form = $app['form.factory']->createNamedBuilder('header_block', 'form', $data)
				->add('main', 'choice', array('choices' => $releases_dropdown))
				->add('sub_1', 'choice', array('choices' => $releases_dropdown))
				->add('sub_2', 'choice', array('choices' => $releases_dropdown))
				->add('sub_3', 'choice', array('choices' => $releases_dropdown))
				->add('sub_4', 'choice', array('choices' => $releases_dropdown))
				->getForm();


			if ($request->getMethod() == 'POST')
			{
				$header_block_form->bind($request);

				if ($header_block_form->isValid())
				{
					$data = $request->get('header_block');
					$app['db']->update('spotlight_feature', array('release_id' => $data['main']), array('stack_order' => 0));
					$app['db']->update('spotlight_feature', array('release_id' => $data['sub_1']), array('stack_order' => 1));
					$app['db']->update('spotlight_feature', array('release_id' => $data['sub_2']), array('stack_order' => 2));
					$app['db']->update('spotlight_feature', array('release_id' => $data['sub_3']), array('stack_order' => 3));
					$app['db']->update('spotlight_feature', array('release_id' => $data['sub_4']), array('stack_order' => 4));

					$app['session']->getFlashBag()->set('notice', 'Great Job!');

					return $app->redirect('/dobismaster/header-blocks');
				}

				$message = "There were errors with your entry. Please scroll down and correct the listed errors.";
			}


			return $app->render('dobis/header-blocks.html.twig', array('form' => $header_block_form->createView(), 'page_script' => 'dobis-header-blocks'));

		})->method("GET|POST")->before($mw['must_be_logged_in']);

		
		// AJAX - Slugify
		$controller->post('/ajax/slugify', function (Request $request) use ($app) {
			if (!$request->isXmlHttpRequest())
				return $app->abort(404, 'No such page.');

			return $app['slugify']($request->get('text'));
		});


		// AJAX - New booking agent form slot
		$controller->post('/ajax/new-booking-agent/{booking_agent_no}', function (Request $request, $booking_agent_no) use ($app) {

			if (!$request->isXmlHttpRequest())
				return $app->abort(404, 'No such page.');

			$new_booking_agent_form = $app['form.factory']->createNamedBuilder($booking_agent_no, 'form', null, array('csrf_protection' => false))
				->add('name', 'text', array('constraints' => array(new Assert\NotBlank())))
				->add('email', 'email', array('constraints' => array(new Assert\Email(), new Assert\NotBlank())));

			$new_booking_agents_form = $app['form.factory']->createNamedBuilder('new_booking_agents', 'form', null, array('csrf_protection' => false))
				->add($new_booking_agent_form)
				->getForm();

			return $app->render('dobis/ajax-new-booking-agent.html.twig', array('booking_agent_no' => $booking_agent_no, 'new_booking_agents_form' => $new_booking_agents_form->createView()));

		})->before($mw['must_be_logged_in']);


		// AJAX - New release form slot
		$controller->post('/ajax/new-release/{release_no}', function (Request $request, $release_no) use ($app) {
			if (!$request->isXmlHttpRequest())
				return $app->abort(404, 'No such page.');

			// Labels Dropdown
			$labels = $app['db']->fetchAll('SELECT * FROM `label`');
			$labels_dropdown = array();
			foreach ($labels as $label)
				$labels_dropdown[$label['id']] = $label['name'];

			$release_form = $app['form.factory']->createNamedBuilder($release_no, 'form', null, array('csrf_protection' => false))
				->add('name', 'text', array('constraints' => array(new Assert\NotBlank())))
				->add('label_id', 'choice', array('choices' => $labels_dropdown))
				->add('spotify_uri')
				->add('itunes_url', 'text')
				->add('release_date', 'date', array('constraints' => array(new Assert\NotBlank())))
				->add('new_picture', 'file', array('constraints' => array(new Assert\NotBlank())))
				->add('twitter_card_description', 'textarea', array('constraints' => array(new Assert\Length(array('max' => 200)))))
				->add('smart_url');

			$new_releases_form = $app['form.factory']->createNamedBuilder('new_releases', 'form', null, array('csrf_protection' => false))
				->add($release_form)
				->getForm();

			return $app->render('dobis/ajax-new-release.html.twig', array('release_no' => $release_no, 'new_releases_form' => $new_releases_form->createView()));
		})->before($mw['must_be_logged_in']);

		/* -- End Controller Definitions //--> */


		return $controller;
	}

	public function setMiddlewares(Application $app)
	{

		$middleware = array();


		/* <!-- Start Middleware Definitions -- */

		$middleware['dobis_version'] = function (Request $request) use ($app) {
			$app['dobis_version'] = "5000XL";
		};

		$middleware['must_be_logged_in'] = function (Request $request) use ($app) {
			$dobis_user = $app['session']->get('dobis_user');

			if (!isset($dobis_user['id']))
				return $app->redirect('/dobismaster/login');

			$app['dobis_user'] = $dobis_user;
		};


		$middleware['must_be_logged_out'] = function (Request $request) use ($app) {
			$dobis_user = $app['session']->get('dobis_user');

			if (isset($dobis_user['id']))
				return $app->redirect('/dobismaster/');
		};


		$middleware['get_artists'] = function (Request $request) use ($app) {

			$artists = $app['db']->fetchAll('SELECT * from `artist` ORDER BY `name` ASC');

			foreach($artists as $artist_key => $artist)
			{
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


		$middleware['get_labels'] = function (Request $request) use ($app) {
			$labels = $app['db']->fetchAll('SELECT * FROM `label` ORDER BY `stack_order` ASC');

			foreach ($labels as $key => $label)
			{
				$labels[$key]['picture_url'] = $app['base_url'] . '/img/thumb/label/' . $label['slug'] . '.' . $label['picture_ext'];
			}

			$app['labels'] = $labels;
			$app['labels_width'] = $app['count_to_width'](count($app['labels'])+1);
		};

		/* -- End Middleware Definitions //--> */


		return $middleware;
	}
}
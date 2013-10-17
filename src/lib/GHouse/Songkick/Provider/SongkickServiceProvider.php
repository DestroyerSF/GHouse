<?php
namespace GHouse\Songkick\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class SongkickServiceProvider implements ServiceProviderInterface
{
	public function register (Application $app)
	{
		// throw an error if the api_key isn't set
		if (!isset($app['songkick.options.api_key']))
		{
			$app['monolog']->addWarning('Songkick API key could not be found. Critical error resulted in HTTP 500 Error.');
			return $app->abort(500, 'API Key (songkick.options.api_key) is not set.');
		}


		$this->api_key = $app['songkick.options.api_key'];


		$app['songkick.get_artist_concerts'] = $app->protect(function ($artist_id = null) use ($app) {
			// return false if no songkick ID
			if (!isset($artist_id))
			{
				return false;
			}

			// send a CURL JSON request to get the artist's upcoming concerts
			$concerts = $this->artistGetConcerts($artist_id, $this->api_key);

			if ($concerts)
			{
				// format array for template
				$concertsArray = array();
				foreach ($concerts as $concert)
				{
					$headliners = array();

					foreach ($concert['performance'] as $key => $performance)
					{
						if ($performance['billing'] == 'headline' && $performance['artist']['id'] != $artist_id)
							$headliners[] = $performance['displayName'];
					}

					$concertsArray[] = array(
						'date' 			=> $concert['start']['date'],
						'headliners' 	=> implode($headliners, ', '),
						'venue'			=> self::abbreviateVenue($concert['venue']['displayName'], $concert['location']['city']),
						'city' 			=> $concert['location']['city'],
						'url'			=> $concert['uri']
					);

				}

				return $concertsArray;
			}
			else
			{
				return false;
			}

		});
	}

	public function boot (Application $app)
	{
		// La-la-la
	}

	/**
	 * abbreviateVenue
	 * @param  string 	$venue_name	Full venue name returned from Songkick API.
	 * @param  string 	$location	Full formatted location name.
	 * @return string             	Abbreviated to last word over 45 chars suffixed w/ ellipsis
	 */
	private function abbreviateVenue($venue_name, $location)
	{
		$location_char_count = strlen($location)+3; // +3 for the two spaces and mdash
		if ((strlen($venue_name) + $location_char_count) > 45)
		{
			$venue_words = explode(" ", $venue_name);
			
			$totalChars = $location_char_count;
			$num_words = null;
			foreach($venue_words as $word_key => $word)
			{
				$totalChars += (strlen($word)+1);
				if ($totalChars > 45)
				{
					$num_words = $word_key-1;
					break;
				}
			}

			array_splice($venue_words, $num_words);

			$venue_name = implode(' ', $venue_words) . '…';

		}

		return $venue_name;
	}

	private function artistGetConcerts ($artist_id, $api_key)
	{
		// Songkick API v3.0 JSON URL Endpoint
		$json_url = 'http://api.songkick.com/api/3.0/artists/' . $artist_id . '/calendar.json?apikey=' . $api_key;
		 
		$ch = curl_init();
		 
		// Setting curl options
		curl_setopt($ch, CURLOPT_URL, $json_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		 
		// Getting results
		$concerts_json =  curl_exec($ch);


		curl_close($ch);

		$concerts = json_decode($concerts_json, true);

		if (is_array($concerts['resultsPage']['results']) && !empty($concerts['resultsPage']['results']))
		{
			return $concerts['resultsPage']['results']['event'];
		}
		else
		{
			return false;
		}
		
	}
}
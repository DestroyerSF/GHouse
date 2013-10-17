<?php
namespace GHouse\Twitter\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;
use Guzzle\Http\Exception\ClientErrorResponseException;


class TwitterServiceProvider implements ServiceProviderInterface
{
    /**
     * Guzzle HTTP Client for making Twitter API requests.
     * 
     * @var \Guzzle\Http\Client
     */
    private $client;

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {

        // if the app is using Twitter REST API v1.1
        if ($app['twitter.rest_api_switch'] == 'on')
        {
            // throw an error if the OAuth credentials aren't set
            if (!isset($app['twitter.oauth']) || empty($app['twitter.oauth']))
            {
                $app['monolog']->addWarning('Twitter OAuth credentials aren\'t set. Please set them in \'app.twitter.oauth\'.');
                return $app->abort(500, 'Twitter OAuth config could not be found.');
            }        

            $this->client = new Client('https://api.twitter.com/1.1/');

            $oauth = new OauthPlugin(array(
                'consumer_key'    => $app['twitter.oauth']['key'],
                'consumer_secret' => $app['twitter.oauth']['secret'],
                'token'           => $app['twitter.oauth']['token'],
                'token_secret'    => $app['twitter.oauth']['token_secret']
            ));

            $this->client->addSubscriber($oauth);


            $app['twitter.statuses.user_timeline'] = $app->protect(function($screen_name, $count = 1) use ($app) {

                $request = $this->client->get('statuses/user_timeline.json');

                $request->getQuery()
                    ->set('screen_name', $screen_name)
                    ->set('count', $count);

                try
                {
                    $response = $request->send();

                    $tweets = $response->json();

                    $tweets_array = array();

                    foreach ($tweets as $tweet)
                    {
                        $tweets_array[] = array(
                            'text'          => $this::linkifyTweet($tweet['text']),
                            'text_raw'      => $tweet['text'],
                            'created_at'    => $tweet['created_at']
                        );
                    }

                    $tweets_array['user'] = array(
                        'screen_name'       => $tweets[0]['user']['screen_name'],
                        'profile_image_url' => $tweets[0]['user']['profile_image_url']
                    );

                    return $tweets_array;
                }
                catch (ClientErrorResponseException $e)
                {
                    $app->log(sprintf('TwitterServiceProvider returned a Guzzle ClientErrorResponseException: %s', $e->getMessage()));
                    return false;
                }

                
            });
        }


        $app['twitter.helper.format_url.tweet'] = $app->protect(function($text, $related = false, $url = false, $smart_url = false) use ($app) {
            
            $tweet_link = "https://twitter.com/share?";

            if ($smart_url && $url)
                $tweet_link .= "url=".urlencode($smart_url)."&counturl=".urlencode($url)."&";
            elseif (!$smart_url && $url)
                $tweet_link .= "url=".urlencode($url)."&";

            if ($app['twitter.site_handle'] != null)
                $tweet_link .= "via=".$app['twitter.site_handle']."&";

            if ($related)
                $tweet_link .="related=".$related."&";

            $tweet_link .= "text=".urlencode($text);

            return $tweet_link;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        // Ho-hum.
    }

    /**
     * Marks up links, mentions, and hashtags in tweets.
     * 
     * @param  string $tweet_text Raw tweet text provided by Twitter API.
     * @return string             Marked up tweet.
     */
    private static function linkifyTweet($tweet_text)
    {
        // linkify external URLs
        $tweet_text = preg_replace(
            "/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i",
            '<a class="ext_link" target="_blank" href="$1">$1</A>',
            $tweet_text
        );

        // linkify mentions
        $tweet_text = preg_replace(
            "/@(\w+)/",
            '<a class="mention" target="_blank" href="http://twitter.com/$1">@$1</a>',
            $tweet_text
        );

        // linkify hashtags
        $tweet_text = preg_replace(
            "/#(\w+)/",
            '<a class="hashtag" target="_blank" href="http://twitter.com/search?q=%23$1">#$1</a>',
            $tweet_text
        );

        return $tweet_text;
    }
}
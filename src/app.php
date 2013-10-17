<?php
require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SymfonyBridgesServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Grom\Silex\ImagineServiceProvider;
use GHouse\Songkick\Provider\SongkickServiceProvider;
use GHouse\Twitter\Provider\TwitterServiceProvider;


class ghouseApplication extends Application
{
    use Application\TwigTrait;
    use Application\SecurityTrait;
    use Application\FormTrait;
    use Application\UrlGeneratorTrait;
    use Application\SwiftmailerTrait;
    use Application\MonologTrait;
    use Application\TranslationTrait;
}


$app = new ghouseApplication();

require __DIR__.'/config.php';


// $app->register(new HttpCacheServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new FormServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new TranslationServiceProvider());
$app->register(new MonologServiceProvider(), array('monolog.name' => 'ghouse'));
$app->register(new ImagineServiceProvider(), $app['imagine.options']);
$app->register(new TwigServiceProvider(), array('twig.path' => $app['twig.path']));
$app->register(new DoctrineServiceProvider());
$app->register(new SongkickServiceProvider());
$app->register(new TwitterServiceProvider());

// global middlewares
// require __DIR__.'/middlewares.php';

$app['slugify'] = $app->protect(function($text) {

    $text = preg_replace('~[^\\pL\d]+~u', '', $text);

    // trim
    $text = trim($text, '');

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // lowercase
    $text = strtolower($text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    if (empty($text))
    {
        return 'n-a';
    }

    return $text;
});

$app['slugify.old'] = $app->protect(function($text) {

    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

    // trim
    $text = trim($text, '-');

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // lowercase
    $text = strtolower($text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    if (empty($text))
    {
        return 'n-a';
    }

    return $text;
});


$app['count_to_width'] = $app->protect(function($count) {
    
    $width = "one-eighth";

    switch ($count)
    {
        case 1:
           $width = "one-whole";
        break;

        case 2:
            $width = "one-half";
        break;

        case 3:
            $width = "one-third";
        break;

        case 4:
            $width = "one-quarter";
        break;

        case 5:
            $width = "one-fifth";
        break;

        case 6:
            $width = "one-sixth";
        break;

        case 7:
            $width = "one-seventh";
        break;
    }

    return $width;
});


// mount controllers
$app->mount('/dobismaster', new GHouse\Controller\Provider\DobisMasterControllerProvider());
$app->mount('/img', new GHouse\Controller\Provider\ImageControllerProvider());
$app->mount('/', new GHouse\Controller\Provider\MainControllerProvider());



// error handler
$app->error(function (Exception $e) use ($app) {



        $error = array(
        	'type' => get_class($e),
        	'code' => $e->getCode(),
        	'message' => $e->getMessage(),
        	'line' => $e->getLine(),
        	'trace' => $e->getTraceAsString()
        );

        if ($app['env'] == 'prod'){
        	// E-mail error digest to clark@ghouse.co
        }

        if($e instanceof NotFoundHttpException || $e instanceof HttpException)
        {
            $error['code'] = 404;
        }

        $app['error'] = $error;


        return $app['twig']->render('error.html.twig');

    }
);


return $app;
<?php

// web/index.php

// use Symfony\Component\HttpFoundation\Request;
// Request::setTrustedProxies(array($ip));

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\HttpKernelInterface;

use Silex\Application;

use Silex\Provider;


$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

require_once __DIR__.'/../vendor/autoload.php';

class MyApplication extends Application
{
    use Application\TwigTrait;
    use Application\SecurityTrait;
    use Application\FormTrait;
    use Application\UrlGeneratorTrait;
    use Application\SwiftmailerTrait;
    use Application\MonologTrait;
    use Application\TranslationTrait;
}

$app = new MyApplication();
// $app = new Silex\Application();
$app['debug'] = true;
$app['charset'] = 'UTF-8';

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/development.log',
));

//Customization
// $app->extend('monolog', function($monolog, $app) {
//     $monolog->pushHandler(...);

//     return $monolog;
// });

// $app['monolog']->addDebug('Testing the Monolog logging.');
// $app['monolog']->addInfo('Testing the Monolog logging.');

// $app->log('test for log');

$app->register(new Provider\HttpFragmentServiceProvider());
$app->register(new Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/../db/app.db',
    ),
));
// $app->register(new Provider\VarDumperServiceProvider());
$app->register(new Provider\WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/../cache/profiler',
    'profiler.mount_prefix' => '/_profiler', // this is the default
));


$app->extend('twig', function($twig, $app) {
    $twig->addGlobal('pi', 3.14);
    $twig->addFilter('levenshtein', new \Twig_Filter_Function('levenshtein'));

    // $profile = new Twig_Profiler_Profile();
    // $twig->addExtension(new Twig_Extension_Profiler($profile));

    // $dumper = new Twig_Profiler_Dumper_Text();
    // $result = $dumper->dump($profile);

    $twig->addGlobal('profile', 'result');

    return $twig;
});


$app->get('/tao/{name}', function ($name) use ($app) {
    return $app['twig']->render('hello.twig', array(
        'name' => $name,
    ));
});

$app->get('/db/{id}', function ($id) use ($app) {
    $sql = "SELECT * FROM posts WHERE id = ?";
    $post = $app['db']->fetchAssoc($sql, array((int) $id));
    return  "<h1>{$post['NAME']}</h1>".
            "<p>{$post['ADDRESS']}</p>";
});

$app->get('/insert', function () use ($app) {
    $sql = "SELECT * FROM posts WHERE id = ?";
    $post = $app['db']->fetchAssoc($sql);

    return  "<h1>ok</h1>";
});


$app->view(function (array $controllerResult) use ($app) {
    if ($app['debug']){
        return 'handle view';
    }
    return 'handle view 2';
});


$app->get('/', function () {
	return 'Home Page';
})
->bind('homepage');


$app->get('/hi', 'Foo::bar');
{
    class Foo
    {
        public function bar(Request $request, Application $app)
        {
            // return 'test controler';
            // return array('name' => 'zhangbaitong');
            return $app->path('homepage').$app->url('homepage');
        }
    }
}

$app->get('/hello/{name}', function ($name) use ($app) {
    return 'Hello '.$name;
    // return 'Hello '.$app->escape($name);
})
->bind('hello');

$app->match('/match', function () {
    return 'match test';
});

$app->match('/match2', function () {
    return 'match test get and post';
})
->method('GET|POST');

$blogPosts = array(
    1 => array(
        'date'      => '2011-03-29',
        'author'    => 'igorw',
        'title'     => 'Using Silex',
        'body'      => '...',
    ),
    2 => array(
    	'date'      => '2011-03-29',
        'author'    => 'igorw',
        'title'     => 'Using Silex2',
        'body'      => '...',
    ),
);

$app->get('/blog', function () use ($blogPosts) {
    $output = '';
    foreach ($blogPosts as $post) {
        $output .= $post['title'];
        $output .= '<br />';
    }

    return $output;
});

$app->get('/blog/{id}', function (Silex\Application $app, $id) use ($blogPosts) {
    if (!isset($blogPosts[$id])) {
        $app->abort(404, "Post $id does not exist.");
    }

    $post = $blogPosts[$id];

    return  "<h1>{$post['title']}</h1>".
            "<p>{$post['body']}</p>";
});

$app->post('/feedback', function (Request $request) {
    $message = $request->get('message');
    $message = 'test mail';
    mail('zhangtaot@infobird.com', '[YourSite] Feedback', $message);

    return new Response('Thank you for your feedback!', 201);
});

$app->match('/error', function () {
    return 'error test';
});

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }
    return new Response('We are sorry, but something went terribly wrong.');
});


$app->get('/hi2', function () use ($app) {
    return $app->redirect('/hi');
});

//Forwards
$app->get('/hi3', function () use ($app) {
    // forward to /hello
    // $subRequest = Request::create('/hi', 'GET');
    $subRequest = Request::create($app['url_generator']->generate('homepage'), 'GET');

    return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
});

//json

function getUser($id) {
    // return null;
    return array('name' => 'zhangbaitong',
                'age' => '22');
}
$app->get('/users/{id}', function ($id) use ($app) {
    $user = getUser($id);

    if (!$user) {
        $error = array('message' => 'The user was not found.');

        return $app->json($error, 404);
    }

    return $app->json($user);
});

//Streaming

$app->get('/images/{file}', function ($file) use ($app) {
    if (!file_exists(__DIR__.'/images/'.$file)) {
        return $app->abort(404, 'The image was not found.');
    }

    $stream = function () use ($file) {
        readfile($file);
    };

    return $app->stream($stream, 200, array('Content-Type' => 'image/png'));
});

//Sending a file

$app->get('/files/{path}', function ($path) use ($app) {
    if (!file_exists('/base/path/' . $path)) {
        $app->abort(404);
    }

    return $app->sendFile('/base/path/' . $path);
});

//DI

$app['some_parameter'] = 'value';
$app['asset.host'] = 'http://cdn.mysite.com/';
//echo $app['some_parameter'];

class MyService
{
    public function hello()
    {
        return 'I am service';
    }
}
$app['some_service'] = function () {
    return new MyService();
};

$app['some_service_factory'] = $app->factory(function () {
    return new MyService();
});

$app['some_service_config'] = function ($app) {
    return new MyService($app['some_other_service'], $app['some_service.config']);
};

$service = $app['some_service'];

// echo $service->hello();

$app['closure_parameter'] = $app->protect(function ($a, $b) {
    return $a + $b;
});

// will not execute the closure
$add = $app['closure_parameter'];

// calling it now
// echo $add(2, 3);

$app->run();
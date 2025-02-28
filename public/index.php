<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', fn () => new \Slim\Views\PhpRenderer(__DIR__ . '/../templates'));

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
})->setName('/');

$app->get('/users', function ($request, $response) {
    $users = json_decode(file_get_contents('cache/users'), true);
    $u = $request->getParam('u');
    $resultUsers = array_filter($users, fn ($user) => str_contains(strtolower($user['nickname']), strtolower($u)));
    sort($resultUsers);
    $params = ['users' => $resultUsers];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    return $this->get('renderer')->render($response, 'users/new.phtml');
})->setName('createUser');

$app->post('/users', function ($request, $response) use ($router) {
    $users = json_decode(file_get_contents('cache/users'), true);
    $data = $request->getParsedBodyParam('user');
    $id = random_int(1, 100);
    $users[] = ['id' => $id, 'nickname' => $data['nickname'], 'email' => $data['email']];
    file_put_contents('cache/users', json_encode($users));

    return $response->withRedirect($router->urlFor('users'), 302);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('course');

$app->get('/users/{id}', function ($request, $response, array $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->run();

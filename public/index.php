<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();
$container->set('renderer', fn () => new \Slim\Views\PhpRenderer(__DIR__ . '/../templates'));
$container->set('flash', fn () => new \Slim\Flash\Messages());

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
})->setName('/');

$app->get('/users', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();
    $users = json_decode(file_get_contents('cache/users'), true);
    $u = $request->getParam('u');
    $resultUsers = array_filter($users, fn ($user) => str_contains(strtolower($user['nickname']), strtolower($u)));
    sort($resultUsers);

    $params = [
        'messages' => $messages,
        'users' => $resultUsers
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users.index');

$app->get('/users/new', function ($request, $response) {
    return $this->get('renderer')->render($response, 'users/new.phtml');
})->setName('users.create');

$app->post('/users', function ($request, $response) use ($router) {
    $users = json_decode(file_get_contents('cache/users'), true);
    $data = $request->getParsedBodyParam('user');
    $id = random_int(1, 100);
    $users[] = ['id' => $id, 'nickname' => $data['nickname'], 'email' => $data['email']];
    file_put_contents('cache/users', json_encode($users));

    $this->get('flash')->addMessage('success', 'User was added successfully');

    return $response->withRedirect($router->urlFor('users.index'), 302);
})->setName('users.store');

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('courses.show');

$app->get('/users/{id}', function ($request, $response, array $args) {
    $users = json_decode(file_get_contents('cache/users'), true);
    $id = (int) $args['id'];
    $user = array_values(array_filter($users, fn ($user) => $user['id'] === $id))[0];

    if (empty($user)) {
        return $response->withStatus(404);
    }
    
    $params = ['user' => $user];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.show');

$app->run();

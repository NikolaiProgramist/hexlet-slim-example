<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Nikolai\HexletSlimExample\Validator;

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
});

$app->get('/users', function ($request, $response) {
    $flash = $this->get('flash')->getMessages();
    $users = json_decode(file_get_contents('cache/users'), true);
    $u = $request->getParam('u');
    $resultUsers = array_filter($users, fn ($user) => str_contains(strtolower($user['nickname']), strtolower($u)));
    sort($resultUsers);

    $params = [
        'flash' => $flash,
        'users' => $resultUsers
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'userData' => [],
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('newUser');

$app->post('/users', function ($request, $response) use ($router) {
    $validator = new Validator();
    $users = json_decode(file_get_contents('cache/users'), true);
    $data = $request->getParsedBodyParam('user');
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        $id = random_int(1, 100);
        $users[] = ['id' => $id, 'nickname' => $data['nickname'], 'email' => $data['email']];
        file_put_contents('cache/users', json_encode($users));
        $this->get('flash')->addMessage('success', 'User was added successfully');

        return $response->withRedirect($router->urlFor('users'), 302);
    }

    $params = [
        'data' => $data,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users.store');

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('course');

$app->get('/users/{id}', function ($request, $response, array $args) {
    $users = json_decode(file_get_contents('cache/users'), true);
    $id = (int) $args['id'];
    $user = array_values(array_filter($users, fn ($user) => $user['id'] === $id))[0];

    if (empty($user)) {
        return $response->withStatus(404);
    }
    
    $params = ['user' => $user];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->run();

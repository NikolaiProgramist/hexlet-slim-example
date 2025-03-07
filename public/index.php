<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use Nikolai\HexletSlimExample\UserValidator;
use Nikolai\HexletSlimExample\Car;
use Nikolai\HexletSlimExample\CarValidator;
use Nikolai\HexletSlimExample\Repositories\CarRepository;

session_start();

$container = new Container();
$container->set('renderer', fn () => new \Slim\Views\PhpRenderer(__DIR__ . '/../templates'));
$container->set('flash', fn () => new \Slim\Flash\Messages());
$container->set(PDO::class, function () {
    $conn = new PDO('sqlite:database.sqlite');
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $conn;
});

$initFilePath = implode('/', [dirname(__DIR__), 'init.sql']);
$initSql = file_get_contents($initFilePath);
$container->get(PDO::class)->exec($initSql);

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});

$app->get('/users', function ($request, $response) {
    $flash = $this->get('flash')->getMessages();
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $u = $request->getParam('u');
    $resultUsers = array_filter($users, fn ($user) => str_contains(strtolower($user['nickname'] ?? ''), strtolower($u ?? '')));
    sort($resultUsers);

    $params = [
        'flash' => $flash,
        'users' => $resultUsers,
        'session' => isset($_SESSION['user'])
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users.index');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'data' => [],
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users.create');

$app->post('/users', function ($request, $response) use ($router) {
    $validator = new UserValidator();
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $data = $request->getParsedBodyParam('user');
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        $id = random_int(1, 100);
        $users[$id] = ['id' => $id, 'nickname' => $data['nickname'], 'email' => $data['email']];
        $users = json_encode($users);
        $this->get('flash')->addMessage('success', 'User was added successfully');

        return $response->withHeader('Set-Cookie', "users={$users}; path=/; secure; httpOnly")->withRedirect($router->urlFor('users.index'), 302);
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
})->setName('course.show');

$app->get('/users/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $user = $users[$id];

    if (empty($user)) {
        return $response->withStatus(404);
    }
    
    $params = [
        'user' => $user
    ];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users.show');

$app->get('/users/{id}/edit', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $user = $users[$id];

    if (empty($user)) {
        $this->get('flash')->addMessage('error', 'User not exists');
        return $response->withRedirect($router->urlFor('users.index'), 404);
    }

    $params = [
        'user' => $user,
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('users.edit');

$app->patch('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $user = $users[$id];

    if (empty($user)) {
        $this->get('flash')->addMessage('error', 'User not exists');
        return $response->withRedirect($router->urlFor('users.index'), 404);
    }

    $data = $request->getParsedBodyParam('user');
    $validator = new UserValidator();
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        $users[$id]['nickname'] = $data['nickname'];
        $users[$id]['email'] = $data['email'];
        $users = json_encode($users);
        $this->get('flash')->addMessage('success', 'User updated successfully');

        return $response->withHeader('Set-Cookie', "users={$users}; path=/; secure; httpOnly")->withRedirect($router->urlFor('users.index'), 302);
    }

    $params = [
        'user' => $user,
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/edit.phtml', $params);
})->setName('editUser');

$app->delete('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    unset($users[$id]);
    $users = json_encode($users);
    $this->get('flash')->addMessage('success', 'User has been removed successfully');

    return $response->withHeader('Set-Cookie', "users={$users}; path=/; secure; httpOnly")->withRedirect($router->urlFor('users.index'), 302);
});

$app->get('/login', function ($request, $response) {
    $params = [
        'errors' => ['email' => ''],
        'email' => ''
    ];

    return $this->get('renderer')->render($response, 'users/login.phtml', $params);
})->setName('users.login');

$app->post('/login', function ($request, $response) use ($router) {
    $email = $request->getParsedBodyParam('user')['email'];
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $user = array_values(array_filter($users, fn ($user) => $user['email'] === $email));

    if (!empty($user)) {
        $id = $user[0]['id'];
        $nickname = $user[0]['nickname'];

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user'] = ['id' => $id, 'nickname' => $nickname];
        return $response->withRedirect($router->urlFor('users.show', ['id' => $id]));
    }

    $params = [
        'errors' => ['email' => 'Uncorrected data of user account'],
        'email' => $email
    ];

    return $this->get('renderer')->render($response->withStatus(422), '/users/login.phtml', $params);
});

$app->delete('/logout', function ($request, $response) use ($router) {
    session_destroy();
    $_SESSION = [];

    return $response->withRedirect($router->urlFor('users.index'));
});

$app->get('/cars', function ($request, $response) {
    $carRepository = $this->get(CarRepository::class);
    $cars = $carRepository->getEntities();
    $messages = $this->get('flash')->getMessages();

    $params = [
        'cars' => $cars,
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'cars/index.phtml', $params);
})->setName('cars.index');

$app->get('/cars/new', function ($request, $response) {
    $params = [
        'car' => new Car(),
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'cars/new.phtml', $params);
})->setName('cars.create');

$app->get('/cars/{id}', function ($request, $response, $args) {
    $carRepository = $this->get(CarRepository::class);
    $id = $args['id'];
    $car = $carRepository->find($id);

    if (is_null($car)) {
        return $response->write('Page not found')->withStatus(404);
    }

    $messages = $this->get('flash')->getMessages();

    $params = [
        'car' => $car,
        'flash' => $messages
    ];

    return $this->get('renderer')->render($response, 'cars/show.phtml', $params);
})->setName('cars.show');

$app->post('/cars', function ($request, $response) use ($router) {
    $carRepository = $this->get(CarRepository::class);
    $carData = $request->getParsedBodyParam('car');

    $validator = new CarValidator();
    $errors = $validator->validate($carData);

    if (count($errors) === 0) {
        $car = Car::fromArray([$carData['make'], $carData['model']]);
        $carRepository->save($car);
        $this->get('flash')->addMessage('success', 'Car was added successfully');
        return $response->withRedirect($router->urlFor('cars.index'));
    }

    $car = Car::fromArray([$carData['make'], $carData['model']]);

    $params = [
        'car' => $car,
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'cars/new.phtml', $params);
})->setName('cars.store');

$app->get('/cars/{id}/edit', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $carRepository = $this->get(CarRepository::class);
    $car = $carRepository->find($id);

    $params = [
        'car' => $car,
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'cars/edit.phtml', $params);
})->setName('cars.edit');

$app->patch('/cars/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $carData = $request->getParsedBodyParam('car');

    $validator = new CarValidator();
    $errors = $validator->validate($carData);

    if (count($errors) === 0) {
        $carRepository = $this->get(CarRepository::class);
        $car = Car::fromArray([$carData['make'], $carData['model']]);
        $car->setId($id);
        $carRepository->update($car);
        $this->get('flash')->addMessage('success', 'Car was updated successfully');

        return $response->withRedirect($router->urlFor('cars.index'));
    }

    $car = Car::fromArray([$carData['make'], $carData['model']]);
    $car->setId($id);

    $params = [
        'car' => $car,
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'cars/edit.phtml', $params);
});

$app->delete('/cars/{id}', function ($request, $response, array $args) use ($router) {
    $id = (int) $args['id'];
    $carRepository = $this->get(CarRepository::class);
    $carRepository->delete($id);
    $this->get('flash')->addMessage('success', 'Car was removed successfully');

    return $response->withRedirect($router->urlFor('cars.index'));
});

$app->run();

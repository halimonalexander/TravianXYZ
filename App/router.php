<?php

use HalimonAlexander\RouterLite\Router;
use App\{
    Routes,
    Controllers\Authorization\ActivationController,
    Controllers\Authorization\LoginController,
    Controllers\Authorization\RegistrationController,
};

$router = new Router();

$parsedUriGetParams = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
if (!empty($parsedUriGetParams)) {
    parse_str($parsedUriGetParams, $_GET);
}

$router->route('GET', Routes::ACTIVATE, function() {
    $controller = new ActivationController();
    $controller->activateAction();
});

$router->route('POST', Routes::ACTIVATE, function() {
    $controller = new ActivationController();
    $controller->activateHandler();
});

$router->route('GET', Routes::DEACTIVATE, function() {
    $controller = new ActivationController();
    $controller->deactivateHandler();
});

$router->route('GET', Routes::LOGIN, function () {
    $controller = new LoginController();
    $controller->loginAction();
});

$router->route('POST', Routes::LOGIN, function () {
    $controller = new LoginController();
    $controller->loginHandler();
});

$router->route('GET', Routes::LOGOUT, function () {
    $controller = new LoginController();
    $controller->logoutHandler();
});

$router->route('GET', ROUTES::REGISTER, function() use ($registry) {
    $controller = new RegistrationController();
    $controller->registerAction();
});

$router->route('POST', ROUTES::REGISTER, function() use ($registry) {
   $controller = new RegistrationController();
   $controller->registerHandler(
       $registry->get('mailer'),
       $registry->get('generator')
   );
});


$router->set404(function() {
    echo 'page 404 here coming soon';
});

$router->run();
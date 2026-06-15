<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Trim base URL prefix (for sub-directory installs)
if (BASE_URL !== '' && str_starts_with($uri, BASE_URL)) {
    $uri = substr($uri, strlen(BASE_URL));
}
$uri = '/' . trim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// [method, pattern, controller, action, param-names]
$routes = [
    ['GET',  '#^/$#',                                                   'DashboardController',    'index'],
    ['GET',  '#^/login$#',                                              'AuthController',         'showLogin'],
    ['POST', '#^/login$#',                                              'AuthController',         'login'],
    ['POST', '#^/logout$#',                                             'AuthController',         'logout'],
    ['GET',  '#^/password/change$#',                                    'AuthController',         'showChangePassword'],
    ['POST', '#^/password/change$#',                                    'AuthController',         'changePassword'],

    ['GET',  '#^/entries$#',                                            'TimeEntryController',    'index'],
    ['GET',  '#^/entries/create$#',                                     'TimeEntryController',    'create'],
    ['POST', '#^/entries/create$#',                                     'TimeEntryController',    'store'],
    ['GET',  '#^/entries/(\d+)/edit$#',                                 'TimeEntryController',    'edit'],
    ['POST', '#^/entries/(\d+)/update$#',                               'TimeEntryController',    'update'],
    ['POST', '#^/entries/(\d+)/delete$#',                               'TimeEntryController',    'destroy'],

    ['GET',  '#^/admin/users$#',                                        'AdminUserController',    'index'],
    ['GET',  '#^/admin/users/create$#',                                 'AdminUserController',    'create'],
    ['POST', '#^/admin/users/create$#',                                 'AdminUserController',    'store'],
    ['GET',  '#^/admin/users/(\d+)/edit$#',                             'AdminUserController',    'edit'],
    ['POST', '#^/admin/users/(\d+)/update$#',                           'AdminUserController',    'update'],
    ['POST', '#^/admin/users/(\d+)/delete$#',                           'AdminUserController',    'destroy'],
    ['POST', '#^/admin/users/(\d+)/adjustments/save$#',                 'AdminUserController',    'adjustmentSave'],
    ['POST', '#^/admin/users/(\d+)/adjustments/(\d+)/delete$#',         'AdminUserController',    'adjustmentDelete'],

    ['GET',  '#^/admin/holidays$#',                                     'AdminHolidayController', 'index'],
    ['GET',  '#^/admin/holidays/create$#',                              'AdminHolidayController', 'create'],
    ['POST', '#^/admin/holidays/create$#',                              'AdminHolidayController', 'store'],
    ['GET',  '#^/admin/holidays/(\d+)/edit$#',                          'AdminHolidayController', 'edit'],
    ['POST', '#^/admin/holidays/(\d+)/update$#',                        'AdminHolidayController', 'update'],
    ['POST', '#^/admin/holidays/(\d+)/delete$#',                        'AdminHolidayController', 'destroy'],
];

$controllerDir = BASE_PATH . '/src/Controllers/';
$matched = false;

foreach ($routes as [$routeMethod, $pattern, $controllerName, $action]) {
    if ($routeMethod !== $method) {
        continue;
    }
    if (!preg_match($pattern, $uri, $matches)) {
        continue;
    }

    require_once $controllerDir . $controllerName . '.php';
    $controller = new $controllerName();
    // Pass captured regex groups as positional params array
    $controller->$action(array_slice($matches, 1));
    $matched = true;
    break;
}

if (!$matched) {
    http_response_code(404);
    $content = '<div class="text-center py-5"><h2>404 – Seite nicht gefunden</h2>'
             . '<a href="' . BASE_URL . '/" class="btn btn-secondary mt-3">Zum Dashboard</a></div>';
    require BASE_PATH . '/templates/layout.php';
}

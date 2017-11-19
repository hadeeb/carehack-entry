<?php

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

//db connection
$container = $app->getContainer();

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Models
require __DIR__. '/../src/models/Appointment.php';
require __DIR__. '/../src/models/Availability.php';
require __DIR__. '/../src/models/Dept.php';
require __DIR__. '/../src/models/Doctor.php';
require __DIR__. '/../src/models/Patient.php';
require __DIR__. '/../src/models/User.php';

// Functions
require __DIR__. '/../src/functions.php';

// Run app
$app->run();
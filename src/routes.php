<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->any('/', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.html', $args);
});

$app->group('/api', function () use ($app) {

    // Version group
    $app->group('/v1', function () use ($app) {
		$app->get('/doctors','doctors');
        $app->get('/about','getAbout');
        $app->get('/info','getInfo');
        $app->get('/appointments','getAppointments');
        $app->get('/login','login');
        $app->get('/patients','getPatients');
        $app->get('/profile','profile');
        $app->post('/addpatient','addPatient');
        $app->post('/addappointment','addAppointment');
        $app->post('/register','register');

        $app->any('',function (Request $request,Response $response) {
            return $this->renderer->render($response, 'api.phtml');
        });
    });

});

//$app->any('/admin','admin');

$app->any('/404',function(Request $request,Response $response){
    return $this->renderer->render($response, '404.html');
});

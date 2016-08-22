<?php
	
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	require __DIR__ . '/../vendor/autoload.php';

	session_start();

	ref::config('expLvl', 0);

	// Instantiate the app
	$settings = require __DIR__ . '/../app/settings.php';
	$app = new \Slim\App($settings);

	// Set up dependencies
	require __DIR__ . '/../app/dependencies.php';
	// Register middleware
	require __DIR__ . '/../app/middleware.php';
	// Register routes
	require __DIR__ . '/../app/routes.php';

	$app->run();
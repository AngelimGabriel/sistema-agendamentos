<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\AvailabilityController;
use App\Controllers\AppointmentController;

$router = new Router();

$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/me', [AuthController::class, 'me']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

$router->get('/users/{id}/availability', [AvailabilityController::class, 'index']);
$router->post('/availability', [AvailabilityController::class, 'store']);
$router->put('/availability/{id}', [AvailabilityController::class, 'update']);
$router->delete('/availability/{id}', [AvailabilityController::class, 'destroy']);

$router->get('/users/{id}/available-slots', [AppointmentController::class, 'availableSlots']);
$router->get('/users/{id}/appointments', [AppointmentController::class, 'index']);
$router->post('/appointments', [AppointmentController::class, 'store']);
$router->put('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

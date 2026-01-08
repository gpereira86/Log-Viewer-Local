<?php

require __DIR__ . '/../src/bootstrap.php';

use LogViewer\Routing\Router;

$router = new Router();

// Rotas
$router->get('/', 'LogViewer\\Controller\\LogController@view');
$router->get('/config', 'LogViewer\\Controller\\ConfigController@index');
$router->get('/logs', 'LogViewer\\Controller\\LogController@view');
$router->get('/api/projects', 'LogViewer\\Controller\\ProjectController@list');
$router->post('/api/projects', 'LogViewer\\Controller\\ProjectController@save');
$router->post('/api/projects/delete', 'LogViewer\\Controller\\ProjectController@delete');
$router->post('/api/projects/test-ssh', 'LogViewer\\Controller\\ProjectController@testSsh');
$router->post('/api/projects/browse-ssh', 'LogViewer\\Controller\\ProjectController@browseSsh');
$router->post('/api/projects/browse-local', 'LogViewer\\Controller\\ProjectController@browseLocal');
$router->get('/api/log-files', 'LogViewer\\Controller\\LogController@listFiles');
$router->get('/api/log-content', 'LogViewer\\Controller\\LogController@getContent');
$router->get('/api/log-entries', 'LogViewer\\Controller\\LogController@entries');

$router->dispatch();



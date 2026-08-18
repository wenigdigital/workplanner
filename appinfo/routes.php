<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#quick', 'url' => '/quick', 'verb' => 'GET'],
		['name' => 'plan#index', 'url' => '/plans', 'verb' => 'GET'],
		['name' => 'plan#save', 'url' => '/plans', 'verb' => 'POST'],
		['name' => 'plan#delete', 'url' => '/plans/{id}', 'verb' => 'DELETE'],
		['name' => 'feed#info', 'url' => '/feed-info', 'verb' => 'GET'],
		['name' => 'feed#show', 'url' => '/feed/{token}', 'verb' => 'GET'],
		['name' => 'location#index', 'url' => '/locations', 'verb' => 'GET'],
		['name' => 'location#create', 'url' => '/locations', 'verb' => 'POST'],
		['name' => 'location#update', 'url' => '/locations/{id}', 'verb' => 'PUT'],
		['name' => 'location#delete', 'url' => '/locations/{id}', 'verb' => 'DELETE'],
	],
];

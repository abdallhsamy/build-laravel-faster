<?php

/* GUI */
Route::get('/faster', '\PipeDream\LaravelCreate\Controllers\PipeDreamWebController@index');

/* API */
Route::post('faster/api/build', '\PipeDream\LaravelCreate\Controllers\PipeDreamAPIController@build');
Route::patch('faster/api/save', '\PipeDream\LaravelCreate\Controllers\PipeDreamAPIController@save');

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\DataRetrievalController;
use App\Http\Controllers\ChatController;

Route::get('/test', function (Request $request) {
    return "API is working";
});

Route::group(['prefix' => 'data-import'],function() use ($router) {
    Route::get('/{any}', [DataImportController::class, 'get'])->where('any', '.*');
    Route::post('file-upload', [DataImportController::class, 'post']);
    Route::post('/{any}', [DataImportController::class, 'otherPostAction'])->where('any', '.*');
    Route::put('/{any}', [DataImportController::class, 'put'])->where('any', '.*');
});

Route::group(['prefix' => 'data-retrieval'],function() use ($router) {
    Route::get('/{any}', [DataRetrievalController::class, 'get'])->where('any', '.*');
    Route::post('/{any}', [DataRetrievalController::class, 'otherPostAction'])->where('any', '.*');
    Route::post('file-upload', [DataImportController::class, 'post']);
});

Route::group(['prefix' => 'chat-service'],function() use ($router) {
   
    Route::get('/{any}', [ChatController::class, 'get'])->where('any', '.*');
    Route::post('/{any}', [ChatController::class, 'otherPostAction'])->where('any', '.*');
});
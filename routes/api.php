<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PowerBIController;

Route::get('/getEmbedInfo', [PowerBIController::class, 'getEmbedInfo']);

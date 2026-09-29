<?php

use App\Http\Controllers\Api\EmailSyncController;
use Illuminate\Support\Facades\Route;

Route::get('email-sync', EmailSyncController::class);

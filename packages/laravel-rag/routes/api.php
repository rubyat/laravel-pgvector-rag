<?php

use Illuminate\Support\Facades\Route;
use RagStarter\Http\Controllers\RagController;

Route::post('ingest', [RagController::class, 'ingest'])->name('rag.ingest');

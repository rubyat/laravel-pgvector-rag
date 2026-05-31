<?php

use Illuminate\Support\Facades\Route;
use RagStarter\Http\Controllers\RagController;

Route::post('ingest', [RagController::class, 'ingest'])->name('rag.ingest');
Route::post('ask', [RagController::class, 'ask'])->name('rag.ask');

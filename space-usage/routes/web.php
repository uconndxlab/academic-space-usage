<?php

use Illuminate\Support\Facades\Route;
use App\Models\Room;
use App\Models\Course;
use App\Models\Department;
use App\Models\Building;
use App\Models\Section;
use App\Models\Term;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\Admin\ImportController;


Route::get('/', [TermController::class, 'index'])->name('terms.index');
Route::get('/terms/{term}', [TermController::class, 'show'])->name('terms.show');

Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings.index');
Route::get('/buildings/{id}', [BuildingController::class, 'show'])->name('buildings.show');




Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
// Show the course details
Route::get('/course/{id}', [CourseController::class, 'show'])->name('courses.show');

// Handle room simulation
Route::post('/course/{id}/simulate', [CourseController::class, 'simulateRoomNeeds'])->name('course.simulateRoomNeeds');


Route::get('course/{subject_code}/{catalog_number}', [CourseController::class, 'showByCodeAndNumber'])->name('course.byCodeAndNumber');

Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/room/{id}', [RoomController::class, 'show'])->name('rooms.show');

// Admin Import Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/upload', [ImportController::class, 'upload'])->name('import.upload');
    Route::post('/import/run', [ImportController::class, 'import'])->name('import.run');
    Route::get('/import/status', [ImportController::class, 'status'])->name('import.status');
});


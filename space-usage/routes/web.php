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
use App\Http\Controllers\AdminController;


Route::middleware('cas.auth')->group(function () {
    Route::get('/', [TermController::class, 'index'])->name('terms.index');
    Route::get('/terms/{term}', [TermController::class, 'show'])->name('terms.show');

    Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings.index');
    Route::get('/buildings/{id}', [BuildingController::class, 'show'])->name('buildings.show');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/filter-options', [CourseController::class, 'getFilterOptions'])->name('courses.filterOptions');
    Route::get('courses/by-day-usage', [CourseController::class, 'byDayUsage'])->name('courses.byDayUsage');
    // Show the course details
    Route::get('/course/{id}', [CourseController::class, 'show'])->name('courses.show');

    // Handle room simulation
    Route::post('/course/{id}/simulate', [CourseController::class, 'simulateRoomNeeds'])->name('course.simulateRoomNeeds');


    Route::get('course/{subject_code}/{catalog_number}', [CourseController::class, 'showByCodeAndNumber'])->name('course.byCodeAndNumber');

    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/room/{id}', [RoomController::class, 'show'])->name('rooms.show');

    //admin routes - only accessible to admins
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/admin', [AdminController::class, 'addUser'])->name('admin.addUser');
    Route::delete('/admin/{id}', [AdminController::class, 'removeUser'])->name('admin.removeUser');
    Route::delete('/admin/{id}/remove', [AdminController::class, 'removeUserForm'])->name('admin.removeUserForm');
    Route::put('/admin/{id}/makeAdmin', [AdminController::class, 'makeAdmin'])->name('admin.makeAdmin');
    Route::put('/admin/{id}/removeAdmin', [AdminController::class, 'removeAdmin'])->name('admin.removeAdmin');
});

Route::get('/invalidLogin', function () {
    return view('invalidLogin');
})->name('invalidLogin');
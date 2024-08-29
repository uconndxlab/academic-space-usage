<?php

use Illuminate\Support\Facades\Route;
use App\Models\Room;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Department;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\CourseController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/departments', [DepartmentController::class, 'index'])->name('department.index');
Route::get('/department/{id}', [DepartmentController::class, 'show'])->name('department.show');
Route::get('/deparatment{name}', [DepartmentController::class, 'showByName'])->name('department.byName');

Route::get('/sections', [CourseController::class, 'index'])->name('courses.index');
Route::get('/section/{id}', [CourseController::class, 'show'])->name('courses.show');

Route::get('course/{subject_code}/{catalog_number}', [CourseController::class, 'showByCodeAndNumber'])->name('course.byCodeAndNumber');




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


Route::get('/', [TermController::class, 'index'])->name('terms.index');
Route::get('/terms/{term}', [TermController::class, 'show'])->name('terms.show');

Route::get('/buildings/{id}', [BuildingController::class, 'show'])->name('buildings.show');



Route::get('/sections', [CourseController::class, 'index'])->name('courses.index');
Route::get('/section/{id}', [CourseController::class, 'show'])->name('courses.show');

Route::get('course/{subject_code}/{catalog_number}', [CourseController::class, 'showByCodeAndNumber'])->name('course.byCodeAndNumber');




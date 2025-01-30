<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get rooms, sorted by building name first, then room number
        $rooms = Room::with('building')
             ->join('buildings', 'rooms.building_id', '=', 'buildings.id')
             ->orderBy('buildings.description')
             ->orderBy('room_number')
             ->get()
             ->groupBy('building_id');
    
        return view('rooms.index', compact('rooms'));
    }
    

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Retrieve the room and load sections ordered by related course subject_code, catalog_number, and section_number
        $room = Room::with(['sections' => function($query) {
            $query->join('courses', 'sections.course_id', '=', 'courses.id')
                  ->orderBy('courses.subject_code', 'asc')
                  ->orderBy('courses.catalog_number', 'asc')
                  ->orderBy('sections.section_number', 'asc') // Order by section_number as well
                  ->select('sections.*'); // Ensures only section data is returned
        }])->find($id);
    
        // Calculate the total enrollment
        $totalEnrollment = $room->sections->sum('day10_enrol');
    
        // Calculate the total number of sections
        $totalSections = $room->sections->count();
    
        return view('rooms.show', compact('room', 'totalEnrollment', 'totalSections'));
    }
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        //
    }
}

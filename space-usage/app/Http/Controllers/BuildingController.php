<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;

class BuildingController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // get buildings sorted by name
        $buildings = Building::orderBy('description')->get();

        return view('buildings.index', compact('buildings'));
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

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
   
        $building = Building::with('rooms.sections')->findOrFail($id);
    

        // Summarize room data
        $totalRooms = $building->rooms->count();
        $totalCapacity = $building->rooms->sum('capacity');
        $totalSections = $building->rooms->sum(function ($room) {
            return $room->sections->count();
        });
        $totalEnrollment = $building->rooms->sum(function ($room) {
            return $room->sections->sum('day10_enrol');
        });

        return view('buildings.show', compact('building', 'totalRooms', 'totalCapacity', 'totalSections', 'totalEnrollment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Building $building)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Building $building)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Building $building)
    {
        //
    }
}

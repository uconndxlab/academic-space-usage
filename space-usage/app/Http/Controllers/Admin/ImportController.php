<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportController
{
    public function index()
    {
        return view('admin.import.index');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:102400', // 100MB max
        ]);

        try {
            $file = $request->file('csv_file');
            $filename = 'comprehensive_data.csv';
            $path = database_path($filename);

            // Move uploaded file to database directory
            $file->move(database_path(), $filename);

            Log::info("CSV file uploaded successfully", ['path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully. Ready to import.',
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            Log::error("CSV upload failed", ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        try {
            // Increase execution time and memory limits
            set_time_limit(600); // 10 minutes
            ini_set('memory_limit', '1024M');
            
            $clearDatabase = $request->input('clear_database', false);

            if ($clearDatabase) {
                Log::info("Clearing database before import");
                // Clear existing data
                Artisan::call('migrate:fresh');
                Log::info("Database cleared");
            }

            Log::info("Starting data import");

            // Run the seeder
            Artisan::call('db:seed', [
                '--class' => 'ComprehensiveDataImporter'
            ]);

            $output = Artisan::output();
            Log::info("Import completed", ['output' => $output]);

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully!',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorType = get_class($e);
            
            // Provide more helpful error messages
            if (strpos($errorMessage, 'Maximum execution time') !== false) {
                $errorMessage = 'Import timed out. For large files (>50MB), please use the command line: ./import-data.sh database/comprehensive_data.csv';
            }
            
            Log::error("Import failed", [
                'error' => $errorMessage,
                'type' => $errorType,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $errorMessage,
                'error_type' => $errorType,
            ], 500);
        }
    }

    public function status()
    {
        // Return current database statistics
        $stats = [
            'terms' => \App\Models\Term::count(),
            'campuses' => \App\Models\Campus::count(),
            'buildings' => \App\Models\Building::count(),
            'rooms' => \App\Models\Room::count(),
            'courses' => \App\Models\Course::count(),
            'sections' => \App\Models\Section::count(),
            'crosslists' => \App\Models\Crosslist::count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}

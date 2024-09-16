<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Course;
use App\Models\Building;
use App\Models\Section;
use App\Models\Term;

use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class Over190Seeder extends Seeder
{
    public function run()
    {
        // Read the CSV file
        $file = fopen(database_path('over-190.csv'), 'r');
        $headers = fgetcsv($file);

        // Map CSV headers to the corresponding model attributes
        while ($row = fgetcsv($file)) {
            $data = array_combine($headers, $row);

            // Handle Term
            $term = Term::firstOrCreate(
                ['term_code' => $data['Term_Code']],
                ['term_descr' => $data['TERM']]
            );

            // Handle Building
            $building = Building::firstOrCreate(
                ['building_code' => $data['building_code']],
                ['description' => $data['Bldg_Description']]
            );

            // Handle Room
            $room = Room::firstOrCreate(
                ['building_id' => $building->id, 'room_number' => $data['Room']],
                ['capacity' => $data['Capacity'], 'room_description' => $data['Room_Description']]
            );

            // Handle Course
            $course = Course::firstOrCreate(
                [
                    'subject_code' => $data['Subject_Code'],
                    'catalog_number' => $data['Catalog_Number'],
                    'term_id' => $term->id,
                ],
                [
                    'class_descr' => $data['CLASS_DESCR'],
                    'wsch_max' => $data['WSCH_Max'],
                    'term_id' => $term->id,
                    'class_duration_weekly' => $data['class_duration_weekly'],
                    'duration_minutes' => $data['Duration_minutes'],
                ]
            );

            //ACES, AGNROther, AcademicServices, Accounting, AfricanaStudiesIns, AgriculturalResour, AlliedHealthScienc, AmericanStudies, AnimalScience, Anthropology, ArtArtHistory, BUSNOther, BiologicalSciences, BiomedicalEngineer, CenterforExcellenc, ChemicalBiomolecul, Chemistry, CivilEnvironmental, CognitiveScience, Communication, ComputerScienceEng, Computing, CurriculumInstruct, DigitalMediaDesign, DramaticArts, ENGROther, EarthSciences, EcologyEvolutionar, Economics, EducationalLeaders, EducationalPsychol, ElectricalComputer, EngineeringPhysics, English, EnvironmentalStudi, Exploratory, Finance, Geography, GlobalAffairs, History, HumanDevelopmentFa, Individualized, Interdisciplinary, Journalism, Kinesiology, LatinoLatinAmerica, Linguistics, LiteraturesCulture, Management, MarineSciences, Marketing, MaterialsScienceEn, Mathematics, MechanicalEngineer, MechanicalAerospac, MolecularCellBiolo, Music, NaturalResourcesth, No_Department, NonDegree, Nursing, NutritionalScience, OfficeofEnrichment, OperationsInformat, PathobiologyVeteri, PharmaceuticalScie, PharmacyPractice, Philosophy, Physics, PhysiologyNeurobio, PlantScienceLandsc, PoliticalScience, PolymerSciencePhD, PreBachelorofSocia, PreIndividualized, PrePharmacy, PreSportManagement, PreTeaching, PsychologicalScien, RoboticsEngineerin, SocialWork, Sociology, SpeechLanguageHear, Statistics, UConnHealthOther, UrbanCommunityStud, WomenGenderSexuali,
            $departmentColumns = [
                'ACES',
                'AGNROther',
                'AcademicServices',
                'Accounting',
                'AfricanaStudiesIns',
                'AgriculturalResour',
                'AlliedHealthScienc',
                'AmericanStudies',
                'AnimalScience',
                'Anthropology',
                'ArtArtHistory',
                'BUSNOther',
                'BiologicalSciences',
                'BiomedicalEngineer',
                'CenterforExcellenc',
                'ChemicalBiomolecul',
                'Chemistry',
                'CivilEnvironmental',
                'CognitiveScience',
                'Communication',
                'ComputerScienceEng',
                'Computing',
                'CurriculumInstruct',
                'DigitalMediaDesign',
                'DramaticArts',
                'ENGROther',
                'EarthSciences',
                'EcologyEvolutionar',
                'Economics',
                'EducationalLeaders',
                'EducationalPsychol',
                'ElectricalComputer',
                'EngineeringPhysics',
                'English',
                'EnvironmentalStudi',
                'Exploratory',
                'Finance',
                'Geography',
                'GlobalAffairs',
                'History',
                'HumanDevelopmentFa',
                'Individualized',
                'Interdisciplinary',
                'Journalism',
                'Kinesiology',
                'LatinoLatinAmerica',
                'Linguistics',
                'LiteraturesCulture',
                'Management',
                'MarineSciences',
                'Marketing',
                'MaterialsScienceEn',
                'Mathematics',
                'MechanicalEngineer',
                'MechanicalAerospac',
                'MolecularCellBiolo',
                'Music',
                'NaturalResourcesth',
                'No_Department',
                'NonDegree',
                'Nursing',
                'NutritionalScience',
                'OfficeofEnrichment',
                'OperationsInformat',
                'PathobiologyVeteri',
                'PharmaceuticalScie',
                'PharmacyPractice',
                'Philosophy',
                'Physics',
                'PhysiologyNeurobio',
                'PlantScienceLandsc',
                'PoliticalScience',
                'PolymerSciencePhD',
                'PreBachelorofSocia',
                'PreIndividualized',
                'PrePharmacy',
                'PreSportManagement',
                'PreTeaching',
                'PsychologicalScien',
                'RoboticsEngineerin',
                'SocialWork',
                'Sociology',
                'SpeechLanguageHear',
                'Statistics',
                'UConnHealthOther',
                'UrbanCommunityStud',
                'WomenGenderSexuali',
            ];

            $enrollments_by_dept = [];

            foreach ($departmentColumns as $column) {
                $enrollments_by_dept[$column] = $data[$column];
            }

            // Handle Section
            Section::create([
                'section_number' => $data['Section'],
                'course_id' => $course->id,
                'enrol_cap' => $data['Enrl_Cap'],
                'day10_enrol' => $data['Day10_Enroll'],
                'component_code' => $data['Component_Code'],
                'room_id' => $room->id,
                'enrollments_by_dept' => json_encode($enrollments_by_dept),
            ]);
        }

        fclose($file);
    }
}

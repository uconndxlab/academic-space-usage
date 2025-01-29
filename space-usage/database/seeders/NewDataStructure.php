<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Term;
use App\Models\Building;
use App\Models\Room;
use App\Models\Course;
use App\Models\Section;
use App\Models\Campus;

// new origianl data structure is as follows:

/**
 * 
 * Term_Code	!
 * Subject_Code	!
 * Division	!
 * Catalog_Number	!
 * Section	!
 * Component_Code	!
 * Class_NBR	
 * CLASS_DESCR	!
 * Day10_Enroll	!
 * class_duration_weekly	!
 * Enrl_Cap	!
 * Instruction_Mode	
 * TL_Building	
 * TL_Room	
 * SA Facility ID	
 * SA Building Code	
 * SA Building Description	
 * SA Building Short Description	
 * SA Facility Room Number	
 * SA Facility Description	
 * SA Facility Short Description	
 * SA Facility Location	
 * SA Facility Academic Organization	
 * SA Facility Type	
 * SA Facility Capacity	
 * Class_Start_Time	
 * Class_End_Time	
 * Class_Days	
 * AGNROther	Accounting	AfricanaStudiesIns	AgriculturalResour	AlliedHealthScienc	AnimalScience	Anthropology	ArtArtHistory	BUSNOther	BiologicalSciences	BiomedicalEngineer	CenterforExcellenc	ChemicalBiomolecul	Chemistry	CivilEnvironmental	CognitiveScience	Communication	Computing	CurriculumInstruct	DigitalMediaDesign	DramaticArts	ENGROther	EarthSciences	EcologyEvolutionar	Economics	EducationalLeaders	EducationalPsychol	ElectricalComputer	EngineeringPhysics	English	Exploratory	Finance	GeographySustainab	History	HumanDevelopmentFa	HumanRights	Individualized	InstituteforSystem	Interdisciplinary	InternationalStudi	Journalism	Kinesiology	LatinoLatinAmerica	Law	Linguistics	LiteraturesCulture	Management	MarineSciences	MaritimeStudies	Marketing	MaterialsScienceEn	Mathematics	MechanicalAerospac	MolecularCellBiolo	Music	NaturalResourcesth	NonDegree	Nursing	NutritionalScience	OfficeofEnrichment	OperationsInformat	PathobiologyVeteri	PharmaceuticalScie	PharmacyPractice	Philosophy	Physics	PhysiologyNeurobio	PlantScienceLandsc	PoliticalScience	PolymerSciencePhD	PreBachelorofSocia	PreIndividualized	PrePharmacy	PreSportManagement	PreTeaching	PsychologicalScien	PublicPolicy	RoboticsEngineerin	SocialCriticalInqu	SocialWork	Sociology	SpeechLanguageHear	StatisticalDataSci	Statistics	UConnHealthOther	Sum_Enrollment
 */


class NewDataStructure extends Seeder
{
    public function run()
    {
        // Read the CSV file
        $file = fopen(database_path('new_data.csv'), 'r');
        $headers = fgetcsv($file);

        // Map CSV headers to the corresponding model attributes
        while ($row = fgetcsv($file)) {
            $data = array_combine($headers, $row);

            if ($data['SA Facility Capacity'] == 0 || $data['SA Facility Capacity'] == null) {
                continue;
            }

            // Handle Term
            $term = Term::firstOrCreate(
                ['term_code' => $data['Term_Code']],
                ['term_descr' => 'Fall 2024']
            );

            // Handle Building
            $building = Building::firstOrCreate(
                ['building_code' => $data['SA Building Code']],
                ['description' => $data['SA Building Description'],
                    'type' => $data['SA Facility Type'],
                ]
                
            );

            // Handle Room
            $room = Room::firstOrCreate(
                ['building_id' => $building->id, 'room_number' => $data['SA Facility Room Number']],
                ['capacity' => $data['SA Facility Capacity'], 'room_description' => $data['SA Facility Description'],
                    'sa_facility_type' => $data['SA Facility Type'],
                ]
            );


            // if it's null just use 0 
            if ($data['class_duration_weekly'] == null) {
                $data['duration_minutes'] = 0;
            } else {
                            // class_duration weekly is in H:MM format, we need to convert it to minutes
            $data['duration_minutes'] = (int)explode(':', $data['class_duration_weekly'])[0] * 60 + (int)explode(':', $data['class_duration_weekly'])[1];
            // print out the duration_minutes to the console
            echo $data['duration_minutes'] . "(Course: " . $data['Subject_Code'] . " " . $data['Catalog_Number'] . ")\n";

            }
            // Handle Course
            $course = Course::firstOrCreate(
                [
                    'subject_code' => $data['Subject_Code'],
                    'catalog_number' => $data['Catalog_Number'],
                    'term_id' => $term->id,
                ],
                [
                    'class_descr' => $data['CLASS_DESCR'],
                    'wsch_max' => 'wsch_max',
                    'term_id' => $term->id,
                    'class_duration_weekly' => $data['class_duration_weekly'],
                    'duration_minutes' => $data['duration_minutes'],
                    'division' => $data['Division'],
                ]
            );

            // AGNROther	Accounting	AfricanaStudiesIns	AgriculturalResour	AlliedHealthScienc	AnimalScience	Anthropology	ArtArtHistory	BUSNOther	BiologicalSciences	BiomedicalEngineer	CenterforExcellenc	ChemicalBiomolecul	Chemistry	CivilEnvironmental	CognitiveScience	Communication	Computing	CurriculumInstruct	DigitalMediaDesign	DramaticArts	ENGROther	EarthSciences	EcologyEvolutionar	Economics	EducationalLeaders	EducationalPsychol	ElectricalComputer	EngineeringPhysics	English	Exploratory	Finance	GeographySustainab	History	HumanDevelopmentFa	HumanRights	Individualized	InstituteforSystem	Interdisciplinary	InternationalStudi	Journalism	Kinesiology	LatinoLatinAmerica	Law	Linguistics	LiteraturesCulture	Management	MarineSciences	MaritimeStudies	Marketing	MaterialsScienceEn	Mathematics	MechanicalAerospac	MolecularCellBiolo	Music	NaturalResourcesth	NonDegree	Nursing	NutritionalScience	OfficeofEnrichment	OperationsInformat	PathobiologyVeteri	PharmaceuticalScie	PharmacyPractice	Philosophy	Physics	PhysiologyNeurobio	PlantScienceLandsc	PoliticalScience	PolymerSciencePhD	PreBachelorofSocia	PreIndividualized	PrePharmacy	PreSportManagement	PreTeaching	PsychologicalScien	PublicPolicy	RoboticsEngineerin	SocialCriticalInqu	SocialWork	Sociology	SpeechLanguageHear	StatisticalDataSci	Statistics	UConnHealthOther


            $departmentColumns = [
                'AGNROther',
                'Accounting',
                'AfricanaStudiesIns',
                'AgriculturalResour',
                'AlliedHealthScienc',
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
                'Exploratory',
                'Finance',
                'GeographySustainab',
                'History',
                'HumanDevelopmentFa',
                'HumanRights',
                'Individualized',
                'InstituteforSystem',
                'Interdisciplinary',
                'InternationalStudi',
                'Journalism',
                'Kinesiology',
                'LatinoLatinAmerica',
                'Law',
                'Linguistics',
                'LiteraturesCulture',
                'Management',
                'MarineSciences',
                'MaritimeStudies',
                'Marketing',
                'MaterialsScienceEn',
                'Mathematics',
                'MechanicalAerospac',
                'MolecularCellBiolo',
                'Music',
                'NaturalResourcesth',
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
                'PublicPolicy',
                'RoboticsEngineerin',
                'SocialCriticalInqu',
                'SocialWork',
                'Sociology',
                'SpeechLanguageHear',
                'StatisticalDataSci',
                'Statistics',
                'UConnHealthOther'
            ];

            $enrollments_by_dept = [];

            foreach ($departmentColumns as $column) {
                $enrollments_by_dept[$column] = $data[$column];
            }

            // Handle Section
           $section = Section::create([
                'section_number' => $data['Section'],
                'course_id' => $course->id,
                'enrol_cap' => $data['Enrl_Cap'],
                'day10_enrol' => $data['Day10_Enroll'],
                'component_code' => $data['Component_Code'],
                'start_time' => $data['Class_Start_Time'],
                'end_time' => $data['Class_End_Time'],
                'days' => $data['Class_Days'],
                'room_id' => $room->id,
                'enrollments_by_dept' => json_encode($enrollments_by_dept),
            ]);


            


            $course->save();

            // Handle Campus
            $campus = Campus::firstOrCreate(
                ['name' => $data['SA Facility Location']]
            );

            $section->campus()->associate($campus);
            $section->save();


        }

        fclose($file);
    }
}

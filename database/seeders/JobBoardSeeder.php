<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\JobAttributeValue;
use App\Models\JobListing;
use App\Models\Language;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class JobBoardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create languages
        $languages = [
            'PHP', 'JavaScript', 'Python', 'Ruby', 'Java', 'C#', 'C++', 'Go', 'Rust', 
            'TypeScript', 'Swift', 'Kotlin', 'SQL', 'HTML', 'CSS', 'React', 'Vue.js', 
            'Angular', 'Node.js', 'Laravel', 'Django', 'Ruby on Rails',
        ];
        
        foreach ($languages as $language) {
            Language::create(['name' => $language]);
        }
        
        // Create categories
        $categories = [
            'Web Development', 'Mobile Development', 'Data Science', 'DevOps', 
            'Security', 'Cloud Computing', 'Machine Learning', 'UI/UX Design', 
            'Project Management', 'Quality Assurance', 'Network Administration',
            'Database Administration', 'Full Stack Development', 'Frontend Development',
            'Backend Development',
        ];
        
        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
        
        // Create locations
        $locations = [
            ['city' => 'New York', 'state' => 'NY', 'country' => 'USA'],
            ['city' => 'San Francisco', 'state' => 'CA', 'country' => 'USA'],
            ['city' => 'Austin', 'state' => 'TX', 'country' => 'USA'],
            ['city' => 'Seattle', 'state' => 'WA', 'country' => 'USA'],
            ['city' => 'London', 'state' => null, 'country' => 'UK'],
            ['city' => 'Berlin', 'state' => null, 'country' => 'Germany'],
            ['city' => 'Toronto', 'state' => 'ON', 'country' => 'Canada'],
            ['city' => 'Sydney', 'state' => 'NSW', 'country' => 'Australia'],
            ['city' => 'Bangalore', 'state' => 'Karnataka', 'country' => 'India'],
            ['city' => 'Singapore', 'state' => null, 'country' => 'Singapore'],
        ];
        
        foreach ($locations as $location) {
            Location::create($location);
        }
        
        // Create attributes
        $attributes = [
            ['name' => 'Years of Experience', 'type' => 'number'],
            ['name' => 'Education Level', 'type' => 'select', 'options' => ['High School', 'Associate', 'Bachelor', 'Master', 'PhD']],
            ['name' => 'Requires Degree', 'type' => 'boolean'],
            ['name' => 'Start Date', 'type' => 'date'],
            ['name' => 'Preferred Skills', 'type' => 'text'],
            ['name' => 'Seniority Level', 'type' => 'select', 'options' => ['Entry', 'Junior', 'Mid', 'Senior', 'Lead', 'Manager', 'Director']],
            ['name' => 'Background Check Required', 'type' => 'boolean'],
            ['name' => 'Travel Percentage', 'type' => 'number'],
            ['name' => 'Requires Certification', 'type' => 'boolean'],
            ['name' => 'Interview Process', 'type' => 'text'],
        ];
        
        foreach ($attributes as $attribute) {
            Attribute::create($attribute);
        }
        
        // Create job listings with relationships and attributes
        $jobTypes = ['full-time', 'part-time', 'contract', 'freelance'];
        $statuses = ['draft', 'published', 'archived'];
        
        // Create 20 sample jobs
        for ($i = 1; $i <= 20; $i++) {
            $isRemote = (bool) rand(0, 1);
            $jobType = $jobTypes[array_rand($jobTypes)];
            $status = $statuses[array_rand($statuses)];
            $publishedAt = $status === 'published' ? Carbon::now()->subDays(rand(1, 30)) : null;
            
            $job = JobListing::create([
                'title' => "Job Title $i",
                'description' => "This is the description for job $i. It includes details about the responsibilities, requirements, and benefits for this position.",
                'company_name' => "Company " . rand(1, 10),
                'salary_min' => rand(50000, 80000),
                'salary_max' => rand(85000, 150000),
                'is_remote' => $isRemote,
                'job_type' => $jobType,
                'status' => $status,
                'published_at' => $publishedAt,
            ]);
            
            // Attach random languages (2-4)
            $languageCount = rand(2, 4);
            $languageIds = Language::inRandomOrder()->limit($languageCount)->pluck('id');
            $job->languages()->attach($languageIds);
            
            // Attach random categories (1-3)
            $categoryCount = rand(1, 3);
            $categoryIds = Category::inRandomOrder()->limit($categoryCount)->pluck('id');
            $job->categories()->attach($categoryIds);
            
            // Attach 1-2 locations, or none if it's a remote job
            if (!$isRemote || rand(0, 1)) {
                $locationCount = rand(1, 2);
                $locationIds = Location::inRandomOrder()->limit($locationCount)->pluck('id');
                $job->locations()->attach($locationIds);
            }
            
            // Add 3-5 random attributes
            $attributeCount = rand(3, 5);
            $attributeIds = Attribute::inRandomOrder()->limit($attributeCount)->get();
            
            foreach ($attributeIds as $attribute) {
                $value = $this->generateAttributeValue($attribute);
                
                JobAttributeValue::create([
                    'job_listing_id' => $job->id,
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                ]);
            }
        }
    }
    
    /**
     * Generate a random value based on attribute type.
     */
    private function generateAttributeValue(Attribute $attribute): string
    {
        switch ($attribute->type) {
            case 'boolean':
                return (string) (bool) rand(0, 1);
            case 'number':
                if ($attribute->name === 'Years of Experience') {
                    return (string) rand(1, 15);
                } elseif ($attribute->name === 'Travel Percentage') {
                    return (string) rand(0, 100);
                }
                return (string) rand(1, 10);
            case 'date':
                return Carbon::now()->addDays(rand(1, 90))->format('Y-m-d');
            case 'select':
                $options = $attribute->options;
                return $options[array_rand($options)];
            case 'text':
            default:
                $texts = [
                    'Strong communication skills required',
                    'Must be a team player',
                    'Self-motivated individual with attention to detail',
                    'Problem-solving abilities and critical thinking',
                    'Ability to work under pressure and meet deadlines',
                ];
                return $texts[array_rand($texts)];
        }
    }
}

<?php
/**
 * Centralized Subject Configuration
 * This file defines all subjects for each education level
 * Used by both admin.php and sms.php to ensure consistency
 */

function getSubjectsConfig(): array
{
    return [
        'Baby Class' => [
            'Kiswahili',
            'English',
            'Pre-Mathematics',
            'Environmental Awareness',
            'Creative Arts',
            'Music and Dance',
            'Physical Education',
            'Moral and Religious Education',
            'Life Skills'
        ],
        'Nursery' => [
            'Kiswahili',
            'English',
            'Pre-Mathematics',
            'Environmental Studies',
            'Creative Arts',
            'Music',
            'Physical Education',
            'Moral Education',
            'Life Skills'
        ],
        'Pre-Unit' => [
            'Kiswahili',
            'English',
            'Mathematics',
            'Environmental Studies',
            'Creative Arts',
            'Music',
            'Physical Education',
            'Moral and Religious Education',
            'Life Skills'
        ],
        'Class 1' => [
            'Kiswahili',
            'English Language',
            'Mathematics',
            'Science',
            'Social Studies',
            'Civic and Moral Education',
            'Vocational Skills',
            'ICT (where available)',
            'Physical Education',
            'Arts and Sports'
        ],
        'Class 2' => [
            'Kiswahili',
            'English Language',
            'Mathematics',
            'Science',
            'Social Studies',
            'Civic and Moral Education',
            'Vocational Skills',
            'ICT',
            'Physical Education',
            'Arts and Sports'
        ],
        'Class 3' => [
            'Kiswahili',
            'English Language',
            'Mathematics',
            'Science and Technology',
            'Social Studies',
            'Civic and Moral Education',
            'Vocational Skills',
            'ICT',
            'Physical Education'
        ],
        'Class 4' => [
            'Kiswahili',
            'English Language',
            'Mathematics',
            'Science and Technology',
            'Social Studies',
            'Civic and Moral Education',
            'Vocational Skills',
            'ICT',
            'Physical Education'
        ],
        'Class 5' => [
            'Kiswahili',
            'English Language',
            'Mathematics',
            'Science and Technology',
            'Social Studies',
            'Civic and Moral Education',
            'Vocational Skills',
            'ICT',
            'Physical Education'
        ],
        'Class 6' => [
            'Kiswahili',
            'English Language',
            'Mathematics',
            'Science and Technology',
            'Social Studies',
            'Civic and Moral Education',
            'Vocational Skills',
            'ICT',
            'Physical Education'
        ]
    ];
}

function getSubjectsForLevel(string $educationLevel): array
{
    $config = getSubjectsConfig();
    return $config[$educationLevel] ?? [];
}

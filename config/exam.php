<?php

// Per SHARED_CONSTANTS.md — must stay in sync with Flutter ExamConstants
return [
    'questions_count'    => 3,
    'score_per_question' => 30,
    'rulings_max_score'  => 10,
    'total_max_score'    => 100,

    'deductions' => [
        'error'        => 2,    // BR-EXAM-03
        'warning'      => 1,    // BR-EXAM-03
        'continuation' => 0.5,  // BR-EXAM-03
    ],
];

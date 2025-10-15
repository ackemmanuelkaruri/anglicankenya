<?php
 $diocese = [
    'name' => 'Diocese of Baringo',
    'archdeaconries' => [
        [
            'name' => 'Archdeaconry 1',
            'deaneries' => [
                [
                    'name' => 'Deanery 1',
                    'parishes' => ['Parish 1', 'Parish 2', 'Parish 3']
                ],
                [
                    'name' => 'Deanery 2',
                    'parishes' => ['Parish 1', 'Parish 2']
                ]
            ]
        ],
        [
            'name' => 'Archdeaconry 2',
            'deaneries' => [
                [
                    'name' => 'Deanery 1',
                    'parishes' => ['Parish 1', 'Parish 2']
                ]
            ]
        ]
    ]
];
?>
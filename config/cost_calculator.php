<?php

// Keyed by program slug; texts live in lang/{locale}/cost_calculator.php under programs.{slug}.
// Amounts are per person (EUR). Items flagged 'annual' are paid every year for 'years' years.
return [
    'malta_mprp' => [
        'country_code' => 'mt',
        'currency'     => 'EUR',
        'years'        => 5,

        'members' => ['main', 'spouse', 'child_under_18', 'child_over_18', 'parent'],

        'options' => [
            'rent' => [
                'base_key' => 'rent',
                'minimum'  => 14000,
                'annual'   => true,
            ],
            'purchase' => [
                'base_key' => 'investment',
                'minimum'  => 375000,
                'annual'   => false,
            ],
        ],

        'items' => [
            ['key' => 'admin_fee', 'note' => 1, 'amounts' => [
                'main' => 60000, 'spouse' => 0, 'child_under_18' => 0, 'child_over_18' => 7500, 'parent' => 7500,
            ]],
            ['key' => 'gov_contribution', 'note' => 2, 'amounts' => ['main' => 37000]],
            ['key' => 'ngo_contribution', 'amounts' => ['main' => 2000]],
            ['key' => 'health_insurance', 'amounts' => [
                'main' => 700, 'spouse' => 700, 'child_under_18' => 500, 'child_over_18' => 500, 'parent' => 700,
            ]],
            ['key' => 'residence_card', 'amounts' => [
                'main' => 500, 'spouse' => 500, 'child_under_18' => 500, 'child_over_18' => 500, 'parent' => 500,
            ]],
            ['key' => 'temp_card', 'optional' => true, 'amounts' => [
                'main' => 100, 'spouse' => 100, 'child_under_18' => 100, 'child_over_18' => 100, 'parent' => 100,
            ]],
            ['key' => 'document_processing', 'note' => 3, 'amounts' => ['main' => 5000]],
            ['key' => 'service_fee', 'note' => 4, 'amounts' => ['main' => 26000]],
            ['key' => 'acf', 'note' => 5, 'annual' => true, 'amounts' => ['main' => 975]],
        ],

        // Step durations in weeks [min, max] drive the elapsed-time estimate; the "+" open ends are estimates.
        // Schedule: step may be a list (first active step wins). share = fraction of the item total,
        // main = fixed amount from the main applicant, neither = whatever is left of the item.
        // Annual items pay year 1 at their step and the remaining years under 'after'.
        'timeline' => [
            'steps' => [
                ['key' => 'due_diligence', 'weeks' => [0.3, 0.3]],
                ['key' => 'temp_permit',   'weeks' => [4, 8], 'requires_item' => 'temp_card'],
                ['key' => 'documents',     'weeks' => [3, 6]],
                ['key' => 'application',   'weeks' => [13, 26]],
                ['key' => 'investment',    'weeks' => [4, 35]],
                ['key' => 'biometrics',    'weeks' => [4, 8]],
            ],
            'schedule' => [
                'service_fee' => [
                    ['step' => 'due_diligence', 'share' => 0.5,  'label' => 'service_fee_1'],
                    ['step' => 'documents',     'share' => 0.25, 'label' => 'service_fee_2'],
                    ['step' => 'investment',    'label' => 'service_fee_3'],
                ],
                '_base' => [
                    ['step' => ['temp_permit', 'investment']],
                ],
                'admin_fee' => [
                    ['step' => ['temp_permit', 'application'], 'main' => 15000, 'label' => 'admin_initial'],
                    ['step' => 'investment', 'label' => 'admin_final'],
                ],
                'health_insurance'    => [['step' => ['temp_permit', 'investment']]],
                'temp_card'           => [['step' => 'temp_permit']],
                'document_processing' => [['step' => 'documents']],
                'gov_contribution'    => [['step' => 'investment']],
                'ngo_contribution'    => [['step' => 'investment']],
                'residence_card'      => [['step' => 'biometrics']],
                'acf'                 => [['step' => 'after']],
            ],
        ],

        'later_members' => [
            'spouse'               => 7500,
            'child_under_18'       => 0,
            'child_over_18'        => 5000,
            'disabled_adult_child' => 0,
            'parent'               => 7500,
            'child_spouse'         => 7500,
            'grandchild'           => 5000,
        ],
    ],
];

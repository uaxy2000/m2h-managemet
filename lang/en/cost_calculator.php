<?php

return [
    'title'       => 'Cost Calculator',
    'language'    => 'Quote language',

    'client_name'      => 'Client name (optional)',
    'family_members'   => 'Family members',
    'total_applicants' => 'Total applicants',

    'options'          => 'Options',
    'minimum'          => 'Minimum :amount',
    'below_minimum'    => 'Below the programme minimum — :amount is used in the calculation.',

    'view'          => 'View',
    'side_by_side'  => 'Side by side',
    'separate'      => 'Separate tables',

    'first_year'    => 'First-year cost',
    'five_year'     => ':years-year total cost',
    'costs_in_eur'  => 'All costs in EUR',
    'amount'        => 'Amount',
    'no_option'     => 'Select at least one option to see the costs.',

    'members' => [
        'main'           => 'Main applicant',
        'spouse'         => 'Spouse',
        'child_under_18' => 'Children under 18',
        'child_over_18'  => 'Children 18 and over',
        'parent'         => 'Parents / grandparents',
    ],

    'later_member' => 'Family member',

    'pdf' => [
        'title'        => 'Cost Estimate',
        'prepared_for' => 'Prepared for',
        'prepared_by'  => 'Prepared by',
        'date'         => 'Date',
        'family'       => 'Family composition',
        'count'        => 'Persons',
        'selected'     => 'Selected options',
        'summary'      => 'Cost summary',
        'option'       => 'Option',
        'comparison'   => 'Cost comparison',
        'details'      => 'Costs by family member',
        'footer'       => 'This estimate is for information purposes only and does not constitute an offer. Amounts may change in line with programme rules.',
    ],

    'timeline' => [
        'title'        => 'Payment timeline',
        'option'       => 'Timeline for',
        'estimated'    => 'Estimated duration until residence cards: ≈ :a–:b months',
        'step_total'   => 'Step total',
        'paid_so_far'  => 'Paid so far',
        'no_payment'   => 'No payment at this step',
        'cumulative'   => 'Elapsed',
        'week_one'     => 'Week 1',
        'month_single' => '≈ month :n',
        'month_range'  => '≈ months :a–:b',
        'check'        => 'The payments in the timeline add up to the :years-year total cost.',
    ],

    'programs' => [
        'malta_mprp' => [
            'name'        => 'Malta Permanent Residence Programme (MPRP)',
            'country'     => 'Malta',
            'description' => 'Permanent residence through property rent or purchase, with government contribution.',
            'family_hint' => 'Foreigners can include close relatives: spouse, children, parents and grandparents.',

            'options' => [
                'rent' => [
                    'title'       => 'Property Rent Option',
                    'input'       => 'Annual rent',
                    'requirement' => 'Annual rent of at least :amount must be maintained for :years years.',
                ],
                'purchase' => [
                    'title'       => 'Property Purchase Option',
                    'input'       => 'Property price',
                    'requirement' => 'Minimum property purchase: :amount.',
                ],
            ],

            'optional_items' => [
                'temp_card' => 'Include temporary residence card',
            ],

            'asset_requirement' => 'Applicants must prove total assets of €500,000, of which €150,000 must be liquid; or total assets of €650,000, of which €75,000 must be liquid.',

            'items' => [
                'rent'                => 'Property rent (:years years × :amount)',
                'investment'          => 'Property purchase',
                'admin_fee'           => 'Non-refundable administrative fee',
                'gov_contribution'    => 'Non-refundable government contribution',
                'ngo_contribution'    => 'Contribution to a Maltese non-governmental organisation',
                'health_insurance'    => 'Health insurance',
                'residence_card'      => 'Residence permit card fee (5-year card)',
                'temp_card'           => 'Temporary residence card fee (optional)',
                'document_processing' => 'Document processing fees',
                'service_fee'         => 'Service fee',
                'acf'                 => 'Annual compliance fee — ACF (:years years × :amount)',
            ],

            'notes' => [
                1 => 'The non-refundable administrative fee is paid in two instalments: €15,000 within 1 month of submitting the application and €45,000 within 2 months of receiving the Letter of Approval in Principle. An additional non-refundable administrative fee of €7,500 is payable for each dependant within 2 months of approval.',
                2 => 'The non-refundable government contribution is paid in a single payment, cannot be split into instalments, and is due within 8 months of the Letter of Approval in Principle.',
                3 => 'Document processing fees are indicative only and cover translation and legalisation of documents where needed. The amount may vary and usually depends on the number of documents.',
                4 => 'The service fee may vary depending on the number of family members.',
                5 => 'An annual compliance report must be submitted every year for the first 5 years (currently €975, subject to change).',
            ],

            'timeline' => [
                'steps' => [
                    'due_diligence' => [
                        'title'       => 'Preliminary due diligence & agreement',
                        'duration'    => '2 days',
                        'description' => 'A confidential preliminary check confirms that nothing prevents the applicant from joining the programme. The service agreement is then signed.',
                    ],
                    'temp_permit' => [
                        'title'       => 'Temporary residence permit (optional)',
                        'duration'    => '1+ months',
                        'description' => 'Before the full application, the main applicant and dependants may obtain a 1-year temporary residence permit. Applicants visit Malta to submit biometrics and documents.',
                        'extras'      => ['Travel to Malta for biometrics (not included)'],
                    ],
                    'documents' => [
                        'title'       => 'Preparing documents',
                        'duration'    => '3+ weeks',
                        'description' => 'Our lawyers guide the collection, certification and translation of documents, typically done in Malta to meet the required standards.',
                    ],
                    'application' => [
                        'title'       => 'Application & due diligence',
                        'duration'    => 'Usually 3–6 months',
                        'description' => 'The Residency Malta Agency reviews the application in 90–180 days and issues a Letter of Approval in Principle. If a temporary permit was obtained, this step must be completed within 6 months of it.',
                    ],
                    'investment' => [
                        'title'       => 'Fulfilling investment conditions',
                        'duration'    => 'Within 8 months of approval',
                        'description' => 'After approval: pay the rest of the administrative fee (within 2 months), rent or buy the property, pay the government contribution (within 8 months) and donate to an NGO. A permanent residence certificate is then issued.',
                    ],
                    'biometrics' => [
                        'title'       => 'Biometrics & residence cards',
                        'duration'    => '4+ weeks',
                        'description' => 'All applicants submit biometrics in person in Malta (infants up to 2 are exempt). Residence cards are printed in about 4 weeks.',
                        'extras'      => ['Travel to Malta for biometrics (not included)'],
                    ],
                    'after' => [
                        'title'       => 'After permanent residence',
                        'duration'    => 'Years 1–:years',
                        'description' => 'Recurring annual payments required to keep the residence status.',
                    ],
                ],
                'payments' => [
                    'service_fee_1'       => 'Service fee — 1st payment (50%)',
                    'service_fee_2'       => 'Service fee — 2nd payment (25%)',
                    'service_fee_3'       => 'Service fee — final payment (25%)',
                    'admin_initial'       => 'Administrative fee — initial part',
                    'admin_final'         => 'Administrative fee — final part (incl. dependants)',
                    'rent_first'          => 'Property rent — 1st year',
                    'rent_remaining'      => 'Property rent — years 2–:years',
                    'investment'          => 'Property purchase',
                    'health_insurance'    => 'Health insurance',
                    'temp_card'           => 'Temporary residence card fee',
                    'document_processing' => 'Document processing (translation & certification)',
                    'gov_contribution'    => 'Government contribution',
                    'ngo_contribution'    => 'Donation to a Maltese NGO',
                    'residence_card'      => '5-year residence card fee',
                    'acf'                 => 'Annual compliance fee (ACF) — :years years',
                ],
                'disclaimer' => 'Stages, timelines and fees are general guidelines and are updated regularly. Conditions may vary due to legislation or individual circumstances.',
            ],

            'later_title'   => 'Adding a family member after permanent residence is granted',
            'later_intro'   => 'After obtaining permanent residence in Malta, the main applicant may include the following family members:',
            'later_members' => [
                'spouse'               => 'Spouse',
                'child_under_18'       => 'Children under 18',
                'child_over_18'        => 'Children 18 and over',
                'disabled_adult_child' => 'Adult child of the main applicant or spouse with a documented disability',
                'parent'               => 'Parents / grandparents',
                'child_spouse'         => 'Son-in-law / daughter-in-law',
                'grandchild'           => 'Grandchildren',
            ],
            'later_rules' => [
                'The spouse;',
                'Children under 18, including the spouse\'s children, provided they are principally dependent on the main applicant;',
                'Children aged 18 and over, including the spouse\'s children, provided they are principally dependent on the main applicant;',
                'An adult child of the main applicant or spouse with a documented disability;',
                'Parents or grandparents, including the spouse\'s parents or grandparents;',
                'Spouses of children who are not dependent on the main applicant;',
                'Grandchildren who are not dependent on the main applicant.',
            ],
        ],
    ],
];

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now()->toDateTimeString();

        // ── Custom fields ─────────────────────────────────────────────
        $fields = [
            ['key' => 'date_of_birth',        'label' => 'Date of Birth',        'type' => 'date',         'sort_order' => 1],
            ['key' => 'main_intention',        'label' => 'Main Intention',       'type' => 'multi_select', 'sort_order' => 2],
            ['key' => 'marital_status',        'label' => 'Marital Status',       'type' => 'select',       'sort_order' => 3],
            ['key' => 'children',              'label' => 'Children',             'type' => 'multi_select', 'sort_order' => 4],
            ['key' => 'wealth_level',          'label' => 'Wealth Level',         'type' => 'select',       'sort_order' => 5],
            ['key' => 'italy_gv_heard_before', 'label' => 'Italy GV Heard Before','type' => 'select',       'sort_order' => 6],
            ['key' => 'eur250k_status',        'label' => '€250k Status',         'type' => 'select',       'sort_order' => 7],
        ];

        $ids = [];
        foreach ($fields as $f) {
            $id = (string) Str::uuid();
            $ids[$f['key']] = $id;
            DB::table('custom_fields')->insert([
                'id'         => $id,
                'key'        => $f['key'],
                'label'      => $f['label'],
                'type'       => $f['type'],
                'sort_order' => $f['sort_order'],
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── Options ───────────────────────────────────────────────────
        $options = [];

        // Main Intention
        foreach ([
            ['investment',       'Investment',                false],
            ['plan_b',           'Plan B',                   false],
            ['kids_education',   "Kids' Education",          false],
            ['visa_free_travel', 'Visa Free Travel',         false],
            ['home_risks',       'Home Country Risks',       false],
            ['business',         'Business Formation',       false],
            ['better_living',    'Better Living Conditions', false],
        ] as $i => [$val, $lbl, $excl]) {
            $options[] = [
                'custom_field_id' => $ids['main_intention'],
                'value'           => $val,
                'label'           => $lbl,
                'is_exclusive'    => $excl ? 1 : 0,
                'sort_order'      => $i + 1,
            ];
        }

        // Marital Status
        foreach ([
            ['single',   'Single'],
            ['married',  'Married'],
            ['divorced', 'Divorced'],
            ['widowed',  'Widowed'],
        ] as $i => [$val, $lbl]) {
            $options[] = [
                'custom_field_id' => $ids['marital_status'],
                'value'           => $val,
                'label'           => $lbl,
                'is_exclusive'    => 0,
                'sort_order'      => $i + 1,
            ];
        }

        // Children (No Kids is exclusive)
        foreach ([
            ['no_kids',        'No Kids',             true],
            ['kids_below_18',  'Have Kids Below 18',  false],
            ['kids_above_18',  'Have Kids Above 18',  false],
        ] as $i => [$val, $lbl, $excl]) {
            $options[] = [
                'custom_field_id' => $ids['children'],
                'value'           => $val,
                'label'           => $lbl,
                'is_exclusive'    => $excl ? 1 : 0,
                'sort_order'      => $i + 1,
            ];
        }

        // Wealth Level (Meta aliases to be set via UI once real values are confirmed)
        foreach ([
            ['level_1', 'Under €250k'],
            ['level_2', '€250k – €500k'],
            ['level_3', '€500k – €1M'],
            ['level_4', 'Over €1M'],
        ] as $i => [$val, $lbl]) {
            $options[] = [
                'custom_field_id' => $ids['wealth_level'],
                'value'           => $val,
                'label'           => $lbl,
                'is_exclusive'    => 0,
                'sort_order'      => $i + 1,
            ];
        }

        // Italy GV Heard Before
        foreach ([
            ['yes', 'Yes'],
            ['no',  'No'],
        ] as $i => [$val, $lbl]) {
            $options[] = [
                'custom_field_id' => $ids['italy_gv_heard_before'],
                'value'           => $val,
                'label'           => $lbl,
                'is_exclusive'    => 0,
                'sort_order'      => $i + 1,
            ];
        }

        // €250k Status
        foreach ([
            ['agree',    'Yes, I can invest'],
            ['not_sure', 'Not sure yet'],
            ['no',       'No, too much'],
        ] as $i => [$val, $lbl]) {
            $options[] = [
                'custom_field_id' => $ids['eur250k_status'],
                'value'           => $val,
                'label'           => $lbl,
                'is_exclusive'    => 0,
                'sort_order'      => $i + 1,
            ];
        }

        foreach ($options as &$opt) {
            $opt['id']           = (string) Str::uuid();
            $opt['meta_aliases'] = null;
        }
        unset($opt);

        DB::table('custom_field_options')->insert($options);

        // ── Meta question mappings ─────────────────────────────────────
        // Keys are pre-normalized (mb_strtolower with İ/I → i)
        $mappings = [
            ['eu farkli programlar sunabiliriz',                                                                                                  'wealth_level'],
            ['italya altin vize programini daha once duydunuz mu',                                                                               'italy_gv_heard_before'],
            ['bu program, önce itayla\'da oturum izni verir, ardından 12 ay içerisinde minimum 250.000 euro veya üstü yatırım yapmayı zorunlu kılar.', 'eur250k_status'],
        ];

        foreach ($mappings as [$questionKey, $fieldKey]) {
            DB::table('meta_question_mappings')->insert([
                'id'                 => (string) Str::uuid(),
                'meta_question_key'  => $questionKey,
                'custom_field_id'    => $ids[$fieldKey],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('meta_question_mappings')->truncate();
        DB::table('lead_custom_values')->truncate();
        DB::table('custom_field_options')->truncate();
        DB::table('custom_fields')->truncate();
    }
};

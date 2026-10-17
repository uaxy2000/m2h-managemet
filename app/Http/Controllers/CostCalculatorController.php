<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CostCalculatorController extends Controller
{
    public function index(): View
    {
        $programs = collect(config('cost_calculator'))->map(fn ($cfg, $slug) => [
            'slug'         => $slug,
            'country_code' => $cfg['country_code'],
            'name'         => trans("cost_calculator.programs.$slug.name", [], 'en'),
            'country'      => trans("cost_calculator.programs.$slug.country", [], 'en'),
            'description'  => trans("cost_calculator.programs.$slug.description", [], 'en'),
        ])->values();

        return view('cost-calculator.index', compact('programs'));
    }

    public function show(string $program): View
    {
        $config = config("cost_calculator.$program");
        abort_unless(is_array($config), 404);

        $texts = [];
        foreach (['en', 'tr'] as $locale) {
            $all = trans('cost_calculator', [], $locale);
            $all['program'] = $all['programs'][$program];
            unset($all['programs']);
            $texts[$locale] = $all;
        }

        return view('cost-calculator.show', [
            'config' => $config,
            'texts'  => $texts,
            'slug'   => $program,
        ]);
    }
}

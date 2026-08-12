<?php

namespace App\View\Components;

use App\Helpers\PhoneFormatter;
use Illuminate\View\Component;
use Illuminate\View\View;

class PhoneNumber extends Component
{
    public string $raw;
    public string $formatted;
    public bool   $valid;
    public ?string $iso;
    public ?string $flag;

    public function __construct(string $number)
    {
        $this->raw = $number;

        $result          = PhoneFormatter::format($number);
        $this->formatted = $result['formatted'];
        $this->valid     = $result['valid'];
        $this->iso       = $result['iso'];
        $this->flag      = $result['flag'];
    }

    public function render(): View
    {
        return view('components.phone-number');
    }
}

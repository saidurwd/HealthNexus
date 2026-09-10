<?php

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Dropdown extends Component
{
    public $align;

    public $width;

    public function __construct($align = 'right', $width = '48')
    {
        $this->align = $align === 'left' ? 'ltr:origin-top-left rtl:origin-top-right' : 'ltr:origin-top-right rtl:origin-top-left';
        $this->width = $width === '48' ? 'w-48' : 'w-'.$width;
    }

    public function render(): View|Closure|string
    {
        return view('components.dropdown');
    }
}

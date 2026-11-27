@props(['href','active'=>false])<a href="{{ $href }}" @if($active) aria-current="page" @endif {{ $attributes->merge(['class'=>'nav-link'.($active?' active':'')]) }}>{{ $slot }}</a>

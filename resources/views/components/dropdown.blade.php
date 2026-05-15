<<<<<<< HEAD
@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white dark:bg-gray-700'])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
=======
@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'bg-white'])

@php
$alignmentClasses = match ($align) {
    'left'  => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top'   => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$widthClass = match ($width) {
    '48' => 'w-48',
    '56' => 'w-56',
    '64' => 'w-64',
>>>>>>> 9ad783d (Initial commit)
    default => $width,
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
<<<<<<< HEAD
    <div @click="open = ! open">
=======
    <div @click="open = !open">
>>>>>>> 9ad783d (Initial commit)
        {{ $trigger }}
    </div>

    <div x-show="open"
<<<<<<< HEAD
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-2 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
=======
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
         class="absolute z-50 mt-2 {{ $widthClass }} {{ $alignmentClasses }} rounded-2xl shadow-card-md border border-slate-200/80 overflow-hidden"
         style="display: none;"
         @click="open = false">
        <div class="{{ $contentClasses }}">
>>>>>>> 9ad783d (Initial commit)
            {{ $content }}
        </div>
    </div>
</div>

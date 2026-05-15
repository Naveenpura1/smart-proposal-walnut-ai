@props(['value'])

<<<<<<< HEAD
<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 dark:text-gray-300']) }}>
=======
<label {{ $attributes->merge(['class' => 'form-label']) }}>
>>>>>>> 9ad783d (Initial commit)
    {{ $value ?? $slot }}
</label>

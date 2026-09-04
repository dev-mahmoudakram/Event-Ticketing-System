{{-- resources/views/components/admin/bilingual-field.blade.php --}}

{{-- nameAr / nameEn default to the "field_ar" / "field_en" pair the event forms use. Forms
     that post arrays (the Creators Hub content editor) pass their own bracketed names. --}}
@props([
    'type' => 'text',
    'name',
    'label' => null,
    'valueAr' => null,
    'valueEn' => null,
    'placeholder' => null,
    'nameAr' => null,
    'nameEn' => null,
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-admin.field
        :type="$type"
        :name="$nameAr ?? $name.'_ar'"
        :label="$label ? $label.' ('.__('Arabic').')' : null"
        :value="$valueAr"
        :placeholder="$placeholder"
        dir="rtl"
    />
    <x-admin.field
        :type="$type"
        :name="$nameEn ?? $name.'_en'"
        :label="$label ? $label.' ('.__('English').')' : null"
        :value="$valueEn"
        :placeholder="$placeholder"
    />
</div>

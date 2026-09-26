<?php

declare(strict_types=1);

return [

    // Merged on top of Laravel's built-in defaults (see vendor/laravel/framework's
    // internal lang/en/validation.php, used as the Arabic fallback for keys this
    // file doesn't define). Covers every rule the app's forms use, so no error on
    // the Arabic site falls back to English.

    'accepted' => 'يجب قبول :attribute.',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا في :date أو بعده.',
    'array' => 'يجب أن يكون :attribute قائمة.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'between' => [
        'array' => 'يجب أن يحتوي :attribute على ما بين :min و:max عناصر.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و:max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و:max.',
        'string' => 'يجب أن يكون طول :attribute بين :min و:max حرفًا.',
    ],
    'boolean' => 'يجب أن تكون قيمة :attribute نعم أو لا.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'date' => 'يجب أن يكون :attribute تاريخًا صحيحًا.',
    'date_format' => 'يجب أن يطابق :attribute الصيغة :format.',
    'digits' => 'يجب أن يتكون :attribute من :digits أرقام.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صحيحًا.',
    'exists' => 'قيمة :attribute المختارة غير صالحة.',
    'file' => 'يجب أن يكون :attribute ملفًا.',
    'gt' => [
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string' => 'يجب أن يكون طول :attribute أكثر من :value حرفًا.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'قيمة :attribute المختارة غير صالحة.',
    'integer' => 'يجب أن يكون :attribute رقمًا صحيحًا.',
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يتجاوز حجم :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألا تتجاوز قيمة :attribute :max.',
        'string' => 'يجب ألا يتجاوز طول :attribute :max حرفًا.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
        'file' => 'يجب أن يكون حجم :attribute :min كيلوبايت على الأقل.',
        'numeric' => 'يجب أن تكون قيمة :attribute :min على الأقل.',
        'string' => 'يجب أن يكون طول :attribute :min أحرف على الأقل.',
    ],
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'password' => [
        'letters' => 'يجب أن تحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن تحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت :attribute في تسريب بيانات. يرجى اختيار كلمة مرور أخرى.',
    ],
    'phone' => 'يجب أن يكون :attribute رقم هاتف صالحًا.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عندما تكون قيمة :other هي :value.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عناصر.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string' => 'يجب أن يكون طول :attribute :size حرفًا.',
    ],
    'string' => 'يجب أن يكون :attribute نصًا.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'uploaded' => 'تعذّر رفع :attribute.',
    'url' => 'يجب أن يكون :attribute رابطًا صحيحًا.',

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'الهاتف',
        'password' => 'كلمة المرور',
        'message' => 'الرسالة',
        'ticket_type_id' => 'نوع التذكرة',
        'influencer_category_id' => 'فئة صانع المحتوى',
        'influencer_category_other' => 'الفئة',
        'coupon_code' => 'كود الخصم',
        'otp' => 'الرمز',
        'photo' => 'الصورة',
        'logo' => 'الشعار',
        'name_ar' => 'الاسم (بالعربية)',
        'name_en' => 'الاسم (بالإنجليزية)',
        'title_ar' => 'المسمى (بالعربية)',
        'title_en' => 'المسمى (بالإنجليزية)',
        'bio_ar' => 'النبذة (بالعربية)',
        'bio_en' => 'النبذة (بالإنجليزية)',
        'website_url' => 'الموقع الإلكتروني',
        'instagram_url' => 'رابط إنستغرام',
        'instagram_followers' => 'عدد متابعي إنستغرام',
        'facebook_url' => 'رابط فيسبوك',
        'facebook_followers' => 'عدد متابعي فيسبوك',
        'tiktok_url' => 'رابط تيك توك',
        'tiktok_followers' => 'عدد متابعي تيك توك',
        'slug' => 'المعرّف',
        'start_date' => 'تاريخ البداية',
        'end_date' => 'تاريخ النهاية',
        'status' => 'الحالة',
    ],

];

<?php

return [
    'file_upload' => [
        'placeholder' => 'فایلی بارگذاری نشده است',
        'actions' => [
            'download' => ['label' => 'دریافت فایل'],
            'open' => ['label' => 'باز کردن در زبانهٔ جدید'],
        ],
    ],
    'repeater' => [
        'reordering' => [
            'item' => 'ردیف',
            'position' => ':label، جایگاه :position از :count',
            'moved' => ':label به جایگاه :position از :count منتقل شد.',
        ],
    ],
    'select' => [
        'actions' => [
            'clear' => ['label' => 'پاک کردن انتخاب'],
            'remove_option' => ['label' => 'حذف :label'],
        ],
        'error_message' => 'بارگیری گزینه‌ها انجام نشد. دوباره تلاش کنید.',
        'required_description' => 'یک گزینه انتخاب کنید.',
        'search_label' => 'جست‌وجو',
    ],
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute harus diizinkan.',
    'active_url' => ':attribute bukan URL yang valid.',
    'after' => ':attribute harus setelah tanggal :date.',
    'after_or_equal' => ':attribute harus setelah tanggal atau sama dengan :date.',
    'alpha' => ':attribute hanya diisi dengan karakter huruf.',
    'alpha_dash' => ':attribute hanya diisi dengan karakter huruf, angka, dashes dan underscores.',
    'alpha_num' => ':attribute hanya diisi dengan karakter huruf dan angka.',
    'array' => ':attribute harus berupa array.',
    'before' => ':attribute harus sebelum tanggal :date.',
    'before_or_equal' => ':attribute harus sebelum tanggal atau sama dengan :date.',
    'between' => [
        'numeric' => ':attribute harus diantara :min dan :max.',
        'file' => ':attribute harus diantara :min dan :max kilobytes.',
        'string' => ':attribute harus diantara :min dan :max characters.',
        'array' => ':attribute harus memiliki :min dan :max item.',
    ],
    'boolean' => ':attribute harus berupa true atau false.',
    'confirmed' => ':attribute konfirmasi tidak cocok.',
    'date' => ':attribute bukan tanggal yang valid.',
    'date_equals' => ':attribute harus sama dengan tanggal :date.',
    'date_format' => ':attribute format tanggal tidak sama dengan format :format.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus terdiri dari :digits digit.',
    'digits_between' => ':attribute harus diantara :min dan :max digits.',
    'dimensions' => ':attribute bukan gambar yang valid.',
    'distinct' => ':attribute sudah ada.',
    'email' => ':attribute harus berupa email yang valid.',
    'ends_with' => ':attribute harus diakhiri dengan: :values.',
    'exists' => ':attribute tidak ada.',
    'exists_file' => 'File :attribute tidak ditemukan.',
    'file' => ':attribute harus berupa file.',
    'filled' => ':attribute harus memiliki nilai.',
    'gt' => [
        'numeric' => ':attribute harus lebih dari :value.',
        'file' => ':attribute harus lebih dari :value kilobytes.',
        'string' => ':attribute harus lebih dari :value characters.',
        'array' => ':attribute harus lebih dari :value item.',
    ],
    'gte' => [
        'numeric' => ':attribute harus lebih dari atau sama dengan :value.',
        'file' => ':attribute harus lebih dari atau sama dengan :value kilobytes.',
        'string' => ':attribute harus lebih dari atau sama dengan :value characters.',
        'array' => ':attribute harus lebih dari atau sama dengan :value item.',
    ],
    'image' => ':attribute harus berupa gambar yang valid.',
    'in' => ':attribute tidak valid.',
    'in_array' => ':attribute tidak terdapat pada :other.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'ip' => ':attribute harus berupa IP yang valid.',
    'ipv4' => ':attribute harus berupa IPv4 yang valid.',
    'ipv6' => ':attribute harus berupa IPv6 yang valid.',
    'json' => ':attribute harus berupa JSON string yang valid.',
    'lt' => [
        'numeric' => ':attribute harus kurang dari :value.',
        'file' => ':attribute harus kurang dari :value kilobytes.',
        'string' => ':attribute harus kurang dari :value characters.',
        'array' => ':attribute harus kurang dari :value item.',
    ],
    'lte' => [
        'numeric' => ':attribute harus kurang dari atau sama dengan :value.',
        'file' => ':attribute harus kurang dari atau sama dengan :value kilobytes.',
        'string' => ':attribute harus kurang dari atau sama dengan :value characters.',
        'array' => ':attribute harus kurang dari :value item.',
    ],
    'max' => [
        'numeric' => ':attribute maksimal :max.',
        'file' => ':attribute maksimal :max kilobytes.',
        'string' => ':attribute maksimal :max characters.',
        'array' => ':attribute maksimal :max item.',
    ],
    'mimes' => 'File :attribute harus memiliki type: :values.',
    'mimetypes' => ':attribute harus memiliki type: :values.',
    'min' => [
        'numeric' => ':attribute sekurang-kurangnya :min.',
        'file' => ':attribute sekurang-kurangnya :min kilobytes.',
        'string' => ':attribute sekurang-kurangnya :min characters.',
        'array' => ':attribute sekurang-kurangnya :min item.',
    ],
    'multiple_of' => ':attribute harus bagian dari :value.',
    'not_in' => ':attribute tidak valid.',
    'not_regex' => ':attribute format tidak valid.',
    'numeric' => ':attribute harus berupa angka.',
    'password' => 'password tidak valid.',
    'present' => ':attribute field must be present.',
    'regex' => ':attribute format tidak valid.',
    'required' => ':attribute harus diisi.',
    'required_if' => ':attribute harus diisi jika :other sama dengan :value.',
    'required_unless' => ':attribute harus diisi :other kurang dari :values.',
    'required_with' => ':attribute harus diisi jika :values ada.',
    'required_with_all' => ':attribute harus diisi jika :values ada.',
    'required_without' => ':attribute harus diisi jika :values tidak ada.',
    'required_without_all' => ':attribute harus diisi jika semuanya :values tidak ada.',
    'same' => ':attribute dan :other must match.',
    'size' => [
        'numeric' => ':attribute harus berukuran :size.',
        'file' => ':attribute harus berukuran :size kilobytes.',
        'string' => ':attribute harus berukuran :size characters.',
        'array' => ':attribute harus berukuran masing-masing :size item.',
    ],
    'starts_with' => ':attribute harus dimulai dari: :values.',
    'string' => ':attribute harus berupa string.',
    'timezone' => ':attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah ada.',
    'uploaded' => ':attribute gaga diunggah.',
    'url' => ':attribute bukan url yang valid.',
    'uuid' => ':attribute bukan format UUID yang valid.',
    'today' => 'hari ini',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => __("field"),

];

##  Laravel 
Yang dibutuhkan :
 - composer, >= php8.2

## Install Dependency
```
composer install
```

## Cara Install
copy file .env.example ke .env
```
cp .env.example .env
```
Sesuaikan .env dengan konfigurasi database anda

## Setting JWT Secret Key
Jalankan :
php artisan jwt:secret

## Database Migration
Jalankan database migration
```
php artisan migrate
```

# Struktur Folder 
```text
app/
├── Http/
│   └── Controllers/        ← Semua controller
├── Models/                 ← Eloquent Models dan Config CRUD
└── Services/               ← Business Logic (Service Layer)

database/
├── migrations/             ← Schema database
└── seeders/                ← Data awal

routes/
└── api.php                 ← Semua route API
```

# Alur Data
```text
HTTP Request
    ↓
routes/api.php          ← Routing & Middleware
    ↓
Http/Requests/          ← Validasi Input
    ↓
Http/Controllers/       ← Menerima request, memanggil Service
    ↓
Services/               ← Business logic
    ↓
Models/                 ← Eloquent ORM
    ↓
HTTP Response (JSON)
```

# Penamaan Endpoint / Routing
Penamaan endpoint API menggunakan format lowercase dan kebab-case (contoh : /product-categories).

# Mapping HTTP method

| Aksi   | Method | Endpoint                         | Permission     |
|--------|--------|----------------------------------|----------------|
| List   | GET    | `{{BASE_URL}}/api/{model}`       | `view-{model}` |
|        |        | `{{BASE_URL}}/api/users`         | `view-users`   |
| Dataset| GET    | `{{BASE_URL}}/api/{model}/dataset` | `dataset-{model}` |
|        |        | `{{BASE_URL}}/api/users/dataset` | `dataset-users` |
| Detail | GET    | `{{BASE_URL}}/api/{model}/{id}`  | `show-{model}` |
|        |        | `{{BASE_URL}}/api/users/1`       | `show-users`   |
| Add  | POST   | `{{BASE_URL}}/api/{model}`      | `create-{model}` |
|      |        | `{{BASE_URL}}/api/users`        | `create-users`   |
| Edit | PUT    | `{{BASE_URL}}/api/{model}/{id}` | `update-{model}` |
|      |        | `{{BASE_URL}}/api/users/1`      | `update-users`   |
| Hapus| DELETE | `{{BASE_URL}}/api/{model}/{id}` | `delete-{model}` |
|      |        | `{{BASE_URL}}/api/users/1`      | `delete-users`   |


# Konvensi Penamaan Tabel & Kolom
# Aturan nama tabel
- Nama tabel menggunakan snake_case, plural dan lowercase. Contoh : users, product_categories, order_items
- Pivot table: urut alfabet, gabung dengan underscore: user_role, role_permission
- Hindari prefix seperti tbl_ atau m_
- Gunakan bahasa yang konsisten (inggris/bahasa indonesia) dalam satu proyek

# Aturan nama kolom
- Nama kolom menggunakan snake_case. Contoh: first_name, created_at
- Foreign key: nama tabel singular + _id → user_id, category_id
- Boolean: awalan is_ atau has_ → is_active, has_verified
- Kolom timestamp menggunakan tipe data timestamptz (dengan timezone). Contoh: created_at, updated_at, deleted_at, verified_at
- Tanggal saja (tanpa jam): akhiran _date → birth_date, expired_date
- Enum/status: akhiran _status atau _type → payment_status
- Hindari reserved words SQL: order, group, key, value

# Index
- Kolom foreign key, kolom yang sering digunakan untuk filter data (WHERE clause) atau sorting data (ORDER BY) 

# Referensi Tabel
## Panduan Tipe Kolom PostgreSQL

| Tipe Kolom                  | Contoh Nama                        | Tipe Data (PostgreSQL) |
|-----------------------------|------------------------------------|-------------------------|
| Primary key                 | `id`                               | `uuid` / `bigint` |
| Foreign key                 | `user_id`, `category_id`           | `bigint` |
| Nama pendek                 | `name`, `title`, `label`           | `varchar(n)` ⇒ `varchar(255)` |
| Teks panjang                | `description`, `notes`, `body`     | `text` |
| Harga/uang                  | `price`, `total`, `amount`         | `numeric/decimal (12,2)`  |
|                             |                                    | _ps: jangan pakai `real` atau `float` untuk uang karena tidak presisi_ |
| Persentase                  | `discount_percent`, `tax_rate`     | `numeric/decimal (5,2)` |
| Jumlah/qty                  | `quantity`, `stock`, `count`       | `integer` / `biginteger` |
| Boolean                     | `is_active`, `has_paid`            | `boolean` |
| JSON/array                  | `metadata`, `settings`, `config`   | `json` / `jsonb` |
| Status/enum                 | `status`, `type`, `role`           | `varchar` / `enum` |
|                             |                                    | _ps: kalau data sering berubah/bertambah pakai lookup table aja_ |
| Tanggal saja                | `date`, `expired_date`             | `date` |
| Jam saja (tanpa timezone)   | `time`, `start_time`, `end_time`   | `time` |
| Tanggal + jam (dengan timezone) | `verified_at`, `created_at`, `updated_at` | `timestamptz` |
| Durasi (`'3 days'`, `'2 hours'`) | `duration`                    | `interval` |

# Kolom yang wajib ada
| Kolom       | Tipe Data       | Keterangan |
|-------------|----------------|-------------|
| `id`        | `biginteger`   | Primary Key |
| `created_at`| `timestamptz`  | Kapan ditambahkan |
| `updated_at`| `timestamptz`  | Kapan terakhir diupdate |
| `created_by`| `biginteger`, FK | Siapa yang menambahkan |
| `updated_by`| `biginteger`, FK | Siapa yang terakhir mengupdate |


# Membuat Migration Table :
1. php artisan make:migration create_{nama_table}
2. php artisan make:migration alter_table_{}

# Generate Model
1. php artisan generate:model
2. php artisan generate:model {model_name}

## Menambahkan API Custom Baru :
1. Buat File Di Folder App/Service/{Modul}
2. Selanjutnya Buat File Baru Sesuai dengan service API yang akan dibuat.
3. Kemudian Setelah Membuat File Service API baru, tambahakan routingnya di file    service.php yang ada di Config/service.php
4. Tambahkan :
    [
        "type" => "{{HTTP Method tanda tanda kurawal}}",
        "end_point" => "{{nama end pointnya tanpa tanda kurawal}}",
        "class" => "{{path file service baru tadi yang ada di service/custom tanpa tanda kurawal}}"
    ],
5. Test end point pada POSTMAN

## Email Queue
QUEUE_CONNECTION=database
php artisan queue:work


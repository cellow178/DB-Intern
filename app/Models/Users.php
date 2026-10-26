<?php

namespace App\Models;

use App\CoreService\CallService;
use App\CoreService\CoreException;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;


class Users extends Model
{
    use HasFactory;

    protected $table = 'users';
    protected $dateFormat = 'c';

    protected $hidden = [
        'password',
        'reset_token',
    ];

    protected $fillable = [
        'fullname',
        'username',
        'password',
        'email',
        'nis',
        'nisn',
        'masa_studi',
        'role_id',
        'status_code',
        'must_change_password',
        'created_by',
        'updated_by',
    ];

    const TABLE = "users";
    const FILEROOT = "/users";
    const IS_LIST = true;
    const IS_ADD = true;
    const IS_EDIT = true;
    const IS_DELETE = true;
    const IS_VIEW = true;
    const FIELD_LIST = [
        "id",
        "fullname",
        "username",
        "email",
        "nis",
        "nisn",
        "masa_studi",
        "role_id",
        "status_code",
        "must_change_password",
        "email_verified_at",
        "last_login_at",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at",
    ];
    const FIELD_ADD = [
        "fullname",
        "username",
        "password",
        "email",
        "nis",
        "nisn",
        "masa_studi",
        "role_id",
        "status_code",
        "must_change_password",
        "created_by",
        "updated_by",
    ];
    const FIELD_EDIT = [
        "fullname",
        "username",
        "password",
        "email",
        "nis",
        "nisn",
        "masa_studi",
        "role_id",
        "status_code",
        "must_change_password",
        "updated_by",
    ];
    const FIELD_VIEW = [
        "id",
        "fullname",
        "username",
        "email",
        "nis",
        "nisn",
        "masa_studi",
        "role_id",
        "status_code",
        "must_change_password",
        "email_verified_at",
        "last_login_at",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at",
    ];
    const FIELD_READONLY = [];
    const FIELD_FILTERABLE = [
        "id" => [
            "operator" => "=",
        ],
        "fullname" => [
            "operator" => "=",
        ],
        "username" => [
            "operator" => "=",
        ],
        "email" => [
            "operator" => "=",
        ],
        "nis" => [
            "operator" => "=",
        ],
        "nisn" => [
            "operator" => "=",
        ],
        "masa_studi" => [
            "operator" => "=",
        ],
        "role_id" => [
            "operator" => "=",
        ],
        "status_code" => [
            "operator" => "=",
        ],
        "must_change_password" => [
            "operator" => "=",
        ],
        "created_by" => [
            "operator" => "=",
        ],
        "updated_by" => [
            "operator" => "=",
        ],
        "created_at" => [
            "operator" => "=",
        ],
        "updated_at" => [
            "operator" => "=",
        ],
    ];
    const FIELD_SEARCHABLE = ["fullname", "username", "email", "nis", "nisn"];
    const FIELD_ARRAY = [];
    const FIELD_SORTABLE = [
        "id",
        "fullname",
        "username",
        "email",
        "nis",
        "nisn",
        "masa_studi",
        "role_id",
        "status_code",
        "must_change_password",
        "email_verified_at",
        "last_login_at",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at",
    ];
    // NOTE: email, nis & nisn nullable — uniqueness-nya dicek manual di
    // beforeInsert/beforeUpdate agar tidak bentrok antar nilai NULL.
    const FIELD_UNIQUE = [["username"]];
    const FIELD_UPLOAD = [];
    const FIELD_TYPE = [
        "id" => "bigint",
        "fullname" => "character_varying",
        "username" => "character_varying",
        "password" => "text",
        "email" => "character_varying",
        "nis" => "character_varying",
        "nisn" => "character_varying",
        "masa_studi" => "integer",
        "role_id" => "bigint",
        "must_change_password" => "boolean",
        "reset_token" => "character_varying",
        "reset_token_expired_at" => "timestamp_with_time_zone",
        "email_verified_at" => "timestamp_with_time_zone",
        "last_login_at" => "timestamp_with_time_zone",
        "status_code" => "character_varying",
        "created_by" => "bigint",
        "updated_by" => "bigint",
        "created_at" => "timestamp_with_time_zone",
        "updated_at" => "timestamp_with_time_zone",
    ];

    const FIELD_DEFAULT_VALUE = [
        "fullname" => "",
        "username" => "",
        "password" => "",
        "email" => "",
        "nis" => "",
        "nisn" => "",
        "masa_studi" => "",
        "role_id" => "",
        "status_code" => "user_active",
        "must_change_password" => "false",
        "created_by" => "",
        "updated_by" => "",
        "created_at" => "",
        "updated_at" => "",
    ];
    const FIELD_RELATION = [
        "role_id" => [
            "linkTable" => "roles",
            "aliasTable" => "D",
            "linkField" => "id",
            "displayName" => "rel_role_id",
            "selectFields" => ["role_name"],
            "selectValue" => "id AS rel_role_id"
        ],
        "created_by" => [
            "linkTable" => "users",
            "aliasTable" => "B",
            "linkField" => "id",
            "displayName" => "rel_created_by",
            "selectFields" => ["fullname"],
            "selectValue" => "id AS rel_created_by"
        ],
        "updated_by" => [
            "linkTable" => "users",
            "aliasTable" => "C",
            "linkField" => "id",
            "displayName" => "rel_updated_by",
            "selectFields" => ["fullname"],
            "selectValue" => "id AS rel_updated_by"
        ],
    ];
    const CUSTOM_RELATION = [];
    const CUSTOM_SELECT = "";
    const FIELD_VALIDATION = [
        "fullname" => "required|string|max:255",
        // password nullable di sini agar update tanpa ganti password lolos validasi;
        // password wajib saat create dicek di beforeInsert.
        "password" => "nullable|string|min:5",
        "username" => "required|string|max:100",
        "email" => "nullable|email|max:255",
        "nis" => "nullable|string|max:20",
        "nisn" => "nullable|string|digits:10",
        "masa_studi" => "nullable|integer|min:1|max:10",
        "role_id" => "required|integer|exists:roles,id",
        "must_change_password" => "nullable|boolean",
        "status_code" => "nullable|string|in:user_active,user_nonactive",
        "created_by" => "nullable|integer",
        "updated_by" => "nullable|integer",
        "created_at" => "nullable|date",
        "updated_at" => "nullable|date",
    ];
    const PARENT_CHILD = [];
    // start custom
    const CUSTOM_LIST_FILTER = [];
    const FIELD_CASTING = [
        //"nama field" => "float",
    ];
    const FIELD_VALIDATION_DATA = [];
    const CHILD_TABLE = [
        //"child_table" => [
        // "foreignField" => "field"
        //]
    ];
    const MAPPING_MULTIPLE_ADD = [
        //"contracts" => [ -- main table (contract_id)
        //    "dataIdTable" => "m_poc", -- data (mapping id)
        //    "fieldAdd" => [],
        //    "fieldUnique" => [],
        //],
    ];

    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Users::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Users::class, 'updated_by');
    }

    /**
     * Pastikan password tidak pernah bocor lewat list/detail
     * (sebenarnya password sudah dikecualikan dari SELECT).
     */
    public static function customizeList($item)
    {
        unset($item->password);
        unset($item->reset_token);
        return $item;
    }

    public static function customizeDetail($item)
    {
        unset($item->password);
        unset($item->reset_token);
        return $item;
    }

    /**
     * Filter turunan untuk list siswa (dipakai Get + Dataset via customFilter).
     * Backend tidak menyimpan tahun_masuk/tahun_lulus (dihitung dari NIS +
     * masa_studi), jadi filter query ?tahun_masuk= / ?tahun_lulus= diterjemahkan
     * di sini menjadi kondisi SQL Postgres. Nilai di-cast ke int dulu sehingga
     * aman di-inline (tanpa binding param). Di luar rentang 2000-2100 => diabaikan.
     */
    public static function customFilter($input)
    {
        $cond = '';

        if (!empty($input['tahun_masuk'])) {
            $tm = (int) $input['tahun_masuk'];
            if ($tm >= 2000 && $tm <= 2100) {
                $cond .= ' AND users.nis ~ \'^[0-9]{2}\' AND (2000 + CAST(SUBSTRING(users.nis FROM 1 FOR 2) AS INTEGER)) = ' . $tm;
            }
        }

        if (!empty($input['tahun_lulus'])) {
            $tl = (int) $input['tahun_lulus'];
            if ($tl >= 2000 && $tl <= 2100) {
                $cond .= ' AND users.masa_studi IS NOT NULL AND users.nis ~ \'^[0-9]{2}\' AND (2000 + CAST(SUBSTRING(users.nis FROM 1 FOR 2) AS INTEGER) + users.masa_studi) = ' . $tl;
            }
        }

        return $cond;
    }

    /**
     * Tahun masuk = 2000 + 2 digit pertama NIS (kode sekolah, bukan NISN).
     * NISN nasional tidak menyimpan info angkatan (3 digit awal = tahun lahir).
     * Return null bila NIS kosong / tak valid.
     */
    public static function calcTahunMasuk($nis): ?int
    {
        $nis = (string) ($nis ?? '');
        if (strlen($nis) < 2 || !ctype_digit(substr($nis, 0, 2))) {
            return null;
        }
        return 2000 + (int) substr($nis, 0, 2);
    }

    /**
     * Tahun lulus = tahun masuk + masa_studi.
     * Cth: NIS 234273345 + masa_studi 4 => 2000 + 23 + 4 = 2027.
     * Return null bila NIS/masa_studi kosong / tak valid.
     */
    public static function calcTahunLulus($nis, $masaStudi): ?int
    {
        $tahunMasuk = self::calcTahunMasuk($nis);
        $masaStudi = (int) ($masaStudi ?? 0);
        if (is_null($tahunMasuk) || $masaStudi < 1) {
            return null;
        }
        return $tahunMasuk + $masaStudi;
    }

    protected static function assertUniqueEmailNisn($input, $exceptId = null)
    {
        if (!empty($input['email'])) {
            $query = self::where('email', $input['email']);
            if (!is_null($exceptId)) {
                $query->where('id', '!=', $exceptId);
            }
            if ($query->exists()) {
                throw new CoreException("Email {$input['email']} sudah digunakan.");
            }
        }

        // NIS: unik bila diisi (opsional, kode sekolah).
        if (!empty($input['nis'])) {
            $query = self::where('nis', $input['nis']);
            if (!is_null($exceptId)) {
                $query->where('id', '!=', $exceptId);
            }
            if ($query->exists()) {
                throw new CoreException("NIS {$input['nis']} sudah digunakan.");
            }
        }

        // NISN: unik bila diisi (identitas nasional 10 digit).
        if (!empty($input['nisn'])) {
            $query = self::where('nisn', $input['nisn']);
            if (!is_null($exceptId)) {
                $query->where('id', '!=', $exceptId);
            }
            if ($query->exists()) {
                throw new CoreException("NISN {$input['nisn']} sudah digunakan.");
            }
        }
    }

    protected static function assertDeveloperAllowed($roleId)
    {
        $currentRoleId = Auth::user()?->role_id;
        if ((int) $roleId === -1 && (int) $currentRoleId !== -1) {
            throw new CoreException("Hanya developer yang dapat menetapkan role developer.", 403);
        }
    }

    public static function beforeInsert($input)
    {
        $isSiswa = ((int) ($input['role_id'] ?? 0) === 3);

        // Siswa: NIS wajib (sumber tahun masuk), NISN wajib 10 digit
        // (identitas unik + password awal). Tanpa generate acak / input manual.
        if ($isSiswa) {
            $nis = trim((string) ($input['nis'] ?? ''));
            if ($nis === '') {
                throw new CoreException("NIS wajib diisi untuk akun siswa.");
            }
            if (!ctype_digit($nis) || strlen($nis) < 2 || strlen($nis) > 20) {
                throw new CoreException("NIS harus berupa angka (maksimal 20 digit).");
            }
            $nisn = trim((string) ($input['nisn'] ?? ''));
            if ($nisn === '') {
                throw new CoreException("NISN wajib diisi untuk akun siswa.");
            }
            if (!ctype_digit($nisn) || strlen($nisn) !== 10) {
                throw new CoreException("NISN harus 10 digit angka.");
            }
            // Username default = NISN bila dikosongkan dari dashboard.
            if (empty($input['username'])) {
                $input['username'] = $nisn;
            }
            $input['password'] = Hash::make($nisn);
        } else {
            // Guru/admin/developer: password diinput manual dari dashboard.
            if (empty($input['password'])) {
                throw new CoreException("Password wajib diisi.");
            }

            if (strlen($input['password']) < 5) {
                throw new CoreException("Password minimal 5 karakter.");
            }

            $input['password'] = Hash::isHashed($input['password'])
                ? $input['password']
                : Hash::make($input['password']);
        }

        if (empty($input['status_code'])) {
            $input['status_code'] = 'user_active';
        }

        // Akun siswa wajib ganti password saat login pertama (kecuali diset eksplisit).
        // Catatan: Add::process hanya menyimpan field yang isset(), jadi false
        // harus ditulis eksplisit agar tidak hilang menjadi NULL.
        if (!array_key_exists('must_change_password', $input)
            || is_null($input['must_change_password'])
            || $input['must_change_password'] === ''
        ) {
            $input['must_change_password'] = ((int) ($input['role_id'] ?? 0) === 3) ? true : false;
        }

        self::assertDeveloperAllowed($input['role_id'] ?? null);
        self::assertUniqueEmailNisn($input);

        return $input;
    }

    public static function afterInsert($object, $input)
    {
        return $input;
    }

    public static function beforeUpdate($input)
    {
        $existing = null;
        if (!empty($input['id'])) {
            $existing = self::find($input['id']);
        }

        // Akun role developer hanya boleh diubah oleh developer.
        if ($existing && (int) $existing->role_id === -1) {
            self::assertDeveloperAllowed(-1);
        }

        if (array_key_exists('role_id', $input) && !is_null($input['role_id']) && $input['role_id'] !== '') {
            self::assertDeveloperAllowed($input['role_id']);
        }

        // Proteksi akun sendiri: role & status tidak boleh diubah sendiri.
        if ($existing && (int) $existing->id === (int) Auth::id()) {
            if (
                array_key_exists('role_id', $input)
                && !is_null($input['role_id']) && $input['role_id'] !== ''
                && (int) $input['role_id'] !== (int) $existing->role_id
            ) {
                throw new CoreException("Tidak dapat mengubah role akun sendiri.", 422);
            }
            if (
                array_key_exists('status_code', $input)
                && !is_null($input['status_code']) && $input['status_code'] !== ''
                && $input['status_code'] !== $existing->status_code
            ) {
                throw new CoreException("Tidak dapat mengubah status akun sendiri.", 422);
            }
        }

        // Password kosong = tidak ganti password (flag wajib-ganti tetap).
        // Password diganti (sendiri) => flag wajib-ganti ikut lunas.
        // Khusus reset admin: controller set must_change_password=true SETELAH hash.
        if (array_key_exists('password', $input)) {
            if (is_null($input['password']) || $input['password'] === '') {
                unset($input['password']);
            } else {
                if (!array_key_exists('must_change_password', $input)) {
                    $input['must_change_password'] = false;
                }
                if (strlen($input['password']) < 5) {
                    throw new CoreException("Password minimal 5 karakter.");
                }
                $input['password'] = Hash::isHashed($input['password'])
                    ? $input['password']
                    : Hash::make($input['password']);
            }
        }

        self::assertUniqueEmailNisn($input, $input['id'] ?? null);

        return $input;
    }

    public static function afterUpdate($object, $input)
    {
        return $input;
    }

    public static function beforeDelete($input)
    {
        if (!empty($input['id']) && (int) $input['id'] === (int) Auth::id()) {
            throw new CoreException("Tidak dapat menghapus akun sendiri.", 422);
        }

        $target = null;
        if (!empty($input['id'])) {
            $target = self::find($input['id']);
        }

        if ($target && (int) $target->role_id === -1) {
            self::assertDeveloperAllowed(-1);
        }

        return $input;
    }

    public static function afterDelete($object, $input)
    {
        return $input;
    }// end custom
}

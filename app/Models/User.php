<?php

namespace App\Models;

use App\Services\GambarService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Model tabel `user` (entitas "pengguna" pada ERD).
 *
 * @property int $id_user
 * @property string $nama_user
 * @property string $username
 * @property string $password
 * @property string $role Admin | Operator | Manager
 * @property string|null $foto path foto profil
 * @property-read string|null $foto_url
 */
class User extends Authenticatable
{
    use HasFactory;

    public const ROLE_ADMIN = 'Admin';

    public const ROLE_OPERATOR = 'Operator';

    public const ROLE_MANAGER = 'Manager';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_OPERATOR, self::ROLE_MANAGER];

    /**
     * Informasi tampilan setiap role (array asosiatif bersarang):
     * ikon, kelas warna Tailwind, dan keterangan tugas.
     */
    public const INFO_ROLE = [
        self::ROLE_ADMIN => [
            'ikon' => 'shield',
            'badge' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
            'kotak' => 'border-brand-100 bg-brand-50/60',
            'ikon_bg' => 'bg-brand-600 text-on-brand',
            'keterangan' => 'Pengelola sistem: mengelola pengguna, kategori, data barang, dan mengoreksi barang masuk.',
        ],
        self::ROLE_OPERATOR => [
            'ikon' => 'clipboard',
            'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'kotak' => 'border-emerald-100 bg-emerald-50/60',
            'ikon_bg' => 'bg-emerald-600 text-white',
            'keterangan' => 'Petugas gudang: mendaftarkan barang, mencatat barang masuk, mengajukan barang keluar.',
        ],
        self::ROLE_MANAGER => [
            'ikon' => 'check-circle',
            'badge' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
            'kotak' => 'border-violet-100 bg-violet-50/60',
            'ikon_bg' => 'bg-violet-600 text-white',
            'keterangan' => 'Pengawas: menyetujui / menolak barang keluar dan melihat seluruh data, tanpa menambah atau mengeluarkan barang.',
        ],
    ];

    protected $table = 'user';

    protected $primaryKey = 'id_user';

    /** Tabel tidak memiliki kolom created_at / updated_at (sesuai desain database). */
    public $timestamps = false;

    /** Kolom remember_token tidak ada di desain database, fitur "remember me" dimatikan. */
    protected $rememberTokenName = '';

    protected $fillable = [
        'nama_user',
        'username',
        'password',
        'role',
        'foto',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            // Password otomatis di-hash (bcrypt) setiap kali diisi.
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Inisial nama untuk avatar, contoh "Marco Ivanos" menjadi "MI". */
    public function inisial(): string
    {
        $kata = preg_split('/\s+/', trim($this->nama_user));

        return strtoupper(collect($kata)->take(2)->map(fn (string $k) => mb_substr($k, 0, 1))->implode(''));
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    /** Alamat foto profil, dipakai sebagai $user->foto_url. */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(fn () => app(GambarService::class)->url($this->foto));
    }

    /** Relasi 1:N "mengelola" barang masuk. */
    public function barangMasuk(): HasMany
    {
        return $this->hasMany(BarangMasuk::class, 'id_user', 'id_user');
    }

    /** Relasi 1:N "mengelola" barang keluar (sebagai pengaju). */
    public function barangKeluar(): HasMany
    {
        return $this->hasMany(BarangKeluar::class, 'id_user', 'id_user');
    }

    /** Relasi 1:N "memverifikasi" barang keluar (sebagai Manager). */
    public function verifikasiBarangKeluar(): HasMany
    {
        return $this->hasMany(BarangKeluar::class, 'id_verifikator', 'id_user');
    }

    /** Pengguna yang sudah memiliki riwayat transaksi tidak boleh dihapus. */
    public function punyaTransaksi(): bool
    {
        return $this->barangMasuk()->exists()
            || $this->barangKeluar()->exists()
            || $this->verifikasiBarangKeluar()->exists();
    }
}

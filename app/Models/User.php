<?php

namespace App\Models;

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
 * @property string $role Admin | Operator
 */
class User extends Authenticatable
{
    use HasFactory;

    public const ROLE_ADMIN = 'Admin';

    public const ROLE_OPERATOR = 'Operator';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_OPERATOR];

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

    /** Relasi 1:N "memverifikasi" barang keluar (sebagai Admin). */
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

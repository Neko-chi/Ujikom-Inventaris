<?php

namespace Tests\Unit;

use App\Support\Suasana;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian unit penentuan suasana waktu (App\Support\Suasana).
 */
class SuasanaTest extends TestCase
{
    public function test_suasana_sesuai_jam(): void
    {
        $this->assertSame('malam', Suasana::dariJam(0));
        $this->assertSame('malam', Suasana::dariJam(3));
        $this->assertSame('pagi', Suasana::dariJam(4));
        $this->assertSame('pagi', Suasana::dariJam(10));
        $this->assertSame('siang', Suasana::dariJam(11));
        $this->assertSame('siang', Suasana::dariJam(14));
        $this->assertSame('sore', Suasana::dariJam(15));
        $this->assertSame('sore', Suasana::dariJam(17));
        $this->assertSame('malam', Suasana::dariJam(18));
        $this->assertSame('malam', Suasana::dariJam(23));
    }

    public function test_salam_sesuai_jam(): void
    {
        $this->assertSame('Selamat pagi', Suasana::salam(7));
        $this->assertSame('Selamat malam', Suasana::salam(21));
    }

    public function test_setiap_suasana_punya_file_audio(): void
    {
        foreach (Suasana::DAFTAR as $kunci => $data) {
            $this->assertFileExists(__DIR__.'/../../public/'.$data['audio'], "Audio suasana $kunci tidak ditemukan");
        }
    }
}

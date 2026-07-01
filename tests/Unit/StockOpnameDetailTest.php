<?php

namespace Tests\Unit;

use App\Models\StockOpnameDetail;
use PHPUnit\Framework\TestCase;

class StockOpnameDetailTest extends TestCase
{
    public function test_selisih_dihitung_dari_stok_fisik_dan_sistem(): void
    {
        $detail = new StockOpnameDetail([
            'stok_sistem' => 10,
            'stok_fisik' => 7,
        ]);

        $detail->syncSelisih();

        $this->assertSame(-3, $detail->selisih);
    }
}

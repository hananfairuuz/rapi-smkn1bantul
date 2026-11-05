<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_monthly_schedule_report_without_damaged_goods_data(): void
    {
        $admin = $this->userWithRole('admin');
        $borrower = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);
        $event = Event::create([
            'user_id' => $borrower->id,
            'room_id' => $room->id,
            'title' => 'Praktik Bulanan',
            'date' => '2026-10-12',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'approved',
        ]);
        $complaint = Complaint::create([
            'room_id' => $room->id,
            'event_id' => $event->id,
            'reporter_id' => $borrower->id,
            'facility_name' => 'Proyektor',
            'description' => 'Tidak menyala.',
        ]);
        $complaint->created_at = '2026-10-15 09:00:00';
        $complaint->save();

        $this->actingAs($admin)->get(route('reports.index', ['month' => 10, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Laporan Bulanan RAPI-Sekolah')
            ->assertSee('Praktik Bulanan')
            ->assertDontSee('Proyektor')
            ->assertSee('Lab Komputer')
            ->assertSee('Unduh DOCX')
            ->assertSee('Simpan PDF / Cetak');

        $response = $this->actingAs($admin)->get(route('reports.docx', ['month' => 10, 'year' => 2026]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertHeader('Content-Disposition', 'attachment; filename=laporan-rapi-2026-10.docx');

        $docxPath = tempnam(sys_get_temp_dir(), 'rapi-test-');
        file_put_contents($docxPath, $response->streamedContent());
        $archive = new \ZipArchive;
        $this->assertSame(true, $archive->open($docxPath));
        $document = $archive->getFromName('word/document.xml');
        $this->assertNotFalse($document);
        $this->assertStringContainsString('Praktik Bulanan', $document);
        $this->assertStringNotContainsString('Proyektor', $document);
        $xml = new \DOMDocument;
        $this->assertTrue($xml->loadXML($document));
        $archive->close();
        unlink($docxPath);
    }

    public function test_monthly_report_excludes_records_outside_selected_month(): void
    {
        $admin = $this->userWithRole('admin');
        $borrower = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Aula']);
        Event::create([
            'user_id' => $borrower->id,
            'room_id' => $room->id,
            'title' => 'Jadwal November',
            'date' => '2026-11-01',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
        $complaint = Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $borrower->id,
            'facility_name' => 'Kerusakan November',
            'description' => 'Dilaporkan pada bulan November.',
        ]);
        $complaint->created_at = '2026-11-01 17:00:00';
        $complaint->save();

        $this->actingAs($admin)->get(route('reports.index', ['month' => 10, 'year' => 2026]))
            ->assertOk()
            ->assertDontSee('Jadwal November')
            ->assertDontSee('Kerusakan November');
    }

    public function test_admin_and_pic_can_view_daily_and_weekly_reports(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00', 'Asia/Jakarta'));
        $admin = $this->userWithRole('admin');
        $pic = $this->userWithRole('pic');
        $reporter = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Periode', 'pic_id' => $pic->id]);
        foreach ([
            ['Agenda Senin', '2026-11-02'],
            ['Agenda Rabu', '2026-11-04'],
            ['Agenda Minggu', '2026-11-08'],
            ['Agenda Senin Depan', '2026-11-09'],
        ] as [$title, $date]) {
            Event::create([
                'user_id' => $reporter->id,
                'room_id' => $room->id,
                'title' => $title,
                'date' => $date,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ]);
        }
        foreach ([
            ['Keluhan Senin', '2026-11-02 09:00:00'],
            ['Keluhan Rabu', '2026-11-04 09:00:00'],
            ['Keluhan Minggu', '2026-11-08 09:00:00'],
            ['Keluhan Senin Depan', '2026-11-09 09:00:00'],
        ] as [$facility, $createdAt]) {
            $complaint = Complaint::create([
                'room_id' => $room->id,
                'reporter_id' => $reporter->id,
                'facility_name' => $facility,
                'description' => 'Rusak.',
            ]);
            $complaint->created_at = $createdAt;
            $complaint->save();
        }

        $this->actingAs($admin)->get(route('reports.index', [
            'period_type' => 'week',
            'date' => '2026-11-04',
        ]))->assertOk()
            ->assertSee('02/11/2026 – 08/11/2026')
            ->assertSee('Agenda Senin')
            ->assertSee('Agenda Minggu')
            ->assertDontSee('Agenda Senin Depan');

        $this->actingAs($admin)->get(route('reports.index', [
            'period_type' => 'day',
            'date' => '2026-11-04',
        ]))->assertOk()
            ->assertSee('Agenda Rabu')
            ->assertDontSee('Agenda Senin');

        $this->actingAs($pic)->get(route('reports.damaged-goods', [
            'period_type' => 'week',
            'date' => '2026-11-04',
        ]))->assertOk()
            ->assertSee('02/11/2026 – 08/11/2026')
            ->assertSee('Keluhan Senin')
            ->assertSee('Keluhan Minggu')
            ->assertDontSee('Keluhan Senin Depan');

        $this->actingAs($pic)->get(route('reports.damaged-goods', [
            'period_type' => 'day',
            'date' => '2026-11-04',
        ]))->assertOk()
            ->assertSee('Keluhan Rabu')
            ->assertDontSee('Keluhan Senin');
    }

    public function test_monthly_report_is_admin_only_and_validates_period(): void
    {
        $guru = $this->userWithRole('guru');
        $admin = $this->userWithRole('admin');
        $pic = $this->userWithRole('pic');

        $this->actingAs($guru)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($guru)->get(route('reports.docx'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.damaged-goods'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.damaged-goods.docx'))->assertForbidden();
        $this->actingAs($pic)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($pic)->get(route('reports.docx'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.index', ['month' => 13, 'year' => 2026]))
            ->assertSessionHasErrors('month');
    }

    public function test_pic_can_view_only_damaged_goods_for_assigned_rooms(): void
    {
        $pic = $this->userWithRole('pic');
        $otherPic = $this->userWithRole('pic');
        $reporter = $this->userWithRole('guru');
        $assignedRoom = Room::create(['name' => 'Lab PIC', 'pic_id' => $pic->id]);
        $otherRoom = Room::create(['name' => 'Aula Lain', 'pic_id' => $otherPic->id]);
        $assignedComplaint = Complaint::create([
            'room_id' => $assignedRoom->id,
            'reporter_id' => $reporter->id,
            'facility_name' => 'Proyektor PIC',
            'description' => 'Tidak menyala.',
        ]);
        $otherComplaint = Complaint::create([
            'room_id' => $otherRoom->id,
            'reporter_id' => $reporter->id,
            'facility_name' => 'Speaker PIC Lain',
            'description' => 'Tidak berbunyi.',
        ]);
        $assignedComplaint->created_at = '2026-10-15 09:00:00';
        $assignedComplaint->save();
        $otherComplaint->created_at = '2026-10-15 10:00:00';
        $otherComplaint->save();

        $this->actingAs($pic)->get(route('reports.damaged-goods', ['month' => 10, 'year' => 2026]))
            ->assertOk()
            ->assertSee('Proyektor PIC')
            ->assertDontSee('Speaker PIC Lain')
            ->assertSee('Lab PIC')
            ->assertDontSee('Aula Lain');

        $response = $this->actingAs($pic)->get(route('reports.damaged-goods.docx', ['month' => 10, 'year' => 2026]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=laporan-barang-rusak-2026-10.docx');
        $docxPath = tempnam(sys_get_temp_dir(), 'rapi-test-');
        file_put_contents($docxPath, $response->streamedContent());
        $archive = new \ZipArchive;
        $this->assertSame(true, $archive->open($docxPath));
        $document = $archive->getFromName('word/document.xml');
        $this->assertNotFalse($document);
        $this->assertStringContainsString('Proyektor PIC', $document);
        $this->assertStringNotContainsString('Speaker PIC Lain', $document);
        $archive->close();
        unlink($docxPath);
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
        ]);
    }
}

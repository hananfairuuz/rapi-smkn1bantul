<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\UserDocumentParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Controller Manajemen Akun Pengguna (User Management)
 *
 * Mengelola hak akses dan akun pengguna sistem RAPI:
 * - Mengelompokkan pengguna berdasarkan peran: Admin, PIC Ruang, Guru, Organisasi.
 * - CRUD akun pengguna lengkap dengan nomor WhatsApp untuk integrasi notifikasi.
 * - Fitur Impor Data Massal dari file Excel (.xlsx), Word (.docx), atau CSV (.csv).
 */
class UserController extends Controller
{
    private const MANAGED_ROLES = ['admin', 'pic', 'guru', 'organisasi'];

    /**
     * Helper Keamanan: Memastikan hanya user ber-role 'admin' yang bisa mengakses manajemen pengguna.
     */
    private function admin(): void
    {
        abort_unless(auth()->user()->role?->name === 'admin', 403);
    }

    /**
     * Menampilkan daftar pengguna dikelompokkan berdasarkan peran (Role Groups).
     */
    public function index()
    {
        $this->admin();
        $search = trim((string) request('search', ''));
        $users = User::with('role')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%")
                    ->orWhereHas('role', fn ($roles) => $roles->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('name')->get();

        $roleGroups = collect([
            'admin' => 'Admin',
            'pic' => 'PIC Ruang',
            'guru' => 'Guru',
            'organisasi' => 'Organisasi',
        ])->map(fn ($label, $role) => [
            'label' => $label,
            'users' => $users->filter(fn ($user) => $user->role?->name === $role),
        ])->filter(fn ($group) => $group['users']->isNotEmpty());

        return view('users.index', compact('roleGroups', 'search'));
    }

    /**
     * Form Tambah Akun Pengguna Baru.
     */
    public function create()
    {
        $this->admin();

        return view('users.form', [
            'user' => new User,
            'roles' => $this->managedRoles(),
            'creating' => true,
        ]);
    }

    public function store(Request $request)
    {
        $this->admin();
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'role_id' => ['required', Rule::exists('roles', 'id')->whereIn('name', self::MANAGED_ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $data['email'] = strtolower($data['email']);
        $data['whatsapp'] = $this->validatedWhatsApp($request);
        $user = User::create($data);
        ActivityLog::record('Tambah Pengguna', auth()->user()->name . " menambahkan akun baru '{$user->name}' ({$user->email}).");

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function importForm()
    {
        $this->admin();

        return view('users.import', [
            'roles' => $this->managedRoles(),
        ]);
    }

    public function parseFile(Request $request, UserDocumentParserService $parser)
    {
        $this->admin();

        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xlsm', 'docx', 'csv', 'txt'])) {
            $msg = 'Format file tidak didukung. Harap gunakan file Excel (.xlsx), Word (.docx), atau CSV (.csv).';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['file' => $msg]);
        }

        try {
            $parsedRows = $parser->parse($file);
        } catch (\Throwable $e) {
            $msg = 'Gagal membaca isi dokumen: ' . $e->getMessage();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['file' => $msg]);
        }

        if (empty($parsedRows)) {
            $msg = 'Dokumen berhasil dibaca, namun tidak ditemukan baris data pengguna di dalamnya.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['file' => $msg]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'count' => count($parsedRows),
                'rows' => $parsedRows,
            ]);
        }

        return redirect()->route('users.import')->with('parsed_rows', $parsedRows);
    }

    public function importStore(Request $request)
    {
        $this->admin();

        $usersData = $request->input('users', []);
        if (empty($usersData) || !is_array($usersData)) {
            return back()->withErrors(['import' => 'Tidak ada data pengguna yang dikirim untuk disimpan.']);
        }

        $roles = $this->managedRoles()->keyBy('id');
        $createdCount = 0;
        $skippedEmails = [];
        $seenEmails = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($usersData as $idx => $row) {
                $rowNum = $idx + 1;
                $name = trim((string) ($row['name'] ?? ''));
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                $password = (string) ($row['password'] ?? '');
                $whatsapp = trim((string) ($row['whatsapp'] ?? ''));
                $roleId = $row['role_id'] ?? null;

                if ($name === '') {
                    $errors[] = "Baris #{$rowNum}: Nama lengkap tidak boleh kosong.";
                    continue;
                }

                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Baris #{$rowNum} ({$name}): Format email '{$email}' tidak valid.";
                    continue;
                }

                if (isset($seenEmails[$email])) {
                    $skippedEmails[] = $email . ' (duplikat di file)';
                    continue;
                }
                $seenEmails[$email] = true;

                if (strlen($password) < 8) {
                    $errors[] = "Baris #{$rowNum} ({$name}): Kata sandi minimal 8 karakter.";
                    continue;
                }

                $cleanWa = preg_replace('/[^0-9]/', '', $whatsapp);
                if (strlen($cleanWa) < 8 || strlen($cleanWa) > 20) {
                    $errors[] = "Baris #{$rowNum} ({$name}): Nomor WhatsApp harus berisi 8-20 digit angka.";
                    continue;
                }

                if (empty($roleId) || !$roles->has($roleId)) {
                    $errors[] = "Baris #{$rowNum} ({$name}): Peran belum dipilih atau tidak valid.";
                    continue;
                }

                if (User::where('email', $email)->exists()) {
                    $skippedEmails[] = $email;
                    continue;
                }

                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'whatsapp' => $cleanWa,
                    'role_id' => $roleId,
                ]);

                $createdCount++;
            }

            if (!empty($errors)) {
                DB::rollBack();
                $errorPreview = implode('<br>', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $errorPreview .= '<br>...dan ' . (count($errors) - 5) . ' kesalahan lainnya.';
                }
                return back()->withInput()->withErrors(['import' => $errorPreview]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['import' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()]);
        }

        $message = "Berhasil menambahkan {$createdCount} pengguna baru.";
        if (!empty($skippedEmails)) {
            $message .= " (" . count($skippedEmails) . " email dilewati karena sudah terdaftar: " . implode(', ', array_slice($skippedEmails, 0, 3)) . (count($skippedEmails) > 3 ? '...' : '') . ")";
        }

        ActivityLog::record('Import Pengguna', auth()->user()->name . " mengimpor {$createdCount} akun pengguna dari dokumen.");

        return redirect()->route('users.index')->with('success', $message);
    }

    public function downloadTemplate(string $format, UserDocumentParserService $parser)
    {
        $this->admin();

        $tpl = $parser->generateTemplate($format);

        return response($tpl['content'])
            ->header('Content-Type', $tpl['mime'])
            ->header('Content-Disposition', "attachment; filename=\"{$tpl['filename']}\"");
    }

    public function edit(User $user)
    {
        $this->admin();

        return view('users.form', ['user' => $user, 'roles' => $this->managedRoles(), 'creating' => false]);
    }

    public function update(Request $request, User $user)
    {
        $this->admin();
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)], 'role_id' => ['required', Rule::exists('roles', 'id')->whereIn('name', self::MANAGED_ROLES)]]);
        $data['email'] = strtolower($data['email']);
        $data['whatsapp'] = $this->validatedWhatsApp($request);
        if (strtolower($user->email) !== $data['email']) {
            $data['google_id'] = null;
        }
        $user->update($data);
        ActivityLog::record('Edit Pengguna', auth()->user()->name . " memperbarui akun pengguna '{$user->name}' ({$user->email}).");

        return redirect()->route('users.index')->with('success', 'Data pengguna dan peran berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->admin();

        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'Akun yang sedang digunakan tidak dapat dihapus.']);
        }

        if ($user->role?->name === 'admin' && User::whereHas('role', fn ($query) => $query->where('name', 'admin'))->count() <= 1) {
            return back()->withErrors(['user' => 'Admin terakhir tidak dapat dihapus.']);
        }

        if ($user->events()->exists()
            || $user->complaints()->exists()
            || $user->roomsAsPic()->exists()
            || $user->systemNotifications()->exists()) {
            return back()->withErrors(['user' => 'Pengguna masih memiliki agenda, pengaduan, tugas PIC, atau notifikasi. Pindahkan atau selesaikan data tersebut sebelum menghapus akun.']);
        }

        $name = $user->name;
        $user->delete();
        ActivityLog::record('Hapus Pengguna', auth()->user()->name . " menghapus akun pengguna '{$name}'.");

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function profile()
    {
        return view('profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)]]);
        $data['whatsapp'] = $this->validatedWhatsApp($request);
        $data['email'] = strtolower($data['email']);
        if (strtolower($user->email) !== $data['email']) {
            $data['google_id'] = null;
        }
        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    private function validatedWhatsApp(Request $request): string
    {
        return $request->validate([
            'whatsapp' => ['required', 'string', 'regex:/^\\+?[0-9]{8,20}$/'],
        ])['whatsapp'];
    }

    private function managedRoles()
    {
        return Role::whereIn('name', self::MANAGED_ROLES)->orderBy('name')->get();
    }
}

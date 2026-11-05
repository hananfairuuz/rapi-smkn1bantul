<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const MANAGED_ROLES = ['admin', 'pic', 'guru', 'organisasi'];

    private function admin(): void
    {
        abort_unless(auth()->user()->role?->name === 'admin', 403);
    }

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
        User::create($data);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
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

        $user->delete();

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

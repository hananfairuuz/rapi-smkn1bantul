import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

/// Halaman Masuk (Login) Aplikasi Mobile RAPI-Sekolah
/// Fitur utama:
/// 1. Tampilan profesional berlatar belakang foto gedung sekolah SMK N 1 Bantul (HD).
/// 2. Autentikasi akun Guru, PIC Ruang, dan Organisasi Siswa melalui API RESTful.
/// 3. Navigasi menuju fitur 'Lupa Kata Sandi' (reset password via OTP email).
/// 4. Validasi form realtime (email harus format valid, password tidak boleh kosong).
class LoginScreen extends StatefulWidget {
  const LoginScreen({
    required this.api,
    required this.onLogin,
    required this.onForgotPassword,
    required this.onBackToCalendar,
    this.initialError,
    this.initialNotice,
    this.initialEmail,
    super.key,
  });

  final ApiService api;
  final ValueChanged<Map<String, dynamic>> onLogin;
  final VoidCallback onForgotPassword;
  final VoidCallback onBackToCalendar;
  final String? initialError;
  final String? initialNotice;
  final String? initialEmail;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  bool _hidePassword = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _error = widget.initialError;
    _email.text = widget.initialEmail ?? '';
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final user = await widget.api.login(_email.text, _password.text);
      if (mounted) widget.onLogin(user);
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Tidak dapat terhubung ke server API.');
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) widget.onBackToCalendar();
      },
      child: Scaffold(
        backgroundColor: sidebar,
        body: Stack(
          fit: StackFit.expand,
          children: [
            // Foto gedung SMK Negeri 1 Bantul (seperti di versi desktop)
            Image.asset(
              'assets/images/school-login-background.png',
              fit: BoxFit.cover,
              alignment: Alignment.center,
              errorBuilder: (_, __, ___) => const SizedBox.shrink(),
            ),

            // Lapisan overlay gelap semi-transparan seperti di desktop
            Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    const Color(0xFF071224).withOpacity(0.25),
                    const Color(0xFF071224).withOpacity(0.38),
                  ],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                ),
              ),
            ),

            // Kartu login
            SafeArea(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 420),
                    child: Card(
                      color: sidebar,
                      elevation: 16,
                      shadowColor: Colors.black.withOpacity(0.6),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                        side: const BorderSide(
                          color: Color(0xFF26384D),
                          width: 1.5,
                        ),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            const BrandLogo(size: 72),
                            const SizedBox(height: 12),
                            const Text(
                              'RAPI-Sekolah',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 26,
                                fontWeight: FontWeight.w800,
                                letterSpacing: -0.5,
                              ),
                            ),
                            const SizedBox(height: 6),
                            const Text(
                              'Masuk dengan akun yang diberikan Admin.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: sidebarMuted,
                                fontSize: 13,
                              ),
                            ),
                            const SizedBox(height: 20),
                            if (_error != null) ...[
                              ErrorBanner(message: _error!),
                              const SizedBox(height: 16),
                            ],
                            if (widget.initialNotice != null) ...[
                              SuccessBanner(message: widget.initialNotice!),
                              const SizedBox(height: 16),
                            ],
                            const Text(
                              'Email',
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 13,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 6),
                            TextFormField(
                              controller: _email,
                              keyboardType: TextInputType.emailAddress,
                              autofillHints: const [AutofillHints.username],
                              decoration: const InputDecoration(
                                hintText: 'nama@email.com',
                                fillColor: Color(0xFFE7EFFF),
                                prefixIcon: Icon(
                                  Icons.email_outlined,
                                  size: 20,
                                ),
                              ),
                              validator: (value) =>
                                  value == null || !value.contains('@')
                                  ? 'Masukkan alamat email yang valid.'
                                  : null,
                            ),
                            const SizedBox(height: 16),
                            const Text(
                              'Kata sandi',
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 13,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 6),
                            TextFormField(
                              controller: _password,
                              obscureText: _hidePassword,
                              autofillHints: const [AutofillHints.password],
                              decoration: InputDecoration(
                                hintText: 'Kata sandi',
                                fillColor: Color(0xFFE7EFFF),
                                prefixIcon: const Icon(
                                  Icons.lock_outline,
                                  size: 20,
                                ),
                                suffixIcon: IconButton(
                                  onPressed: () => setState(
                                    () => _hidePassword = !_hidePassword,
                                  ),
                                  icon: Icon(
                                    _hidePassword
                                        ? Icons.visibility_outlined
                                        : Icons.visibility_off_outlined,
                                    size: 20,
                                  ),
                                ),
                              ),
                              validator: (value) =>
                                  value == null || value.isEmpty
                                  ? 'Kata sandi wajib diisi.'
                                  : null,
                              onFieldSubmitted: (_) => _login(),
                            ),
                            const SizedBox(height: 22),
                            FilledButton(
                              style: FilledButton.styleFrom(
                                backgroundColor: accent,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(
                                  vertical: 14,
                                ),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(9),
                                ),
                              ),
                              onPressed: _busy ? null : _login,
                              child: _busy
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Text(
                                      'Masuk',
                                      style: TextStyle(
                                        fontWeight: FontWeight.w700,
                                        fontSize: 15,
                                      ),
                                    ),
                            ),
                            const SizedBox(height: 12),
                            TextButton(
                              onPressed: _busy ? null : widget.onForgotPassword,
                              style: TextButton.styleFrom(
                                foregroundColor: accent,
                              ),
                              child: const Text('Lupa kata sandi?'),
                            ),
                            const SizedBox(height: 4),
                            OutlinedButton.icon(
                              onPressed: _busy ? null : widget.onBackToCalendar,
                              icon: const Icon(Icons.arrow_back, size: 18),
                              label: const Text('Kembali ke kalender'),
                              style: OutlinedButton.styleFrom(
                                foregroundColor: sidebarMuted,
                                side: const BorderSide(color: sidebarMuted),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    ),
  );
  }
}

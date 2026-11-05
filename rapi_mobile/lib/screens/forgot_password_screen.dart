import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({
    required this.api,
    required this.onBack,
    required this.onComplete,
    super.key,
  });

  final ApiService api;
  final VoidCallback onBack;
  final ValueChanged<String> onComplete;

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _code = TextEditingController();
  final _password = TextEditingController();
  final _confirmation = TextEditingController();
  bool _codeSent = false;
  bool _verified = false;
  bool _busy = false;
  bool _hidePassword = true;
  bool _hideConfirmation = true;
  String? _error;
  String? _notice;

  @override
  void dispose() {
    _email.dispose();
    _code.dispose();
    _password.dispose();
    _confirmation.dispose();
    super.dispose();
  }

  Future<void> _sendCode() async {
    if (!_formKey.currentState!.validate()) return;
    await _run(() async {
      await widget.api.requestPasswordReset(_email.text);
      setState(() {
        _codeSent = true;
        _verified = false;
        _notice =
            'Kode verifikasi telah dikirim ke email Anda (berlaku 10 menit).';
      });
    });
  }

  Future<void> _verifyCode() async {
    if (_code.text.trim().length != 6) {
      setState(() => _error = 'Masukkan 6 digit kode verifikasi.');
      return;
    }
    await _run(() async {
      await widget.api.verifyPasswordResetCode(_email.text, _code.text);
      setState(() {
        _verified = true;
        _notice = 'Email berhasil diverifikasi. Silakan buat kata sandi baru.';
      });
    });
  }

  Future<void> _resetPassword() async {
    if (!_formKey.currentState!.validate()) return;
    await _run(() async {
      await widget.api.resetPassword(
        _email.text,
        _password.text,
        _confirmation.text,
      );
      widget.onComplete(_email.text.trim());
    });
  }

  Future<void> _run(Future<void> Function() action) async {
    setState(() {
      _busy = true;
      _error = null;
      _notice = null;
    });
    try {
      await action();
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) widget.onBack();
      },
      child: Scaffold(
        backgroundColor: canvas,
        appBar: AppBar(
          leading: IconButton(
            onPressed: widget.onBack,
            icon: const Icon(Icons.arrow_back),
            tooltip: 'Kembali ke login',
          ),
          title: const Row(
            children: [
              BrandLogo(size: 32),
              SizedBox(width: 8),
              Text(
                'Lupa Kata Sandi',
                style: TextStyle(fontWeight: FontWeight.w700, fontSize: 18),
              ),
            ],
          ),
        ),
        body: SafeArea(
          child: BrandWatermark(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 420),
                  child: Card(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            PageHeading(
                              title: _verified
                                  ? 'Kata Sandi Baru'
                                  : (_codeSent
                                        ? 'Verifikasi Kode'
                                        : 'Atur Ulang Sandi'),
                              subtitle: _verified
                                  ? 'Buat kata sandi baru yang aman untuk akun Anda.'
                                  : (_codeSent
                                        ? 'Masukkan kode 6 digit yang dikirim ke email Anda.'
                                        : 'Masukkan alamat email akun Anda untuk menerima kode.'),
                            ),
                            if (_error != null) ...[
                              ErrorBanner(message: _error!),
                              const SizedBox(height: 14),
                            ],
                            if (_notice != null) ...[
                              SuccessBanner(message: _notice!),
                              const SizedBox(height: 14),
                            ],
                            // Step 1: Input Email
                            TextFormField(
                              controller: _email,
                              enabled: !_codeSent,
                              keyboardType: TextInputType.emailAddress,
                              decoration: InputDecoration(
                                labelText: 'Email Akun',
                                prefixIcon: const Icon(
                                  Icons.email_outlined,
                                  size: 20,
                                ),
                                suffixIcon: _codeSent
                                    ? IconButton(
                                        tooltip: 'Ubah email',
                                        icon: const Icon(Icons.edit, size: 18),
                                        onPressed: () {
                                          setState(() {
                                            _codeSent = false;
                                            _verified = false;
                                            _code.clear();
                                          });
                                        },
                                      )
                                    : null,
                              ),
                              validator: (value) =>
                                  value == null ||
                                      !RegExp(
                                        r'^[^@]+@[^@]+\.[^@]+$',
                                      ).hasMatch(value.trim())
                                  ? 'Masukkan alamat email yang valid.'
                                  : null,
                            ),
                            if (!_codeSent) ...[
                              const SizedBox(height: 18),
                              FilledButton(
                                style: FilledButton.styleFrom(
                                  backgroundColor: accent,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 14,
                                  ),
                                ),
                                onPressed: _busy ? null : _sendCode,
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
                                        'Kirim Kode Verifikasi',
                                        style: TextStyle(
                                          fontWeight: FontWeight.w700,
                                        ),
                                      ),
                              ),
                            ] else if (!_verified) ...[
                              // Step 2: Verification Code
                              const SizedBox(height: 16),
                              TextFormField(
                                controller: _code,
                                keyboardType: TextInputType.number,
                                maxLength: 6,
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  fontSize: 22,
                                  letterSpacing: 8,
                                  fontWeight: FontWeight.w800,
                                ),
                                decoration: const InputDecoration(
                                  labelText: 'Kode Verifikasi (6 Digit)',
                                  counterText: '',
                                  prefixIcon: Icon(
                                    Icons.pin_outlined,
                                    size: 20,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 16),
                              FilledButton(
                                style: FilledButton.styleFrom(
                                  backgroundColor: accent,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 14,
                                  ),
                                ),
                                onPressed: _busy ? null : _verifyCode,
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
                                        'Verifikasi Kode',
                                        style: TextStyle(
                                          fontWeight: FontWeight.w700,
                                        ),
                                      ),
                              ),
                              const SizedBox(height: 8),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  TextButton(
                                    onPressed: _busy ? null : _sendCode,
                                    child: const Text('Kirim ulang kode'),
                                  ),
                                  const Text(
                                    '·',
                                    style: TextStyle(color: muted),
                                  ),
                                  TextButton(
                                    onPressed: () {
                                      setState(() {
                                        _codeSent = false;
                                        _code.clear();
                                      });
                                    },
                                    child: const Text('Ganti email'),
                                  ),
                                ],
                              ),
                            ] else ...[
                              // Step 3: New Password
                              const SizedBox(height: 16),
                              TextFormField(
                                controller: _password,
                                obscureText: _hidePassword,
                                decoration: InputDecoration(
                                  labelText: 'Kata Sandi Baru',
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
                                    value == null || value.length < 8
                                    ? 'Kata sandi minimal 8 karakter.'
                                    : null,
                              ),
                              const SizedBox(height: 14),
                              TextFormField(
                                controller: _confirmation,
                                obscureText: _hideConfirmation,
                                decoration: InputDecoration(
                                  labelText: 'Ulangi Kata Sandi Baru',
                                  prefixIcon: const Icon(
                                    Icons.lock_reset,
                                    size: 20,
                                  ),
                                  suffixIcon: IconButton(
                                    onPressed: () => setState(
                                      () => _hideConfirmation =
                                          !_hideConfirmation,
                                    ),
                                    icon: Icon(
                                      _hideConfirmation
                                          ? Icons.visibility_outlined
                                          : Icons.visibility_off_outlined,
                                      size: 20,
                                    ),
                                  ),
                                ),
                                validator: (value) => value != _password.text
                                    ? 'Konfirmasi kata sandi tidak cocok.'
                                    : null,
                              ),
                              const SizedBox(height: 18),
                              FilledButton(
                                style: FilledButton.styleFrom(
                                  backgroundColor: accent,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 14,
                                  ),
                                ),
                                onPressed: _busy ? null : _resetPassword,
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
                                        'Simpan Kata Sandi Baru',
                                        style: TextStyle(
                                          fontWeight: FontWeight.w700,
                                        ),
                                      ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

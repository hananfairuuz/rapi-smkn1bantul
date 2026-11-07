import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import 'forgot_password_screen.dart';
import 'login_screen.dart';
import 'mobile_shell.dart';
import 'public_shell.dart';

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  final _api = ApiService();
  bool _checking = true;
  bool _showLogin = false;
  bool _showForgotPassword = false;
  Map<String, dynamic>? _user;
  String? _restoreError;
  String? _loginNotice;
  String? _loginEmail;

  @override
  void initState() {
    super.initState();
    _restoreSession();
  }

  Future<void> _restoreSession() async {
    try {
      final token = await _api.restoreToken();
      if (token != null) {
        final user = await _api.getUser();
        _user = user;
      }
    } on ApiException catch (error) {
      _restoreError = error.message;
    } catch (_) {
      _restoreError =
          'Tidak dapat terhubung ke server. Periksa alamat API dan koneksi jaringan.';
    } finally {
      if (mounted) setState(() => _checking = false);
    }
  }

  void _onLogin(Map<String, dynamic> user) {
    setState(() {
      _user = user;
      _restoreError = null;
      _loginNotice = null;
      _showLogin = false;
      _showForgotPassword = false;
    });
  }

  void _onLogout() {
    setState(() {
      _user = null;
      _showLogin = false;
      _showForgotPassword = false;
      _restoreError = null;
      _loginNotice = null;
    });
  }

  void _onProfileUpdated(Map<String, dynamic> user) {
    setState(() => _user = user);
  }

  @override
  Widget build(BuildContext context) {
    if (_checking) {
      return Scaffold(
        backgroundColor: sidebar,
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Image.asset(
                'assets/images/school-dashboard-watermark.png',
                width: 260,
                height: 260,
                fit: BoxFit.contain,
              ),
              const SizedBox(height: 24),
              const CircularProgressIndicator(color: accent),
            ],
          ),
        ),
      );
    }

    if (_user == null) {
      if (_showForgotPassword) {
        return ForgotPasswordScreen(
          api: _api,
          onBack: () => setState(() => _showForgotPassword = false),
          onComplete: (email) => setState(() {
            _showForgotPassword = false;
            _showLogin = true;
            _loginEmail = email;
            _loginNotice = 'Kata sandi berhasil diubah. Silakan masuk kembali.';
          }),
        );
      }

      // Default state: show public navigation shell (Kalender, Tentang, Layanan, Bantuan)
      if (!_showLogin) {
        return PublicShell(
          api: _api,
          onLogin: () => setState(() => _showLogin = true),
        );
      }

      // Login screen
      return LoginScreen(
        api: _api,
        initialError: _restoreError,
        initialNotice: _loginNotice,
        initialEmail: _loginEmail,
        onForgotPassword: () => setState(() => _showForgotPassword = true),
        onBackToCalendar: () => setState(() => _showLogin = false),
        onLogin: _onLogin,
      );
    }

    return MobileShell(
      api: _api,
      user: _user!,
      onLogout: _onLogout,
      onUserUpdated: _onProfileUpdated,
    );
  }
}

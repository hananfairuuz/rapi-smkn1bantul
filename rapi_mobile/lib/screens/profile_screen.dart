import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({
    required this.api,
    required this.user,
    required this.onLogout,
    required this.onUserUpdated,
    super.key,
  });

  final ApiService api;
  final Map<String, dynamic> user;
  final VoidCallback onLogout;
  final ValueChanged<Map<String, dynamic>> onUserUpdated;

  Future<void> _editProfile(BuildContext context) async {
    final updated = await showDialog<Map<String, String>>(
      context: context,
      builder: (_) => ProfileEditDialog(user: user),
    );
    if (updated == null) return;
    try {
      final saved = await api.updateProfile(updated);
      onUserUpdated(saved);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Profil berhasil diperbarui.')),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    }
  }

  Future<void> _confirmLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Keluar dari Akun?'),
        content: const Text(
          'Apakah Anda yakin ingin keluar dari aplikasi RAPI-Sekolah?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: danger),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Keluar'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      onLogout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final name = user['name']?.toString() ?? 'Pengguna';
    final role = user['role']?.toString();

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const PageHeading(
          title: 'Profil Saya',
          subtitle: 'Informasi dan pengelolaan akun RAPI-Sekolah.',
        ),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(22),
            child: Column(
              children: [
                CircleAvatar(
                  radius: 36,
                  backgroundColor: accentSoft,
                  foregroundColor: accentHover,
                  child: Text(
                    name.isEmpty ? 'U' : name[0].toUpperCase(),
                    style: const TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  name,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: ink,
                  ),
                ),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: accentSoft,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    roleName(role).toUpperCase(),
                    style: const TextStyle(
                      color: accentHover,
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                    ),
                  ),
                ),
                const Divider(height: 28),
                _ProfileInfoRow(
                  icon: Icons.email_outlined,
                  label: 'Email',
                  value: user['email']?.toString() ?? '-',
                ),
                _ProfileInfoRow(
                  icon: Icons.phone_outlined,
                  label: 'Nomor WhatsApp',
                  value: user['whatsapp']?.toString() ?? '-',
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 14),
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(
            padding: const EdgeInsets.symmetric(vertical: 13),
            side: const BorderSide(color: line),
          ),
          onPressed: () => _editProfile(context),
          icon: const Icon(Icons.edit_outlined, size: 18),
          label: const Text('Ubah Profil Akun'),
        ),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(
            foregroundColor: danger,
            side: const BorderSide(color: dangerBorder),
            padding: const EdgeInsets.symmetric(vertical: 13),
          ),
          onPressed: () => _confirmLogout(context),
          icon: const Icon(Icons.logout, size: 18),
          label: const Text('Keluar dari Akun'),
        ),
      ],
    );
  }
}

class _ProfileInfoRow extends StatelessWidget {
  const _ProfileInfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: muted),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(color: muted, fontSize: 11)),
              Text(
                value,
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 14,
                  color: ink,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class ProfileEditDialog extends StatefulWidget {
  const ProfileEditDialog({required this.user, super.key});

  final Map<String, dynamic> user;

  @override
  State<ProfileEditDialog> createState() => _ProfileEditDialogState();
}

class _ProfileEditDialogState extends State<ProfileEditDialog> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _email;
  late final TextEditingController _whatsapp;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: widget.user['name']?.toString() ?? '');
    _email = TextEditingController(
      text: widget.user['email']?.toString() ?? '',
    );
    _whatsapp = TextEditingController(
      text: widget.user['whatsapp']?.toString() ?? '',
    );
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _whatsapp.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
    title: const Text('Ubah Profil'),
    content: Form(
      key: _formKey,
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextFormField(
              controller: _name,
              decoration: const InputDecoration(labelText: 'Nama Lengkap'),
              validator: requiredValidator,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(labelText: 'Alamat Email'),
              validator: (value) =>
                  value == null ||
                      !RegExp(r'^[^@]+@[^@]+\.[^@]+$').hasMatch(value.trim())
                  ? 'Masukkan email yang valid.'
                  : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _whatsapp,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                labelText: 'Nomor WhatsApp',
                hintText: '62812...',
              ),
              validator: (value) =>
                  value == null ||
                      !RegExp(r'^\+?[0-9]{8,20}$').hasMatch(value.trim())
                  ? 'Masukkan nomor WhatsApp yang valid.'
                  : null,
            ),
          ],
        ),
      ),
    ),
    actions: [
      TextButton(
        onPressed: () => Navigator.pop(context),
        child: const Text('Batal'),
      ),
      FilledButton(
        style: FilledButton.styleFrom(backgroundColor: accent),
        onPressed: () {
          if (!_formKey.currentState!.validate()) return;
          Navigator.pop(context, {
            'name': _name.text.trim(),
            'email': _email.text.trim(),
            'whatsapp': _whatsapp.text.trim(),
          });
        },
        child: const Text('Simpan'),
      ),
    ],
  );
}

import 'package:flutter/material.dart';

import '../constants.dart';
import '../widgets/common_widgets.dart';

class PublicAboutScreen extends StatelessWidget {
  const PublicAboutScreen({this.onLogin, super.key});

  final VoidCallback? onLogin;

  @override
  Widget build(BuildContext context) {
    return BrandWatermark(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
        children: [
          // Header Chip & Title
          Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFEFF6FF),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: const Color(0xFFBFDBFE)),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.verified, size: 14, color: Color(0xFF2563EB)),
                  SizedBox(width: 6),
                  Text(
                    'PLATFORM RESMI SARPRAS',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1D4ED8),
                      letterSpacing: 0.5,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),

          const Text(
            'Digitalisasi Sarana & Prasarana',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w900,
              color: ink,
              height: 1.25,
            ),
          ),
          const SizedBox(height: 4),
          const Text(
            'SMK Negeri 1 Bantul',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: accent,
            ),
          ),
          const SizedBox(height: 10),
          const Text(
            'Sistem manajemen fasilitas sekolah modern yang menghubungkan operasional sarana prasarana, agenda peminjaman ruangan, dan aplikasi mobile civitas sekolah secara aman, transparan, dan real-time.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 13, color: muted, height: 1.5),
          ),
          const SizedBox(height: 20),

          // Banner Maskot & Status Monitoring
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF0B192E), Color(0xFF1E293B)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.12),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 8,
                          height: 8,
                          decoration: const BoxDecoration(
                            color: Color(0xFF22C55E),
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 8),
                        const Text(
                          'MONITORING FASILITAS AKTIF',
                          style: TextStyle(
                            color: Colors.white70,
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.8,
                          ),
                        ),
                      ],
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFF22C55E).withOpacity(0.18),
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(
                          color: const Color(0xFF22C55E).withOpacity(0.4),
                        ),
                      ),
                      child: const Text(
                        'Online 24/7',
                        style: TextStyle(
                          color: Color(0xFF4ADE80),
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: Image.asset(
                        'assets/images/app-mascot-logo.png',
                        width: 58,
                        height: 58,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const BrandLogo(size: 48),
                      ),
                    ),
                    const SizedBox(width: 14),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Pusat Layanan Terpadu',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 15,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          SizedBox(height: 4),
                          Text(
                            'Integrasi digital server SMKN 1 Bantul dengan aplikasi mobile Android.',
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 11.5,
                              height: 1.4,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                const Divider(color: Colors.white12, height: 1),
                const SizedBox(height: 14),

                // Metrics / Highlights
                _buildLiveFeatureRow(
                  Icons.meeting_room_outlined,
                  'Manajemen Ruangan',
                  'Lab Komputer, Aula, Ruang Rapat & Teori',
                ),
                const SizedBox(height: 10),
                _buildLiveFeatureRow(
                  Icons.calendar_month_outlined,
                  'Sinkronisasi Kalender',
                  'Pencegahan bentrok jadwal otomatis',
                ),
                const SizedBox(height: 10),
                _buildLiveFeatureRow(
                  Icons.build_circle_outlined,
                  'Pengaduan Sarpras',
                  'Pelaporan sarana rusak langsung ke teknisi',
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Section Title: 3 Keunggulan
          const Text(
            'Keunggulan Sistem RAPI',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: ink,
            ),
          ),
          const SizedBox(height: 12),

          _buildBenefitCard(
            icon: Icons.shield_outlined,
            iconColor: const Color(0xFF2563EB),
            iconBg: const Color(0xFFDBEAFE),
            title: 'Transparan & Akuntabel',
            desc:
                'Setiap peminjaman ruangan tercatat otomatis dengan jejak persetujuan PIC dan administrator yang jelas.',
          ),
          const SizedBox(height: 10),

          _buildBenefitCard(
            icon: Icons.flash_on_outlined,
            iconColor: const Color(0xFFF59E0B),
            iconBg: const Color(0xFFFEF3C7),
            title: 'Respon Pengaduan Cepat',
            desc:
                'Kerusakan sarana prasarana dapat difoto dan dilaporkan saat itu juga untuk segera ditangani tim teknisi.',
          ),
          const SizedBox(height: 10),

          _buildBenefitCard(
            icon: Icons.devices_outlined,
            iconColor: const Color(0xFF10B981),
            iconBg: const Color(0xFFD1FAE5),
            title: 'Kemudahan Akses Multi-Device',
            desc:
                'Dapat diakses oleh civitas akademika melalui smartphone Android maupun portal web desktop.',
          ),
          const SizedBox(height: 24),

          // Tombol Masuk
          if (onLogin != null)
            FilledButton.icon(
              onPressed: onLogin,
              style: FilledButton.styleFrom(
                backgroundColor: accent,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              icon: const Icon(Icons.login),
              label: const Text(
                'Masuk ke Akun Anda',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800),
              ),
            ),
        ],
      ),
    );
  }

  static Widget _buildLiveFeatureRow(
    IconData icon,
    String title,
    String subtitle,
  ) {
    return Row(
      children: [
        Container(
          width: 32,
          height: 32,
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.08),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, size: 17, color: accent),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12.5,
                  fontWeight: FontWeight.w700,
                ),
              ),
              Text(
                subtitle,
                style: const TextStyle(color: Colors.white60, fontSize: 11),
              ),
            ],
          ),
        ),
      ],
    );
  }

  static Widget _buildBenefitCard({
    required IconData icon,
    required Color iconColor,
    required Color iconBg,
    required String title,
    required String desc,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: line),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: iconBg,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: iconColor, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w800,
                    color: ink,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  desc,
                  style: const TextStyle(
                    fontSize: 11.5,
                    color: muted,
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}


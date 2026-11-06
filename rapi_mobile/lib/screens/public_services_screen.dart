import 'package:flutter/material.dart';

import '../constants.dart';
import '../widgets/common_widgets.dart';

class PublicServicesScreen extends StatelessWidget {
  const PublicServicesScreen({this.onLogin, super.key});

  final VoidCallback? onLogin;

  @override
  Widget build(BuildContext context) {
    return BrandWatermark(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
        children: [
          // Section Header
          Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFF0FDF4),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: const Color(0xFFBBF7D0)),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.layers_outlined, size: 14, color: Color(0xFF16A34A)),
                  SizedBox(width: 6),
                  Text(
                    'FITUR SISTEM TERPADU',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF15803D),
                      letterSpacing: 0.5,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),

          const Text(
            'Layanan Siswa, Guru,\nPIC Ruangan & Admin',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w900,
              color: ink,
              height: 1.25,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Setiap peran dirancang memiliki modul kerja yang optimal sesuai standar operasional pengelolaan sarana prasarana sekolah.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12.5, color: muted, height: 1.45),
          ),
          const SizedBox(height: 20),

          // KARTU 1: SISWA & GURU (ANDROID)
          _buildRoleCard(
            tag: 'Aplikasi Siswa & Guru (Android)',
            tagColor: const Color(0xFF16A34A),
            tagBg: const Color(0xFFDCFCE7),
            title: 'Mobile RAPI Siswa & Guru',
            desc:
                'Dirancang khusus agar bapak/ibu guru dan perwakilan organisasi dapat memantau jadwal dan mengajukan permohonan dari smartphone.',
            items: [
              'Cek Kalender Real-Time: Jadwal peminjaman dan libur nasional.',
              'Pengajuan Pinjam Ruangan Praktis: Tanpa perlu formulir kertas fisik.',
              'Lapor Sarpras Rusak Instan: Unggah foto dan rincian kerusakan.',
              'Notifikasi WhatsApp: Pembaruan status permohonan langsung ke nomor WA.',
            ],
          ),
          const SizedBox(height: 16),

          // KARTU 2: PIC RUANGAN
          _buildRoleCard(
            tag: 'PIC / Penanggung Jawab Ruangan',
            tagColor: const Color(0xFF2563EB),
            tagBg: const Color(0xFFDBEAFE),
            title: 'Verifikasi & Pengawasan PIC',
            desc:
                'Menjaga ketersediaan, kelayakan fasilitas, dan mengonfirmasi penggunaan ruangan yang menjadi tanggung jawabnya.',
            items: [
              'Persetujuan / Penolakan Agenda: Kontrol penuh atas izin penggunaan ruangan.',
              'Monitoring Kesiapan Sarpras: Memastikan lab / aula siap sebelum dipakai.',
              'Tindak Lanjut Aduan: Validasi kerusakan barang untuk diperbaiki teknisi.',
            ],
          ),
          const SizedBox(height: 16),

          // KARTU 3: ADMINISTRATOR
          _buildRoleCard(
            tag: 'Administrator Sarpras (Web Portal)',
            tagColor: const Color(0xFF9333EA),
            tagBg: const Color(0xFFF3E8FF),
            title: 'Command Center Sarpras',
            desc:
                'Portal desktop terpadu untuk kepala sarpras dan administrator dalam mengelola aset dan dokumen resmi.',
            items: [
              'Manajemen Master Ruangan & Aset: Kontrol kapasitas dan fasilitas sekolah.',
              'Cetak Laporan Resmi: Export agenda dan aduan rusak ke format DOCX/Surat Tugas.',
              'Audit Trail & Keamanan: Pemantauan aktivitas penggunaan sarpras.',
            ],
          ),
          const SizedBox(height: 24),

          // SOP PEMINJAMAN RUANGAN (4 LANGKAH)
          const Text(
            'Alur Standar Peminjaman Ruangan',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: ink,
            ),
          ),
          const SizedBox(height: 12),

          _buildStepCard(
            '1',
            'Pengajuan Permohonan',
            'Pilih ruangan, tanggal, waktu mulai, dan keperluan acara melalui aplikasi.',
          ),
          _buildStepCard(
            '2',
            'Verifikasi PIC Ruangan',
            'Penanggung jawab ruangan memeriksa kelayakan dan ketersediaan jadwal.',
          ),
          _buildStepCard(
            '3',
            'Konfirmasi & Notifikasi',
            'Pemohon menerima kabar persetujuan dan agenda langsung masuk ke kalender publik.',
          ),
          _buildStepCard(
            '4',
            'Penggunaan & Pelaporan',
            'Gunakan ruangan sesuai ketentuan dan laporkan jika ada kendala sarana.',
            isLast: true,
          ),
          const SizedBox(height: 20),

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

  static Widget _buildRoleCard({
    required String tag,
    required Color tagColor,
    required Color tagBg,
    required String title,
    required String desc,
    required List<String> items,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: line),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: tagBg,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Text(
              tag,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w800,
                color: tagColor,
              ),
            ),
          ),
          const SizedBox(height: 10),
          Text(
            title,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: ink,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            desc,
            style: const TextStyle(fontSize: 12, color: muted, height: 1.4),
          ),
          const SizedBox(height: 12),
          const Divider(height: 1, color: line),
          const SizedBox(height: 12),
          ...items.map(
            (item) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.check_circle_rounded, size: 16, color: tagColor),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      item,
                      style: const TextStyle(
                        fontSize: 12,
                        color: ink,
                        height: 1.35,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  static Widget _buildStepCard(
    String stepNum,
    String title,
    String desc, {
    bool isLast = false,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 28,
              height: 28,
              decoration: const BoxDecoration(
                color: sidebar,
                shape: BoxShape.circle,
              ),
              child: Center(
                child: Text(
                  stepNum,
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 12,
                  ),
                ),
              ),
            ),
            if (!isLast)
              Container(width: 2, height: 42, color: const Color(0xFFD1D5DB)),
          ],
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Padding(
            padding: EdgeInsets.only(bottom: isLast ? 0 : 16),
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
                const SizedBox(height: 2),
                Text(
                  desc,
                  style: const TextStyle(
                    fontSize: 11.5,
                    color: muted,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}


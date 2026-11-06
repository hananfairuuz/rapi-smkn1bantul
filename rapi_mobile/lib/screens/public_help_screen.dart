import 'package:flutter/material.dart';

import '../constants.dart';
import '../widgets/common_widgets.dart';

class PublicHelpScreen extends StatelessWidget {
  const PublicHelpScreen({this.onLogin, super.key});

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
                color: const Color(0xFFFEF3C7),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.support_agent_outlined,
                    size: 14,
                    color: Color(0xFFD97706),
                  ),
                  SizedBox(width: 6),
                  Text(
                    'PUSAT BANTUAN & INFORMASI',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFFB45309),
                      letterSpacing: 0.5,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),

          const Text(
            'Bantuan & Tata Tertib\nSarana Prasarana',
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
            'Panduan tata tertib peminjaman, kontak resmi pengelola sarpras, dan pertanyaan umum seputar fasilitas sekolah.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12.5, color: muted, height: 1.45),
          ),
          const SizedBox(height: 20),

          // KARTU KONTAK RESMI SARPRAS
          Container(
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
                const Row(
                  children: [
                    Icon(
                      Icons.contact_phone_outlined,
                      color: accent,
                      size: 20,
                    ),
                    SizedBox(width: 8),
                    Text(
                      'Kontak Layanan Sarpras',
                      style: TextStyle(
                        fontSize: 14.5,
                        fontWeight: FontWeight.w800,
                        color: ink,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                const Divider(height: 1, color: line),
                const SizedBox(height: 12),

                _buildContactItem(
                  Icons.location_on_outlined,
                  'Lokasi Kantor',
                  'Ruang Sarpras, Gedung Utama SMK Negeri 1 Bantul',
                ),
                const SizedBox(height: 10),
                _buildContactItem(
                  Icons.access_time_outlined,
                  'Jam Operasional',
                  'Senin - Jumat (07.00 - 16.00 WIB)',
                ),
                const SizedBox(height: 10),
                _buildContactItem(
                  Icons.chat_outlined,
                  'Layanan WhatsApp',
                  'PIC Ruangan / Koordinator Sarpras Sekolah',
                ),
                const SizedBox(height: 10),
                _buildContactItem(
                  Icons.language_outlined,
                  'Website Resmi',
                  'https://rapi-smkn1bantul.web.id',
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // TATA TERTIB PENGGUNAAN RUANGAN
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(
                      Icons.assignment_outlined,
                      color: Color(0xFF2563EB),
                      size: 20,
                    ),
                    SizedBox(width: 8),
                    Text(
                      'Tata Tertib Peminjaman Ruangan',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: ink,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                _buildRuleItem('1', 'Pengajuan diajukan minimal H-1 sebelum waktu kegiatan dilaksanakan.'),
                _buildRuleItem('2', 'Wajib menjaga ketertiban, kebersihan, dan kerapian ruangan setelah selesai.'),
                _buildRuleItem('3', 'Matikan proyektor, AC, kipas angin, dan lampu sebelum meninggalkan ruangan.'),
                _buildRuleItem('4', 'Dilarang memindahkan inventaris antar ruangan tanpa izin resmi PIC.'),
                _buildRuleItem('5', 'Laporkan kerusakan sarpras segera melalui menu Pengaduan di aplikasi.', isLast: true),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // FAQ ACCORDION-STYLE CARDS
          const Text(
            'Pertanyaan Sering Diajukan (FAQ)',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: ink,
            ),
          ),
          const SizedBox(height: 10),

          _buildFaqTile(
            question: 'Bagaimana cara mendapatkan akun?',
            answer:
                'Akun untuk bapak/ibu guru dan pengurus organisasi/ekstrakurikuler dibuatkan langsung oleh Administrator Sarpras sekolah. Silakan hubungi bagian sarpras jika belum memiliki akun.',
          ),
          _buildFaqTile(
            question: 'Mengapa Admin tidak bisa login di HP?',
            answer:
                'Sesuai prosedur operasional, akun Administrator memiliki wewenang manajemen data master, kontrol database, dan cetak dokumen resmi (SPJ/DOCX) yang dioperasikan melalui portal web desktop di laptop/PC.',
          ),
          _buildFaqTile(
            question: 'Bagaimana jika ada jadwal bentrok?',
            answer:
                'Sistem secara otomatis memeriksa ketersediaan ruangan pada tanggal dan jam yang diajukan. Jika sudah ada agenda yang disetujui, sistem akan memblokir jadwal tersebut sehingga tidak akan pernah tumpang tindih.',
          ),
          _buildFaqTile(
            question: 'Apakah aplikasi butuh internet?',
            answer:
                'Ya, aplikasi ini berjalan secara online dan terhubung langsung ke server sekolah sehingga data agenda, ruangan, dan pengaduan selalu sinkron secara real-time dari mana saja.',
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

  static Widget _buildContactItem(
    IconData icon,
    String label,
    String value,
  ) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 30,
          height: 30,
          decoration: BoxDecoration(
            color: const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, size: 16, color: ink),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 11,
                  color: muted,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 1),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w700,
                  color: ink,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  static Widget _buildRuleItem(
    String num,
    String text, {
    bool isLast = false,
  }) {
    return Padding(
      padding: EdgeInsets.only(bottom: isLast ? 0 : 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 20,
            height: 20,
            decoration: const BoxDecoration(
              color: Color(0xFF2563EB),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                num,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 10.5,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(fontSize: 12, color: ink, height: 1.35),
            ),
          ),
        ],
      ),
    );
  }

  static Widget _buildFaqTile({
    required String question,
    required String answer,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: surface,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: line),
      ),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
        childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
        shape: const Border(),
        collapsedShape: const Border(),
        iconColor: accent,
        collapsedIconColor: muted,
        title: Text(
          question,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w800,
            color: ink,
          ),
        ),
        children: [
          Text(
            answer,
            style: const TextStyle(fontSize: 12, color: muted, height: 1.4),
          ),
        ],
      ),
    );
  }
}


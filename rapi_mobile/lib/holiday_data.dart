/// Data dan helper untuk hari libur nasional serta cuti bersama resmi di Indonesia.
/// Mengikuti penetapan SKB 3 Menteri dan kalender nasional Indonesia.
class HolidayData {
  HolidayData._();

  /// Daftar hari libur nasional dan cuti bersama Indonesia
  static final Map<String, String> _builtinHolidays = {
    // 2024
    '2024-01-01': 'Tahun Baru 2024 Masehi',
    '2024-02-08': 'Isra Mikraj Nabi Muhammad SAW',
    '2024-02-09': 'Cuti Bersama Tahun Baru Imlek',
    '2024-02-10': 'Tahun Baru Imlek 2575 Kongzili',
    '2024-03-11': 'Hari Suci Nyepi Tahun Baru Saka 1946',
    '2024-03-12': 'Cuti Bersama Hari Suci Nyepi',
    '2024-03-29': 'Wafat Yesus Kristus',
    '2024-03-31': 'Hari Paskah',
    '2024-04-08': 'Cuti Bersama Idul Fitri 1445 H',
    '2024-04-09': 'Cuti Bersama Idul Fitri 1445 H',
    '2024-04-10': 'Hari Raya Idul Fitri 1445 H',
    '2024-04-11': 'Hari Raya Idul Fitri 1445 H',
    '2024-04-12': 'Cuti Bersama Idul Fitri 1445 H',
    '2024-04-15': 'Cuti Bersama Idul Fitri 1445 H',
    '2024-05-01': 'Hari Buruh Internasional',
    '2024-05-09': 'Kenaikan Yesus Kristus',
    '2024-05-10': 'Cuti Bersama Kenaikan Yesus Kristus',
    '2024-05-23': 'Hari Raya Waisak 2568 BE',
    '2024-05-24': 'Cuti Bersama Hari Raya Waisak',
    '2024-06-01': 'Hari Lahir Pancasila',
    '2024-06-17': 'Hari Raya Idul Adha 1445 H',
    '2024-06-18': 'Cuti Bersama Hari Raya Idul Adha',
    '2024-07-07': 'Tahun Baru Islam 1446 H',
    '2024-08-17': 'Hari Proklamasi Kemerdekaan RI',
    '2024-09-16': 'Maulid Nabi Muhammad SAW',
    '2024-12-24': 'Cuti Bersama Hari Raya Natal',
    '2024-12-25': 'Hari Raya Natal',
    '2024-12-26': 'Cuti Bersama Hari Raya Natal',

    // 2025
    '2025-01-01': 'Tahun Baru 2025 Masehi',
    '2025-01-27': 'Isra Mikraj Nabi Muhammad SAW',
    '2025-01-28': 'Cuti Bersama Tahun Baru Imlek',
    '2025-01-29': 'Tahun Baru Imlek 2576 Kongzili',
    '2025-03-28': 'Cuti Bersama Hari Suci Nyepi',
    '2025-03-29': 'Hari Suci Nyepi Tahun Baru Saka 1947',
    '2025-03-31': 'Hari Raya Idul Fitri 1446 H',
    '2025-04-01': 'Hari Raya Idul Fitri 1446 H',
    '2025-04-02': 'Cuti Bersama Idul Fitri 1446 H',
    '2025-04-03': 'Cuti Bersama Idul Fitri 1446 H',
    '2025-04-04': 'Cuti Bersama Idul Fitri 1446 H',
    '2025-04-07': 'Cuti Bersama Idul Fitri 1446 H',
    '2025-04-18': 'Wafat Yesus Kristus',
    '2025-04-20': 'Hari Paskah',
    '2025-05-01': 'Hari Buruh Internasional',
    '2025-05-12': 'Hari Raya Waisak 2569 BE',
    '2025-05-13': 'Cuti Bersama Hari Raya Waisak',
    '2025-05-29': 'Kenaikan Yesus Kristus',
    '2025-05-30': 'Cuti Bersama Kenaikan Yesus Kristus',
    '2025-06-01': 'Hari Lahir Pancasila',
    '2025-06-06': 'Hari Raya Idul Adha 1446 H',
    '2025-06-09': 'Cuti Bersama Hari Raya Idul Adha',
    '2025-06-27': 'Tahun Baru Islam 1447 H',
    '2025-08-17': 'Hari Proklamasi Kemerdekaan RI',
    '2025-09-05': 'Maulid Nabi Muhammad SAW',
    '2025-12-24': 'Cuti Bersama Hari Raya Natal',
    '2025-12-25': 'Hari Raya Natal',
    '2025-12-26': 'Cuti Bersama Hari Raya Natal',

    // 2026
    '2026-01-01': 'Tahun Baru 2026 Masehi',
    '2026-01-16': 'Isra Mikraj Nabi Muhammad SAW',
    '2026-02-16': 'Cuti Bersama Tahun Baru Imlek',
    '2026-02-17': 'Tahun Baru Imlek 2577 Kongzili',
    '2026-03-19': 'Hari Suci Nyepi Tahun Baru Saka 1948',
    '2026-03-20': 'Hari Raya Idul Fitri 1447 H',
    '2026-03-21': 'Hari Raya Idul Fitri 1447 H',
    '2026-03-23': 'Cuti Bersama Idul Fitri 1447 H',
    '2026-03-24': 'Cuti Bersama Idul Fitri 1447 H',
    '2026-04-03': 'Wafat Yesus Kristus',
    '2026-04-05': 'Hari Paskah',
    '2026-05-01': 'Hari Buruh Internasional',
    '2026-05-14': 'Kenaikan Yesus Kristus',
    '2026-05-27': 'Hari Raya Idul Adha 1447 H',
    '2026-05-31': 'Hari Raya Waisak 2570 BE',
    '2026-06-01': 'Hari Lahir Pancasila',
    '2026-06-16': 'Tahun Baru Islam 1448 H',
    '2026-08-17': 'Hari Proklamasi Kemerdekaan RI',
    '2026-08-25': 'Maulid Nabi Muhammad SAW',
    '2026-12-24': 'Cuti Bersama Hari Raya Natal',
    '2026-12-25': 'Hari Raya Natal',
    '2026-12-26': 'Cuti Bersama Hari Raya Natal',

    // 2027
    '2027-01-01': 'Tahun Baru 2027 Masehi',
    '2027-01-06': 'Isra Mikraj Nabi Muhammad SAW',
    '2027-02-06': 'Tahun Baru Imlek 2578 Kongzili',
    '2027-03-09': 'Hari Raya Idul Fitri 1448 H',
    '2027-03-10': 'Hari Raya Idul Fitri 1448 H',
    '2027-03-11': 'Cuti Bersama Idul Fitri',
    '2027-03-12': 'Cuti Bersama Idul Fitri',
    '2027-03-26': 'Wafat Yesus Kristus',
    '2027-03-28': 'Hari Paskah',
    '2027-04-07': 'Hari Suci Nyepi Tahun Baru Saka 1949',
    '2027-05-01': 'Hari Buruh Internasional',
    '2027-05-06': 'Kenaikan Yesus Kristus',
    '2027-05-17': 'Hari Raya Idul Adha 1448 H',
    '2027-05-20': 'Hari Raya Waisak 2571 BE',
    '2027-06-01': 'Hari Lahir Pancasila',
    '2027-06-06': 'Tahun Baru Islam 1449 H',
    '2027-08-15': 'Maulid Nabi Muhammad SAW',
    '2027-08-17': 'Hari Proklamasi Kemerdekaan RI',
    '2027-12-24': 'Cuti Bersama Hari Raya Natal',
    '2027-12-25': 'Hari Raya Natal',
  };

  /// Ambil seluruh daftar hari libur nasional untuk tahun tertentu,
  /// dengan menggabungkan respons dari API jika tersedia.
  static Map<String, String> getHolidaysForYear(
    int year, [
    Map<String, dynamic>? apiHolidays,
  ]) {
    final result = <String, String>{};
    final prefix = '$year-';

    // 1. Ambil dari database lokal
    for (final entry in _builtinHolidays.entries) {
      if (entry.key.startsWith(prefix)) {
        result[entry.key] = entry.value;
      }
    }

    // 2. Berikan libur tetap jika tahun di luar jangkauan
    if (result.isEmpty) {
      result['$year-01-01'] = 'Tahun Baru $year Masehi';
      result['$year-05-01'] = 'Hari Buruh Internasional';
      result['$year-06-01'] = 'Hari Lahir Pancasila';
      result['$year-08-17'] = 'Hari Kemerdekaan RI';
      result['$year-12-25'] = 'Hari Raya Natal';
      result['$year-12-26'] = 'Cuti Bersama Hari Raya Natal';
    }

    // 3. Gabungkan dengan data dari API server jika ada
    if (apiHolidays != null && apiHolidays.isNotEmpty) {
      for (final entry in apiHolidays.entries) {
        if (entry.key.startsWith(prefix)) {
          result[entry.key] = entry.value.toString();
        }
      }
    }

    return result;
  }

  /// Cek apakah tanggal adalah hari Minggu
  static bool isSunday(DateTime date) => date.weekday == DateTime.sunday;

  /// Cek apakah tanggal adalah hari libur akhir pekan (Sabtu atau Minggu)
  static bool isWeekend(DateTime date) =>
      date.weekday == DateTime.saturday || date.weekday == DateTime.sunday;

  /// Cek apakah tanggal tertentu adalah hari libur nasional
  static String? getHolidayName(DateTime date, Map<String, String> holidays) {
    final key =
        '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
    return holidays[key];
  }

  /// Cek apakah tanggal harus berwarna merah (Akhir Pekan: Sabtu/Minggu atau Hari Libur Nasional)
  static bool isRedDate(DateTime date, Map<String, String> holidays) {
    return isWeekend(date) || getHolidayName(date, holidays) != null;
  }

  /// Mempersingkat nama hari libur untuk ditampilkan di dalam kotak kalender
  static String shortHolidayName(String fullName) {
    final lower = fullName.toLowerCase();
    if (lower.contains('cuti') && lower.contains('natal')) return 'Cuti Natal';
    if (lower.contains('cuti') && lower.contains('idul fitri')) return 'Cuti Idul Fitri';
    if (lower.contains('cuti') && lower.contains('imlek')) return 'Cuti Imlek';
    if (lower.contains('cuti') && lower.contains('nyepi')) return 'Cuti Nyepi';
    if (lower.contains('cuti')) return 'Cuti Bersama';
    if (lower.contains('tahun baru') && lower.contains('islam')) return '1 Muharram';
    if (lower.contains('tahun baru') && lower.contains('imlek')) return 'Imlek';
    if (lower.contains('tahun baru')) return 'Tahun Baru';
    if (lower.contains('idul fitri')) return 'Idul Fitri';
    if (lower.contains('idul adha')) return 'Idul Adha';
    if (lower.contains('natal')) return 'Natal';
    if (lower.contains('isra mikraj')) return 'Isra Mikraj';
    if (lower.contains('nyepi')) return 'Nyepi';
    if (lower.contains('waisak')) return 'Waisak';
    if (lower.contains('paskah')) return 'Paskah';
    if (lower.contains('wafat yesus')) return 'Wafat Yesus';
    if (lower.contains('kenaikan')) return 'Kenaikan Yesus';
    if (lower.contains('buruh')) return 'Hari Buruh';
    if (lower.contains('pancasila')) return 'Pancasila';
    if (lower.contains('kemerdekaan')) return 'HUT RI';
    if (lower.contains('maulid')) return 'Maulid Nabi';
    return fullName.length > 12 ? '${fullName.substring(0, 10)}..' : fullName;
  }

  /// Mencari semua hari libur yang cocok dengan kata kunci
  static List<MapEntry<String, String>> searchHolidays(String query, [int? year]) {
    final cleanQuery = query.toLowerCase().trim();
    if (cleanQuery.isEmpty) return [];

    final list = <MapEntry<String, String>>[];
    final currentYear = year ?? DateTime.now().year;

    // Cari di tahun saat ini, setahun sebelumnya, dan 2 tahun ke depan
    for (var y = currentYear - 1; y <= currentYear + 2; y++) {
      final yearHolidays = getHolidaysForYear(y);
      for (final entry in yearHolidays.entries) {
        if (entry.value.toLowerCase().contains(cleanQuery) ||
            entry.key.contains(cleanQuery)) {
          list.add(entry);
        }
      }
    }

    list.sort((a, b) => a.key.compareTo(b.key));
    return list;
  }
}


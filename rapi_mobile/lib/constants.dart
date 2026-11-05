import 'package:flutter/material.dart';

// Brand colors match the blue and yellow desktop dashboard palette.
const ink = Color(0xFF162335);
const muted = Color(0xFF8492A6);
const line = Color(0xFFE9EEF4);
const inputBorder = Color(0xFFDCE3EB);
const surface = Color(0xFFFFFFFF);
const canvas = Color(0xFFF7F9FB);
const sidebar = Color(0xFF0B192E);
const sidebarMuted = Color(0xFFA0AEC0);
const accent = Color(0xFFF59E0B);
const accentHover = Color(0xFFE58B08);
const accentSoft = Color(0xFFFFFBEB);
const danger = Color(0xFFF43F5E);
const dangerText = Color(0xFFE11D48);
const dangerBorder = Color(0xFFFECDD3);
const dangerSoft = Color(0xFFFFF1F2);
const success = Color(0xFF10B981);
const successText = Color(0xFF059669);
const successBorder = Color(0xFFA7F3D0);
const successSoft = Color(0xFFECFDF5);
const warning = Color(0xFFF59E0B);
const warningText = Color(0xFFB45309);
const pendingText = Color(0xFFB45309);
const warningSoft = Color(0xFFFFFBEB);

Map<String, dynamic> asMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return {};
}

String? requiredValidator(String? value) =>
    value == null || value.trim().isEmpty ? 'Bagian ini wajib diisi.' : null;

String twoDigits(int value) => value.toString().padLeft(2, '0');

Color calendarEventColor(String? organizerRole) => switch (organizerRole) {
  'guru' => const Color(0xFF16A34A),
  'organisasi' => const Color(0xFF2563EB),
  'admin' => const Color(0xFFEAB308),
  _ => muted,
};

String formatTimeOfDay(TimeOfDay time) =>
    '${twoDigits(time.hour)}:${twoDigits(time.minute)}';

String dateIso(DateTime date) =>
    '${date.year}-${twoDigits(date.month)}-${twoDigits(date.day)}';

String formatDate(DateTime date) =>
    '${twoDigits(date.day)} ${monthName(date.month)} ${date.year}';

String formatDateValue(dynamic value) {
  if (value == null) return '-';
  final text = value.toString();
  try {
    return formatDate(DateTime.parse(text.substring(0, 10)));
  } on FormatException {
    return text;
  }
}

String monthName(int month) => const [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember',
][month - 1];

String shortTime(dynamic value) {
  if (value == null) return '--:--';
  final text = value.toString();
  return text.length >= 5 ? text.substring(0, 5) : text;
}

bool endsNextDay(dynamic startTime, dynamic endTime) {
  if (startTime == null || endTime == null) return false;
  final start = shortTime(startTime);
  final end = shortTime(endTime);
  return end.compareTo(start) < 0;
}

DateTime eventEndDate(Map<String, dynamic> event) {
  final start = DateTime.parse(event['date'].toString().substring(0, 10));
  final end = event['end_date']?.toString();
  if (end != null && end.isNotEmpty) {
    return DateTime.parse(end.substring(0, 10));
  }
  return endsNextDay(event['start_time'], event['end_time'])
      ? start.add(const Duration(days: 1))
      : start;
}

String eventDateRange(Map<String, dynamic> event) {
  final start = formatDateValue(event['date']);
  final end = eventEndDate(event);
  final startDate = DateTime.parse(event['date'].toString().substring(0, 10));
  return end.isAfter(startDate) ? '$start – ${formatDate(end)}' : start;
}

bool eventEndsNextDay(Map<String, dynamic> event) =>
    eventEndDate(event)
        .difference(DateTime.parse(event['date'].toString().substring(0, 10)))
        .inDays ==
    1;

String roleName(String? role) => switch (role) {
  'admin' => 'Waka / Admin',
  'pic' => 'PIC Ruang',
  'guru' => 'Guru',
  'organisasi' => 'Organisasi',
  _ => 'Pengguna',
};

Color statusColor(String? status) => switch (status) {
  'approved' || 'Sudah Diperbaiki' => successText,
  'rejected' => dangerText,
  _ => pendingText,
};

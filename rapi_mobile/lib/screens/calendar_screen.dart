import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../holiday_data.dart';
import '../widgets/common_widgets.dart';

class CalendarScreen extends StatefulWidget {
  const CalendarScreen({
    required this.api,
    this.onLogin,
    this.currentUser,
    this.onReportDamage,
    this.isEmbedded = false,
    super.key,
  });

  final ApiService api;
  final VoidCallback? onLogin;
  final Map<String, dynamic>? currentUser;
  final ValueChanged<Map<String, dynamic>>? onReportDamage;
  final bool isEmbedded;

  @override
  State<CalendarScreen> createState() => _CalendarScreenState();
}

class _CalendarScreenState extends State<CalendarScreen> {
  late DateTime _month;
  DateTime? _selectedDate;
  late Future<Map<String, dynamic>> _calendar;

  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _month = DateTime(now.year, now.month);
    _selectedDate = DateTime(now.year, now.month, now.day);
    _calendar = _loadMonth();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<Map<String, dynamic>> _loadMonth() =>
      widget.api.getCalendar('${_month.year}-${twoDigits(_month.month)}');

  void _changeMonth(int offset) {
    setState(() {
      _month = DateTime(_month.year, _month.month + offset);
      _selectedDate = null;
      _calendar = _loadMonth();
    });
  }

  void _goToToday() {
    final now = DateTime.now();
    setState(() {
      _month = DateTime(now.year, now.month);
      _selectedDate = DateTime(now.year, now.month, now.day);
      _calendar = _loadMonth();
    });
  }

  void _selectDateAndMonth(DateTime targetDate) {
    final targetMonth = DateTime(targetDate.year, targetDate.month);
    final needReload = targetMonth.year != _month.year || targetMonth.month != _month.month;

    setState(() {
      _month = targetMonth;
      _selectedDate = targetDate;
      if (needReload) {
        _calendar = _loadMonth();
      }
      _searchController.clear();
      _searchQuery = '';
    });
  }

  Future<void> _refresh() async {
    final next = _loadMonth();
    setState(() => _calendar = next);
    await next;
  }

  void _showEventDetails(Map<String, dynamic> event) {
    final userRole = widget.currentUser?['role']?.toString();
    final canReport =
        (userRole == 'guru' || userRole == 'organisasi') &&
        widget.onReportDamage != null &&
        event['status'] == 'approved';

    showEventDetailSheet(
      context: context,
      event: event,
      onReportDamage: canReport ? () => widget.onReportDamage!(event) : null,
    );
  }

  Color _eventColor(dynamic event) =>
      calendarEventColor(asMap(event)['organizer_role']?.toString());

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: _calendar,
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return _wrapPage(
            ErrorState(error: snapshot.error, onRetry: _refresh),
          );
        }
        if (!snapshot.hasData) return _wrapPage(const LoadingState());

        // Ambil data libur nasional
        final apiHolidays = snapshot.data!['holidays'] is Map
            ? Map<String, dynamic>.from(snapshot.data!['holidays'] as Map)
            : null;
        final holidays = HolidayData.getHolidaysForYear(_month.year, apiHolidays);

        // Ambil data agenda ruangan
        final events = List<dynamic>.from(
          snapshot.data!['events'] as List? ?? [],
        );
        final eventsByDate = <String, List<dynamic>>{};
        for (final event in events) {
          final eventData = asMap(event);
          final startsAt = DateTime.parse(
            eventData['date'].toString().substring(0, 10),
          );
          final endsAt = eventEndDate(eventData);
          final monthStart = DateTime(_month.year, _month.month);
          final monthEnd = DateTime(_month.year, _month.month + 1, 0);
          var day = startsAt.isAfter(monthStart) ? startsAt : monthStart;
          final lastDay = endsAt.isBefore(monthEnd) ? endsAt : monthEnd;
          while (!day.isAfter(lastDay)) {
            eventsByDate.putIfAbsent(dateIso(day), () => []).add(event);
            day = day.add(const Duration(days: 1));
          }
        }

        // Kalender mengikuti standar Indonesia: dimulai dari Minggu (Min) hingga Sabtu (Sab)
        // DateTime weekday: Senin=1 .. Minggu=7. Dengan rumus `weekday % 7`, Minggu menjadi indeks 0.
        final firstOffset = DateTime(_month.year, _month.month, 1).weekday % 7;
        final daysInMonth = DateTime(_month.year, _month.month + 1, 0).day;
        final selectedEvents = _selectedDate == null
            ? <dynamic>[]
            : eventsByDate[dateIso(_selectedDate!)] ?? <dynamic>[];
        final selectedHoliday = _selectedDate == null
            ? null
            : HolidayData.getHolidayName(_selectedDate!, holidays);

        // Hari libur pada bulan ini
        final monthPrefix = '${_month.year}-${twoDigits(_month.month)}-';
        final monthHolidays = holidays.entries
            .where((e) => e.key.startsWith(monthPrefix))
            .toList()
          ..sort((a, b) => a.key.compareTo(b.key));

        final isSearching = _searchQuery.isNotEmpty;

        return _wrapPage(
          RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              children: [
                PageHeading(
                  title: 'Kalender Agenda',
                  subtitle: 'Agenda ruangan sekolah dan hari libur nasional.',
                ),

                // Kotak Pencarian Acara & Hari Libur
                Container(
                  margin: const EdgeInsets.only(bottom: 12),
                  decoration: BoxDecoration(
                    color: surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: inputBorder),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.03),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: TextField(
                    controller: _searchController,
                    textInputAction: TextInputAction.search,
                    onChanged: (val) => setState(() => _searchQuery = val.trim()),
                    onSubmitted: (val) {
                      final query = val.trim().toLowerCase();
                      if (query.isEmpty) return;
                      final matchedHolidays = HolidayData.searchHolidays(query, _month.year);
                      if (matchedHolidays.isNotEmpty) {
                        final parts = matchedHolidays.first.key.split('-');
                        final dt = DateTime(int.parse(parts[0]), int.parse(parts[1]), int.parse(parts[2]));
                        _selectDateAndMonth(dt);
                        return;
                      }
                      final matchedEvents = events.where((event) {
                        final ev = asMap(event);
                        final title = ev['title']?.toString().toLowerCase() ?? '';
                        final room = asMap(ev['room'])['name']?.toString().toLowerCase() ?? '';
                        final organizer = ev['organizer_role']?.toString().toLowerCase() ?? '';
                        final desc = ev['description']?.toString().toLowerCase() ?? '';
                        return title.contains(query) || room.contains(query) || organizer.contains(query) || desc.contains(query);
                      }).toList();
                      if (matchedEvents.isNotEmpty) {
                        final ev = asMap(matchedEvents.first);
                        final dateStr = ev['date']?.toString();
                        if (dateStr != null && dateStr.length >= 10) {
                          final dt = DateTime.parse(dateStr.substring(0, 10));
                          _selectDateAndMonth(dt);
                        }
                      }
                    },
                    decoration: InputDecoration(
                      hintText: 'Cari acara, ruangan, atau libur nasional...',
                      hintStyle: const TextStyle(color: muted, fontSize: 13),
                      prefixIcon: const Icon(Icons.search, color: muted, size: 20),
                      suffixIcon: _searchQuery.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.close, color: muted, size: 18),
                              onPressed: () {
                                _searchController.clear();
                                setState(() => _searchQuery = '');
                              },
                            )
                          : null,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 12,
                      ),
                    ),
                  ),
                ),

                // Tampilan hasil pencarian bila pengguna sedang mencari
                if (isSearching) ...[
                  _buildSearchResults(
                    events: events,
                    holidays: holidays,
                  ),
                ] else ...[
                  // Legenda Warna (termasuk Libur Nasional merah)
                  Wrap(
                    spacing: 14,
                    runSpacing: 8,
                    children: [
                      for (final entry in [
                        ('Guru', calendarEventColor('guru')),
                        ('Organisasi', calendarEventColor('organisasi')),
                        ('Admin', calendarEventColor('admin')),
                        ('Libur Nasional', dangerText),
                      ])
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              width: 10,
                              height: 10,
                              decoration: BoxDecoration(
                                color: entry.$2,
                                borderRadius: BorderRadius.circular(3),
                              ),
                            ),
                            const SizedBox(width: 6),
                            Text(
                              entry.$1,
                              style: const TextStyle(color: muted, fontSize: 12),
                            ),
                          ],
                        ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  // Kartu Kalender Bulanan
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        children: [
                          // Navigasi Bulan & Tombol Hari Ini
                          Row(
                            children: [
                              IconButton(
                                onPressed: () => _changeMonth(-1),
                                icon: const Icon(Icons.chevron_left),
                                tooltip: 'Bulan sebelumnya',
                              ),
                              Expanded(
                                child: Text(
                                  '${monthName(_month.month)} ${_month.year}',
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                    fontSize: 16,
                                    color: ink,
                                  ),
                                ),
                              ),
                              TextButton(
                                onPressed: _goToToday,
                                style: TextButton.styleFrom(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 4,
                                  ),
                                  visualDensity: VisualDensity.compact,
                                ),
                                child: const Text(
                                  'Hari Ini',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 12,
                                    color: accent,
                                  ),
                                ),
                              ),
                              IconButton(
                                onPressed: () => _changeMonth(1),
                                icon: const Icon(Icons.chevron_right),
                                tooltip: 'Bulan berikutnya',
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),

                          // Header Hari (Min, Sen, Sel, Rab, Kam, Jum, Sab)
                          // Min (Minggu) berwarna merah
                          Row(
                            children: [
                              for (var i = 0; i < 7; i++)
                                Expanded(
                                  child: Center(
                                    child: Text(
                                      const ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][i],
                                      style: TextStyle(
                                        color: i == 0 ? dangerText : muted,
                                        fontSize: 11,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                ),
                            ],
                          ),
                          const SizedBox(height: 8),

                          // Grid Tanggal
                          GridView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: firstOffset + daysInMonth,
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 7,
                              mainAxisSpacing: 4,
                              crossAxisSpacing: 4,
                              childAspectRatio: 0.82,
                            ),
                            itemBuilder: (context, index) {
                              if (index < firstOffset) {
                                return const SizedBox.shrink();
                              }
                              final dateNum = index - firstOffset + 1;
                              final dateValue = DateTime(
                                _month.year,
                                _month.month,
                                dateNum,
                              );
                              final key = dateIso(dateValue);
                              final dayEvents = eventsByDate[key] ?? [];
                              final hasEvents = dayEvents.isNotEmpty;
                              final holidayName = HolidayData.getHolidayName(
                                dateValue,
                                holidays,
                              );
                              final isHoliday = holidayName != null;
                              final isSunday = HolidayData.isSunday(dateValue);
                              final isRed = isHoliday || isSunday;

                              final selected =
                                  _selectedDate != null &&
                                  dateIso(_selectedDate!) == key;
                              final today = DateTime.now();
                              final isToday =
                                  today.year == dateValue.year &&
                                  today.month == dateValue.month &&
                                  today.day == dateValue.day;

                              return InkWell(
                                borderRadius: BorderRadius.circular(8),
                                onTap: () => setState(() => _selectedDate = dateValue),
                                child: DecoratedBox(
                                  decoration: BoxDecoration(
                                    color: selected ? accent : Colors.transparent,
                                    borderRadius: BorderRadius.circular(8),
                                    border: isToday && !selected
                                        ? Border.all(color: accent, width: 1.5)
                                        : null,
                                  ),
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      // Nomor Tanggal (Merah untuk Minggu dan Hari Libur Nasional)
                                      Text(
                                        '$dateNum',
                                        style: TextStyle(
                                          color: selected
                                              ? Colors.white
                                              : (isRed ? dangerText : ink),
                                          fontWeight: selected || isToday || isRed
                                              ? FontWeight.w800
                                              : FontWeight.w600,
                                          fontSize: 13,
                                        ),
                                      ),

                                      // Badge / Pill Hari Libur Nasional (Merah seperti dalam foto)
                                      if (isHoliday) ...[
                                        const SizedBox(height: 2),
                                        Container(
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 2.5,
                                            vertical: 1,
                                          ),
                                          decoration: BoxDecoration(
                                            color: selected
                                                ? Colors.white.withOpacity(0.25)
                                                : dangerSoft,
                                            borderRadius: BorderRadius.circular(3),
                                            border: Border.all(
                                              color: selected
                                                  ? Colors.white
                                                  : dangerBorder,
                                              width: 0.5,
                                            ),
                                          ),
                                          child: Text(
                                            HolidayData.shortHolidayName(holidayName),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            textAlign: TextAlign.center,
                                            style: TextStyle(
                                              fontSize: 7.5,
                                              fontWeight: FontWeight.w700,
                                              color: selected ? Colors.white : dangerText,
                                            ),
                                          ),
                                        ),
                                      ],

                                      // Indikator Titik Agenda Ruangan
                                      if (hasEvents)
                                        Padding(
                                          padding: const EdgeInsets.only(top: 2),
                                          child: Wrap(
                                            spacing: 2,
                                            alignment: WrapAlignment.center,
                                            children: [
                                              for (final event in dayEvents.take(3))
                                                Container(
                                                  width: 5,
                                                  height: 5,
                                                  decoration: BoxDecoration(
                                                    color: selected
                                                        ? Colors.white
                                                        : _eventColor(event),
                                                    shape: BoxShape.circle,
                                                  ),
                                                ),
                                            ],
                                          ),
                                        ),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Detail Agenda & Libur pada Tanggal Terpilih
                  SectionCard(
                    title: _selectedDate == null
                        ? 'Agenda ${monthName(_month.month)}'
                        : 'Agenda ${formatDate(_selectedDate!)}',
                    trailing: Text(
                      '${selectedEvents.length} kegiatan',
                      style: const TextStyle(color: muted, fontSize: 12),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        // Jika tanggal yang dipilih adalah Hari Libur Nasional
                        if (selectedHoliday != null) ...[
                          Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: dangerSoft,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: dangerBorder),
                            ),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: danger.withOpacity(0.12),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(
                                    Icons.celebration_rounded,
                                    color: dangerText,
                                    size: 22,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        selectedHoliday,
                                        style: const TextStyle(
                                          fontWeight: FontWeight.w800,
                                          fontSize: 14,
                                          color: dangerText,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      const Text(
                                        'Hari Libur Nasional / Acara Nasional',
                                        style: TextStyle(
                                          fontSize: 11,
                                          color: dangerText,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],

                        // Daftar Kegiatan Ruangan
                        if (selectedEvents.isNotEmpty) ...[
                          for (final event in selectedEvents)
                            _CalendarEventTile(
                              data: asMap(event),
                              onTap: () => _showEventDetails(asMap(event)),
                            ),
                        ] else if (selectedHoliday == null) ...[
                          const EmptyState(
                            message: 'Tidak ada agenda ruangan atau hari libur pada tanggal ini.',
                          ),
                        ],
                      ],
                    ),
                  ),

                  // Daftar Hari Libur Nasional Bulan Ini (Jika Belum Memilih Tanggal Tertentu)
                  if (_selectedDate == null && monthHolidays.isNotEmpty) ...[
                    const SizedBox(height: 14),
                    SectionCard(
                      title: 'Libur Nasional Bulan Ini',
                      trailing: Text(
                        '${monthHolidays.length} hari libur',
                        style: const TextStyle(color: dangerText, fontSize: 12, fontWeight: FontWeight.w700),
                      ),
                      child: Column(
                        children: [
                          for (final holiday in monthHolidays)
                            _HolidayListTile(
                              dateStr: holiday.key,
                              name: holiday.value,
                              onTap: () {
                                final parts = holiday.key.split('-');
                                final dt = DateTime(
                                  int.parse(parts[0]),
                                  int.parse(parts[1]),
                                  int.parse(parts[2]),
                                );
                                setState(() => _selectedDate = dt);
                              },
                            ),
                        ],
                      ),
                    ),
                  ],

                  // Daftar Seluruh Agenda Bulan Ini
                  if (_selectedDate == null && events.isNotEmpty) ...[
                    const SizedBox(height: 14),
                    SectionCard(
                      title: 'Seluruh Agenda Bulan Ini',
                      child: Column(
                        children: [
                          for (final event in events)
                            _CalendarEventTile(
                              data: asMap(event),
                              onTap: () => _showEventDetails(asMap(event)),
                            ),
                        ],
                      ),
                    ),
                  ],
                ],
              ],
            ),
          ),
        );
      },
    );
  }

  /// Membangun tampilan daftar hasil pencarian agenda dan hari libur
  Widget _buildSearchResults({
    required List<dynamic> events,
    required Map<String, String> holidays,
  }) {
    final query = _searchQuery.toLowerCase();

    // 1. Cari di Hari Libur Nasional
    final matchedHolidays = HolidayData.searchHolidays(_searchQuery, _month.year);

    // 2. Cari di Agenda Ruangan
    final matchedEvents = events.where((event) {
      final ev = asMap(event);
      final title = ev['title']?.toString().toLowerCase() ?? '';
      final room = asMap(ev['room'])['name']?.toString().toLowerCase() ?? '';
      final organizer = ev['organizer_role']?.toString().toLowerCase() ?? '';
      final desc = ev['description']?.toString().toLowerCase() ?? '';
      return title.contains(query) ||
          room.contains(query) ||
          organizer.contains(query) ||
          desc.contains(query);
    }).toList();

    final totalCount = matchedHolidays.length + matchedEvents.length;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Row(
            children: [
              Text(
                'Hasil Pencarian untuk "$_searchQuery"',
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                  color: ink,
                ),
              ),
              const Spacer(),
              Text(
                '$totalCount ditemukan',
                style: const TextStyle(fontSize: 12, color: muted),
              ),
            ],
          ),
        ),
        if (totalCount == 0)
          const EmptyState(
            message: 'Tidak ada acara atau hari libur yang cocok dengan pencarian.',
          )
        else ...[
          // Tampilkan Libur Nasional yang cocok
          if (matchedHolidays.isNotEmpty) ...[
            SectionCard(
              title: 'Hari Libur Nasional',
              trailing: Text(
                '${matchedHolidays.length}',
                style: const TextStyle(color: dangerText, fontSize: 12, fontWeight: FontWeight.w700),
              ),
              child: Column(
                children: [
                  for (final holiday in matchedHolidays)
                    _HolidayListTile(
                      dateStr: holiday.key,
                      name: holiday.value,
                      onTap: () {
                        final parts = holiday.key.split('-');
                        final dt = DateTime(
                          int.parse(parts[0]),
                          int.parse(parts[1]),
                          int.parse(parts[2]),
                        );
                        _selectDateAndMonth(dt);
                      },
                    ),
                ],
              ),
            ),
            const SizedBox(height: 12),
          ],

          // Tampilkan Agenda Ruangan yang cocok
          if (matchedEvents.isNotEmpty) ...[
            SectionCard(
              title: 'Agenda Ruangan Sekolah',
              trailing: Text(
                '${matchedEvents.length}',
                style: const TextStyle(color: muted, fontSize: 12),
              ),
              child: Column(
                children: [
                  for (final event in matchedEvents)
                    _CalendarEventTile(
                      data: asMap(event),
                      onTap: () {
                        final ev = asMap(event);
                        final dateStr = ev['date']?.toString();
                        if (dateStr != null && dateStr.length >= 10) {
                          final dt = DateTime.parse(dateStr.substring(0, 10));
                          _selectDateAndMonth(dt);
                        }
                        _showEventDetails(ev);
                      },
                    ),
                ],
              ),
            ),
          ],
        ],
      ],
    );
  }

  Widget _wrapPage(Widget body) {
    if (widget.isEmbedded || widget.onLogin == null) {
      return BrandWatermark(child: body);
    }
    return Scaffold(
      backgroundColor: canvas,
      appBar: AppBar(
        title: const Row(
          children: [
            BrandLogo(size: 32),
            SizedBox(width: 8),
            Text(
              'RAPI-Sekolah',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 18,
                color: Colors.white,
              ),
            ),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: TextButton.icon(
              onPressed: widget.onLogin,
              icon: const Icon(Icons.login),
              label: const Text('Masuk'),
            ),
          ),
        ],
      ),
      body: BrandWatermark(child: body),
    );
  }
}

/// Widget baris untuk daftar Hari Libur Nasional
class _HolidayListTile extends StatelessWidget {
  const _HolidayListTile({
    required this.dateStr,
    required this.name,
    required this.onTap,
  });

  final String dateStr;
  final String name;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final parts = dateStr.split('-');
    final dt = DateTime(
      int.parse(parts[0]),
      int.parse(parts[1]),
      int.parse(parts[2]),
    );

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
        child: Row(
          children: [
            Container(
              width: 4,
              height: 40,
              decoration: BoxDecoration(
                color: danger,
                borderRadius: BorderRadius.circular(3),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 13.5,
                      color: dangerText,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${formatDate(dt)} · Ketuk untuk langsung diarahkan ➜',
                    style: const TextStyle(
                      fontSize: 11.5,
                      color: dangerText,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.arrow_forward_ios, size: 14, color: muted),
          ],
        ),
      ),
    );
  }
}

class _CalendarEventTile extends StatelessWidget {
  const _CalendarEventTile({required this.data, required this.onTap});

  final Map<String, dynamic> data;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final room = asMap(data['room']);
    final isOvernight = eventEndsNextDay(data);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 4),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 4,
              height: 44,
              decoration: BoxDecoration(
                color: calendarEventColor(data['organizer_role']?.toString()),
                borderRadius: BorderRadius.circular(3),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    data['title']?.toString() ?? 'Agenda',
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                      color: ink,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${room['name'] ?? 'Ruangan'} · ${eventDateRange(data)} · ${shortTime(data['start_time'])}–${shortTime(data['end_time'])}${isOvernight && data['end_date'] == null ? ' (+1)' : ''}',
                    style: const TextStyle(fontSize: 12, color: muted),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, size: 20, color: muted),
          ],
        ),
      ),
    );
  }
}

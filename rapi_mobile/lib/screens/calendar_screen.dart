import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class CalendarScreen extends StatefulWidget {
  const CalendarScreen({
    required this.api,
    this.onLogin,
    this.currentUser,
    this.onReportDamage,
    super.key,
  });

  final ApiService api;
  final VoidCallback? onLogin;
  final Map<String, dynamic>? currentUser;
  final ValueChanged<Map<String, dynamic>>? onReportDamage;

  @override
  State<CalendarScreen> createState() => _CalendarScreenState();
}

class _CalendarScreenState extends State<CalendarScreen> {
  late DateTime _month;
  DateTime? _selectedDate;
  late Future<Map<String, dynamic>> _calendar;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _month = DateTime(now.year, now.month);
    _selectedDate = DateTime(now.year, now.month, now.day);
    _calendar = _loadMonth();
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

        final firstOffset = DateTime(_month.year, _month.month).weekday - 1;
        final days = DateTime(_month.year, _month.month + 1, 0).day;
        final selectedEvents = _selectedDate == null
            ? <dynamic>[]
            : eventsByDate[dateIso(_selectedDate!)] ?? <dynamic>[];

        return _wrapPage(
          RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
              children: [
                PageHeading(
                  title: 'Kalender Agenda',
                  subtitle: 'Agenda ruangan sekolah yang telah disetujui.',
                ),
                Wrap(
                  spacing: 14,
                  runSpacing: 8,
                  children: [
                    for (final entry in [
                      ('Guru', calendarEventColor('guru')),
                      ('Organisasi', calendarEventColor('organisasi')),
                      ('Admin', calendarEventColor('admin')),
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
                const SizedBox(height: 8),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      children: [
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
                                  fontSize: 17,
                                  color: ink,
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
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            for (final day in [
                              'Sen',
                              'Sel',
                              'Rab',
                              'Kam',
                              'Jum',
                              'Sab',
                              'Min',
                            ])
                              Expanded(
                                child: Center(
                                  child: Text(
                                    day,
                                    style: const TextStyle(
                                      color: muted,
                                      fontSize: 11,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        GridView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: firstOffset + days,
                          gridDelegate:
                              const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 7,
                                mainAxisSpacing: 4,
                                crossAxisSpacing: 4,
                                childAspectRatio: 0.95,
                              ),
                          itemBuilder: (context, index) {
                            if (index < firstOffset) {
                              return const SizedBox.shrink();
                            }
                            final date = index - firstOffset + 1;
                            final value = DateTime(
                              _month.year,
                              _month.month,
                              date,
                            );
                            final key = dateIso(value);
                            final dayEvents = eventsByDate[key] ?? [];
                            final hasEvents = dayEvents.isNotEmpty;
                            final selected =
                                _selectedDate != null &&
                                dateIso(_selectedDate!) == key;
                            final today = DateTime.now();
                            final isToday =
                                today.year == value.year &&
                                today.month == value.month &&
                                today.day == value.day;

                            return InkWell(
                              borderRadius: BorderRadius.circular(8),
                              onTap: () =>
                                  setState(() => _selectedDate = value),
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
                                    Text(
                                      '$date',
                                      style: TextStyle(
                                        color: selected ? Colors.white : ink,
                                        fontWeight: selected || isToday
                                            ? FontWeight.w800
                                            : FontWeight.normal,
                                      ),
                                    ),
                                    if (hasEvents)
                                      Padding(
                                        padding: const EdgeInsets.only(top: 2),
                                        child: Wrap(
                                          spacing: 2,
                                          children: [
                                            for (final event in dayEvents.take(
                                              3,
                                            ))
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
                SectionCard(
                  title: _selectedDate == null
                      ? 'Agenda ${monthName(_month.month)}'
                      : 'Agenda ${formatDate(_selectedDate!)}',
                  trailing: Text(
                    '${selectedEvents.length} kegiatan',
                    style: const TextStyle(color: muted, fontSize: 12),
                  ),
                  child: selectedEvents.isEmpty
                      ? const EmptyState(
                          message: 'Tidak ada agenda ruangan pada tanggal ini.',
                        )
                      : Column(
                          children: [
                            for (final event in selectedEvents)
                              _CalendarEventTile(
                                data: asMap(event),
                                onTap: () => _showEventDetails(asMap(event)),
                              ),
                          ],
                        ),
                ),
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
            ),
          ),
        );
      },
    );
  }

  Widget _wrapPage(Widget body) {
    if (widget.onLogin == null) return body;
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

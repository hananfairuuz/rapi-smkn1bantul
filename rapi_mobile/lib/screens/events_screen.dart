import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class EventsScreen extends StatefulWidget {
  const EventsScreen({
    required this.api,
    required this.user,
    this.onReportDamage,
    super.key,
  });

  final ApiService api;
  final Map<String, dynamic> user;
  final ValueChanged<Map<String, dynamic>>? onReportDamage;

  @override
  State<EventsScreen> createState() => _EventsScreenState();
}

class _EventsScreenState extends State<EventsScreen> {
  late Future<List<dynamic>> _events;
  String _filter = 'all';
  String _search = '';
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _events = widget.api.getEvents();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  bool _isUpcoming(Map<String, dynamic> event, DateTime now) {
    if (event['status'] != 'approved') return false;
    final date = DateTime.tryParse(event['date']?.toString() ?? '');
    if (date == null) return false;
    final today = DateTime(now.year, now.month, now.day);
    final lastUpcomingDay = today
        .add(const Duration(days: 4))
        .subtract(const Duration(microseconds: 1));
    final startDay = DateTime(date.year, date.month, date.day);
    final startTime = event['start_time']?.toString() ?? '00:00';
    final timeParts = startTime.split(':');
    final startAt = DateTime(
      date.year,
      date.month,
      date.day,
      int.tryParse(timeParts.first) ?? 0,
      timeParts.length > 1 ? int.tryParse(timeParts[1]) ?? 0 : 0,
    );
    return !startAt.isBefore(now) &&
        !startDay.isBefore(today) &&
        !startAt.isAfter(lastUpcomingDay);
  }

  bool _isToday(Map<String, dynamic> event, DateTime now) {
    if (event['status'] != 'approved') return false;
    final startDate = DateTime.tryParse(event['date']?.toString() ?? '');
    if (startDate == null) return false;
    final endDate =
        DateTime.tryParse(event['end_date']?.toString() ?? '') ?? startDate;
    final today = DateTime(now.year, now.month, now.day);
    var effectiveEndDate = DateTime(endDate.year, endDate.month, endDate.day);
    final endTimeParts = (event['end_time']?.toString() ?? '00:00').split(':');
    final startTimeParts = (event['start_time']?.toString() ?? '00:00').split(
      ':',
    );
    final endMinutes =
        (int.tryParse(endTimeParts.first) ?? 0) * 60 +
        (endTimeParts.length > 1 ? int.tryParse(endTimeParts[1]) ?? 0 : 0);
    final startMinutes =
        (int.tryParse(startTimeParts.first) ?? 0) * 60 +
        (startTimeParts.length > 1 ? int.tryParse(startTimeParts[1]) ?? 0 : 0);
    if (event['end_date'] == null && endMinutes < startMinutes) {
      effectiveEndDate = DateTime(
        startDate.year,
        startDate.month,
        startDate.day,
      ).add(const Duration(days: 1));
    }
    return !today.isBefore(
          DateTime(startDate.year, startDate.month, startDate.day),
        ) &&
        !today.isAfter(effectiveEndDate);
  }

  String _searchableText(Map<String, dynamic> event) {
    final room = asMap(event['room']);
    return [
      event['title'],
      event['description'],
      event['borrower_name'],
      room['name'],
    ].map((value) => value?.toString() ?? '').join(' ').toLowerCase();
  }

  Future<void> _reload() async {
    final next = widget.api.getEvents();
    setState(() => _events = next);
    await next;
  }

  Future<void> _createEvent() async {
    final created = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => EventForm(api: widget.api, user: widget.user),
    );
    if (created == true) {
      await _reload();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Pengajuan agenda berhasil dikirim dan menunggu persetujuan.',
            ),
          ),
        );
      }
    }
  }

  bool _canDeleteEvent(Map<String, dynamic> event) {
    return event['can_delete'] == true;
  }

  Future<void> _deleteEvent(Map<String, dynamic> event) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus agenda?'),
        content: Text(
          'Agenda "${event['title'] ?? 'Agenda'}" akan dihapus permanen.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: danger),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await widget.api.deleteEvent(event['id'] as int);
      await _reload();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Agenda berhasil dihapus.')),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal menghapus agenda: $error')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _createEvent,
        backgroundColor: accent,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add),
        label: const Text('Ajukan agenda'),
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _events,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorState(error: snapshot.error, onRetry: _reload);
          }
          if (!snapshot.hasData) return const LoadingState();

          final allEvents = snapshot.data!;
          final now = DateTime.now();
          final filteredEvents = allEvents.where((e) {
            final event = asMap(e);
            final status = event['status']?.toString();
            final matchesFilter = switch (_filter) {
              'upcoming' => _isUpcoming(event, now),
              'current' => status == 'approved',
              'today' => _isToday(event, now),
              'pending' => status == 'pending',
              'rejected' => status == 'rejected',
              _ => true,
            };
            return matchesFilter &&
                _searchableText(event).contains(_search.toLowerCase());
          }).toList();

          return RefreshIndicator(
            onRefresh: _reload,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
              children: [
                const PageHeading(
                  title: 'Agenda Ruangan',
                  subtitle:
                      'Cari agenda berdasarkan waktu dan status persetujuannya. Agenda disetujui otomatis ditandai terlaksana setelah selesai.',
                ),
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    labelText: 'Cari agenda',
                    hintText: 'Judul, ruangan, atau peminjam',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _search.isEmpty
                        ? null
                        : IconButton(
                            tooltip: 'Hapus pencarian',
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _search = '');
                            },
                            icon: const Icon(Icons.clear),
                          ),
                    border: const OutlineInputBorder(),
                  ),
                  onChanged: (value) => setState(() => _search = value.trim()),
                ),
                const SizedBox(height: 12),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _FilterChip(
                        label: 'Semua (${allEvents.length})',
                        selected: _filter == 'all',
                        onSelected: () => setState(() => _filter = 'all'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Mendatang (${allEvents.where((e) => _isUpcoming(asMap(e), now)).length})',
                        selected: _filter == 'upcoming',
                        onSelected: () => setState(() => _filter = 'upcoming'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Terkini · Disetujui (${allEvents.where((e) => asMap(e)['status'] == 'approved').length})',
                        selected: _filter == 'current',
                        onSelected: () => setState(() => _filter = 'current'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Hari ini (${allEvents.where((e) => _isToday(asMap(e), now)).length})',
                        selected: _filter == 'today',
                        onSelected: () => setState(() => _filter = 'today'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Pending · Belum disetujui (${allEvents.where((e) => asMap(e)['status'] == 'pending').length})',
                        selected: _filter == 'pending',
                        onSelected: () => setState(() => _filter = 'pending'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Ditolak (${allEvents.where((e) => asMap(e)['status'] == 'rejected').length})',
                        selected: _filter == 'rejected',
                        onSelected: () => setState(() => _filter = 'rejected'),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                if (filteredEvents.isEmpty)
                  const SectionCard(
                    title: 'Daftar Agenda',
                    child: EmptyState(
                      message: 'Tidak ada agenda pada kategori ini.',
                    ),
                  )
                else
                  for (final event in filteredEvents)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: EventCard(
                        data: asMap(event),
                        canDelete: _canDeleteEvent(asMap(event)),
                        onDelete: () => _deleteEvent(asMap(event)),
                        onTap: () {
                          showEventDetailSheet(
                            context: context,
                            event: asMap(event),
                            onReportDamage:
                                asMap(event)['status'] == 'approved' &&
                                    widget.onReportDamage != null
                                ? () => widget.onReportDamage!(asMap(event))
                                : null,
                          );
                        },
                        onReportDamage:
                            asMap(event)['status'] == 'approved' &&
                                widget.onReportDamage != null
                            ? () => widget.onReportDamage!(asMap(event))
                            : null,
                      ),
                    ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.selected,
    required this.onSelected,
  });

  final String label;
  final bool selected;
  final VoidCallback onSelected;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(
      label: Text(
        label,
        style: TextStyle(
          fontSize: 12,
          fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
          color: selected ? Colors.white : ink,
        ),
      ),
      selected: selected,
      selectedColor: accent,
      backgroundColor: surface,
      side: BorderSide(color: selected ? accent : line),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      onSelected: (_) => onSelected(),
    );
  }
}

class EventCard extends StatelessWidget {
  const EventCard({
    required this.data,
    this.onTap,
    this.onReportDamage,
    this.canDelete = false,
    this.onDelete,
    super.key,
  });

  final Map<String, dynamic> data;
  final VoidCallback? onTap;
  final VoidCallback? onReportDamage;
  final bool canDelete;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    final room = asMap(data['room']);
    final isOvernight = eventEndsNextDay(data);
    final status = data['status']?.toString() ?? 'pending';

    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      data['title']?.toString() ?? 'Agenda',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: ink,
                      ),
                    ),
                  ),
                  StatusBadge(status: status),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(
                    Icons.meeting_room_outlined,
                    size: 16,
                    color: muted,
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      '${room['name'] ?? 'Ruangan'}${room['location'] != null && room['location'].toString().isNotEmpty ? ' (${room['location']})' : ''}',
                      style: const TextStyle(color: muted, fontSize: 13),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.access_time, size: 16, color: muted),
                  const SizedBox(width: 6),
                  Text(
                    '${eventDateRange(data)} · ${shortTime(data['start_time'])}–${shortTime(data['end_time'])}${isOvernight && data['end_date'] == null ? ' (+1 hari)' : ''}',
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                      color: ink,
                    ),
                  ),
                ],
              ),
              if (status == 'approved') ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(
                      Icons.check_circle_outline,
                      size: 16,
                      color: success,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      'Pelaksanaan: ${data['execution_status'] ?? 'Belum terlaksana'}',
                      style: const TextStyle(
                        color: successText,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ],
              if (data['borrower_name'] != null &&
                  data['borrower_name'].toString().isNotEmpty) ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.person_outline, size: 16, color: muted),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        'Peminjam: ${data['borrower_name']}${data['borrower_whatsapp'] != null ? ' (${data['borrower_whatsapp']})' : ''}',
                        style: const TextStyle(color: muted, fontSize: 12),
                      ),
                    ),
                  ],
                ),
              ],
              if (data['description'] != null &&
                  data['description'].toString().trim().isNotEmpty) ...[
                const Divider(height: 18),
                Text(
                  data['description'].toString(),
                  style: const TextStyle(color: ink, fontSize: 13),
                ),
              ],
              if (data['move_reason'] != null &&
                  data['move_reason'].toString().trim().isNotEmpty) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: warningSoft,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info, color: warningText, size: 16),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          'Ruangan dipindahkan: ${data['move_reason']}',
                          style: const TextStyle(
                            color: warningText,
                            fontSize: 12,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              if (status == 'rejected' &&
                  data['rejection_reason'] != null &&
                  data['rejection_reason'].toString().trim().isNotEmpty) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: dangerSoft,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.cancel, color: dangerText, size: 16),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          'Alasan penolakan: ${data['rejection_reason']}',
                          style: const TextStyle(
                            color: dangerText,
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              if (status == 'approved' && onReportDamage != null) ...[
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: warningText,
                      side: const BorderSide(color: accent),
                    ),
                    onPressed: onReportDamage,
                    icon: const Icon(Icons.report_problem_outlined, size: 18),
                    label: const Text('Catat Barang Rusak'),
                  ),
                ),
              ],
              if (canDelete && onDelete != null) ...[
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(foregroundColor: danger),
                    onPressed: onDelete,
                    icon: const Icon(Icons.delete_outline, size: 18),
                    label: const Text('Hapus agenda'),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class EventForm extends StatefulWidget {
  const EventForm({required this.api, required this.user, super.key});

  final ApiService api;
  final Map<String, dynamic> user;

  @override
  State<EventForm> createState() => _EventFormState();
}

class _EventFormState extends State<EventForm> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _borrower = TextEditingController();
  final _whatsapp = TextEditingController();
  late DateTime _date;
  late DateTime _endDate;
  List<dynamic> _rooms = [];
  int? _roomId;
  Map<String, dynamic> _periods = {};
  String _startLesson = '1';
  String _endLesson = '2';
  TimeOfDay _startCustomTime = const TimeOfDay(hour: 17, minute: 45);
  TimeOfDay _endCustomTime = const TimeOfDay(hour: 6, minute: 0);
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final minimumDate = DateUtils.dateOnly(
      DateTime.now().add(const Duration(days: 1)),
    );
    _date = minimumDate;
    _endDate = minimumDate;
    _borrower.text = widget.user['name']?.toString() ?? '';
    _whatsapp.text = widget.user['whatsapp']?.toString() ?? '';
    _loadOptions();
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _borrower.dispose();
    _whatsapp.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    try {
      final results = await Future.wait([
        widget.api.getRooms(),
        widget.api.getLessonPeriods(),
      ]);
      if (!mounted) return;
      setState(() {
        _rooms = results[0] as List<dynamic>;
        _periods = asMap((results[1] as Map<String, dynamic>)['periods']);
        _roomId = _rooms.isEmpty ? null : asMap(_rooms.first)['id'] as int;
        _loading = false;
      });
    } catch (error) {
      if (mounted) {
        setState(() {
          _error = error.toString();
          _loading = false;
        });
      }
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateUtils.dateOnly(
        DateTime.now().add(const Duration(days: 1)),
      ),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() {
        _date = picked;
        if (_endDate.isBefore(_date)) _endDate = _date;
      });
    }
  }

  Future<void> _pickEndDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _endDate.isBefore(_date) ? _date : _endDate,
      firstDate: _date,
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _endDate = picked);
  }

  Future<void> _pickCustomTime({required bool start}) async {
    final picked = await showTimePicker(
      context: context,
      initialTime: start ? _startCustomTime : _endCustomTime,
    );
    if (picked == null) return;
    setState(() {
      if (start) {
        _startCustomTime = picked;
      } else {
        _endCustomTime = picked;
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _roomId == null) return;
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final fields = <String, String>{
        'room_id': '$_roomId',
        'title': _title.text.trim(),
        'borrower_name': _borrower.text.trim(),
        'borrower_whatsapp': _whatsapp.text.trim(),
        'description': _description.text.trim(),
        'date': dateIso(_date),
        'end_date': dateIso(_endDate),
        'start_lesson': _startLesson,
        'end_lesson': _endLesson,
      };
      if (_startLesson == 'custom') {
        fields['start_custom_time'] = formatTimeOfDay(_startCustomTime);
      }
      if (_endLesson == 'custom') {
        fields['end_custom_time'] = formatTimeOfDay(_endCustomTime);
      }
      await widget.api.createEvent(fields);
      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(
        20,
        16,
        20,
        MediaQuery.viewInsetsOf(context).bottom + 20,
      ),
      child: _loading
          ? const SizedBox(height: 220, child: LoadingState())
          : Form(
              key: _formKey,
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Ajukan Agenda Ruangan',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: ink,
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(context),
                        ),
                      ],
                    ),
                    const Text(
                      'Peminjaman diajukan paling lambat H-1. Pengajuan lebih dari satu hari tetap menunggu persetujuan admin.',
                      style: TextStyle(color: muted, fontSize: 12),
                    ),
                    const SizedBox(height: 14),
                    if (_error != null) ...[
                      ErrorBanner(message: _error!),
                      const SizedBox(height: 12),
                    ],
                    if (_rooms.isEmpty)
                      const ErrorBanner(
                        message: 'Belum ada ruangan yang tersedia.',
                      )
                    else
                      DropdownButtonFormField<int>(
                        value: _roomId,
                        decoration: const InputDecoration(labelText: 'Ruangan'),
                        items: [
                          for (final room in _rooms)
                            DropdownMenuItem(
                              value: asMap(room)['id'] as int,
                              child: Text(
                                '${asMap(room)['name']}${asMap(room)['location'] != null ? ' — ${asMap(room)['location']}' : ''}',
                              ),
                            ),
                        ],
                        onChanged: (value) => setState(() => _roomId = value),
                      ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _title,
                      decoration: const InputDecoration(
                        labelText: 'Judul Agenda',
                        hintText: 'Contoh: Rapat OSIS, Praktik Kimia',
                      ),
                      validator: requiredValidator,
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: _pickDate,
                      icon: const Icon(Icons.calendar_today_outlined, size: 18),
                      label: Text('Tanggal: ${formatDate(_date)}'),
                    ),
                    const SizedBox(height: 8),
                    OutlinedButton.icon(
                      onPressed: _pickEndDate,
                      icon: const Icon(Icons.event_outlined, size: 18),
                      label: Text('Tanggal selesai: ${formatDate(_endDate)}'),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _startLesson,
                            decoration: const InputDecoration(
                              labelText: 'Jam Mulai',
                            ),
                            items: _periodOptions(),
                            onChanged: (value) => setState(() {
                              if (value != null) {
                                _startLesson = value;
                                if (value == 'custom') {
                                  _endLesson = 'custom';
                                } else if (_endLesson != 'custom' &&
                                    int.parse(_endLesson) < int.parse(value)) {
                                  _endLesson = value;
                                }
                              }
                            }),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _endLesson,
                            decoration: const InputDecoration(
                              labelText: 'Jam Selesai',
                            ),
                            items: _periodOptions(),
                            onChanged: (value) => setState(() {
                              if (value != null &&
                                  (_startLesson != 'custom' ||
                                      value == 'custom')) {
                                _endLesson = value;
                              }
                            }),
                          ),
                        ),
                      ],
                    ),
                    if (_startLesson == 'custom' || _endLesson == 'custom') ...[
                      const SizedBox(height: 8),
                      if (_startLesson == 'custom')
                        OutlinedButton.icon(
                          onPressed: () => _pickCustomTime(start: true),
                          icon: const Icon(Icons.schedule, size: 18),
                          label: Text(
                            'Mulai Bebas: ${formatTimeOfDay(_startCustomTime)}',
                          ),
                        ),
                      if (_endLesson == 'custom')
                        OutlinedButton.icon(
                          onPressed: () => _pickCustomTime(start: false),
                          icon: const Icon(Icons.schedule, size: 18),
                          label: Text(
                            'Selesai Bebas: ${formatTimeOfDay(_endCustomTime)}',
                          ),
                        ),
                      const SizedBox(height: 4),
                      const Text(
                        'Jam bebas berlaku setelah pukul 17.45 hingga pukul 06.00 hari berikutnya.',
                        style: TextStyle(color: muted, fontSize: 11),
                      ),
                    ],
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _borrower,
                      decoration: const InputDecoration(
                        labelText: 'Nama Peminjam Ruangan',
                      ),
                      validator: requiredValidator,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _whatsapp,
                      keyboardType: TextInputType.phone,
                      decoration: const InputDecoration(
                        labelText: 'Nomor WhatsApp Peminjam',
                        hintText: '62812...',
                      ),
                      validator: (value) =>
                          value == null ||
                              !RegExp(
                                r'^\+?[0-9]{8,20}$',
                              ).hasMatch(value.trim())
                          ? 'Masukkan nomor WhatsApp yang valid.'
                          : null,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _description,
                      minLines: 2,
                      maxLines: 4,
                      decoration: const InputDecoration(
                        labelText: 'Keterangan Agenda (Opsional)',
                      ),
                    ),
                    const SizedBox(height: 18),
                    FilledButton(
                      style: FilledButton.styleFrom(
                        backgroundColor: accent,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed: _saving || _rooms.isEmpty ? null : _submit,
                      child: _saving
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text(
                              'Kirim Pengajuan Agenda',
                              style: TextStyle(fontWeight: FontWeight.w700),
                            ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  List<DropdownMenuItem<String>> _periodOptions() {
    return [
      for (final entry in _periods.entries)
        DropdownMenuItem(
          value: entry.key,
          child: Text(
            'Jam ke-${entry.key} (${asMap(entry.value)['start']}–${asMap(entry.value)['end']})',
            style: const TextStyle(fontSize: 13),
          ),
        ),
      const DropdownMenuItem(
        value: 'custom',
        child: Text('Jam bebas', style: TextStyle(fontSize: 13)),
      ),
    ];
  }
}

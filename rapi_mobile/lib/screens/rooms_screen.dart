import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class RoomsScreen extends StatefulWidget {
  const RoomsScreen({required this.api, super.key});

  final ApiService api;

  @override
  State<RoomsScreen> createState() => _RoomsScreenState();
}

class _RoomsScreenState extends State<RoomsScreen> {
  late Future<List<dynamic>> _rooms;
  final _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _rooms = widget.api.getRooms();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    final next = widget.api.getRooms();
    setState(() => _rooms = next);
    await next;
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<dynamic>>(
      future: _rooms,
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return ErrorState(error: snapshot.error, onRetry: _reload);
        }
        if (!snapshot.hasData) return const LoadingState();

        final allRooms = snapshot.data!;
        final filteredRooms = allRooms.where((room) {
          final map = asMap(room);
          final name = map['name']?.toString().toLowerCase() ?? '';
          final location = map['location']?.toString().toLowerCase() ?? '';
          final q = _searchQuery.toLowerCase();
          return name.contains(q) || location.contains(q);
        }).toList();

        return RefreshIndicator(
          onRefresh: _reload,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const PageHeading(
                title: 'Ruangan Tugas Saya',
                subtitle:
                    'Daftar fasilitas ruangan yang menjadi tanggung jawab Anda.',
              ),
              TextField(
                controller: _searchController,
                decoration: InputDecoration(
                  hintText: 'Cari nama atau lokasi ruangan...',
                  prefixIcon: const Icon(Icons.search, size: 20),
                  suffixIcon: _searchQuery.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 18),
                          onPressed: () {
                            _searchController.clear();
                            setState(() => _searchQuery = '');
                          },
                        )
                      : null,
                ),
                onChanged: (val) => setState(() => _searchQuery = val.trim()),
              ),
              const SizedBox(height: 16),
              if (filteredRooms.isEmpty)
                EmptyState(
                  message: _searchQuery.isEmpty
                      ? 'Belum ada ruangan yang ditugaskan kepada Anda.'
                      : 'Tidak ada ruangan yang cocok dengan pencarian.',
                )
              else
                for (final item in filteredRooms)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: accentSoft,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(
                                Icons.meeting_room_outlined,
                                color: accentHover,
                                size: 24,
                              ),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          asMap(item)['name']?.toString() ??
                                              'Ruangan',
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w800,
                                            fontSize: 16,
                                            color: ink,
                                          ),
                                        ),
                                      ),
                                      Container(
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 8,
                                          vertical: 3,
                                        ),
                                        decoration: BoxDecoration(
                                          color: successSoft,
                                          borderRadius: BorderRadius.circular(
                                            6,
                                          ),
                                        ),
                                        child: const Text(
                                          'PIC AKTIF',
                                          style: TextStyle(
                                            color: successText,
                                            fontSize: 10,
                                            fontWeight: FontWeight.w800,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  if (asMap(item)['location'] != null &&
                                      asMap(
                                        item,
                                      )['location'].toString().isNotEmpty) ...[
                                    const SizedBox(height: 4),
                                    Row(
                                      children: [
                                        const Icon(
                                          Icons.place_outlined,
                                          size: 14,
                                          color: muted,
                                        ),
                                        const SizedBox(width: 4),
                                        Text(
                                          asMap(item)['location'].toString(),
                                          style: const TextStyle(
                                            color: muted,
                                            fontSize: 12,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                  if (asMap(item)['description'] != null &&
                                      asMap(item)['description']
                                          .toString()
                                          .isNotEmpty) ...[
                                    const SizedBox(height: 8),
                                    Text(
                                      asMap(item)['description'].toString(),
                                      style: const TextStyle(
                                        color: ink,
                                        fontSize: 13,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
            ],
          ),
        );
      },
    );
  }
}

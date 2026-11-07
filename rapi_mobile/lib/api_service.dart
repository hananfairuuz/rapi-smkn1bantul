import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import 'constants.dart';

class ApiException implements Exception {
  const ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

class ApiService {
  ApiService()
    : _baseUrl = const String.fromEnvironment(
        'API_BASE_URL',
        defaultValue: 'https://rapi-smkn1bantul.web.id/api',
      ) {
    final apiUri = Uri.tryParse(_baseUrl);
    if (kReleaseMode &&
        (apiUri == null ||
            apiUri.scheme != 'https' ||
            apiUri.host.isEmpty ||
            apiUri.host == 'localhost' ||
            apiUri.host == '127.0.0.1' ||
            apiUri.host == '::1')) {
      throw StateError(
        'Build release memerlukan API_BASE_URL berupa URL HTTPS produksi.',
      );
    }
  }

  static const _storage = FlutterSecureStorage();
  final String _baseUrl;
  String? _token;

  Future<String?> restoreToken() async {
    _token = await _storage.read(key: 'api_token');
    return _token;
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _send(
      () => http.post(
        _uri('/login'),
        headers: {'Accept': 'application/json'},
        body: {'email': email.trim(), 'password': password},
      ),
    );
    final data = _decode(response);
    final user = Map<String, dynamic>.from(data['user'] as Map);
    _token = data['token'] as String?;
    if (_token == null) {
      throw const ApiException('Server tidak mengembalikan token masuk.');
    }
    await _storage.write(key: 'api_token', value: _token);
    return user;
  }

  Future<void> requestPasswordReset(String email) async {
    await _request('POST', '/password/forgot', body: {'email': email.trim()});
  }

  Future<void> verifyPasswordResetCode(String email, String code) async {
    await _request(
      'POST',
      '/password/verify-code',
      body: {'email': email.trim(), 'code': code.trim()},
    );
  }

  Future<void> resetPassword(
    String email,
    String password,
    String confirmation,
  ) async {
    await _request(
      'POST',
      '/password/reset',
      body: {
        'email': email.trim(),
        'password': password,
        'password_confirmation': confirmation,
      },
    );
  }

  Future<void> logout() async {
    try {
      await _request('POST', '/logout');
    } finally {
      _token = null;
      await _storage.delete(key: 'api_token');
    }
  }

  Future<Map<String, dynamic>> getUser() async {
    final data = await _request('GET', '/user');
    final user = Map<String, dynamic>.from(data['user'] as Map);
    if (user['role'] == 'admin') {
      await logout();
      throw const ApiException(
        'Akun Admin hanya dapat masuk melalui versi desktop.',
      );
    }
    return user;
  }

  Future<Map<String, dynamic>> updateProfile(Map<String, String> fields) async {
    final data = await _request('PUT', '/profile', body: fields);
    return Map<String, dynamic>.from(data['user'] as Map);
  }

  Future<Map<String, dynamic>> getDashboard() => _request('GET', '/dashboard');

  Future<Map<String, dynamic>> getCalendar(String month) =>
      _request('GET', '/calendar', query: {'month': month});

  Future<List<dynamic>> getRooms() async {
    final data = await _request('GET', '/rooms');
    return List<dynamic>.from(data['rooms'] as List);
  }

  Future<Map<String, dynamic>> createRoom(Map<String, dynamic> body) =>
      _request('POST', '/rooms', body: body);

  Future<Map<String, dynamic>> updateRoom(int id, Map<String, dynamic> body) =>
      _request('PUT', '/rooms/$id', body: body);

  Future<void> deleteRoom(int id) => _request('DELETE', '/rooms/$id');

  Future<Map<String, dynamic>> getLessonPeriods() =>
      _request('GET', '/lesson-periods');

  Future<Map<String, dynamic>> getDamagedGoodsReport(
    String periodType,
    DateTime date,
  ) {
    final query = periodType == 'month'
        ? {
            'period_type': periodType,
            'selected_month': '${date.year}-${twoDigits(date.month)}',
          }
        : {
            'period_type': periodType,
            'date':
                '${date.year}-${twoDigits(date.month)}-${twoDigits(date.day)}',
          };
    return _request('GET', '/reports/damaged-goods', query: query);
  }

  Future<Uint8List> getDamagedGoodsReportDocx(
    String periodType,
    DateTime date,
  ) async {
    final query = periodType == 'month'
        ? {
            'period_type': periodType,
            'selected_month': '${date.year}-${twoDigits(date.month)}',
          }
        : {
            'period_type': periodType,
            'date':
                '${date.year}-${twoDigits(date.month)}-${twoDigits(date.day)}',
          };
    final response = await _send(
      () => http.get(
        _uri('/reports/damaged-goods/docx', query: query),
        headers: _headers(),
      ),
    );
    if (response.statusCode < 200 || response.statusCode >= 300) {
      _decode(response);
    }
    if (response.bodyBytes.length < 2 ||
        response.bodyBytes[0] != 0x50 ||
        response.bodyBytes[1] != 0x4b) {
      throw const ApiException('Server tidak mengembalikan berkas DOCX.');
    }
    return response.bodyBytes;
  }

  Future<List<dynamic>> getEvents() async {
    final data = await _request('GET', '/events');
    return List<dynamic>.from(data['events'] as List);
  }

  Future<List<dynamic>> getComplaints() async {
    final data = await _request('GET', '/complaints');
    return List<dynamic>.from(data['complaints'] as List);
  }

  Future<Map<String, dynamic>> createEvent(Map<String, String> fields) =>
      _request('POST', '/events', body: fields);

  Future<void> deleteEvent(int id) async {
    await _request('DELETE', '/events/$id');
  }

  Future<Map<String, dynamic>> checkoutEvent(
    int id, {
    required String condition,
    String? notes,
  }) =>
      _request(
        'POST',
        '/events/$id/checkout',
        body: {
          'checkout_condition': condition,
          if (notes != null && notes.isNotEmpty) 'checkout_notes': notes,
        },
      );

  Future<Map<String, dynamic>> getEventReceipt(int id) =>
      _request('GET', '/events/$id/receipt');

  Future<Map<String, dynamic>> createComplaint(
    Map<String, String> fields, {
    String? photoPath,
  }) async {
    final request = http.MultipartRequest('POST', _uri('/complaints'))
      ..headers.addAll(_headers())
      ..fields.addAll(fields);
    if (photoPath != null) {
      request.files.add(await http.MultipartFile.fromPath('photo', photoPath));
    }
    final streamedResponse = await _send(request.send);
    final response = await http.Response.fromStream(streamedResponse);
    return _decode(response);
  }

  Future<Map<String, dynamic>> reviewComplaint(int id, String approvalStatus) =>
      _request(
        'PATCH',
        '/complaints/$id/review',
        body: {'approval_status': approvalStatus},
      );

  Future<Map<String, dynamic>> updateComplaintRepair(
    int id,
    String status,
    String notes,
  ) => _request(
    'PATCH',
    '/complaints/$id/repair',
    body: {'status_repair': status, 'repair_notes': notes},
  );

  Future<Map<String, dynamic>> getNotifications() =>
      _request('GET', '/notifications');

  Future<void> markAllNotificationsRead() =>
      _request('POST', '/notifications/read-all');

  Future<void> markNotificationRead(int id) =>
      _request('PATCH', '/notifications/$id/read');

  Uri complaintPhotoUri(int id) => _uri('/complaints/$id/photo');

  Map<String, String> get requestHeaders => _headers();

  Map<String, String> _cleanBody(Map<String, dynamic> raw) {
    final result = <String, String>{};
    for (final entry in raw.entries) {
      if (entry.value != null) {
        result[entry.key] = entry.value.toString();
      }
    }
    return result;
  }

  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Map<String, String>? query,
    Map<String, dynamic>? body,
  }) async {
    final uri = _uri(path, query: query);
    final headers = _headers();
    final stringBody = body != null ? _cleanBody(body) : null;
    final response = await _send(() async {
      switch (method) {
        case 'GET':
          return http.get(uri, headers: headers);
        case 'POST':
          return http.post(uri, headers: headers, body: stringBody);
        case 'PUT':
          return http.put(uri, headers: headers, body: stringBody);
        case 'PATCH':
          return http.patch(uri, headers: headers, body: stringBody);
        case 'DELETE':
          return http.delete(uri, headers: headers);
        default:
          throw UnsupportedError('Metode API tidak didukung: $method');
      }
    });
    return _decode(response);
  }

  Future<T> _send<T>(Future<T> Function() request) async {
    try {
      return await request();
    } on http.ClientException catch (error) {
      final host = Uri.tryParse(_baseUrl)?.host;
      final connectionHint = host == '127.0.0.1' || host == 'localhost'
          ? 'Jika HP tersambung melalui USB, jalankan '
                '`adb reverse tcp:8000 tcp:8000`. Jika melalui Wi-Fi, '
                'gunakan IP LAN komputer pada API_BASE_URL.'
          : 'Pastikan koneksi internet HP aktif dan server online ($_baseUrl) dapat diakses.';
      throw ApiException(
        'Tidak dapat terhubung ke API ($_baseUrl). Pastikan server Laravel '
        'aktif. $connectionHint ${error.message}',
      );
    }
  }

  Uri _uri(String path, {Map<String, String>? query}) {
    final base = _baseUrl.endsWith('/')
        ? _baseUrl.substring(0, _baseUrl.length - 1)
        : _baseUrl;
    return Uri.parse('$base$path').replace(queryParameters: query);
  }

  Map<String, String> _headers() => {
    'Accept': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  Map<String, dynamic> _decode(http.Response response) {
    Map<String, dynamic> data = {};
    if (response.body.isNotEmpty) {
      try {
        data = Map<String, dynamic>.from(jsonDecode(response.body) as Map);
      } on FormatException {
        throw ApiException(
          'Respons server tidak dapat dibaca (${response.statusCode}).',
        );
      }
    }
    if (response.statusCode == 401) {
      _token = null;
      _storage.delete(key: 'api_token');
    }
    if (response.statusCode < 200 || response.statusCode >= 300) {
      final errors = data['errors'];
      final firstError = errors is Map
          ? errors.values
                .expand((value) => value is List ? value : [value])
                .firstOrNull
          : null;
      throw ApiException(
        (firstError ??
                data['message'] ??
                'Permintaan gagal (${response.statusCode}).')
            .toString(),
      );
    }
    return data;
  }
}

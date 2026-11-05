import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:rapi_mobile/api_service.dart';
import 'package:rapi_mobile/main.dart';

void main() {
  testWidgets('login screen shows RAPI-Sekolah account fields', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: LoginScreen(
          api: ApiService(),
          onLogin: (_) {},
          onForgotPassword: () {},
          onBackToCalendar: () {},
        ),
      ),
    );

    expect(find.text('RAPI-Sekolah'), findsOneWidget);
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Kata sandi'), findsNWidgets(2));
    expect(find.text('Masuk'), findsOneWidget);
    expect(find.text('Lupa kata sandi?'), findsOneWidget);
    expect(find.text('Kembali ke kalender'), findsOneWidget);
  });
}

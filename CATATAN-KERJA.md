# Catatan kerja

## Fase 1
- Keputusan: menata layout admin satu kolom dengan sidebar agar sesuai kebutuhan HP dan desktop.
- Alasan: aplikasi memang diutamakan untuk perangkat mobile, sehingga struktur yang ringkas memudahkan tampilan responsif.

## Fase 2
- Keputusan: login tetap memakai form Breeze asli dengan desain baru yang lebih modern.
- Alasan: fase ini hanya memerlukan perombakan UI dan redirect ke dashboard tanpa mengubah mekanisme autentikasi.

## Fase 3-9
- Keputusan: semua fitur operasi bisnis dikelola lewat controller yang memakai DB::transaction, DB::statement call prosedur, dan validasi manual.
- Alasan: struktur database sudah ditentukan dan tidak boleh diubah, jadi pendekatan yang aman adalah memanfaatkan stored procedure serta trigger yang tersedia.

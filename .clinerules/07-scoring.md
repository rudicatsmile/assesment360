# Scoring & Perhitungan Rata-rata Rules (KepsekEval)

## Skor Standar untuk Single Choice

- Setiap pertanyaan tipe Single Choice harus memiliki skor numerik:
  - Sangat Setuju / Sangat Baik = 5
  - Setuju / Baik = 4
  - Kurang Setuju / Cukup = 3
  - Tidak Setuju / Kurang = 2
  - Abstain / Tidak Jawab = 0 (atau null, tergantung kebijakan)
- Skor ini harus bisa dikonfigurasi per AnswerOption (field: score integer).
- Admin boleh mengubah skor default saat membuat/mengedit pertanyaan.

## Perhitungan Rata-rata

- Rata-rata skor dihitung per pertanyaan, per kuisioner, dan per kategori penilai (Guru, Tata Usaha, Orang Tua).
- Rumus rata-rata: total_skor / jumlah_responden_yang_menjawab (abaikan yang abstain jika diatur).
- Tampilkan:
  - Rata-rata keseluruhan kuisioner
  - Rata-rata per kelompok penilai
  - Persentase distribusi jawaban (contoh: 45% Sangat Setuju)
- Gunakan decimal (2 angka di belakang koma) untuk rata-rata skor.

## Analytics Requirements

- Dashboard admin harus menampilkan:
  - Total responden per kelompok
  - Rata-rata skor keseluruhan
  - Rata-rata skor per pertanyaan (diurutkan dari tertinggi ke terendah)
  - Chart distribusi jawaban (pie/bar) jika memungkinkan
- Hitung juga "tingkat partisipasi" = (jumlah responden / jumlah target penilai) × 100%

## Rules Teknis

- Buat service class atau trait bernama `HasScoring` atau `QuestionnaireScorer` untuk menghitung skor.
- Simpan skor yang sudah dihitung di tabel Response atau buat tabel summary (QuestionnaireSummary) untuk performa lebih baik.
- Cache hasil perhitungan selama kuisioner masih aktif (gunakan Laravel Cache).
- Selalu hitung ulang ketika ada responden baru submit jawaban.

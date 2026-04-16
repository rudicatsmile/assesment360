# KepsekEval Project Specific Rules

## Project Overview

- Nama proyek: KepsekEval
- Tujuan: Aplikasi penilaian kinerja Kepala Sekolah
- Penilai terdiri dari 3 kelompok:
  - Guru
  - Pegawai Tata Usaha
  - Orang Tua Murid
- Setiap kuisioner berisi pertanyaan dengan tipe jawaban yang fleksibel.

## Core Business Rules

- Satu Kuisioner dapat memiliki banyak Pertanyaan.
- Setiap Pertanyaan memiliki tipe jawaban:
  1. Single Choice (Radio Button) → contoh: Sangat Setuju, Setuju, Kurang Setuju, Tidak Setuju, Abstain
  2. Essay (Isian Bebas / Textarea)
  3. Combined (Single Choice + Essay) → penilai harus memilih pilihan DAN mengisi alasan/esai
- Jawaban pilihan harus bisa dikonfigurasi per pertanyaan (tidak hardcode).
- Setiap responden (penilai) hanya boleh mengisi satu kuisioner satu kali (kecuali admin mengizinkan ulang).
- Semua pengisian kuisioner bersifat anonim untuk penilai (kecuali admin).

## Model & Database Rules

- User model: gunakan role (admin, guru, tu, orangtua)
- Questionnaire model (nama, deskripsi, tanggal aktif, status: draft/active/closed)
- Question model (pertanyaan, tipe: single_choice / essay / combined, urutan)
- AnswerOption model (untuk pilihan jawaban, terkait Question)
- Response model (relasi ke Questionnaire dan User penilai)
- Answer model (relasi ke Response dan Question, simpan nilai jawaban)
- Gunakan polymorphic relationship jika diperlukan, tapi prioritaskan relasi yang sederhana dan jelas.
- Gunakan soft deletes pada model yang penting.

## Livewire + Flux UI Rules

- Gunakan Flux components sebanyak mungkin untuk konsistensi UI.
- Halaman pengisian kuisioner harus dinamis (render pertanyaan dan input sesuai tipe jawaban secara otomatis).
- Gunakan wire:live atau wire:debounce untuk form pengisian.
- Tampilkan progress bar pengisian kuisioner.
- Gunakan Flux Modal untuk konfirmasi submit kuisioner.
- Dashboard admin harus menampilkan statistik (jumlah responden, rata-rata skor, dll.) dengan Flux Card dan Flux Table.

## Security & Access Control Rules

- Hanya Admin/Kepala Sekolah yang bisa membuat, edit, dan melihat hasil lengkap kuisioner.
- Penilai (Guru, TU, Orang Tua) hanya bisa melihat dan mengisi kuisioner yang ditujukan kepada mereka.
- Gunakan Laravel Policies dan Gates untuk authorization.
- Pengisian kuisioner harus terlindungi dari duplicate submission (gunakan unique constraint atau flag).
- Jangan tampilkan identitas penilai di hasil kuisioner (anonim).

## Naming Convention

- Model: Questionnaire, Question, AnswerOption, Response, Answer
- Livewire Components:
  - Admin: QuestionnaireList, QuestionnaireForm, QuestionManager, ResponseAnalytics
  - Penilai: AvailableQuestionnaires, QuestionnaireFill, MyResponses
- Route names: admin.questionnaires._, fill.questionnaire._, dll.
- Blade/Livewire views: gunakan folder yang rapi (livewire/admin/, livewire/fill/, dll.)

## Scoring & Analytics Rules

- Untuk pertanyaan tipe Single Choice: berikan skor numerik (misal: Sangat Setuju = 5, Setuju = 4, dst.)
- Hitung rata-rata skor per kuisioner dan per kategori penilai (Guru, TU, Orang Tua).
- Tampilkan grafik sederhana di dashboard admin (gunakan Laravel + Flux atau Chart.js jika diperlukan).

## Workflow Rules

- Admin membuat kuisioner → menambahkan pertanyaan → mengatur jawaban pilihan → publish.
- Penilai melihat daftar kuisioner aktif → mengisi → submit (sekali saja).
- Admin melihat hasil analisis setelah kuisioner ditutup atau selama periode aktif.
- Selalu validasi bahwa semua pertanyaan wajib dijawab sebelum submit.

Gunakan rules ini bersama dengan rules di file 01-general.md sampai 05-workflow.md.
Prioritaskan clean code, maintainability, dan user experience yang mudah digunakan oleh guru dan orang tua.

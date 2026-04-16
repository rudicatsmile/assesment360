# Role Management & Assignment Kuisioner Rules (KepsekEval)

## Role System

- User roles yang didukung:
  - admin (Kepala Sekolah / Super Admin)
  - guru
  - tata_usaha
  - orang_tua

- Gunakan Spatie Laravel Permission atau custom role dengan enum di User model.
- Setiap user memiliki satu role utama.

## Assignment Kuisioner

- Satu kuisioner bisa ditugaskan ke satu atau lebih kelompok penilai (multi-select).
- Field di Questionnaire: target_groups (JSON atau pivot table)
- Contoh target: ["guru", "tata_usaha", "orang_tua"]
- Admin bisa mengatur apakah kuisioner bersifat:
  - Terbuka untuk semua (public)
  - Hanya untuk kelompok tertentu

## Akses Control Rules

- Admin → bisa melihat semua kuisioner, membuat, edit, delete, dan melihat semua hasil analisis.
- Guru / Tata Usaha / Orang Tua → hanya bisa melihat dan mengisi kuisioner yang ditugaskan kepadanya dan masih aktif.
- Penilai tidak boleh melihat hasil analisis kuisioner.
- Setelah kuisioner ditutup (status = closed), penilai tidak bisa mengisi lagi.

## Technical Implementation

- Gunakan Laravel Policies:
  - QuestionnairePolicy
  - ResponsePolicy
- Gunakan middleware atau Gate untuk route protection.
- Buat Livewire component terpisah:
  - Admin: QuestionnaireAssignment (form untuk memilih target groups)
  - Penilai: AvailableQuestionnaires (hanya tampilkan kuisioner yang sesuai role-nya)
- Saat user login, tampilkan dashboard sesuai role (role-based dashboard).

## Additional Rules

- Orang tua murid harus terdaftar dengan relasi ke Student (jika diperlukan di masa depan).
- Hindari duplikasi pengisian: satu user hanya boleh submit satu Response per Questionnaire.
- Log aktivitas pengisian untuk audit (opsional).
- Admin dapat "reset" pengisian untuk user tertentu jika diperlukan.

# Dynamic Form Pengisian Kuisioner Rules (KepsekEval)

## Prinsip Utama

- Form pengisian kuisioner harus **dinamis** berdasarkan tipe pertanyaan.
- Livewire component utama: QuestionnaireFill
- Render pertanyaan secara otomatis sesuai urutan (sort by order column).

## Tipe Jawaban yang Didukung

1. **Single Choice**
   - Gunakan Flux Radio Group
   - Tampilkan semua AnswerOption sebagai pilihan
   - Wajib dipilih (kecuali diatur sebagai optional)

2. **Essay**
   - Gunakan Flux Textarea
   - Support minimal dan maksimal karakter (default 500-2000 karakter)
   - Tampilkan word/character count

3. **Combined (Choice + Essay)**
   - Flux Radio Group + Flux Textarea di bawahnya
   - Essay hanya muncul setelah pilihan dipilih (gunakan wire:live)
   - Essay bersifat wajib jika tipe Combined

## UX Requirements

- Tampilkan nomor pertanyaan (1 dari 15)
- Progress bar di atas form (persentase pertanyaan yang sudah dijawab)
- Tombol "Sebelumnya" dan "Berikutnya" (stepper style) atau tampilkan semua pertanyaan sekaligus (pilih salah satu yang lebih user-friendly)
- Gunakan optimistic UI saat menyimpan jawaban sementara (autosave setiap 30 detik atau saat pindah pertanyaan)
- Validasi real-time dengan Flux validation messages
- Konfirmasi submit akhir dengan Flux Modal + ringkasan jawaban

## Teknis Implementation

- Gunakan Laravel Livewire dengan `wire:model.live` atau `wire:live` untuk dynamic rendering.
- Simpan jawaban sementara di database (table Answer dengan status draft) sebelum final submit.
- Buat component reusable: DynamicQuestionRenderer
- Pastikan form mobile-friendly (Flux sudah responsive secara default).

## Accessibility

- Gunakan proper labels dan aria attributes
- Support keyboard navigation
- Pastikan kontras warna cukup untuk guru dan orang tua.

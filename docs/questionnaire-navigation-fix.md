# Questionnaire Navigation Fix

## Root Cause
- `wire:model.defer` pada input jawaban membuat sinkronisasi state ke server tertunda.
- Saat user klik `Berikutnya`, request pertama sering dipakai untuk sinkronisasi nilai defer, sehingga aksi `nextQuestion` terasa seperti butuh klik kedua.
- Polling/autosave background sebelumnya memperbesar queue request Livewire dan menambah latensi transisi.

## Perubahan
- Ubah binding input dari `defer` menjadi `live` (`live.debounce` untuk textarea) agar state jawaban siap saat aksi navigasi.
- Autosave dipindah ke aksi navigasi (`next/prev/goTo`) dan polling periodik dinonaktifkan.
- Persist draft dilakukan hanya untuk pertanyaan aktif saat navigasi, dengan `upsert` atomic untuk mencegah race condition.
- Tambahkan loading state khusus transisi pertanyaan agar user mendapat feedback jelas.

## Dampak
- Tombol `Berikutnya` responsif dengan sekali klik.
- Konten pertanyaan, indikator progress, dan quick-nav berubah konsisten dalam satu transisi.
- Risiko duplicate insert saat autosave berkurang karena upsert berbasis key unik (`response_id`, `question_id`).

## Regression Safety
- Test ditambahkan:
  - `tests/Feature/Fill/QuestionnaireFillNavigationTest.php`
  - Memastikan `nextQuestion` berpindah dengan satu call dan jawaban tersimpan.

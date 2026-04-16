# Security & Best Practices

- Selalu gunakan mass assignment protection ($fillable / $guarded).
- Gunakan Rate Limiting di controller jika perlu.
- Hindari raw SQL kecuali sangat diperlukan.
- Gunakan Sanctum / Passport hanya jika butuh API.
- Validasi semua input dari user.

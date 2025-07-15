# Sistem Chat AI untuk Layanan Delivery Berbasis WhatsApp

Sistem ini adalah platform layanan delivery lokal yang terintegrasi dengan WhatsApp dan menggunakan AI untuk mengotomatisasi interaksi dengan pelanggan dan mitra.

## 🚀 Fitur Utama

### Chatbot WhatsApp Berbasis AI
- ✅ Respons alami seperti ChatGPT
- ✅ Customer service otomatis 24/7
- ✅ Konversi kontekstual dengan pelanggan
- ✅ Deteksi otomatis customer/partner

### Jenis Layanan
- 🚗 **Ojek** - Antar jemput
- 📦 **Pengantaran** - Kirim makanan/barang
- 🛒 **Jasa Belanja** - Belanja dan antar
- 🔧 **Jasa Panggilan** - Tukang, pijat, service

### Fitur Sistem
- 💰 Perhitungan tarif otomatis berdasarkan jarak
- 📍 Deteksi lokasi dari share location atau alamat
- 📊 Rekap order harian/mingguan/bulanan
- 🔔 Notifikasi otomatis ke mitra
- 💳 Sistem saldo dan transaksi
- 📈 Laporan performa real-time

## 🛠️ Teknologi yang Digunakan

- **Backend**: Laravel 10
- **Database**: MySQL
- **WhatsApp API**: Meta WhatsApp Business API
- **AI Chat**: OpenRouter (Mistral, Gemini, Claude - Gratis)
- **Geolocation**: OpenStreetMap Nominatim (Gratis)
- **Authentication**: Laravel Sanctum

## 📋 Prasyarat

- PHP 8.1+
- Composer
- MySQL 8.0+
- Node.js & NPM
- WhatsApp Business API Account
- OpenRouter API Key (Gratis)

## 🚀 Instalasi

### 1. Clone Repository
```bash
git clone <repository-url>
cd wablastauthv1
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Setup Environment
```bash
cp .env.example .env
```

Edit file `.env` dengan konfigurasi yang sesuai:
```env
# WhatsApp API Configuration
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_API_TOKEN=your_whatsapp_api_token_here
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id_here
WHATSAPP_VERIFY_TOKEN=your_verify_token_here

# AI Configuration (OpenRouter - Free)
OPENAI_API_KEY=your_openrouter_api_key_here
OPENAI_API_BASE=https://openrouter.ai/api/v1
OPENAI_MODEL=mistralai/mistral-7b-instruct

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wablastauthv1
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Setup Database
```bash
php artisan migrate
php artisan db:seed --class=ServiceSeeder
```

### 6. Build Assets
```bash
npm run build
```

### 7. Start Server
```bash
php artisan serve
```

## 🔧 Konfigurasi WhatsApp API

### 1. Setup WhatsApp Business API
1. Buat akun di [Meta for Developers](https://developers.facebook.com/)
2. Buat WhatsApp Business App
3. Dapatkan Phone Number ID dan Access Token
4. Setup Webhook URL: `https://yourdomain.com/api/whatsapp/webhook`

### 2. Konfigurasi Webhook
- **Verify Token**: Token untuk verifikasi webhook
- **Webhook URL**: URL endpoint untuk menerima pesan
- **Events**: messages, message_deliveries, message_reads

## 🤖 Konfigurasi AI Chat

### OpenRouter (Gratis)
1. Daftar di [OpenRouter](https://openrouter.ai/)
2. Dapatkan API Key
3. Pilih model AI yang diinginkan:
   - `mistralai/mistral-7b-instruct` (Rekomendasi)
   - `google/gemini-flash-1.5`
   - `anthropic/claude-3-haiku`

## 📱 Cara Penggunaan

### Untuk Customer
1. Kirim pesan ke nomor WhatsApp yang terdaftar
2. Pilih layanan (1-4)
3. Kirim lokasi penjemputan
4. Kirim lokasi tujuan
5. Konfirmasi pesanan

### Untuk Partner
1. Daftar sebagai mitra
2. Set status online/offline
3. Terima notifikasi order baru
4. Ambil order dengan ketik "AMBIL"
5. Update status order

## 🔌 API Endpoints

### WhatsApp Webhook
```
GET  /api/whatsapp/webhook - Verify webhook
POST /api/whatsapp/webhook - Receive messages
```

### Orders API
```
GET    /api/v1/orders - List orders
POST   /api/v1/orders - Create order
GET    /api/v1/orders/{id} - Get order
PUT    /api/v1/orders/{id} - Update order
POST   /api/v1/orders/calculate-price - Calculate price
GET    /api/v1/orders/statistics - Get statistics
```

### Customers API
```
GET    /api/v1/customers - List customers
POST   /api/v1/customers - Create customer
GET    /api/v1/customers/{id} - Get customer
PUT    /api/v1/customers/{id} - Update customer
GET    /api/v1/customers/{id}/orders - Get customer orders
GET    /api/v1/customers/{id}/transactions - Get customer transactions
```

### Partners API
```
GET    /api/v1/partners - List partners
POST   /api/v1/partners - Create partner
GET    /api/v1/partners/{id} - Get partner
PUT    /api/v1/partners/{id} - Update partner
GET    /api/v1/partners/{id}/orders - Get partner orders
GET    /api/v1/partners/{id}/transactions - Get partner transactions
POST   /api/v1/partners/{id}/go-online - Set partner online
POST   /api/v1/partners/{id}/go-offline - Set partner offline
```

### Services API
```
GET    /api/v1/services - List services
POST   /api/v1/services - Create service
GET    /api/v1/services/{id} - Get service
PUT    /api/v1/services/{id} - Update service
```

## 📊 Struktur Database

### Tabel Utama
- `customers` - Data pelanggan
- `partners` - Data mitra
- `services` - Jenis layanan
- `orders` - Data pesanan
- `transactions` - Transaksi keuangan
- `chat_sessions` - Sesi percakapan
- `chat_messages` - Pesan WhatsApp

## 🔒 Keamanan

- Validasi input pada semua endpoint
- Rate limiting untuk API
- Logging untuk audit trail
- Enkripsi data sensitif
- CORS protection

## 📈 Monitoring

### Logs
- WhatsApp webhook logs
- AI chat logs
- Error logs
- Transaction logs

### Metrics
- Total orders per periode
- Revenue per periode
- Partner performance
- Customer satisfaction

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure SSL certificate
- [ ] Setup database backup
- [ ] Configure queue workers
- [ ] Setup monitoring tools

### Environment Variables
```bash
# Production settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=your_db_host
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# WhatsApp API
WHATSAPP_API_TOKEN=your_production_token
WHATSAPP_PHONE_NUMBER_ID=your_production_phone_id
WHATSAPP_VERIFY_TOKEN=your_production_verify_token

# AI API
OPENAI_API_KEY=your_production_ai_key
```

## 🤝 Kontribusi

1. Fork repository
2. Buat feature branch
3. Commit changes
4. Push ke branch
5. Buat Pull Request

## 📄 Lisensi

MIT License - lihat file [LICENSE](LICENSE) untuk detail.

## 📞 Support

Untuk bantuan dan pertanyaan:
- Email: support@example.com
- WhatsApp: +62xxx-xxxx-xxxx
- Dokumentasi: [Wiki](https://github.com/username/repo/wiki)

## 🔄 Changelog

### v1.0.0 (2024-01-01)
- ✅ Initial release
- ✅ WhatsApp integration
- ✅ AI chat functionality
- ✅ Order management
- ✅ Partner management
- ✅ Basic reporting

---

**Dibuat dengan ❤️ untuk layanan delivery lokal Indonesia**

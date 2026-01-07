# Tailwind CSS Setup Dokümantasyonu

## Genel Bakış

Bu dokümantasyon, Symfony projesinde Tailwind CSS'in **Webpack Encore + PostCSS** üzerinden kurulum ve kullanımını açıklamaktadır.

## Kurulum Tarihi
- **Tarih**: 7 Ocak 2026
- **Versiyon**: Tailwind CSS v4.1.18
- **Kurulum Yöntemi**: Encore + PostCSS

---

## Mevcut Konfigürasyon

### Yüklü Paketler

```json
{
  "dependencies": {
    "@tailwindcss/postcss": "^4.1.18",
    "postcss": "^8.5.6",
    "postcss-loader": "^8.2.0",
    "tailwindcss": "^4.1.18"
  },
  "devDependencies": {
    "@symfony/webpack-encore": "^5.1.0",
    "webpack": "^5.74.0",
    "webpack-cli": "^5.1.0"
  }
}
```

### Dosya Yapısı

```
pehlione/
├── webpack.config.js          # Encore konfigürasyonu
├── postcss.config.mjs          # PostCSS plugin'leri
├── assets/
│   ├── app.js                  # Ana JavaScript entry
│   └── styles/
│       └── app.css             # Ana CSS dosyası
├── public/
│   └── build/                  # Derlenmiş assets
└── templates/
    ├── base.html.twig          # Base şablonu
    └── category/               # Category template'leri
        ├── index.html.twig
        ├── show.html.twig
        ├── edit.html.twig
        ├── new.html.twig
        ├── _form.html.twig
        └── _delete_form.html.twig
```

---

## Konfigürasyon Detayları

### 1. webpack.config.js

Encore konfigürasyonunda PostCSS loader aktif edilmiştir:

```javascript
Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .enablePostCssLoader()      // ← PostCSS aktif
    .addEntry('app', './assets/app.js')
    // ... diğer ayarlar
```

**Önemli**: `.enablePostCssLoader()` satırı Tailwind CSS işlemesi için gereklidir.

### 2. postcss.config.mjs

Tailwind PostCSS plugin'i tanımlanmıştır:

```javascript
export default {
  plugins: {
    "@tailwindcss/postcss": {},
  },
};
```

### 3. assets/styles/app.css

Ana CSS dosyasında Tailwind import'u:

```css
@import "tailwindcss";
@source not "../../public";
```

**Not**: `@source not "../../public"` satırı, watch modunda sonsuz rebuild loop'unu engellemek için gereklidir.

### 4. assets/app.js

CSS dosyası JavaScript entry'sinden import edilir:

```javascript
import './stimulus_bootstrap.js';
import './styles/app.css';  // ← CSS import
```

### 5. templates/base.html.twig

Twig şablonunda Encore tag'ları kullanılır:

```twig
<!DOCTYPE html>
<html>
    <head>
        {% block stylesheets %}
            {{ encore_entry_link_tags('app') }}
        {% endblock %}
        {% block javascripts %}
            {{ encore_entry_script_tags('app') }}
        {% endblock %}
    </head>
    <body>
        {% block body %}{% endblock %}
    </body>
</html>
```

**Uyarı**: `asset('styles/app.css')` kullanmak YANLIŞ'tır. Mutlaka `encore_entry_link_tags('app')` kullanın.

---

## NPM Komutları

### Development (Watch Mode)

```bash
npm run watch
```

- Dosyaları izler
- Değişikliklerde otomatik rebuild eder
- Hot reload desteği (bazı durumlarda)
- Geliştirme sırasında kullanılır

**Terminal Kombosu**:
```bash
# Terminal 1
npm run watch

# Terminal 2
symfony server:start
```

Tarayıcıda açın: `https://127.0.0.1:8000`

### Development (Manual Build)

```bash
npm run dev
```

- Tek seferlik development build
- Watch yapmaz

### Production Build

```bash
npm run build
```

- Minified CSS/JS üretir
- Hashed filenames (cache busting)
- Source maps kapatılır
- Deployment'a hazırdır

### Build Output

Build tamamlandığında:

```
8 files written to public\build
Entrypoint app 194 KiB = 
  runtime.bf310e00.js (2.56 KiB)
  837.a112645a.js (172 KiB)
  app.4b9277f4.css (14.7 KiB)
  app.18675e37.js (5.45 KiB)
```

---

## Tailwind CSS Kullanımı

### Temel Sınıflar

Tailwind CSS, utility-first yaklaşım kullanır:

```html
<!-- Padding -->
<div class="px-6 py-4">Content</div>

<!-- Text -->
<p class="text-sm text-gray-600">Küçük gri metin</p>

<!-- Colors -->
<button class="bg-blue-600 hover:bg-blue-700">Blue Button</button>

<!-- Flexbox -->
<div class="flex items-center justify-between">
    <span>Sol</span>
    <span>Sağ</span>
</div>

<!-- Grid -->
<div class="grid grid-cols-3 gap-4">
    <div>1</div>
    <div>2</div>
    <div>3</div>
</div>

<!-- Responsive -->
<div class="text-sm md:text-base lg:text-lg">
    Responsive metin
</div>
```

### Shade Sistemi

Tailwind'in renk paleti 50-950 arası shade'leri içerir:

```html
<!-- Grays -->
<div class="bg-gray-50">Çok açık gri</div>
<div class="bg-gray-500">Orta gri</div>
<div class="bg-gray-950">Çok koyu gri</div>

<!-- Blues -->
<button class="bg-blue-600 hover:bg-blue-700">Normal</button>

<!-- Reds (Danger) -->
<button class="bg-red-600 hover:bg-red-700">Sil</button>

<!-- Ambers (Warning) -->
<button class="bg-amber-600 hover:bg-amber-700">Düzenle</button>
```

### Hover & Focus States

```html
<button class="bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-300">
    Tıkla
</button>
```

### Spacing

```html
<!-- Margin -->
<div class="m-4">Tüm tarафta 1rem margin</div>
<div class="mx-4">Yatay 1rem margin</div>
<div class="mb-2">Aşağı 0.5rem margin</div>

<!-- Padding -->
<div class="p-4">Tüm tarafta 1rem padding</div>
<div class="px-6 py-4">Yatay 1.5rem, dikey 1rem</div>
```

### Typography

```html
<!-- Font Size -->
<p class="text-xs">Extra küçük</p>
<p class="text-sm">Küçük</p>
<p class="text-base">Normal</p>
<p class="text-lg">Büyük</p>
<p class="text-2xl">Çok büyük</p>

<!-- Font Weight -->
<p class="font-normal">Normal</p>
<p class="font-semibold">Yarı kalın</p>
<p class="font-bold">Kalın</p>

<!-- Text Color -->
<p class="text-gray-900">Koyu metin</p>
<p class="text-gray-600">Orta metin</p>
<p class="text-gray-500">Açık metin</p>
```

### Layout

```html
<!-- Flexbox Container -->
<div class="flex items-center justify-between gap-4">
    <span>Sol</span>
    <span>Sağ</span>
</div>

<!-- Grid Container -->
<div class="grid grid-cols-3 gap-4 md:grid-cols-6">
    <!-- 3 kolona, md'de 6 kolona -->
</div>

<!-- Min Height -->
<div class="min-h-screen">Full ekran yüksekliği</div>

<!-- Max Width -->
<div class="max-w-7xl mx-auto">Orta hizalı, maksimum 80rem</div>
```

---

## Projede Kullanılan Bileşenler

### 1. Tablo (category/index.html.twig)

```twig
<table class="min-w-full divide-y divide-gray-200 bg-white">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">
                Header
            </th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
        <tr class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 text-sm text-gray-900">Data</td>
        </tr>
    </tbody>
</table>
```

### 2. Buton Varyasyonları

```twig
<!-- Primary (Mavi) -->
<a class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition">
    Primary
</a>

<!-- Secondary (Gri) -->
<a class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-6 py-2 rounded-md transition">
    Secondary
</a>

<!-- Danger (Kırmızı) -->
<button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition">
    Delete
</button>

<!-- Warning (Turuncu) -->
<a class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md transition">
    Edit
</a>
```

### 3. Card

```twig
<div class="rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-6 py-4">
        <h1 class="text-2xl font-bold">Başlık</h1>
    </div>
    <div class="p-6">
        İçerik
    </div>
</div>
```

### 4. Form

```twig
<div class="space-y-6">
    {{ form_start(form) }}
    
    {% for field in form %}
        {% if not field.rendered %}
            <div>
                {{ form_label(field) }}
                {{ form_widget(field, {
                    'attr': {
                        'class': 'mt-1 block w-full rounded-md border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500'
                    }
                }) }}
                {{ form_errors(field) }}
            </div>
        {% endif %}
    {% endfor %}
    
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
        Gönder
    </button>
    
    {{ form_end(form) }}
</div>
```

---

## Sık Sorulan Sorular

### S: CSS değişiklikleri görünmüyor?

**C**: Aşağıdaki kontrol listesini takip edin:

1. `npm run watch` çalışıyor mu?
2. CSS dosyası kaydedildi mi?
3. Build hataları var mı? (`npm run dev` ile test edin)
4. Cache temizledi mi? (F12 → Network → "Disable cache" işaretleyin)
5. Tarayıcı yenilemesi yaptı mı? (Hard refresh: `Ctrl+Shift+R`)

### S: Yeni utility'ler nasıl eklenir?

**C**: Tailwind config dosyasında custom utilities tanımlanabilir (şu anki projede gerçekleştirilmemiştir, varsayılan konfigürasyon kullanılır).

### S: Prodükte CSS boyutu büyük mü?

**C**: Tailwind v4, sadece kullanılan class'ları içerir. Build çıktısı (~14.7 KiB) normaldır.

### S: Tailwind ile Bootstrap karışabilir mi?

**C**: **Hayır, önerilmez**. Tailwind ve Bootstrap'ın CSS class'ları çakışabilir. Bir taneyi seçin.

### S: Dark mode nasıl etkinleştirilir?

**C**: Varsayılan konfigürasyonda dark mode entegrasyonu yapılmamıştır. Tailwind config'inde `darkMode: 'class'` eklenerek etkinleştirilebilir.

---

## Referanslar

- [Tailwind CSS Dokümantasyonu](https://tailwindcss.com/docs)
- [Symfony Webpack Encore](https://symfony.com/doc/current/frontend.html)
- [PostCSS Dokümantasyonu](https://postcss.org/)
- [Tailwind CSS v4 Kılavuzu](https://tailwindcss.com/docs/v4)

---

## Notlar

- Proje **Tailwind CSS v4.1.18** kullanmaktadır
- **Webpack Encore** ile derlenmiş, **PostCSS** ile işlenmiştir
- Production build'ler `/public/build/` dizinine yazılır
- Tüm template'ler Tailwind utility class'ları ile yazılmıştır
- No global CSS / custom CSS dosyası yoktur (pure Tailwind)

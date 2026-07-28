# 🚀 دليل نشر Facturo على DockHosting من الصفر

> احتفظ بهذا الدليل! ستحتاجه في المستقبل.

---

## ✅ الخطوة 1 — إنشاء مشروع جديد

1. افتح [app.dockhosting.dev](https://app.dockhosting.dev) وسجّل دخولك.
2. اضغط **New Project**.
3. اختر مستودع GitHub: `facturoo` (أو `facturo` إن تغير الاسم).
4. اختر الـ Branch: `main`.
5. في خانة **Runtime**: اختر `PHP` أو `Laravel` إن وجد.

---

## ✅ الخطوة 2 — إعداد قاعدة البيانات

في صفحة **Database** (الخطوة 4):
- اختر **"Create new"** لإنشاء قاعدة بيانات MySQL جديدة مُدارة.
- اضغط **Continue**.

> ⚠️ لا ترفع ملف `.env` من حاسوبك، المنصة ستحقن بيانات DB تلقائياً.

---

## ✅ الخطوة 3 — نشر المشروع (Deploy)

- في صفحة Deploy (الخطوة 5): اكتب اسم مشروع جديد مختلف إن طُلب.
- اضغط **Deploy** وانتظر حتى يظهر ✅ **Success**.

---

## ✅ الخطوة 4 — إضافة متغيرات البيئة (Environment)

بعد النشر، اذهب إلى تبويب **Environment** في صفحة المشروع وأضف هذه المتغيرات:

| Key | Value |
|-----|-------|
| `APP_KEY` | `base64:EvbTMvcNQ9QXxtxVlQ0b0XRk8JngzVkUmXQNIn4McSI=` |
| `APP_URL` | `https://[اسم-مشروعك].dockhosting.dev` |
| `APP_DEBUG` | `false` (في الإنتاج) |

بعد الحفظ، اضغط **Trigger Deploy** لتطبيق المتغيرات.

---

## ✅ الخطوة 5 — استيراد قاعدة البيانات (البيانات + المستخدمين)

اذهب إلى قسم **Run Migrations** وشغّل هذا الأمر:

```
php artisan tinker --execute="DB::unprepared(file_get_contents('production_backup.sql'));"
```

> ⚠️ **شرط:** ملف `production_backup.sql` يجب أن يكون موجوداً في GitHub قبل النشر.
> قبل الـ Deploy، قم بـ:
> ```
> git add production_backup.sql
> git commit -m "add: db backup for deployment"
> git push
> ```
> وبعد النجاح احذفه فوراً من Git:
> ```
> git rm --cached production_backup.sql
> git commit -m "chore: remove db backup from git"
> git push
> ```

**النتيجة:** جميع العملاء، المنتجات، الفواتير، وحساب الدخول موجودة فوراً! 🎉

---

## 🔑 بيانات الدخول (Credentials) وطرق استعادتها

### 1. الحساب الافتراضي (إذا قمت بتشغيل Seeders فقط):
إذا قمت بتثبيت قاعدة بيانات نظيفة وشغّلت الـ Seeders (`php artisan db:seed`)، الحساب الافتراضي هو:
*   **البريد الإلكتروني:** `admin@facturo.com`
*   **كلمة المرور:** `oumar@123`

### 2. الحسابات المستوردة من النسخة الاحتياطية:
إذا نفّذت الخطوة 5 أعلاه، فستعمل **نفس الحسابات (الإيميل وكلمة المرور) التي كانت لديك سابقاً**.

### 3. إنشاء أو إعادة تعيين كلمة مرور أي حساب (من لوحة تحكم DockHosting):
إذا نسيت كلمة المرور أو واجهت مشكلة في الدخول، اذهب إلى قسم **Run Migrations** وشغّل هذا الأمر لتعيين إيميل وباسورد جديدين فوراً:
```bash
php artisan tinker --execute="App\Models\User::updateOrCreate(['email' => 'YOUR_EMAIL@example.com'], ['name' => 'Admin', 'password' => Hash::make('YOUR_NEW_PASSWORD')]);"
```
*(قم بتغيير `YOUR_EMAIL@example.com` و `YOUR_NEW_PASSWORD` بالبيانات التي تريدها).*

---

## ✅ الخطوة 6 — التحقق من الموقع

افتح `https://[اسم-مشروعك].dockhosting.dev` وتأكد من:
- [ ] تظهر صفحة تسجيل الدخول بألوانها الكاملة (CSS)
- [ ] تستطيع الدخول بإيميلك القديم
- [ ] الصور تظهر في صفحة المنتجات
- [ ] الفواتير والعملاء موجودون

---

## 🔄 كيفية أخذ نسخة احتياطية ومزامنة البيانات حياً (Live Backup & Sync)

في المستقبل، كلما أردت سحب نسخة احتياطية كاملة ومحدثة من الفواتير والبيانات الحية من السيرفر ومزامنتها محلياً:

### 1️⃣ الخطوة الأولى: توليد رابط التصدير الحي من السيرفر
1. افتح صفحة مشروعك في لوحة تحكم DockHosting: [app.dockhosting.dev/projects](https://app.dockhosting.dev/projects)
2. اذهب إلى قسم **Run Migrations**، انسخ هذا الأمر المشفر واضغط **Run Migration**:

```bash
php artisan tinker --execute="file_put_contents('public/export.php', base64_decode('PD9waHAgcmVxdWlyZSBfX0RJUl9fIC4gJy8uLi92ZW5kb3IvYXV0b2xvYWQucGhwJzsgJGFwcCA9IHJlcXVpcmVfb25jZSBfX0RJUl9fIC4gJy8uLi9ib290c3RyYXAvYXBwLnBocCc7ICRrZXJuZWwgPSAkYXBwLT5tYWtlKElsbHVtaW5hdGVcQ29udHJhY3RzXEh0dHBcS2VybmVsOjpjbGFzcyk7ICRrZXJuZWwtPmJvb3RzdHJhcCgpOyBoZWFkZXIoJ0NvbnRlbnQtVHlwZTogdGV4dC9wbGFpbicpOyBoZWFkZXIoJ0NvbnRlbnQtRGlzcG9zaXRpb246IGF0dGFjaG1lbnQ7IGZpbGVuYW1lPSJmcmVzaF9iYWNrdXAuc3FsIicpOyAkdGFibGVzID0gSWxsdW1pbmF0ZVxTdXBwb3J0XEZhY2FkZXNcREI6OnNlbGVjdCgnU0hPVyBUQUJMRVMnKTsgZWNobyAiU0VUIEZPUkVJR05fS0VZX0NIRUNLUz0wO1xuU0VUIFNRTF9NT0RFPSdOT19BVVRPX1ZBTFVFX09OX1pFUk8nO1xuU0VUIE5BTUVTIHV0ZjhtYjQ7XG5cbiI7IGZvcmVhY2ggKCR0YWJsZXMgYXMgJHQpIHsgJHZhcnMgPSBnZXRfb2JqZWN0X3ZhcnMoJHQpOyAkdGFibGVOYW1lID0gcmVzZXQoJHZhcnMpOyAkY3JlYXRlID0gSWxsdW1pbmF0ZVxTdXBwb3J0XEZhY2FkZXNcREI6OnNlbGVjdCgiU0hPVyBDUkVBVEUgVEFCTEUgYHskdGFibGVOYW1lfWAiKTsgJGNyZWF0ZVZhcnMgPSBnZXRfb2JqZWN0X3ZhcnMoJGNyZWF0ZVswXSk7ICRjcmVhdGVTcWwgPSBlbmQoJGNyZWF0ZVZhcnMpOyBlY2hvICJEUk9QIFRBQkxFIElGIEVYSVNUUyBgeyR0YWJsZU5hbWV9YDtcbiIgLiAkY3JlYXRlU3FsIC4gIjtcblxuIjsgJHJvd3MgPSBJbGx1bWluYXRlXFN1cHBvcnRcRmFjYWRlc1xEQjo6dGFibGUoJHRhYmxlTmFtZSktPmdldCgpOyBpZiAoJHJvd3MtPmNvdW50KCkgPiAwKSB7IGZvcmVhY2ggKCRyb3dzLT5jaHVuayg1MCkgYXMgJGNodW5rKSB7ICRmaXJzdFJvdyA9IChhcnJheSkkY2h1bmstPmZpcnN0KCk7ICRjb2xzID0gImAiIC4gaW1wbG9kZSgiYCxgIiwgYXJyYXlfa2V5cygkZmlyc3RSb3cpKSAuICJgIjsgZWNobyAiSU5TRVJUIElOVE8gYHskdGFibGVOYW1lfWAgKHskY29sc30pIFZBTFVFU1xuIjsgJHZhbFJvd3MgPSBbXTsgZm9yZWFjaCAoJGNodW5rIGFzICRyKSB7ICR2YWxzID0gYXJyYXlfbWFwKGZ1bmN0aW9uKCR2YWwpIHsgcmV0dXJuICR2YWwgPT09IG51bGwgPyAnTlVMTCcgOiBJbGx1bWluYXRlXFN1cHBvcnRcRmFjYWRlc1xEQjo6Z2V0UGRvKCktPnF1b3RlKCR2YWwpOyB9LCAoYXJyYXkpJHIpOyAkdmFsUm93c1tdID0gIigiIC4gaW1wbG9kZSgiLCIsICR2YWxzKSAuICIpIjsgfSBlY2hvIGltcGxvZGUoIixcbiIsICR2YWxSb3dzKSAuICI7XG5cbiI7IH0gfSB9IGVjaG8gIlNFVCBGT1JFSUdOX0tFWV9DSEVDS1M9MTtcbiI7'));"
```

### 2️⃣ الخطوة الثانية: تنزيل ملف النسخة الاحتياطية الحية
افتح هذا الرابط المباشر في متصفحك وسينزل ملف `fresh_backup.sql` فوراً بجميع الفواتير والعملاء الحالية:
📥 **[https://facturo.dockhosting.dev/export.php](https://facturo.dockhosting.dev/export.php)**

### 3️⃣ الخطوة الثالثة: المزامنة المحلية على حاسوبك
ضع الملف المنزّل باسم `backup.sql` في مجلد التنزيل Downloads، ثم شغّل هذا الأمر في PowerShell:
```powershell
node c:\Users\youco\.gemini\antigravity-ide\brain\4e3d84e9-93f6-410d-abdc-90f1afc24012\scratch\apply_final_backup.js
```

**النتيجة:** سيتم حفظ نسخة مؤرخة في مجلد `backups/` وتحديث `production_backup.sql` ومزامنة قاعدة بياناتك المحلية بالكامل! 🎉

---

## 🔄 كيف تُحدّث الموقع مستقبلاً؟

كل ما عليك فعله بعد أي تعديل في الكود:
```bash
git add .
git commit -m "وصف التعديل"
git push
```
DockHosting سيكتشف التغيير تلقائياً وينشره! ✅

---

## ⚠️ أشياء مهمة تتذكرها دائماً

| الموضوع | التفاصيل |
|---------|----------|
| **ملف `.env`** | لا ترفعه أبداً لـ GitHub، أضف المتغيرات يدوياً في لوحة Environment |
| **`production_backup.sql`** | ارفعه مؤقتاً للنشر، ثم احذفه فوراً من Git |
| **الصور** | تُخزّن في `public/product-images/` وتُنشر تلقائياً مع الكود |
| **APP_KEY** | يجب أن يكون نفس القيمة دائماً لكي تعمل كلمات المرور القديمة |
| **CSS لا يظهر** | أضف `APP_URL` بالرابط الكامل `https://...dockhosting.dev` |

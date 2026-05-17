## بنك أسئلة الحفظ (Recitation Questions)

ميزة خاصة بمشروع الإتقان — راجع SPECS.md (BR-EXAM-10..13) و ADR-013 في DECISIONS.md.

### الكيانات الأساسية
- `App\Models\RecitationQuestion` — صف لكل سؤال في البنك (سورة/آية/صفحة، بداية ↔ نهاية + `group_number` 1..6).
- `App\Enums\QuestionGroup` — Group1..Group6 مع `shortLabel()` ("م1"...) و `fullLabel()` و `juzRange()` و `fullQuranPairs()`.
- `App\Support\Surah` — تحويل اسم السورة العربية ↔ رقم 1..114 (يدعم تنويعات إملائية وتشكيل).
- `App\Services\ExamQuestionPicker` — قرعة `pickForFullQuran()` / `pickForHalfQuran(array $groupNumbers)`.
- `App\Services\RecitationQuestionImportService` — استيراد من xlsx/xls/csv عبر phpoffice/phpspreadsheet.

### قواعد لازم تحترمها

1. **لا تستنتج `group_number` من السورة/الآية.** السؤال قد يمتد عبر حدود الأجزاء. المجموعة تأتي صراحةً من البنك (BR-EXAM-10).

2. **القرعة في الـ service فقط**، لا في الـ controller. الـ controller يستدعي `ExamQuestionPicker` ويربط النتائج بـ `exam_questions`.

3. **`full_quran`** ⇒ `selected_groups = null` على `exams`.
   **`half_quran`** ⇒ `selected_groups = [g1, g2, g3]` (3 أرقام مختلفة في 1..6). الـ validation في `StartExamRequest` يفرض ذلك (BR-EXAM-11).

4. **منع التكرار**: داخل اختبار واحد، لا يجوز سحب نفس `RecitationQuestion` مرتين. الـ picker يضمن ذلك عبر `excludeIds` حتى لو لزم الأمر.

5. **أسماء السور كأرقام في DB.** عند العرض، استخدم `Surah::nameFor($number)`. عند الاستيراد، استخدم `Surah::numberFor($name)` — يقبل اسماً عربياً أو رقماً مباشرة.

6. **الاستيراد**: الأعمدة المطلوبة (header row بأي ترتيب):
   `question_number, group_number, start_surah, start_ayah, start_page, end_surah, end_ayah, end_page`.
   وضعان مدعومان: `replace` (يحذف الكل ويستورد) أو `upsert` (تحديث المتطابق `(group_number, question_number)` وإضافة الجديد).
   - **كل ملف يتم استيراده يُحفظ** في `storage/app/private/imports/recitation-questions/{Y-m-d}/{Ymd-His}_{slug}.{ext}` بغض النظر عن النجاح. لا تحذف هذه الملفات تلقائياً — هي سجل دائم.
   - لو فشلت صفوف، يُكتب CSV مرافق بنفس الاسم + `.errors.csv` يحتوي كل صف فاشل بأعمدته الأصلية + عمود `error` + UTF-8 BOM. الـ Livewire admin يعرض زر "تحميل سجل الأخطاء" بعد كل استيراد فاشل جزئياً.
   - الـ AuditLog يحتفظ بـ counts فقط + paths، **لا** بيانات الصفوف (تجنّباً لتضخّم الجدول).

7. **الـ Resource** الموحّد للسؤال هو `App\Http\Resources\RecitationQuestionResource` — يُرجع `start` و `end` ككائنين فيهما `surah_number`, `surah_name`, `ayah`, `page`.

8. **معاينة** (BR-EXAM-12): على API استخدم `POST /v1/exams/preview-questions`. على الويب، المعاينة sub-step داخل `Examiner\ExamSession` (state = `previewing`) — لا توجد صفحة منفصلة. الـ flow:
   `search → setup → (selecting_groups إذا half_quran) → previewing → active → saved`
   لا تكتب صف Exam في DB قبل `confirmAndStart` — `pickedQuestions` يعيش في component state فقط.

### Pitfalls شائعة
- لا تنسَ `->load(['questions.recitationQuestion'])` على الـ Exam قبل إعادته في الـ Response، وإلا الـ resource سيُعيد `recitation_question` كـ null.
- `selected_groups` cast كـ array في الـ `Exam` model — تعامل معها كـ PHP array، لا JSON string.
- الـ `inRandomOrder()` فيه أداء معقول لـ ~300 صف بكل مجموعة. لو نمى البنك لآلاف، ضع شرط `WHERE id >= RAND()*MAX(id)` أو خزّن seed.
- ext-zip مطلوبة من PhpSpreadsheet — لو فشلت الـ imports في بيئة جديدة فحص `php -m | grep zip`.

### Routes
- **API**: `POST /api/v1/exams/preview-questions`, `POST /api/v1/exams/start` (الأخير يولّد ويربط).
- **Web (Admin)**: `admin.recitation-questions` — list/CRUD/import.
- **Web (Examiner)**: `examiner.exam` — صفحة واحدة (`Examiner\ExamSession`) تحتوي كل خطوات الـ flow.

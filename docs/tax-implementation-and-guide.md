# कर प्रणाली (Tax System) पूर्ण निर्देशिका तथा प्राविधिक दस्तावेज
### Multi-Country Dynamic Tax Engine & Accounting Integration (RNSaaS)

---

# भाग १: नेपाली व्यवसायिक गाइड (लेखा तथा इन्जिनियरिङ मार्गचित्र)
> **नोट:** यो भाग एक सिनियर चार्टर्ड एकाउन्टेन्ट र सफ्टवेयर आर्किटेक्टको दृष्टिकोणबाट तयार पारिएको हो ताकि नयाँ (Fresher) प्रयोगकर्ता वा विकासकर्ताले कर प्रणालीको मर्म, सिद्धान्त, प्रयोग र सेटिङ सजिलै बुझ्न सकून्।

---

## १. कर प्रणालीका आधारभूत Terminology (शब्दावलीहरू) र तिनको कार्यप्रणाली

लेखा प्रणाली (Accounting) मा कर भनेको कम्पनीको आम्दानी (Income) वा वास्तविक खर्च (Expense) होइन। यो सरकारको पैसा हो, जुन हामीले ग्राहकसँग उठाएर सरकारलाई बुझाउनुपर्ने **दायित्व (Liability)** हुन्छ वा सामान किन्दा अग्रिम तिरेको कर सरकारसँग कट्टी दाबी गर्ने **अधिकार (Asset)** हुन्छ।

### क) मूल्य अभिवृद्धि कर (VAT - Value Added Tax)
- **परिभाषा:** उत्पादनदेखि अन्तिम उपभोक्तासम्म पुग्दा वस्तु वा सेवामा थपिएको मूल्य (Value Addition) मा लाग्ने अप्रत्यक्ष कर।
- **नेपालको सन्दर्भ:** नेपालमा सामान्यतया **१३%** भ्याट लागु छ।

### ख) Output Tax (बिक्री भ्याट)
- **के हो?** जब तपाईंले ग्राहकलाई कुनै सामान वा सेवा बेच्नुहुन्छ, तब बिक्री मूल्यमा १३% कर थपेर ग्राहकसँग असुल गर्नुहुन्छ।
- **लेखा प्रभाव:** यो कम्पनीको आम्दानी होइन, सरकारलाई बुझाउनुपर्ने **दायित्व (Current Liability)** हो।

### ग) Input Tax (खरिद भ्याट / Input Tax Credit)
- **के हो?** जब तपाईंले आफ्नो व्यवसायका लागि कुनै सामान, कच्चा पदार्थ वा सेवा खरिद गर्दा बिक्रेतालाई १३% कर तिर्नुहुन्छ।
- **लेखा प्रभाव:** भ्याटमा दर्ता भएको व्यवसायले यो कर सरकारबाट फिर्ता वा बिक्री करसँग कट्टी गर्न पाउँछ। त्यसैले यो **चालु सम्पत्ति (Current Asset)** जस्तै हो।

### घ) Net Tax Payable / Refundable (भ्याट मिलान)
- **सूत्र:** `Net VAT = Output Tax (उठेको कर) - Input Tax (तिरेको कर)`
- **उदाहरण:**
  - महिनाभर ग्राहकसँग बिक्री गर्दा उठेको भ्याट: रु. १,३०,००० (Output Tax)
  - महिनाभर सामान किन्दा तिरेको भ्याट: रु. ९०,००० (Input Tax)
  - सरकार (IRD) लाई बुझाउनुपर्ने खुद भ्याट: रु. १,३०,००० - ९०,००० = **रु. ४०,०००**।
  - यदि बिक्री भन्दा खरिद कर बढी भयो भने त्यो रकम अर्को महिनामा सार्न (Carry Forward) वा फिर्ता (Refund) माग्न पाइन्छ।

### ङ) Taxable, Exempt र Zero-Rated (करका तीन प्रकार)
1. **Taxable (करयोग्य - १३%):** सामान्य सबै व्यापारिक सामान तथा सेवाहरू (जस्तै: कम्प्युटर, फर्निचर, मोबाइल, परामर्श सेवा)।
2. **Exempt (कर छुट - ०%):** भ्याट ऐनको अनुसूची १ अनुसारका आधारभूत वस्तुहरू (जस्तै: चामल, दाल, नुन, ताजा तरकारी, औषधि, स्वास्थ्य तथा शिक्षा सेवा)।
   - *विशेषता:* यसमा बिक्री गर्दा भ्याट जोडिँदैन, र यस्ता सामान किन्दा तिरेको भ्याट पनि **फिर्ता वा कट्टी दाबी गर्न पाइँदैन** (खर्चमा जोडिन्छ)।
3. **Zero-Rated (शून्य दर - ०%):** भ्याट ऐनको अनुसूची २ अनुसार विदेशमा सामान वा सेवा निकासी (Export) गर्दा।
   - *विशेषता:* बिलमा ०% कर हुन्छ, तर यस्तो सामान बनाउन वा किन्न लागेको सबै Input Tax **१००% फिर्ता दाबी गर्न पाइन्छ**।

### च) Withholding Tax / TDS (स्रोतमा कर कट्टी)
- **के हो?** भुक्तानी दिने व्यक्तिले भुक्तानी पाउने व्यक्तिको आयकर बापत पहिल्यै केही प्रतिशत रकम काटेर राजस्व खातामा जम्मा गरिदिने प्रक्रिया।
- **नेपालका मुख्य TDS दरहरू:**
  - घर बहाल (House Rent): **१०%**
  - ठेक्का वा सामान आपूर्ति (Contract/Supply): **१.५%**
  - परामर्श तथा अडिट सेवा (Consultancy): भ्याट बिल भए **१.५%**, पान बिल भए **१५%**।

### छ) Tax Exclusive vs Tax Inclusive मूल्य
- **Tax Exclusive (कर बाहेक):** सामानको तोकिएको मूल्यमा कर जोडिएको हुँदैन। (उदा: सामान रु. १,००० + १३% भ्याट रु. १३० = जम्मा रु. १,१३०)।
- **Tax Inclusive (कर सहित):** सामानको तोकिएको मूल्यभित्रै कर समावेश हुन्छ। (उदा: रु. १,१३० को सामान किन्दा त्यसमा रु. १,००० सामानको र रु. १३० भ्याट पहिले नै जोडिएको हुन्छ)।

---

## २. प्रणालीमा भएका सबै Tax Links / Pages र तिनको काम

हाम्रो प्रणालीमा Tax Management अन्तर्गत निम्न ९ वटा मुख्य पृष्ठहरू उपलब्ध छन्:

| क्र.सं. | मेनु / URL | मुख्य काम (Purpose) |
|---|---|---|
| १ | **Tax Dashboard** (`/admin/tax`) | महिनाभरको बिक्री कर, खरिद कर, खुद बुझाउनुपर्ने कर र कर अडिटको मुख्य सारांश हेर्न। |
| २ | **Tax Rates** (`/admin/tax/rates`) | करका प्रतिशतहरू (१३%, ०%, १.५%, १०%) थप्न, मिलाउन र भविष्यमा मिति तोकेर लागु गर्न। |
| ३ | **Tax Categories** (`/admin/tax/categories`) | सामानहरूलाई वर्गीकरण गर्न (Standard Taxable, Exempt, Zero-Rated)। |
| ४ | **Tax Types** (`/admin/tax/types`) | करका मुख्य कानुनहरू (VAT, TDS, Excise, Custom Duty) व्यवस्थापन गर्न। |
| ५ | **Tax Rules** (`/admin/tax/rules`) | विशेष सर्तहरू बनाउन (जस्तै: फलानो राज्य वा देश बाहिर जाँदा स्वतः ०% लागु हुने)। |
| ६ | **Tax Exemptions** (`/admin/tax/exemptions`) | कुटनीतिक नियोग (Embassy) वा कर छुट पाएका संस्थाका प्रमाणपत्र दर्ता गरी कर मिनाहा गर्न। |
| ७ | **Tax Settings** (`/admin/tax/settings`) | कम्पनीको देश, प्यान नम्बर, डिफल्ट भ्याट दर, राउन्डिङ र १-क्लिक नेपाल प्रिसेट मिलाउन। |
| ८ | **GL Account Mapping** (`/admin/tax/accounts`) | करको पैसा कुन-कुन खाता (Output, Input, TDS, Settlement) मा जाने हो जोड्न। |
| ९ | **Tax Reports** (`/admin/tax/reports`) | आन्तरिक राजस्व कार्यालय (IRD) मा बुझाउनुपर्ने मासिक/चौमासिक भ्याट र TDS विवरण हेर्न र छाप्न। |

---

## ३. सबै Field हरूको विस्तृत व्याख्या (Field-by-Field Breakdown)

### क) Tax Settings (`/admin/tax/settings`) का Fields

1. **Country (देश):**
   - *के भर्ने:* `Nepal` (वा व्यवसाय संचालन भएको देश)।
   - *किन चाहिन्छ:* देश अनुसार करको नियम र मुद्रा (Currency) फरक हुने भएकाले।
2. **Tax Registration Number (कर दर्ता / PAN नम्बर):**
   - *के भर्ने:* आन्तरिक राजस्व विभागले दिएको **९ अंकको PAN नम्बर**।
   - *किन चाहिन्छ:* इनभ्वाइस र अडिट रिपोर्टमा अनिवार्य कानुनी विवरण छाप्न।
3. **Registered Business Name (दर्ता भएको फर्म/कम्पनीको नाम):**
   - *के भर्ने:* पान दर्ता प्रमाणपत्रमा भएको आधिकारिक नाम।
4. **Tax Authority Name (कर कार्यालयको नाम):**
   - *के भर्ने:* `Inland Revenue Department (IRD)` वा सम्बन्धित आन्तरिक राजस्व कार्यालय (जस्तै: आ.रा.का. पुतलीसडक)।
5. **Tax Regime (कर प्रणालीको ढाँचा):**
   - *विकल्प:* `VAT` (मूल्य अभिवृद्धि कर), `GST` (भारतको लागि), `Sales Tax` (अमेरिकाको लागि)। नेपालको लागि `VAT` छान्न पर्छ।
6. **Reporting Frequency (विवरण बुझाउने अवधि):**
   - *विकल्प:* `Monthly` (मासिक - ठूला व्यवसाय), `Trimonthly/Quarterly` (चौमासिक - साना व्यवसाय)।
7. **Accounting Method (लेखा विधि):**
   - *विकल्प:* `Accrual` (बिल काट्ने बित्तिकै कर गणना हुने - ९९% व्यवसायले यही गर्छन्) वा `Cash` (पैसा हात परेपछि मात्र कर गणना हुने)।
8. **Default Sales Tax Rate (डिफल्ट बिक्री कर दर):**
   - *विकल्प:* `VAT 13%`। नयाँ सामान बेच्दा स्वतः यो दर छानेर आउँछ।
9. **Default Purchase Tax Rate (डिफल्ट खरिद कर दर):**
   - *विकल्प:* `VAT 13%`। नयाँ सामान किन्दा स्वतः यो दर छानेर आउँछ।
10. **Default Pricing Mode (मूल्यको तरिका):**
    - *Tax Exclusive:* सामानको मूल्यमा कर बाहेक (सामान + १३%)।
    - *Tax Inclusive:* सामानको मूल्यमै कर समावेश (MRP मूल्य)।
11. **Rounding Level & Precision (दशांश मिलाउने):**
    - *Rounding Level:* `Line Item` (प्रत्येक सामानको लाइनमा दशमलव मिलाउने) वा `Invoice Total` (कुल जोडमा मात्र मिलाउने)।
    - *Precision:* `2` (पैसामा दुई अंक: जस्तै रु. १२.५०)।
12. **Enable E-Invoice Compliance:**
    - *के हो:* आन्तरिक राजस्व विभागको केन्द्रीय बिलिङ प्रणाली (CBMS) सँग सिधै बिल पठाउने तयारी खोल्ने वा बन्द गर्ने।

---

### ख) GL Account Mapping (`/admin/tax/accounts`) का Fields

यो पृष्ठले **कर मोड्युल** र **लेखा मोड्युल (Chart of Accounts)** बीच पुलको काम गर्दछ:

1. **Output Tax Account (बिक्री कर खाता):**
   - *के छान्ने:* `2100 - VAT Output (Sales Tax)`।
   - *किन:* ग्राहकबाट उठाएको १३% भ्याट यो खातामा क्रेडिट (जम्मा) हुन्छ। यो तिर्नुपर्ने दायित्व हो।
2. **Input Tax Account (खरिद कर खाता):**
   - *के छान्ने:* `2110 - VAT Input (Purchase Tax)`।
   - *किन:* सामान किन्दा तिरेको १३% भ्याट यो खातामा डेबिट हुन्छ। पछि बिक्री करसँग कट्टी गर्न पाइन्छ।
3. **Withholding Tax Account (TDS भुक्तानी खाता):**
   - *के छान्ने:* `2120 - TDS Payable`।
   - *किन:* कुनै भेन्डर वा घरबेटीलाई भुक्तानी गर्दा काटेको १.५% वा १०% रकम यो खातामा बस्छ।
4. **Tax Settlement Account (कर मिलान खाता):**
   - *के छान्ने:* `2130 - Tax Settlement / Clearing`।
   - *किन:* महिनाको अन्त्यमा Input Tax र Output Tax को हिसाब मिलान गर्दा यो खाता प्रयोग हुन्छ।

---

### ग) Tax Rates (`/admin/tax/rates`) का Fields

1. **Rate Name (दरको नाम):** जस्तै `VAT 13%`, `TDS Contract 1.5%`, `Exempt 0%`।
2. **Tax Code (कोड):** प्रणालीमा छिटो चिन्न प्रयोग हुने संक्षिप्त रूप (जस्तै: `VAT-13`, `EXT-0`, `TDS-15`)।
3. **Tax Rate (प्रतिशत):** दरको संख्यात्मक मान (जस्तै: `13.00`, `0.00`, `1.50`)।
4. **Rate Type (प्रकार):** `Percentage` (प्रतिशत) वा `Fixed Amount` (निश्चित रकम)। ९९% अवस्थामा `Percentage` हुन्छ।
5. **Tax Category:** `Standard` (१३%), `Exempt` (छुट), `Zero-Rated` (निकासी), `Withholding` (TDS)।
6. **Timeline Status (समय चक्र):**
   - `Active`: हाल तुरुन्त बिलमा प्रयोग भइरहेको दर।
   - `Scheduled`: सरकारले बजेटमा घोषणा गरेको तर फलानो मितिबाट मात्र लागु हुने दर।
   - `Expired`: पुरानो भइसकेको दर।
7. **Valid From / Valid Until (लागु हुने मिति):**
   - सरकारको नयाँ नियम सुरु हुने र सकिने मिति।

---

### घ) Tax Categories (`/admin/tax/categories`) का Fields

1. **Category Title:** नाम (जस्तै: `Standard Rateable Goods`, `Exempt Agriculture & Health`, `Exported Services`)।
2. **Category Code:** कोड (`STANDARD`, `EXEMPT`, `ZERO_RATED`)।
3. **Tax Treatment:** कर लाग्ने ढाँचा (`Taxable`, `Exempt`, `Zero-Rated`)।
4. **Claimable Input (खरिद भ्याट कट्टी गर्न मिल्ने/नमिल्ने):**
   - `STANDARD`: मिल्छ (Yes)।
   - `EXEMPT`: मिल्दैन (No - खरिद भ्याट खर्चमा जोडिन्छ)।
   - `ZERO_RATED`: मिल्छ (Yes - निकासी भएकाले १००% फिर्ता पाइन्छ)।

---

## ४. कहाँ, कसरी र किन प्रयोग हुन्छ? (व्यवसायिक जीवनचक्र / Business Flow)

### पहिलो चरण: सामान बनाउँदा (Item Master)
- जब स्टोर किपर वा अकाउन्टेन्टले नयाँ सामान थप्छन् (उदा: ल्यापटप), त्यसमा **Tax Category: STANDARD** छानिएको हुन्छ।
- यदि चामल वा औषधि थपिएको छ भने, त्यसमा **Tax Category: EXEMPT** छानिएको हुन्छ।

### दोस्रो चरण: खरिद गर्दा (Purchase Bill Entry)
- जब सप्लायरबाट रु. १,००,००० को ल्यापटप किनियो, बिल इन्ट्री गर्दा स्वतः १३% भ्याट (रु. १३,०००) जोडिन्छ।
- **लेखा भौचर (Journal Entry):**
  - डेबिट: Inventory (सामान) = रु. १,००,०००
  - डेबिट: Input Tax Account (२११०) = रु. १३,०००
  - क्रेडिट: Accounts Payable (साहुको खाता) = रु. १,१३,०००

### तेस्रो चरण: बिक्री गर्दा (Sales Invoice / POS)
- जब ग्राहकलाई रु. १,५०,००० मा त्यो ल्यापटप बेचिन्छ, बिलमा स्वतः १३% भ्याट (रु. १९,५००) लाग्छ।
- **लेखा भौचर (Journal Entry):**
  - डेबिट: Accounts Receivable (ग्राहक वा नगद) = रु. १,६९,५००
  - क्रेडिट: Sales Revenue (बिक्री आम्दानी) = रु. १,५०,०००
  - क्रेडिट: Output Tax Account (२१००) = रु. १९,५००

### चौथो चरण: महिनाको अन्त्यमा कर मिलान (Month-End VAT Settlement)
- महिना मरेपछि आन्तरिक राजस्व कार्यालय (IRD) मा भ्याट विवरण भर्नुपर्छ:
  - ग्राहकसँग उठाएको कर (Output Tax): रु. १९,५००
  - सामान किन्दा तिरेको कर (Input Tax): रु. १३,०००
  - सरकारलाई बुझाउनुपर्ने खुद कर = **रु. ६,५००**।
- अकाउन्टेन्टले बैंकबाट रु. ६,५०० राजस्व दाखिला गरेपछि Input र Output दुवै खाता शून्य हुन्छन्।

---

## ५. यो कर प्रणालीलाई Enable वा Disable कसरी गर्ने?

### क) यदि व्यवसाय भ्याटमा दर्ता छैन (Non-VAT / केवल PAN मात्र छ) भने के गर्ने?
- धेरै साना पसल वा खुद्रा व्यापारीहरू भ्याटमा दर्ता नभई केवल **PAN** मा मात्र दर्ता भएका हुन्छन्।
- यस्तो अवस्थामा:
  1. **Admin -> Tax Management -> Tax Settings** मा जानुहोस्।
  2. **Default Sales Tax Rate:** `Exempt 0%` वा `Non-Taxable 0%` छान्नुहोस्।
  3. सामान (Items) बनाउँदा सबै सामानमा `EXEMPT` क्याटगोरी तोक्नुहोस्।
  4. यसले गर्दा ग्राहकलाई काटिने बिलमा १३% भ्याट लाग्दैन, बिल सादा PAN बिलको रूपमा जारी हुन्छ।

### ख) यदि व्यवसाय भ्याट (VAT) मा दर्ता छ भने:
- **Tax Settings** मा गएर **⚡ Apply NP Standard Tax Rates** थिच्नुहोस्।
- यसले स्वतः नेपालको १३% भ्याट, ०% छुट, र TDS का नियमहरू लागु गर्छ।

### ग) के पूरै Tax Module हटाउन मिल्छ?
- हाम्रो प्रणालीमा Tax Module कानूनी अनुपालन (Statutory Compliance) को अभिन्न अङ्ग भएकाले यसलाई स्थायी रूपमा सक्रिय (Enabled) राखिएको छ।
- तर यदि कुनै संस्थाले करको झन्झट बिना सादा बिलिङ मात्र गर्न चाहेमा, **Tax Rate लाई ०% बनाएर** वा **Items मा कर नजोडी** सजिलै प्रयोग गर्न सकिन्छ। कुनै कोड हटाउनु वा मेटाउनु पर्दैन।

---

# भाग २: प्राविधिक तथा इन्जिनियरिङ दस्तावेज (Technical Architecture)

---

## ६. Executive Summary & Root Cause Analysis (500 Error Resolution)

### 6.1 Root Cause of 500 Errors in Tax Module
Earlier, three key endpoints in the Tax module threw HTTP 500 Internal Server Errors:
1. `/admin/tax/categories` (`TaxCategoryController@index`)
2. `/admin/tax/settings` (`TaxSettingController@index` & `applyPreset`)
3. `/admin/tax/accounts` (`TaxAccountMappingController@index`)

#### Cause 1: Database Column Mismatch (`is_default` & `is_active` vs `timeline_status`)
- **What happened:** In `TaxCategoryController.php` and `TaxSettingController.php`, Eloquent queries were filtering by non-existent columns:
  ```php
  // Faulty query:
  TaxRate::where('is_active', true)->where('is_default', true)->first();
  ```
- **Database Reality:** The `tax_rates` database table schema tracks rate lifecycles using:
  - `timeline_status` (`VARCHAR`: `active`, `scheduled`, `expired`, `draft`)
  - `tax_category` (`VARCHAR`: `standard`, `reduced`, `exempt`, `zero_rated`, `withholding`)
  There was never an `is_default` or `is_active` column in `tax_rates`.
- **The Fix:** Updated all controller lookups and preset seeders to query:
  ```php
  TaxRate::where('tenant_id', $tenantId)
      ->where('timeline_status', 'active')
      ->where('tax_category', 'standard')
      ->first();
  ```

#### Cause 2: Inertia View Path Resolution Error
- **What happened:** In `TaxAccountMappingController.php`, the render call was using a double colon:
  ```php
  return Inertia::render('Tax::Accounts/Index', [...]);
  ```
- **Inertia Reality:** The Vite dynamic page resolver in `resources/js/app.ts` parses module page paths as `Tax/Accounts/Index.vue`. The `Tax::` syntax failed page resolution and triggered an unhandled Inertia exception (500).
- **The Fix:** Normalized the view render path:
  ```php
  return Inertia::render('Tax/Accounts/Index', [...]);
  ```

#### Cause 3: Non-existent Column in `tax_types` Table
- **What happened:** In `TaxAccountMappingController.php`, line 58 was querying:
  ```php
  TaxType::where('tenant_id', $tenantId)->get(['id', 'name', 'code', 'category']);
  ```
- **Database Reality:** The `tax_types` table has columns `scope` (`sales`, `purchases`, `both`) and `status`, but NO `category` column. This triggered `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'category' in 'SELECT'`.
- **The Fix:** Changed the query to fetch `scope` instead of `category`:
  ```php
  TaxType::where('tenant_id', $tenantId)->get(['id', 'name', 'code', 'scope']);
  ```

---

## 7. Tax Architecture & Database Schema

The tax module is organized under `Modules/Tax` and interacts with `Modules/Accounting` (General Ledger) and `Modules/Inventory` (Item Tax Categories).

```
Modules/Tax/
├── app/
│   ├── Http/Controllers/
│   │   ├── TaxRateController.php          # CRUD for Tax Rates & Timelines
│   │   ├── TaxCategoryController.php      # Tax Categories (Standard, Exempt, Zero-Rated)
│   │   ├── TaxSettingController.php       # System Tax Config & Country Presets
│   │   ├── TaxAccountMappingController.php# GL Account Mapping for Auto-Journaling
│   │   └── TaxAuditController.php         # Audit logs for tax changes
│   └── Models/
│       ├── TaxRate.php
│       ├── TaxCategory.php
│       ├── TaxSetting.php
│       └── TaxType.php
└── resources/js/Pages/
    ├── Rates/Index.vue
    ├── Categories/Index.vue
    ├── Settings/Index.vue
    └── Accounts/Index.vue
```

### Key Database Tables

| Table | Description | Key Columns |
|---|---|---|
| `tax_settings` | Organization-level tax configuration | `tenant_id`, `country`, `tax_regime`, `tax_registration_number` (PAN/VAT), `default_sales_tax_rate_id`, `default_purchase_tax_rate_id`, `output_tax_account_id`, `input_tax_account_id`, `withholding_tax_account_id`, `tax_settlement_account_id` |
| `tax_rates` | Dynamic rates with timeline versioning | `tenant_id`, `name`, `code`, `rate`, `rate_type` (`percentage`/`fixed`), `tax_category` (`standard`, `reduced`, `exempt`, `zero_rated`, `withholding`), `timeline_status` (`active`, `scheduled`, `expired`, `draft`), `valid_from`, `valid_until` |
| `tax_categories` | Item classification | `tenant_id`, `name`, `code` (`STANDARD`, `EXEMPT`, `ZERO_RATED`), `description` |
| `chart_of_accounts` | Accounting ledger (GL) accounts | `tenant_id`, `code`, `name`, `type` (`current_liability`, `current_asset`), `financial_statement_section` |

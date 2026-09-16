# Tax Integration Across All Modules — SathiSaaS System
**प्रणालीका सम्पूर्ण मोड्युलहरूमा कर (Tax/VAT/TDS) कार्यान्वयनको विस्तृत अडिट, डेटा प्रवाह र प्राविधिक गाइड**

---

## १. कार्यकारी सारांश (Executive Summary)

SathiSaaS प्लेटफर्ममा **Tax Module** ले केन्द्रीय कर इन्जिन (Centralized Tax Engine) को रूपमा काम गर्दछ। आधुनिक बहु-भाडामा लिइएको (Multi-Tenant) आर्किटेक्चरमा, कर केवल एउटा मोड्युलमा मात्र सीमित नरहेर संस्थाका सम्पूर्ण व्यावसायिक प्रक्रियाहरू—जस्तै बिक्री (Sales), खरिद (Purchases), खुद्रा काउन्टर (POS), मौज्दात (Inventory), कर्मचारी तलब (Payroll), र उत्पादन (MRP)—मा प्रत्यक्ष जोडिएको हुन्छ।

यस कागजातमा प्रणालीका सबै १५ वटा मोड्युलहरूमा करको कार्यान्वयन स्थिति (Implementation Status), लेखा प्रविष्टि (Journal Entries & GL Mapping), डेटा प्रवाह (Data Flow), र अन्तर-सम्बन्धको विस्तृत विश्लेषण गरिएको छ।

---

## २. मोड्युल अनुसार कर कार्यान्वयन स्थिति (Module-Wise Tax Status Matrix)

| क्र.सं. | मोड्युल (Module) | करको प्रकार (Tax Types Handled) | मुख्य तालिकाहरू (Key DB Tables) | GL खाता म्यापिङ (GL Accounts) | स्थिति (Status) |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **१** | **Tax (केन्द्रीय कर मोड्युल)** | VAT, GST, Sales Tax, TDS, Statutory Withholding | `tax_settings`, `tax_rates`, `tax_types`, `tax_categories`, `tax_exemption_reasons` | Output Tax (2100), Input Tax (2110), TDS (2120), Settlement (2130) | **पूर्ण (100% Completed)** |
| **२** | **Accounting (लेखा मोड्युल)** | Purchase Input VAT, Sales Output VAT, Vendor TDS | `accounting_sales_invoices`, `accounting_purchase_bills`, `accounting_journal_entries` | 2100 (VAT Output), 2110 (VAT Input), 2120 (TDS Payable) | **पूर्ण एकीकृत (Fully Integrated)** |
| **३** | **POS (खुद्रा बिक्री काउन्टर)** | POS Output Tax, Inclusive/Exclusive Pricing | `pos_orders`, `pos_order_items`, `pos_settings` | Tax Profile & Default Sales Rate -> POS Receipt & Daily Totals | **पूर्ण एकीकृत (Fully Integrated)** |
| **४** | **Inventory (सामान तथा मौज्दात)** | Item Tax Rates, Tax Categories | `inventory_items`, `inventory_categories` | `tax_rate` column linked to `TaxRate` selector & defaults | **एकीकृत (Integrated)** |
| **५** | **Payroll (तलब तथा मानव संसाधन)** | Salary TDS (पारिश्रमिक कर / Section 87), PCB/PAYE | `payroll_runs`, `payroll_payslips`, `payroll_components` | 2140-04 (Payroll Tax / TDS Withholding Payable) | **एकीकृत (Integrated)** |
| **६** | **Customer & Vendor (ग्राहक/साहु)** | Tax Identification (PAN / VAT Number), Exemption | `accounting_customers`, `accounting_vendors` | `tax_number`, `tax_exemption_reason` | **एकीकृत (Integrated)** |
| **७** | **MRP (उत्पादन/म्यानुफ्याक्चरिङ)** | Procurement Input Tax, Finished Goods Tax | `mrp_production_orders`, `mrp_boms` | Raw materials via Purchase Bills, Finished goods via Sales | **अप्रत्यक्ष एकीकृत (Via Accounting)** |
| **८** | **Subscription (साँस सदस्यता)** | Platform SaaS Billing VAT / Fee Invoices | `subscriptions`, `subscription_plans` | Platform/Tenant SaaS fee ledger | **आधारभूत (Basic / Ready)** |

---

## ३. प्रत्येक मोड्युलमा कर कसरी काम गर्दछ? (Deep Dive per Module)

### ३.१ Accounting Module (लेखा मोड्युल)

लेखा मोड्युलमा कर दुईतर्फी (Double-Entry Bookkeeping) का आधारमा स्वचालित रूपमा जर्नल भौचरमा दर्ता हुन्छ:

#### क) Purchase Bills (खरिद बिल - Input Tax / VAT Input)
जब संस्थाले सामान वा सेवा खरिद गर्छ, खरिद बिल जारी गर्दा विक्रेताले लगाएको १३% भ्याटलाई **Input Tax (सम्पत्ति/कट्टी पाउने कर)** को रूपमा राखिन्छ।
* **Action:** `Modules\Accounting\Application\Actions\PurchaseBills\PostPurchaseBillAction`
* **Journal Entry (लेखा भौचर):**
  - **Debit:** सम्बन्धित खर्च वा मौज्दात खाता (Expense/Inventory Account) `[Line Subtotal]`
  - **Debit:** खरिद कर खाता (Input Tax Account - Code: 2110) `[Line Tax Amount]`
  - **Credit:** साहुको खाता (Accounts Payable / Vendor) `[Bill Grand Total]`
* **उदाहरण:**
  - सामान मूल्य: रु. १०,०००
  - १३% भ्याट: रु. १,३००
  - जम्मा तिर्नुपर्ने: रु. ११,३००
  - *Dr. Inventory/Expense: रु. १०,०००*
  - *Dr. VAT Input Account (2110): रु. १,३००*
  - *Cr. Vendor Payable: रु. ११,३००*

#### ख) Sales Invoices (बिक्री बिल - Output Tax / VAT Output)
जब संस्थाले ग्राहकलाई सामान वा सेवा बिक्री गर्छ, ग्राहकबाट संकलन गरिएको १३% भ्याटलाई **Output Tax (सरकारलाई बुझाउनुपर्ने दायित्व)** को रूपमा राखिन्छ।
* **Action:** `Modules\Accounting\Application\Actions\Invoices\PostSalesInvoiceAction`
* **Journal Entry (लेखा भौचर):**
  - **Debit:** आसामी/ग्राहक खाता (Accounts Receivable / Customer) `[Invoice Grand Total]`
  - **Credit:** बिक्री आम्दानी खाता (Sales Revenue Account) `[Invoice Subtotal]`
  - **Credit:** बिक्री कर खाता (Output Tax Account - Code: 2100) `[Invoice Tax Total]`
* **विशेष सुधार (Recently Refined):** `PostSalesInvoiceAction` ले अब `TaxSetting` मा कन्फिगर गरिएको `output_tax_account_id` (वा पूर्वनिर्धारित २१०० खाता) मा कर रकमलाई स्वतः क्रेडिट गर्दछ। जसले गर्दा आम्दानी (Revenue) वास्तविक कर-रहित मूल्यमा बस्दछ।

#### ग) Tax Settlement / VAT Clearing (कर मिलान भौचर)
महिनाको अन्त्यमा वा कर विवरण (VAT Return) बुझाउँदा:
* यदि **Output VAT > Input VAT** भएमा: संस्थाले बाँकी रकम सरकारलाई कर कार्यालयमा बुझाउनुपर्छ।
  - *Dr. VAT Output (2100)*
  - *Cr. VAT Input (2110)*
  - *Cr. Tax Settlement Payable (2130) / Bank Account*
* यदि **Input VAT > Output VAT** भएमा: बाँकी रकम आउँदो महिनामा कट्टी गर्न (Carry Forward) वा फिर्ता दाबी (VAT Refund) गर्न पाइन्छ।

---

### ३.२ POS Module (खुद्रा बिक्री काउन्टर)

POS मोड्युल खुद्रा पसल, सुपरमार्केट तथा रेस्टुरेन्टहरूको लागि डिजाइन गरिएको हो, जहाँ द्रुत बिलिङ आवश्यक हुन्छ।

* **कर प्रोफाइल लोड (Dynamic Tax Profile):**
  - क्यासियरले लगइन गर्दा `PosController` ले टेनेन्टको `TaxSetting` र `TaxRate` बाट करको नियम लोड गर्छ।
  - मूल्यको मोड (Pricing Mode):
    - **Tax-Exclusive (कर बाहेक):** सामानको मूल्य रु. १०० भए १३% भ्याट थपेर रु. ११३ लिइन्छ।
    - **Tax-Inclusive (कर सहित):** सामानको मूल्य रु. १०० भए त्यसभित्रै कर समाहित हुन्छ (मूल्य: रु. ८८.५० + भ्याट: रु. ११.५०)।
* **आधिकारिक कर बिजक (Official Tax Invoice):**
  - `PosSettingController` र `ReceiptTemplates.vue` मा ५ प्रकारका रसिद ढाँचाहरू उपलब्ध छन् (`modern`, `classic`, `restaurant`, `tax_invoice`, `boutique`)।
  - प्रत्येक रसिदमा संस्थाको **PAN/VAT Number**, ग्राहकको PAN (यदि रु. १०,००० भन्दा माथिको बिक्री भएमा), करको दर, कर रकम, र QR Code छापिने व्यवस्था छ।
* **दैनिक बिक्री तथा कर सारांश (End of Day POS Tax Summary):**
  - `PosReportController` ले दैनिक कति खुद बिक्री (Net Sales) भयो र कति भ्याट (Tax Total) संकलन भयो, सो को छुट्टाछुट्टै हिसाब राख्दछ।

---

### ३.३ Inventory Module (सामान तथा मौज्दात व्यवस्थापन)

सामानको मौज्दात र मूल्य निर्धारण करको दरसँग प्रत्यक्ष जोडिन्छ:

* **Item Tax Configuration (`inventory_items`):**
  - प्रत्येक सामानमा `tax_rate` फिल्ड रहन्छ।
  - सामान दर्ता वा सम्पादन गर्दा (`ItemController::create`, `ItemController::edit`), टेनेन्टको `TaxRate` तालिकाबाट दरहरू ड्रपडाउनमा आउँछन्।
  - यदि कुनै सामान कर-मुक्त (VAT Exempt - जस्तै चामल, नुन, औषधि) हो भने दर ०% चयन गर्न सकिन्छ।
* **मौज्दात मूल्याङ्कन (Inventory Valuation - FIFO / Weighted Average):**
  - भ्याट दर्ता भएको संस्थाको लागि खरिद गर्दा तिरेको १३% भ्याट कट्टी पाइने भएकोले, सामानको लागत मूल्य (Cost Price) मा भ्याट जोडिँदैन। यसले सामानको मौज्दात मूल्याङ्कन सही राख्दछ।

---

### ३.४ Payroll Module (कर्मचारी तलब तथा पारिश्रमिक कर)

पारिश्रमिकमा लाग्ने करलाई **Salary TDS (पारिश्रमिक आयकर कट्टी - Section 87 under Income Tax Act 2058)** भनिन्छ।

* **करयोग्य र करमुक्त भत्ताहरू (Taxable vs Non-Taxable Components):**
  - `PayrollComponentController` मा प्रत्येक भत्ताको लागि `is_taxable` फ्ल्याग हुन्छ।
  - उदाहरण:
    - आधारभूत तलब (Basic Salary): करयोग्य (Taxable)
    - महँगी भत्ता (Dearness Allowance): करयोग्य (Taxable)
    - यातायात खर्च / खाजा खर्च (नियम अनुसारको सीमासम्म): करमुक्त (Non-Taxable)
* **स्ल्याब अनुसारको कर कट्टी (Tax Slabs & Social Security):**
  - `StatutorySchemeController` ले बहु-देशीय कर प्रणाली समर्थन गर्दछ (नेपालको १%, १०%, २०%, ३०%, ३६% स्ल्याबहरू, भारतको Sec 192, मलेसियाको PCB/MTD, अमेरिकाको FITW)।
* **तलब भुक्तानीको जर्नल भौचर (Payroll Journal Voucher):**
  - **Debit:** कर्मचारी तलब खर्च (Salaries & Wages Expense) `[Gross Salary]`
  - **Credit:** सामाजिक सुरक्षा कोष कट्टी (SSF Payable)
  - **Credit:** पारिश्रमिक कर खाता (Payroll TDS Payable - Code: 2140-04)
  - **Credit:** बैंक / खुद भुक्तानी दिनुपर्ने (Net Salary Payable / Bank)

---

### ३.५ Customer & Vendor Modules (ग्राहक तथा साहु)

* **कर दर्ता नम्बर (PAN / VAT ID):**
  - ग्राहक र साहुको प्रोफाइलमा `tax_number` सुरक्षित गरिन्छ।
  - जब कुनै खरिद बिल वा बिक्री बिल बनाइन्छ, सो नम्बर स्वतः बिजकमा प्रदर्शित हुन्छ। नेपालको कानुन अनुसार रु. १०,००० भन्दा बढीको कारोबारमा क्रेताको स्थायी लेखा नम्बर अनिवार्य उल्लेख हुनुपर्छ।
* **कर छुट (Tax Exemption Status):**
  - कुटनीतिक नियोग (Diplomatic Missions) वा नेपाल सरकारको कर छुट प्राप्त आयोजनाहरूको लागि `tax_exemption_reason` छानेर ०% भ्याट बिलिङ गर्न सकिन्छ।

---

### ३.६ MRP Module (उत्पादन तथा म्यानुफ्याक्चरिङ)

* उत्पादन प्रक्रियामा कच्चा पदार्थ (Raw Material) को खरिद **Accounting Purchase Bills** बाट हुन्छ, जहाँ भ्याट कट्टी (Input Tax) बुक हुन्छ।
* उत्पादित तयारी वस्तु (Finished Goods) को बिक्री **Sales Invoice / POS** मार्फत हुन्छ, जहाँ बिक्री भ्याट (Output Tax) संकलन हुन्छ।
* उत्पादन लागत (Bill of Materials - BOM Cost) मा भ्याट समावेश हुँदैन, किनभने भ्याट उपभोग्य कर (Consumer Tax) हो, लागत खर्च होइन।

---

## ४. डेटा प्रवाह रेखाचित्र (Data Flow Architecture)

```
                       +-----------------------------+
                       |    TAX MODULE (Engine)      |
                       |  - Tax Rates (13%, 0%, etc) |
                       |  - Tax Categories           |
                       |  - GL Account Mappings      |
                       +--------------+--------------+
                                      |
         +----------------------------+----------------------------+
         |                            |                            |
         v                            v                            v
+------------------+         +------------------+         +------------------+
| INVENTORY MODULE |         |    POS MODULE    |         |  PAYROLL MODULE  |
| - Items tax_rate |         | - Cart Tax Calc  |         | - Component Tax  |
| - Exempt flags   |         | - Inclusive/Excl |         | - Salary TDS     |
+--------+---------+         | - Official Recpt |         | - SSF / Tax Deduct|
         |                   +--------+---------+         +--------+---------+
         |                            |                            |
         +--------------------+       |                            |
                              v       v                            v
                      +----------------------------------------------------+
                      |                 ACCOUNTING MODULE                  |
                      |  - Sales Invoices: Dr Receivable / Cr Output VAT   |
                      |  - Purchase Bills: Dr Expense / Dr Input VAT       |
                      |  - Payroll Voucher: Cr TDS Payable                 |
                      |  - Tax Settlement: Offset Input vs Output VAT      |
                      +-------------------------+--------------------------+
                                                |
                                                v
                               +---------------------------------+
                               |    STATUTORY TAX REPORTS        |
                               |  - Annex 10 (बिक्री खाता)       |
                               |  - Annex 11 (खरिद खाता)        |
                               |  - Schedule 10 (मासिक भ्याट)    |
                               |  - TDS Withholding Register     |
                               +---------------------------------+
```

---

## ५. लेखा मापदण्ड र कर अनुपालन चेकलिस्ट (Compliance Checklist)

| आवश्यकता (Requirement) | कानुनी आधार (Legal Basis) | SathiSaaS मा स्थिति (System Status) |
| :--- | :--- | :---: |
| **अनुसूची १० (बिक्री खाता - Sales Book)** | मूल्य अभिवृद्धि कर नियमावली, २०५२ | `TaxReportController::vatReturn` र Sales Invoice बाट स्वतः तयार हुन्छ। |
| **अनुसूची ११ (खरिद खाता - Purchase Book)** | मूल्य अभिवृद्धि कर नियमावली, २०५२ | `PurchaseBill` र `TaxReportController` बाट खरिद विवरण तयार हुन्छ। |
| **अनुसूची १० कर विवरण (Monthly VAT Return)** | मूल्य अभिवृद्धि कर ऐन, २०५२ | कर ड्यासबोर्ड र कर रिपोर्टिङमा प्रत्यक्ष उपलब्ध छ। |
| **पारिश्रमिक आयकर (Salary TDS - Sec 87)** | आयकर ऐन, २०५८ | Payroll मोड्युलमा कर स्ल्याब र पेस्लिप कट्टी समावेश छ। |
| **भुक्तानीमा अग्रिम कर (Withholding Tax - Sec 88)**| आयकर ऐन, २०५८ | Tax Accounts Mapping मा २१२० (TDS Payable) खाता उपलब्ध छ। |
| **द्वि-प्रविष्टि लेखा मिलान (Balanced Journal)** | International Financial Reporting Standards (IFRS) | Purchase Bill र Sales Invoice दुवैमा डेबिट = क्रेडिट सन्तुलित हुन्छ। |

---

## ६. भविष्यमा थप गर्न सकिने उत्कृष्ट सुविधाहरू (Recommended Enhancements)

१. **POS End-of-Day Auto-Journal:** POS मा दिनभर भएको नगद बिक्री, उधारो बिक्री र संकलित भ्याटलाई दिनको अन्त्यमा एकमुष्ठ लेखा भौचर (Consolidated Daily Sales Journal) मा पोस्ट गर्ने सुविधा।
२. **IRD Real-time E-Billing API Integration:** नेपालको आन्तरिक राजस्व विभाग (IRD) को कम्प्युटराइज्ड बिलिङ प्रणालीसँग सिधै API मार्फत बिजक प्रमाणित गर्ने व्यवस्था।
३. **Automated Reverse Charge VAT:** विदेशी सफ्टवेयर वा सेवा आयात गर्दा लाग्ने रिभर्स चार्ज भ्याट (Reverse Charge Mechanism) को स्वचालित लेखांकन।

# 💰 Enterprise Payroll & Compensation Engine (पेरोल तथा पारिश्रमिक प्रणाली)

## 📌 परिचय (Overview)
यो **SathiSaaS Multi-Tenant Enterprise Payroll Engine** हो। यस मोड्युलले विभिन्न प्रकृतिका व्यावसायिक संस्थाहरू (Corporate Offices, Manufacturing, IT Companies, Retails, Healthcare, Hotels & Restaurants) को लागि पूर्ण तथा स्वचालित पारिश्रमिक व्यवस्थापन (Payroll Lifecycle) प्रदान गर्दछ।

यस मोड्युलमा कर्मचारीहरूको मासिक तलब, दैनिक/घण्टा ज्याला (Hourly Wages), हाजिरी अनुसार गैर-हाजिर कट्टी (Attendance Loss of Pay - LOP), अतिरिक्त समय काम (Overtime Pay), कर्मचारी ऋण तथा पेश्की कट्टी (Loans & Salary Advances), नेपाल तथा अन्तर्राष्ट्रिय वैधानिक कर (Nepal SSF ३१%, IRD Salary TDS कर स्ल्याब, CIT, PF) र लेखा प्रणाली (General Ledger Journal Entry) को स्वचालित समायोजन समावेश गरिएको छ।

---

## 🏛️ मुख्य वास्तुकला र प्रणाली ढाँचा (System Architecture)

```
[HRM Module]
  ├── Attendance (हाजिरी & LOP) ──┐
  └── Overtime (स्वीकृत ओभरटाइम) ─┼──> [PayrollCalculationService]
                                  │           │
[Staff Salary Profile] ───────────┼──> (मासिक गणना इन्जिन)
[Employee Loan / Advance] ────────┼──> (SSF ३१% + TDS Tax)
[Statutory Schemes (TDS/SSF)] ────┘           │
                                              ▼
                                      [Payslips & Line Items]
                                              │
                                              ▼ (Finalize/Approve)
                                  [PayrollAccountingBridgeService]
                                              │
                                              ▼
                                   [Accounting Journal Entry]
```

---

## 🚀 मुख्य विशेषताहरू (Core Enterprise Features)

### १. कर्मचारी तलब प्रोफाइल (Employee Salary Profiles & Revision Tracking)
- **मासिक वा घण्टाको आधार (Wage Types):** Monthly Fixed, Hourly Rate, Weekly तथा Daily basis support.
- **अनुकूलित भत्ता र कट्टी (Custom Earnings & Deductions):** महँगी भत्ता (Dearness Allowance), घरभाडा भत्ता (House Rent), खाजा खर्च (Meal Allowance), यातायात भत्ता (Transport Allowance), आदि।
- **तलब वृद्धिको इतिहास (Salary Revision History):** समय-समयमा हुने तलब वृद्धि, कार्यसम्पादन मूल्यांकन (Appraisal) तथा पदोन्नतिको पूर्ण अडिट ट्रेल।
- **भुक्तानी विवरण (Banking Information):** बैंकको नाम, खाता नम्बर, शाखा र भुक्तानी माध्यम (Bank Giro, Cheque, Cash)।

### २. हाजिरी र गैर-हाजिर कट्टी इन्जिन (Attendance LOP & Overtime Integration)
- **Loss of Pay (LOP) Deduction:**
  $$\text{Per Day Rate} = \frac{\text{Basic Salary}}{\text{Working Days}}$$
  $$\text{LOP Deduction} = \text{Per Day Rate} \times \text{Absent Days}$$
- **Overtime Calculation (अतिरिक्त समय गणना):**
  $$\text{Hourly Rate} = \frac{\text{Basic Salary}}{\text{Working Days} \times 8}$$
  $$\text{Overtime Pay} = \text{Approved Overtime Hours} \times (\text{Hourly Rate} \times 1.5)$$

### ३. कर्मचारी ऋण तथा तलब पेश्की व्यवस्थापन (Staff Loans & Salary Advances)
- कर्मचारीहरूलाई दिइने सापटी, पेश्की (Salary Advance) वा उपकरण ऋण (Device Loan)।
- मासिक किस्ता (Monthly EMI) निर्धारण र पेरोल रन गर्दा पेस्लिपबाट स्वचालित रूपमा किस्ता कट्टी हुने र बाँकी बक्यौता रकम (Remaining Balance) अपडेट हुने व्यवस्था।

### ४. नेपाल तथा अन्तर्राष्ट्रिय वैधानिक कर प्रणाली (Nepal Statutory TDS & SSF 31%)
- **सामाजिक सुरक्षा कोष (Social Security Fund - SSF):**
  - **कर्मचारी योगदान (Employee Contribution):** ११% (आधारभूत तलबबाट कट्टी)
  - **रोजगारदाता योगदान (Employer Contribution):** २०% (संस्थाको अतिरिक्त लागत)
  - **जम्मा SSF दाखिला:** ३१%
- **आन्तरिक राजस्व विभाग (IRD) पारिश्रमिक आयकर (Salary TDS):**
  - **एकल व्यक्ति (Single / Individual):**
    - रु. ५,००,००० सम्म: १% (सामाजिक सुरक्षा कर)
    - रु. ५,००,००१ देखि रु. ७,००,००० सम्म: १०%
    - रु. ७,००,००१ देखि रु. १०,००,००० सम्म: २०%
    - रु. १०,००,००१ देखि रु. २०,००,००० सम्म: ३०%
    - रु. २०,००,००० भन्दा माथि: ३६%
  - **दम्पती (Married / Couple):**
    - रु. ६,००,००० सम्म: १% (सामाजिक सुरक्षा कर)
    - रु. ६,००,००१ देखि रु. ८,००,००० सम्म: १०%
    - रु. ८,००,००१ देखि रु. ११,००,००० सम्म: २०%
    - रु. ११,००,००१ देखि रु. २०,००,००० सम्म: ३०%
    - रु. २०,००,००० भन्दा माथि: ३६%

### ५. स्वचालित लेखा भौचर प्रविष्टि (General Ledger Accounting Integration)
जब पेरोल रन फाइनल वा स्वीकृत (Finalize / Approve) हुन्छ, `PayrollAccountingBridgeService` ले Accounting मोड्युलमा सन्तुलित डेबिट/क्रेडिट भौचर (Journal Entry) तयार पार्दछ:
- **Debit:** Salary & Wages Expense (कुल कुल तलब रकम)
- **Debit:** Employer SSF Expense (रोजगारदाताको २०% योगदान खर्च)
- **Credit:** TDS Tax Payable (कर्मचारीबाट कट्टी गरिएको आयकर दायित्व)
- **Credit:** SSF Payable (कुल ३१% सामाजिक सुरक्षा कोष दायित्व)
- **Credit:** Staff Loans Receivable (ऋण किस्ता कट्टी फिर्ता)
- **Credit:** Bank / Cash Account (कर्मचारीको खातामा भुक्तानी हुने खुद तलब रकम)

---

## 🗄️ डाटाबेस मोडेल तथा सम्बन्धहरू (Database Schema & Models)

1. `PayrollRun` (`payroll_runs`): पेरोल महिना, समूह, कुल कर्मचारी, कुल तलब, कट्टी रकम र खुद तलबको रेकर्ड।
2. `Payslip` (`payslips`): प्रत्येक कर्मचारीको व्यक्तिगत मासिक पेस्लिप (Gross, Deductions, Net, Attendance Metrics)।
3. `PayslipItem` (`payslip_items`): पेस्लिप भित्रका विस्तृत शीर्षकहरू (Basic, Allowances, Overtime, LOP, SSF, TDS, Loan)।
4. `EmployeeSalaryProfile` (`employee_salary_profiles`): कर्मचारीको आधारभूत तलब, भत्ता, बैंक विवरण, SSF/PAN नम्बर।
5. `PayrollComponent` (`payroll_components`): संस्था अनुसार तलबका शीर्षकहरू (Earning, Allowance, Deduction, Tax, Loan)।
6. `EmployeeLoan` (`employee_loans`): कर्मचारी ऋण, ब्याज, मासिक किस्ता, चुक्ता रकम र बाँकी बक्यौता।
7. `StatutoryScheme` (`statutory_schemes`): देश तथा नीति अनुसारको कर तथा सामाजिक सुरक्षा नियमहरू।
8. `PayrollGroup` (`payroll_groups`): पेरोल चक्र (Monthly, Weekly, Bi-weekly) तथा भुक्तानी मिति।

---

## 🌐 रूटहरू र नेभिगेसन (Routes & Navigation)

| Route Name | URL | कार्य (Function) |
| :--- | :--- | :--- |
| `admin.payroll.dashboard` | `/admin/payroll` | पेरोल ड्यासबोर्ड, लागत विश्लेषण, आगामी मितिहरू |
| `admin.payroll.runs.index` | `/admin/payroll/runs` | पेरोल रन सूची र नयाँ रन सिर्जना |
| `admin.payroll.runs.show` | `/admin/payroll/runs/{id}` | पेरोल रन विवरण, कर्मचारी सूची, गणना, स्वीकृति |
| `admin.payroll.payslips.index`| `/admin/payroll/payslips` | सबै कर्मचारीहरूको पेस्लिप खोज तथा डाउनलोड |
| `admin.payroll.payslips.show` | `/admin/payroll/payslips/{id}` | व्यक्तिगत प्रिन्ट गर्न मिल्ने आधिकारिक तलब स्लिप |
| `admin.payroll.employees.index`| `/admin/payroll/employees` | कर्मचारी तलब संरचना, पद र तलब पुनरावलोकन |
| `admin.payroll.loans.index` | `/admin/payroll/loans` | कर्मचारी सापटी, ऋण स्वीकृति र किस्ता ट्र्याकिङ |
| `admin.payroll.components.index`| `/admin/payroll/components`| तलबका शीर्षकहरू (भत्ता, कर, कट्टी) कन्फिगरेसन |
| `admin.payroll.statutory.index`| `/admin/payroll/statutory` | SSF, TDS, CIT कर स्ल्याब तथा नियमहरू |
| `admin.payroll.groups.index` | `/admin/payroll/groups` | पेरोल क्यालेन्डर तथा भुक्तानी समूहहरू |
| `admin.payroll.reports.index` | `/admin/payroll/reports` | बैंक ट्रान्सफर फाइल, SSF विवरण, कर अडिट रिपोर्ट |

---

## 🧪 टेस्टिङ र प्रमाणिकरण (Verification & Testing)
यस मोड्युलमा Pest Feature Tests समावेश गरिएको छ। टेस्ट रन गर्न:
```bash
php artisan test Modules/Payroll/tests/Feature/PayrollEngineTest.php
```

---
**Prepared by:** Senior Principal Full-Stack Engineer & Chartered SaaS Architect

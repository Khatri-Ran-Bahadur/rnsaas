/**
 * Universal Tax Terminology Helper
 * Dynamically resolves country, jurisdiction, and regime specific tax terminology.
 */

export interface TaxTerminology {
    registrationLabel: string;
    registrationPlaceholder: string;
    invoiceTitle: string;
    outputTaxLabel: string;
    inputTaxLabel: string;
    withholdingTaxLabel: string;
    authorityPlaceholder: string;
    defaultRegimeName: string;
}

const COUNTRY_TAX_TERMINOLOGY: Record<string, TaxTerminology> = {
    // Nepal
    NP: {
        registrationLabel: 'PAN / VAT Registration Number',
        registrationPlaceholder: 'e.g. 601234567 (9-digit PAN/VAT)',
        invoiceTitle: 'Tax Invoice (कर विजक)',
        outputTaxLabel: 'Output VAT (बिक्री कर)',
        inputTaxLabel: 'Input VAT (खरिद कर)',
        withholdingTaxLabel: 'TDS / Withholding Tax (अग्रिम कर कट्टी)',
        authorityPlaceholder: 'Inland Revenue Department (आन्तरिक राजस्व विभाग - IRD)',
        defaultRegimeName: 'Value Added Tax (VAT 13%)',
    },
    // India
    IN: {
        registrationLabel: 'GSTIN (GST Identification Number)',
        registrationPlaceholder: 'e.g. 27AAAAA0000A1Z5 (15-character GSTIN)',
        invoiceTitle: 'Tax / GST Invoice',
        outputTaxLabel: 'Output GST (CGST/SGST/IGST)',
        inputTaxLabel: 'Input Tax Credit (ITC)',
        withholdingTaxLabel: 'TDS / TCS',
        authorityPlaceholder: 'Goods and Services Tax Network (GSTN) / CBIC',
        defaultRegimeName: 'Goods and Services Tax (GST)',
    },
    // Malaysia
    MY: {
        registrationLabel: 'SST Registration Number / TIN',
        registrationPlaceholder: 'e.g. W10-2401-32000891',
        invoiceTitle: 'Tax Invoice / Invois Cukai',
        outputTaxLabel: 'Sales & Service Tax (SST)',
        inputTaxLabel: 'Purchase Tax',
        withholdingTaxLabel: 'Withholding Tax (WHT)',
        authorityPlaceholder: 'Royal Malaysian Customs Department (JKDM) / LHDN',
        defaultRegimeName: 'Sales and Service Tax (SST)',
    },
    // United Kingdom
    GB: {
        registrationLabel: 'VAT Registration Number (VRN)',
        registrationPlaceholder: 'e.g. GB 123 4567 89 (9 digits)',
        invoiceTitle: 'VAT Invoice',
        outputTaxLabel: 'Output VAT',
        inputTaxLabel: 'Input VAT (Reclaimable)',
        withholdingTaxLabel: 'CIS / Withholding',
        authorityPlaceholder: 'HM Revenue & Customs (HMRC)',
        defaultRegimeName: 'Value Added Tax (Standard 20%)',
    },
    // United States
    US: {
        registrationLabel: 'EIN / Federal Tax ID / State Tax ID',
        registrationPlaceholder: 'e.g. 12-3456789 (9-digit EIN)',
        invoiceTitle: 'Commercial Invoice',
        outputTaxLabel: 'State / Local Sales Tax',
        inputTaxLabel: 'Purchases / Resale Exemption',
        withholdingTaxLabel: 'Backup Withholding (1099/W-9)',
        authorityPlaceholder: 'Internal Revenue Service (IRS) / State Dept of Revenue',
        defaultRegimeName: 'State & Local Sales Tax',
    },
    // Singapore
    SG: {
        registrationLabel: 'GST Registration Number / UEN',
        registrationPlaceholder: 'e.g. M90368123A',
        invoiceTitle: 'Tax Invoice',
        outputTaxLabel: 'Output GST (9%)',
        inputTaxLabel: 'Input GST',
        withholdingTaxLabel: 'Withholding Tax',
        authorityPlaceholder: 'Inland Revenue Authority of Singapore (IRAS)',
        defaultRegimeName: 'Goods and Services Tax (GST 9%)',
    },
    // Australia
    AU: {
        registrationLabel: 'Australian Business Number (ABN / GST)',
        registrationPlaceholder: 'e.g. 51 824 753 556 (11-digit ABN)',
        invoiceTitle: 'Tax Invoice',
        outputTaxLabel: 'GST on Sales (10%)',
        inputTaxLabel: 'GST Credits on Purchases',
        withholdingTaxLabel: 'PAYG Withholding',
        authorityPlaceholder: 'Australian Taxation Office (ATO)',
        defaultRegimeName: 'Goods and Services Tax (GST 10%)',
    },
    // Canada
    CA: {
        registrationLabel: 'Business Number / GST/HST Account',
        registrationPlaceholder: 'e.g. 123456789 RT 0001',
        invoiceTitle: 'Tax Invoice',
        outputTaxLabel: 'GST / HST / PST',
        inputTaxLabel: 'Input Tax Credit (ITC)',
        withholdingTaxLabel: 'Non-Resident Withholding Tax',
        authorityPlaceholder: 'Canada Revenue Agency (CRA)',
        defaultRegimeName: 'GST / HST / PST',
    },
    // United Arab Emirates
    AE: {
        registrationLabel: 'Tax Registration Number (TRN)',
        registrationPlaceholder: 'e.g. 100 1234 5678 0003 (15 digits)',
        invoiceTitle: 'Tax Invoice / فاتورة ضريبية',
        outputTaxLabel: 'Output VAT (5%)',
        inputTaxLabel: 'Recoverable Input VAT',
        withholdingTaxLabel: 'Withholding Tax',
        authorityPlaceholder: 'Federal Tax Authority (FTA)',
        defaultRegimeName: 'Value Added Tax (VAT 5%)',
    },
    // Saudi Arabia
    SA: {
        registrationLabel: 'VAT Account Number (ZATCA TIN)',
        registrationPlaceholder: 'e.g. 300123456700003 (15 digits)',
        invoiceTitle: 'Tax Invoice / فاتورة ضريبية',
        outputTaxLabel: 'Output VAT (15%)',
        inputTaxLabel: 'Input VAT',
        withholdingTaxLabel: 'Withholding Tax',
        authorityPlaceholder: 'Zakat, Tax and Customs Authority (ZATCA)',
        defaultRegimeName: 'Value Added Tax (VAT 15%)',
    },
    // European Union (Generic)
    EU: {
        registrationLabel: 'VAT Identification Number (VATIN)',
        registrationPlaceholder: 'e.g. DE123456789 / FR12345678901',
        invoiceTitle: 'Tax / VAT Invoice',
        outputTaxLabel: 'Output VAT',
        inputTaxLabel: 'Deductible Input VAT',
        withholdingTaxLabel: 'Reverse Charge / WHT',
        authorityPlaceholder: 'National Tax Administration / VIES',
        defaultRegimeName: 'Value Added Tax (VAT)',
    },
};

const DEFAULT_TAX_TERMINOLOGY: TaxTerminology = {
    registrationLabel: 'Tax Registration Number (Tax ID / VAT / GST)',
    registrationPlaceholder: 'e.g. Tax Registration or Business ID',
    invoiceTitle: 'Tax Invoice',
    outputTaxLabel: 'Output Tax',
    inputTaxLabel: 'Input Tax',
    withholdingTaxLabel: 'Withholding Tax',
    authorityPlaceholder: 'National / Regional Revenue Authority',
    defaultRegimeName: 'Universal Value Added Tax',
};

/**
 * Get country and regime specific tax terminology.
 */
export function getTaxTerminology(countryCode?: string | null, regime?: string | null): TaxTerminology {
    const code = (countryCode || '').toUpperCase();
    const base = COUNTRY_TAX_TERMINOLOGY[code] || DEFAULT_TAX_TERMINOLOGY;

    if (regime === 'sales_tax') {
        return {
            ...base,
            outputTaxLabel: 'Sales Tax',
            inputTaxLabel: 'Purchases Tax',
            invoiceTitle: 'Sales Invoice',
            defaultRegimeName: 'Retail Sales Tax',
        };
    }

    if (regime === 'gst') {
        return {
            ...base,
            outputTaxLabel: 'Output GST',
            inputTaxLabel: 'Input GST / Tax Credit',
            invoiceTitle: 'GST Invoice',
            defaultRegimeName: 'Goods and Services Tax',
        };
    }

    if (regime === 'vat') {
        return {
            ...base,
            outputTaxLabel: 'Output VAT',
            inputTaxLabel: 'Input VAT',
            invoiceTitle: 'VAT Invoice',
            defaultRegimeName: 'Value Added Tax (VAT)',
        };
    }

    return base;
}

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Vue Composable for accessing reactive Tax Terminology based on current tenant context.
 */
export function useTaxTerminology() {
    const page = usePage();
    const currentTenant = computed(() => (page.props.current_tenant as any) ?? null);
    const countryCode = computed(() => currentTenant.value?.country || currentTenant.value?.country_code || (page.props as any).country_code || null);

    const terms = computed(() => getTaxTerminology(countryCode.value));

    return {
        terms,
        countryCode,
        getTaxTerminology,
    };
}


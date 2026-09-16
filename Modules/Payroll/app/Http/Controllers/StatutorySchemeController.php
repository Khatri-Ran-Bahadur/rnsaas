<?php

namespace Modules\Payroll\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentTenantId;
use App\Http\Controllers\Controller;
use App\Support\ReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payroll\Models\StatutoryScheme;
use Modules\Tenancy\Models\Tenant;

class StatutorySchemeController extends Controller
{
    use ResolvesCurrentTenantId;

    public function index(Request $request): Response
    {
        $tenantId = $this->getTenantId($request);
        $tenant = $tenantId ? Tenant::find($tenantId) : null;
        $country = strtoupper((string) ($tenant?->country_code ?? 'GLOBAL'));

        $dbSchemes = StatutoryScheme::where('tenant_id', $tenantId)->get();

        if ($dbSchemes->isEmpty()) {
            $presets = $this->getCountryPresets($country);
            foreach ($presets as $p) {
                StatutoryScheme::create(array_merge($p, ['tenant_id' => $tenantId]));
            }
            $dbSchemes = StatutoryScheme::where('tenant_id', $tenantId)->get();
        }

        $formatted = $dbSchemes->map(fn (StatutoryScheme $s): array => [
            'id' => $s->id,
            'code' => $s->code ?: strtoupper(Str::slug($s->name, '_')),
            'name' => $s->name,
            'country' => $s->country_code,
            'authority' => $s->authority ?? 'Statutory Tax & Social Security Authority',
            'employee_rate_default' => $s->employee_rate > 0 ? "{$s->employee_rate}%" : 'Slab / Tiered',
            'employer_rate_default' => $s->employer_rate > 0 ? "{$s->employer_rate}%" : '0%',
            'wage_ceiling' => $s->wage_ceiling ? number_format((float) $s->wage_ceiling, 2) : 'No Ceiling / Assessed',
            'filing_frequency' => 'Monthly',
            'status' => $s->is_active ? 'active' : 'inactive',
        ])->toArray();

        $reliefs = [
            [
                'name' => 'Standard Individual / Personal Tax Relief',
                'code' => 'RELIEF_PERSONAL',
                'annual_cap' => 9000.00,
            ],
            [
                'name' => 'Retirement & Social Security Contribution Relief',
                'code' => 'RELIEF_RETIREMENT',
                'annual_cap' => 4000.00,
            ],
            [
                'name' => 'Medical & Health Insurance Deduction',
                'code' => 'RELIEF_HEALTH',
                'annual_cap' => 3000.00,
            ],
            [
                'name' => 'Education & Skills Training Relief',
                'code' => 'RELIEF_EDUCATION',
                'annual_cap' => 7000.00,
            ],
            [
                'name' => 'Life Insurance & Voluntary Pension Deduction',
                'code' => 'RELIEF_LIFE_INS',
                'annual_cap' => 3000.00,
            ],
            [
                'name' => 'Spouse & Child Maintenance Relief',
                'code' => 'RELIEF_DEPENDENT',
                'annual_cap' => 4000.00,
            ],
        ];

        $allCountries = ['GLOBAL' => 'Global Universal - Multi-Tiered Tax & Social Security Engine'];
        foreach (ReferenceData::countries() as $cCode => $cData) {
            $allCountries[$cCode] = ($cData['flag'] ?? '').' '.$cData['name'].' ('.$cCode.')';
        }

        return Inertia::render('Payroll/Statutory/Index', [
            'country' => $country,
            'schemes' => $formatted,
            'reliefs' => $reliefs,
            'supportedCountries' => $allCountries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);

        $name = (string) $request->input('name');
        $code = (string) ($request->input('code') ?: strtoupper(Str::slug($name, '_')));

        StatutoryScheme::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => $code,
            'country_code' => strtoupper((string) $request->input('country_code', 'GLOBAL')),
            'scheme_type' => (string) $request->input('scheme_type', 'social_security'),
            'authority' => (string) $request->input('authority', 'National Regulatory Body'),
            'employee_rate' => (float) $request->input('employee_rate', 0),
            'employer_rate' => (float) $request->input('employer_rate', 0),
            'wage_ceiling' => $request->filled('wage_ceiling') ? (float) $request->input('wage_ceiling') : null,
            'calculation_rules' => $request->input('calculation_rules', []),
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Statutory scheme registered successfully.');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $tenantId = $this->getTenantId($request);
        $scheme = StatutoryScheme::where('tenant_id', $tenantId)->findOrFail($id);

        $scheme->update([
            'name' => $request->input('name', $scheme->name),
            'code' => $request->input('code', $scheme->code),
            'employee_rate' => $request->input('employee_rate', $scheme->employee_rate),
            'employer_rate' => $request->input('employer_rate', $scheme->employer_rate),
            'wage_ceiling' => $request->input('wage_ceiling', $scheme->wage_ceiling),
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : ! $scheme->is_active,
        ]);

        return redirect()->back()->with('success', 'Statutory scheme updated successfully.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getCountryPresets(string $country): array
    {
        if ($country === 'NP' || $country === 'NPR') {
            return [
                [
                    'name' => 'Social Security Fund (SSF)',
                    'code' => 'NP_SSF',
                    'country_code' => 'NP',
                    'scheme_type' => 'social_security',
                    'authority' => 'Social Security Fund Board',
                    'employee_rate' => 11.0,
                    'employer_rate' => 20.0,
                    'wage_ceiling' => null,
                    'is_active' => true,
                ],
                [
                    'name' => 'Citizen Investment Trust (CIT)',
                    'code' => 'NP_CIT',
                    'country_code' => 'NP',
                    'scheme_type' => 'provident_fund',
                    'authority' => 'Citizen Investment Trust',
                    'employee_rate' => 0.0,
                    'employer_rate' => 0.0,
                    'wage_ceiling' => 300000.0,
                    'is_active' => true,
                ],
                [
                    'name' => 'Employees Provident Fund (EPF)',
                    'code' => 'NP_EPF',
                    'country_code' => 'NP',
                    'scheme_type' => 'provident_fund',
                    'authority' => 'Employees Provident Fund Board',
                    'employee_rate' => 10.0,
                    'employer_rate' => 10.0,
                    'wage_ceiling' => null,
                    'is_active' => true,
                ],
                [
                    'name' => 'Salary Income Tax Withholding (TDS / PAYE)',
                    'code' => 'NP_TDS',
                    'country_code' => 'NP',
                    'scheme_type' => 'tax',
                    'authority' => 'Inland Revenue Department',
                    'employee_rate' => 0.0,
                    'employer_rate' => 0.0,
                    'wage_ceiling' => null,
                    'is_active' => true,
                ],
            ];
        }

        return [
            [
                'name' => 'Statutory Social Security & Pension Fund',
                'code' => 'GL_SOCSEC',
                'country_code' => $country,
                'scheme_type' => 'social_security',
                'authority' => 'National Social Insurance Body',
                'employee_rate' => 11.0,
                'employer_rate' => 13.0,
                'wage_ceiling' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Payroll Income Tax Withholding (PAYE / TDS / WHT)',
                'code' => 'GL_TAX',
                'country_code' => $country,
                'scheme_type' => 'tax',
                'authority' => 'Inland Revenue & Tax Authority',
                'employee_rate' => 0.0,
                'employer_rate' => 0.0,
                'wage_ceiling' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Statutory Healthcare & Disability Scheme',
                'code' => 'GL_HEALTH',
                'country_code' => $country,
                'scheme_type' => 'health_insurance',
                'authority' => 'National Healthcare Board',
                'employee_rate' => 1.5,
                'employer_rate' => 2.5,
                'wage_ceiling' => null,
                'is_active' => true,
            ],
        ];
    }
}

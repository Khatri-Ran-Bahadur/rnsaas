<?php

namespace Modules\SuperAdmin\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SuperAdmin\Models\CustomPage;

class CustomPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<h2>About SathiSaaS</h2><p>SathiSaaS is an enterprise-grade multi-tenant platform built to empower retail chains, restaurants, manufacturing plants, and fast-growing businesses with interconnected Point of Sale, Inventory, Manufacturing (MRP), Double-Entry Accounting, Payroll, and Statutory Tax Compliance.</p><h3>Our Mission</h3><p>To eliminate operational silos and deliver frictionless, unified commerce and financial clarity for modern business owners.</p>',
                'meta_title' => 'About SathiSaaS - Enterprise Cloud ERP Platform',
                'meta_description' => 'Learn about SathiSaaS, our mission, and our multi-tenant business operating system for enterprise commerce and manufacturing.',
                'is_published' => true,
                'show_in_header' => true,
                'show_in_footer' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => '<h2>Terms of Service</h2><p>Last updated: September 2026</p><p>By accessing or using SathiSaaS platforms and services, you agree to be bound by these terms. Organizations are responsible for maintaining the confidentiality of their credentials and tenant databases.</p><h3>1. Service Availability</h3><p>We target 99.9% platform uptime with redundant backups and automated disaster recovery protocols.</p><h3>2. Subscriptions & Billing</h3><p>Subscriptions renew automatically at the end of each billing cycle unless cancelled prior to renewal.</p>',
                'meta_title' => 'Terms of Service - SathiSaaS',
                'meta_description' => 'Read our terms of service regarding software licensing, billing, data isolation, and service level agreements.',
                'is_published' => true,
                'show_in_header' => false,
                'show_in_footer' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<h2>Privacy Policy</h2><p>Last updated: September 2026</p><p>We respect your privacy and enforce strict data isolation across tenant organizations. Your customer, inventory, and accounting records are encrypted and never shared with third parties.</p><h3>Data Retention & GDPR</h3><p>You own your data. You may request data export or account closure at any time via your organization portal.</p>',
                'meta_title' => 'Privacy Policy & Data Security - SathiSaaS',
                'meta_description' => 'Our privacy commitments, GDPR compliance, tenant data isolation, and encryption standards.',
                'is_published' => true,
                'show_in_header' => false,
                'show_in_footer' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Frequently Asked Questions (FAQ)',
                'slug' => 'faq',
                'content' => '<h2>Frequently Asked Questions</h2><h3>How does multi-tenancy work?</h3><p>Each registered company is provisioned with its own isolated tenant context, ensuring users only access their own orders, invoices, and stock data.</p><h3>Can I connect thermal receipt printers?</h3><p>Yes, our Point of Sale system supports ESC/POS thermal printers (80mm and 58mm) over USB, Bluetooth, and network IP connections.</p><h3>Can I customize tax rates?</h3><p>Yes, SathiSaaS includes a comprehensive Tax Engine with support for SST, VAT, GST, and custom compound tax rules.</p>',
                'meta_title' => 'Frequently Asked Questions - SathiSaaS',
                'meta_description' => 'Find answers to common questions about SathiSaaS pricing, POS printing, multi-tenancy, and module capabilities.',
                'is_published' => true,
                'show_in_header' => true,
                'show_in_footer' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($pages as $page) {
            CustomPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}

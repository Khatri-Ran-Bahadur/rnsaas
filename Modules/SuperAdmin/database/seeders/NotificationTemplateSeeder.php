<?php

namespace Modules\SuperAdmin\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SuperAdmin\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'New User Welcome',
                'slug' => 'user_welcome',
                'type' => 'email',
                'subject' => 'Welcome to {platform_name}, {user_name}!',
                'content' => '<p>Hello <strong>{user_name}</strong>,</p><p>Welcome to <strong>{platform_name}</strong>! Your organization <strong>{company_name}</strong> has been successfully configured and is ready for business.</p><p>You can log in to your portal anytime at: <a href="{app_url}">{app_url}</a></p><p>Best regards,<br>{platform_name} Team</p>',
                'variables' => ['user_name', 'company_name', 'platform_name', 'app_url'],
                'is_active' => true,
            ],
            [
                'name' => 'Customer Invoice Issued',
                'slug' => 'customer_invoice_sent',
                'type' => 'email',
                'subject' => 'Invoice #{invoice_number} from {company_name}',
                'content' => '<p>Dear <strong>{customer_name}</strong>,</p><p>Please find attached invoice <strong>#{invoice_number}</strong> for the total amount of <strong>{currency} {total_amount}</strong> due on <strong>{due_date}</strong>.</p><p>You can review and pay your invoice online here: <a href="{invoice_url}">View Invoice</a></p><p>Thank you for your business!<br>{company_name}</p>',
                'variables' => ['customer_name', 'invoice_number', 'currency', 'total_amount', 'due_date', 'invoice_url', 'company_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Invoice Payment Received',
                'slug' => 'invoice_payment_received',
                'type' => 'email',
                'subject' => 'Payment Receipt for Invoice #{invoice_number}',
                'content' => '<p>Dear <strong>{customer_name}</strong>,</p><p>We have successfully received your payment of <strong>{currency} {amount_paid}</strong> via <strong>{payment_method}</strong> for Invoice <strong>#{invoice_number}</strong>.</p><p>Your updated remaining balance is: <strong>{currency} {remaining_balance}</strong>.</p><p>Thank you,<br>{company_name}</p>',
                'variables' => ['customer_name', 'invoice_number', 'currency', 'amount_paid', 'payment_method', 'remaining_balance', 'company_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Payment Due Reminder',
                'slug' => 'payment_reminder',
                'type' => 'email',
                'subject' => 'Friendly Reminder: Invoice #{invoice_number} is Due',
                'content' => '<p>Dear <strong>{customer_name}</strong>,</p><p>This is a friendly reminder that Invoice <strong>#{invoice_number}</strong> in the amount of <strong>{currency} {total_amount}</strong> is due on <strong>{due_date}</strong>.</p><p>Please click here to settle your payment: <a href="{invoice_url}">Pay Invoice</a></p><p>Thank you,<br>{company_name}</p>',
                'variables' => ['customer_name', 'invoice_number', 'currency', 'total_amount', 'due_date', 'invoice_url', 'company_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Subscription Trial Expiring',
                'slug' => 'subscription_trial_expiring',
                'type' => 'email',
                'subject' => 'Your 14-Day {plan_name} Trial is Ending Soon',
                'content' => '<p>Hello <strong>{user_name}</strong>,</p><p>Your 14-day free trial of <strong>{plan_name}</strong> for <strong>{company_name}</strong> will expire on <strong>{trial_ends_at}</strong>.</p><p>To avoid service interruptions across your POS, Inventory, and Accounting modules, please renew or upgrade your plan: <a href="{subscription_url}">Renew Subscription</a></p><p>Best regards,<br>{platform_name} Billing Team</p>',
                'variables' => ['user_name', 'company_name', 'plan_name', 'trial_ends_at', 'subscription_url', 'platform_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Team Member Invitation',
                'slug' => 'team_member_invitation',
                'type' => 'email',
                'subject' => 'You have been invited to join {company_name} on {platform_name}',
                'content' => '<p>Hello,</p><p><strong>{inviter_name}</strong> has invited you to join the team at <strong>{company_name}</strong> with the role <strong>{role_name}</strong>.</p><p>Click the link below to accept your invitation and activate your account:<br><a href="{invitation_url}">Accept Invitation</a></p><p>Best regards,<br>{platform_name}</p>',
                'variables' => ['inviter_name', 'company_name', 'role_name', 'invitation_url', 'platform_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Password Reset Request',
                'slug' => 'password_reset',
                'type' => 'email',
                'subject' => 'Reset Your {platform_name} Password',
                'content' => '<p>Hello <strong>{user_name}</strong>,</p><p>You are receiving this email because we received a password reset request for your account.</p><p><a href="{reset_url}">Reset Password</a></p><p>This password reset link will expire in 60 minutes. If you did not request a password reset, no further action is required.</p><p>Best regards,<br>{platform_name}</p>',
                'variables' => ['user_name', 'reset_url', 'platform_name'],
                'is_active' => true,
            ],
            [
                'name' => 'New Support Ticket Created',
                'slug' => 'new_ticket_created',
                'type' => 'email',
                'subject' => 'Ticket Received: #{ticket_number} - {ticket_subject}',
                'content' => '<p>Dear <strong>{user_name}</strong>,</p><p>We have received your support request <strong>#{ticket_number}</strong>: <em>{ticket_subject}</em>.</p><p>Our technical team is reviewing your inquiry and will respond shortly.</p><p>Best regards,<br>{platform_name} Support</p>',
                'variables' => ['user_name', 'ticket_number', 'ticket_subject', 'platform_name'],
                'is_active' => true,
            ],
            [
                'name' => 'Support Ticket Reply',
                'slug' => 'ticket_reply',
                'type' => 'email',
                'subject' => 'Update on Ticket #{ticket_number}: {ticket_subject}',
                'content' => '<p>Dear <strong>{user_name}</strong>,</p><p>A response has been posted on your ticket <strong>#{ticket_number}</strong> by <strong>{agent_name}</strong>:</p><blockquote>{reply_content}</blockquote><p><a href="{ticket_url}">View Ticket Online</a></p><p>Best regards,<br>{platform_name} Support</p>',
                'variables' => ['user_name', 'ticket_number', 'ticket_subject', 'agent_name', 'reply_content', 'ticket_url', 'platform_name'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}

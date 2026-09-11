<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'addresses_user_id_idx' => 'CREATE INDEX IF NOT EXISTS "addresses_user_id_idx" ON "addresses" ("user_id")',
            'conversations_shipment_id_idx' => 'CREATE INDEX IF NOT EXISTS "conversations_shipment_id_idx" ON "conversations" ("shipment_id")',
            'email_logs_sender_id_idx' => 'CREATE INDEX IF NOT EXISTS "email_logs_sender_id_idx" ON "email_logs" ("sender_id")',
            'email_logs_recipient_user_id_idx' => 'CREATE INDEX IF NOT EXISTS "email_logs_recipient_user_id_idx" ON "email_logs" ("recipient_user_id")',
            'guest_chat_conversations_shipment_id_idx' => 'CREATE INDEX IF NOT EXISTS "guest_chat_conversations_shipment_id_idx" ON "guest_chat_conversations" ("shipment_id")',
            'guest_chat_conversations_closed_by_idx' => 'CREATE INDEX IF NOT EXISTS "guest_chat_conversations_closed_by_idx" ON "guest_chat_conversations" ("closed_by")',
            'guest_chat_messages_guest_chat_conversation_id_idx' => 'CREATE INDEX IF NOT EXISTS "guest_chat_messages_guest_chat_conversation_id_idx" ON "guest_chat_messages" ("guest_chat_conversation_id")',
            'guest_chat_messages_admin_user_id_idx' => 'CREATE INDEX IF NOT EXISTS "guest_chat_messages_admin_user_id_idx" ON "guest_chat_messages" ("admin_user_id")',
            'messages_conversation_id_idx' => 'CREATE INDEX IF NOT EXISTS "messages_conversation_id_idx" ON "messages" ("conversation_id")',
            'messages_user_id_idx' => 'CREATE INDEX IF NOT EXISTS "messages_user_id_idx" ON "messages" ("user_id")',
            'notification_preferences_user_id_idx' => 'CREATE INDEX IF NOT EXISTS "notification_preferences_user_id_idx" ON "notification_preferences" ("user_id")',
            'notifications_user_id_idx' => 'CREATE INDEX IF NOT EXISTS "notifications_user_id_idx" ON "notifications" ("user_id")',
            'payment_audit_logs_admin_user_id_idx' => 'CREATE INDEX IF NOT EXISTS "payment_audit_logs_admin_user_id_idx" ON "payment_audit_logs" ("admin_user_id")',
            'payment_proofs_payment_request_id_idx' => 'CREATE INDEX IF NOT EXISTS "payment_proofs_payment_request_id_idx" ON "payment_proofs" ("payment_request_id")',
            'payment_proofs_payment_transaction_id_idx' => 'CREATE INDEX IF NOT EXISTS "payment_proofs_payment_transaction_id_idx" ON "payment_proofs" ("payment_transaction_id")',
            'payment_proofs_verified_by_idx' => 'CREATE INDEX IF NOT EXISTS "payment_proofs_verified_by_idx" ON "payment_proofs" ("verified_by")',
            'payment_requests_shipment_id_idx' => 'CREATE INDEX IF NOT EXISTS "payment_requests_shipment_id_idx" ON "payment_requests" ("shipment_id")',
            'payment_requests_created_by_idx' => 'CREATE INDEX IF NOT EXISTS "payment_requests_created_by_idx" ON "payment_requests" ("created_by")',
            'payment_requests_verified_by_idx' => 'CREATE INDEX IF NOT EXISTS "payment_requests_verified_by_idx" ON "payment_requests" ("verified_by")',
            'payment_transactions_payment_request_id_idx' => 'CREATE INDEX IF NOT EXISTS "payment_transactions_payment_request_id_idx" ON "payment_transactions" ("payment_request_id")',
            'payments_verified_by_idx' => 'CREATE INDEX IF NOT EXISTS "payments_verified_by_idx" ON "payments" ("verified_by")',
            'shipment_attachments_uploaded_by_idx' => 'CREATE INDEX IF NOT EXISTS "shipment_attachments_uploaded_by_idx" ON "shipment_attachments" ("uploaded_by")',
            'shipment_drafts_customer_user_id_idx' => 'CREATE INDEX IF NOT EXISTS "shipment_drafts_customer_user_id_idx" ON "shipment_drafts" ("customer_user_id")',
            'shipment_schedules_shipment_id_idx' => 'CREATE INDEX IF NOT EXISTS "shipment_schedules_shipment_id_idx" ON "shipment_schedules" ("shipment_id")',
            'shipments_driver_id_idx' => 'CREATE INDEX IF NOT EXISTS "shipments_driver_id_idx" ON "shipments" ("driver_id")',
            'shipments_warehouse_id_idx' => 'CREATE INDEX IF NOT EXISTS "shipments_warehouse_id_idx" ON "shipments" ("warehouse_id")',
            'support_tickets_user_id_idx' => 'CREATE INDEX IF NOT EXISTS "support_tickets_user_id_idx" ON "support_tickets" ("user_id")',
            'support_tickets_shipment_id_idx' => 'CREATE INDEX IF NOT EXISTS "support_tickets_shipment_id_idx" ON "support_tickets" ("shipment_id")',
            'tracking_events_shipment_id_idx' => 'CREATE INDEX IF NOT EXISTS "tracking_events_shipment_id_idx" ON "tracking_events" ("shipment_id")',
            'tracking_events_created_by_idx' => 'CREATE INDEX IF NOT EXISTS "tracking_events_created_by_idx" ON "tracking_events" ("created_by")',
            'payments_shipment_id_stage_idx' => 'CREATE INDEX IF NOT EXISTS "payments_shipment_id_stage_idx" ON "payments" ("shipment_id", "stage")',
        ];

        foreach ($indexes as $name => $sql) {
            $table = explode('"', $sql)[3] ?? '';
            if ($table && ! Schema::hasTable($table)) {
                continue;
            }
            try {
                DB::statement($sql);
            } catch (\Exception $e) {
                // index may already exist
            }
        }
    }

    public function down(): void
    {
        $indexNames = [
            'addresses_user_id_idx', 'conversations_shipment_id_idx', 'email_logs_sender_id_idx',
            'email_logs_recipient_user_id_idx', 'guest_chat_conversations_shipment_id_idx',
            'guest_chat_conversations_closed_by_idx', 'guest_chat_messages_guest_chat_conversation_id_idx',
            'guest_chat_messages_admin_user_id_idx', 'messages_conversation_id_idx', 'messages_user_id_idx',
            'notification_preferences_user_id_idx', 'notifications_user_id_idx',
            'payment_audit_logs_admin_user_id_idx', 'payment_proofs_payment_request_id_idx',
            'payment_proofs_payment_transaction_id_idx', 'payment_proofs_verified_by_idx',
            'payment_requests_shipment_id_idx', 'payment_requests_created_by_idx',
            'payment_requests_verified_by_idx', 'payment_transactions_payment_request_id_idx',
            'payments_verified_by_idx', 'shipment_attachments_uploaded_by_idx',
            'shipment_drafts_customer_user_id_idx', 'shipment_schedules_shipment_id_idx',
            'shipments_driver_id_idx', 'shipments_warehouse_id_idx', 'support_tickets_user_id_idx',
            'support_tickets_shipment_id_idx', 'tracking_events_shipment_id_idx',
            'tracking_events_created_by_idx', 'payments_shipment_id_stage_idx',
        ];

        foreach ($indexNames as $name) {
            try {
                DB::statement("DROP INDEX IF EXISTS \"{$name}\"");
            } catch (\Exception $e) {
                //
            }
        }
    }
};

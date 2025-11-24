<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ext_chatbot_products')) {
            return;
        }

        Schema::table('ext_chatbot_products', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (! Schema::hasColumn('ext_chatbot_products', 'auto_detected')) {
                $table->boolean('auto_detected')->default(false)->after('updated_at');
            }

            if (! Schema::hasColumn('ext_chatbot_products', 'detection_source_url')) {
                $table->string('detection_source_url', 500)->nullable()->after('auto_detected');
            }

            if (! Schema::hasColumn('ext_chatbot_products', 'detection_timestamp')) {
                $table->timestamp('detection_timestamp')->nullable()->after('detection_source_url');
            }

            if (! Schema::hasColumn('ext_chatbot_products', 'detection_confidence')) {
                $table->decimal('detection_confidence', 3, 2)->nullable()->after('detection_timestamp');
            }

            if (! Schema::hasColumn('ext_chatbot_products', 'last_validation')) {
                $table->timestamp('last_validation')->nullable()->after('detection_confidence');
            }

            if (! Schema::hasColumn('ext_chatbot_products', 'detection_metadata')) {
                $table->json('detection_metadata')->nullable()->after('last_validation');
            }
        });

        // Add indexes separately to avoid conflicts
        try {
            Schema::table('ext_chatbot_products', function (Blueprint $table) {
                if (! $this->indexExists('ext_chatbot_products', 'idx_auto_detected_chatbot')) {
                    $table->index(['auto_detected', 'chatbot_id'], 'idx_auto_detected_chatbot');
                }

                if (! $this->indexExists('ext_chatbot_products', 'idx_detection_source_url')) {
                    $table->index(['detection_source_url'], 'idx_detection_source_url');
                }

                if (! $this->indexExists('ext_chatbot_products', 'idx_detection_timestamp')) {
                    $table->index(['detection_timestamp'], 'idx_detection_timestamp');
                }
            });
        } catch (\Exception $e) {
            // Indexes might already exist, continue
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName)
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);

        return array_key_exists($indexName, $indexes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('ext_chatbot_products')) {
            return;
        }

        Schema::table('ext_chatbot_products', function (Blueprint $table) {
            $table->dropIndex('idx_auto_detected_chatbot');
            $table->dropIndex('idx_detection_source_url');
            $table->dropIndex('idx_detection_timestamp');

            $table->dropColumn([
                'auto_detected',
                'detection_source_url',
                'detection_timestamp',
                'detection_confidence',
                'last_validation',
                'detection_metadata',
            ]);
        });
    }
};

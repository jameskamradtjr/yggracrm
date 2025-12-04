<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumn('user_sites', 'meta_pixel_id', 'VARCHAR(50)', [
            'nullable' => true,
            'after' => 'instagram_url'
        ]);
        
        $this->addColumn('user_sites', 'google_analytics_id', 'VARCHAR(50)', [
            'nullable' => true,
            'after' => 'meta_pixel_id'
        ]);
    }

    public function down(): void
    {
        $this->dropColumn('user_sites', 'meta_pixel_id');
        $this->dropColumn('user_sites', 'google_analytics_id');
    }
};


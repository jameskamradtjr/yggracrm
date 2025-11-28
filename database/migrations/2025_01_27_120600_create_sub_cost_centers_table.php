<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('sub_cost_centers', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('cost_center_id');
            $table->string('name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            
            $table->index('cost_center_id');
            $table->index('user_id');
            $table->foreign('cost_center_id', 'id', 'cost_centers', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('sub_cost_centers');
    }
};


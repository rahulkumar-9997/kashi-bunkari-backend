<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('order_statuses')) {
            $this->command->error('Order statuses table does not exist!');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('order_statuses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $statuses = [
            ['name' => 'Pending', 'slug' => 'pending', 'description' => 'Order placed but payment pending.', 'status' => 1, 'color' => '#ffc107', 'sort_order' => 1],
            ['name' => 'Confirmed', 'slug' => 'confirmed', 'description' => 'Order confirmed by admin.', 'status' => 1, 'color' => '#0d6efd', 'sort_order' => 2],
            ['name' => 'Processing', 'slug' => 'processing', 'description' => 'Order is being processed.', 'status' => 1, 'color' => '#0dcaf0', 'sort_order' => 3],
            ['name' => 'Packed', 'slug' => 'packed', 'description' => 'Order packed.', 'status' => 1, 'color' => '#6c757d', 'sort_order' => 4],
            ['name' => 'Shipped', 'slug' => 'shipped', 'description' => 'Order shipped.', 'status' => 1, 'color' => '#198754', 'sort_order' => 5],
            ['name' => 'Out for Delivery', 'slug' => 'out-for-delivery', 'description' => 'Out for delivery.', 'status' => 1, 'color' => '#fd7e14', 'sort_order' => 6],
            ['name' => 'Delivered', 'slug' => 'delivered', 'description' => 'Delivered successfully.', 'status' => 1, 'color' => '#20c997', 'sort_order' => 7],
            ['name' => 'Cancelled', 'slug' => 'cancelled', 'description' => 'Order cancelled.', 'status' => 1, 'color' => '#dc3545', 'sort_order' => 8],
            ['name' => 'Returned', 'slug' => 'returned', 'description' => 'Order returned.', 'status' => 1, 'color' => '#f5365c', 'sort_order' => 9],
            ['name' => 'Refunded', 'slug' => 'refunded', 'description' => 'Amount refunded.', 'status' => 1, 'color' => '#ff6b6b', 'sort_order' => 10],
        ];

        foreach ($statuses as $status) {
            DB::table('order_statuses')->insert([
                'name' => $status['name'],
                'slug' => $status['slug'],
                'description' => $status['description'],
                'status' => $status['status'],
                'color' => $status['color'],
                'sort_order' => $status['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Order statuses seeded successfully!');
    }
}
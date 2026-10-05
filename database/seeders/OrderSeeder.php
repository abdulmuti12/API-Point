<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Seed order dummy untuk testing endpoint get-orders.
     *
     * Customers dummy:
     *   - testcustomer@example.com / password
     *   - budi.santoso@example.com / password
     *   - siti.nurhaliza@example.com / password
     *   - agus.pratama@example.com / password
     */
    public function run(): void
    {
        // 1. Buat / ambil customer-customer dummy
        $customers = $this->seedCustomers();

        // Bersihkan order lama untuk semua customer dummy ini (idempotent seeder)
        Order::whereIn('customer_id', $customers->pluck('id'))->delete();

        // 2. Definisikan order dummy (variasi produk, status, alamat, payment)
        $orderDefs = [
            // ============ Customer 1: Test Customer ============
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260619-001',
                'days_ago'       => 0,
                'status'         => 'processing',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'bank_transfer',
                'notes'          => 'Mohon packing dengan kayu',
                'items'          => [
                    ['name' => 'Casa Italia Marble', 'variant' => '120x240 Hitam',  'qty' => 3, 'price' => 850000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Marble'],
                    ['name' => 'Granito Premium',    'variant' => '60x60 White',     'qty' => 5, 'price' => 400000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Granito'],
                ],
            ],
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260618-002',
                'days_ago'       => 1,
                'status'         => 'shipped',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Keramik Italian Style', 'variant' => '80x80 Glossy',   'qty' => 2, 'price' => 560000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Keramik'],
                    ['name' => 'Travertino Natural',    'variant' => '60x60 Matte',    'qty' => 4, 'price' => 300000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Travertin'],
                    ['name' => 'Roman Stone',           'variant' => '120x120 Grey',   'qty' => 3, 'price' => 900000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Roman'],
                ],
            ],
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260615-003',
                'days_ago'       => 4,
                'status'         => 'delivered',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Marble Carrara', 'variant' => '100x100 White', 'qty' => 1, 'price' => 750000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Carrara'],
                ],
            ],
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260610-004',
                'days_ago'       => 9,
                'status'         => 'delivered',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Onyx Gold',     'variant' => '120x240 Gold',   'qty' => 2, 'price' => 2000000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Onyx'],
                    ['name' => 'Slate Natural', 'variant' => '60x60 Black',    'qty' => 4, 'price' => 300000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Slate'],
                ],
            ],
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260605-005',
                'days_ago'       => 14,
                'status'         => 'delivered',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Botticino Classico', 'variant' => '60x60 Polished',  'qty' => 6, 'price' => 450000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Botticino'],
                    ['name' => 'Crema Marfil',       'variant' => '120x240 Honed',   'qty' => 2, 'price' => 1200000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Crema'],
                    ['name' => 'Nero Marquina',      'variant' => '60x60 Polished',  'qty' => 3, 'price' => 850000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Nero'],
                ],
            ],
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260530-006',
                'days_ago'       => 20,
                'status'         => 'delivered',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Pietra Serena', 'variant' => '120x120 Leopardo', 'qty' => 2, 'price' => 1800000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Pietra'],
                ],
            ],
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260525-007',
                'days_ago'       => 25,
                'status'         => 'delivered',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Volakas White',  'variant' => '100x100 Polished', 'qty' => 5, 'price' => 650000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Volakas'],
                    ['name' => 'Thassos Pure',   'variant' => '60x60 Honed',      'qty' => 4, 'price' => 500000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Thassos'],
                ],
            ],
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260520-008',
                'days_ago'       => 30,
                'status'         => 'delivered',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Emperador Dark', 'variant' => '60x60 Polished',   'qty' => 3, 'price' => 750000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Emperador'],
                    ['name' => 'Rosso Verona',   'variant' => '120x240 Natural',  'qty' => 2, 'price' => 1400000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Rosso'],
                ],
            ],
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260515-009',
                'days_ago'       => 35,
                'status'         => 'delivered',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'e_wallet',
                'notes'          => 'Sudah diterima dengan baik',
                'items'          => [
                    ['name' => 'Statuario Venato',  'variant' => '120x240 Premium', 'qty' => 1, 'price' => 3500000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Statuario'],
                    ['name' => 'Calacatta Gold',    'variant' => '100x100 Polished','qty' => 2, 'price' => 2200000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Calacatta'],
                ],
            ],
            [
                'customer_email' => 'testcustomer@example.com',
                'order_number'   => 'ORD-20260510-010',
                'days_ago'       => 40,
                'status'         => 'delivered',
                'recipient'      => 'Test Customer',
                'phone'          => '081234567890',
                'address'        => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Marquina Black', 'variant' => '80x80 Polished', 'qty' => 8, 'price' => 450000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Marquina'],
                ],
            ],

            // ============ Customer 2: Budi Santoso ============
            [
                'customer_email' => 'budi.santoso@example.com',
                'order_number'   => 'ORD-20260619-011',
                'days_ago'       => 0,
                'status'         => 'processing',
                'recipient'      => 'Budi Santoso',
                'phone'          => '081298765432',
                'address'        => 'Jl. Merdeka No. 45, Bandung',
                'payment_method' => 'bank_transfer',
                'notes'          => 'Tolong kirim pagi hari',
                'items'          => [
                    ['name' => 'Granito Alaska',    'variant' => '60x60 Grey',      'qty' => 12, 'price' => 350000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Alaska'],
                    ['name' => 'Keramik Mosaic',   'variant' => '30x30 Mix Color',  'qty' => 20, 'price' => 180000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Mosaic'],
                ],
            ],
            [
                'customer_email' => 'budi.santoso@example.com',
                'order_number'   => 'ORD-20260617-012',
                'days_ago'       => 2,
                'status'         => 'shipped',
                'recipient'      => 'Budi Santoso',
                'phone'          => '081298765432',
                'address'        => 'Jl. Merdeka No. 45, Bandung',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Marble Statuario', 'variant' => '100x100 White', 'qty' => 5, 'price' => 1500000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Statuario'],
                    ['name' => 'Granito Beige',    'variant' => '80x80 Beige',   'qty' => 8, 'price' => 420000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Beige'],
                ],
            ],
            [
                'customer_email' => 'budi.santoso@example.com',
                'order_number'   => 'ORD-20260612-013',
                'days_ago'       => 7,
                'status'         => 'delivered',
                'recipient'      => 'Budi Santoso',
                'phone'          => '081298765432',
                'address'        => 'Jl. Merdeka No. 45, Bandung',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Tropical Green', 'variant' => '60x60 Hijau', 'qty' => 15, 'price' => 380000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Tropical'],
                ],
            ],
            [
                'customer_email' => 'budi.santoso@example.com',
                'order_number'   => 'ORD-20260608-014',
                'days_ago'       => 11,
                'status'         => 'delivered',
                'recipient'      => 'Budi Santoso',
                'phone'          => '081298765432',
                'address'        => 'Jl. Merdeka No. 45, Bandung',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Sahara Beige',   'variant' => '80x80 Matte',    'qty' => 6, 'price' => 480000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Sahara'],
                    ['name' => 'Wood Look Teak', 'variant' => '20x120 Kayu',    'qty' => 18, 'price' => 220000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Wood'],
                ],
            ],
            [
                'customer_email' => 'budi.santoso@example.com',
                'order_number'   => 'ORD-20260602-015',
                'days_ago'       => 17,
                'status'         => 'delivered',
                'recipient'      => 'Budi Santoso',
                'phone'          => '081298765432',
                'address'        => 'Jl. Merdeka No. 45, Bandung',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Carrara White',  'variant' => '60x120 Premium', 'qty' => 4, 'price' => 1200000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Carrara'],
                    ['name' => 'Mosaic Biru',    'variant' => '30x30 Biru',     'qty' => 25, 'price' => 160000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=BlueMosaic'],
                ],
            ],
            [
                'customer_email' => 'budi.santoso@example.com',
                'order_number'   => 'ORD-20260528-016',
                'days_ago'       => 22,
                'status'         => 'delivered',
                'recipient'      => 'Budi Santoso',
                'phone'          => '081298765432',
                'address'        => 'Jl. Merdeka No. 45, Bandung',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Quartz Grey', 'variant' => '80x80 Grey Polished', 'qty' => 10, 'price' => 520000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Quartz'],
                ],
            ],
            [
                'customer_email' => 'budi.santoso@example.com',
                'order_number'   => 'ORD-20260522-017',
                'days_ago'       => 28,
                'status'         => 'delivered',
                'recipient'      => 'Budi Santoso',
                'phone'          => '081298765432',
                'address'        => 'Jl. Merdeka No. 45, Bandung',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Marble Beige',    'variant' => '100x100 Beige',  'qty' => 3, 'price' => 980000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Beige'],
                    ['name' => 'Granito Cream',   'variant' => '60x60 Cream',    'qty' => 14, 'price' => 340000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Cream'],
                ],
            ],

            // ============ Customer 3: Siti Nurhaliza ============
            [
                'customer_email' => 'siti.nurhaliza@example.com',
                'order_number'   => 'ORD-20260618-018',
                'days_ago'       => 1,
                'status'         => 'shipped',
                'recipient'      => 'Siti Nurhaliza',
                'phone'          => '085711223344',
                'address'        => 'Jl. Diponegoro No. 88, Surabaya',
                'payment_method' => 'credit_card',
                'notes'          => 'Harap hati-hati, barang fragile',
                'items'          => [
                    ['name' => 'Crystal White', 'variant' => '120x240 Polished', 'qty' => 2, 'price' => 2800000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Crystal'],
                    ['name' => 'Onyx Pink',     'variant' => '100x100 Pink',      'qty' => 3, 'price' => 1850000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=PinkOnyx'],
                ],
            ],
            [
                'customer_email' => 'siti.nurhaliza@example.com',
                'order_number'   => 'ORD-20260614-019',
                'days_ago'       => 5,
                'status'         => 'delivered',
                'recipient'      => 'Siti Nurhaliza',
                'phone'          => '085711223344',
                'address'        => 'Jl. Diponegoro No. 88, Surabaya',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Travertino Silver', 'variant' => '60x60 Silver', 'qty' => 10, 'price' => 420000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Silver'],
                    ['name' => 'Batu Alam Andesit', 'variant' => '40x40 Natural', 'qty' => 8, 'price' => 280000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Andesit'],
                ],
            ],
            [
                'customer_email' => 'siti.nurhaliza@example.com',
                'order_number'   => 'ORD-20260609-020',
                'days_ago'       => 10,
                'status'         => 'delivered',
                'recipient'      => 'Siti Nurhaliza',
                'phone'          => '085711223344',
                'address'        => 'Jl. Diponegoro No. 88, Surabaya',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Keramik Motif Batu', 'variant' => '60x60 Abu-abu', 'qty' => 12, 'price' => 320000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Batu'],
                    ['name' => 'Granito Terazzo',     'variant' => '80x80 Multi',   'qty' => 5, 'price' => 580000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Terazzo'],
                ],
            ],
            [
                'customer_email' => 'siti.nurhaliza@example.com',
                'order_number'   => 'ORD-20260604-021',
                'days_ago'       => 15,
                'status'         => 'delivered',
                'recipient'      => 'Siti Nurhaliza',
                'phone'          => '085711223344',
                'address'        => 'Jl. Diponegoro No. 88, Surabaya',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Marble Calacatta', 'variant' => '100x100 Premium', 'qty' => 2, 'price' => 2400000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Calacatta'],
                ],
            ],
            [
                'customer_email' => 'siti.nurhaliza@example.com',
                'order_number'   => 'ORD-20260530-022',
                'days_ago'       => 20,
                'status'         => 'delivered',
                'recipient'      => 'Siti Nurhaliza',
                'phone'          => '085711223344',
                'address'        => 'Jl. Diponegoro No. 88, Surabaya',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Granito Premium',  'variant' => '60x60 Putih', 'qty' => 20, 'price' => 400000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Putih'],
                    ['name' => 'Wood Look Walnut', 'variant' => '20x120 Coklat','qty' => 14, 'price' => 240000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Walnut'],
                ],
            ],
            [
                'customer_email' => 'siti.nurhaliza@example.com',
                'order_number'   => 'ORD-20260524-023',
                'days_ago'       => 26,
                'status'         => 'delivered',
                'recipient'      => 'Siti Nurhaliza',
                'phone'          => '085711223344',
                'address'        => 'Jl. Diponegoro No. 88, Surabaya',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Marble Statuario', 'variant' => '60x120 Premium', 'qty' => 6, 'price' => 1450000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Statuario'],
                    ['name' => 'Granito Black',    'variant' => '80x80 Hitam',    'qty' => 7, 'price' => 460000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Black'],
                ],
            ],
            [
                'customer_email' => 'siti.nurhaliza@example.com',
                'order_number'   => 'ORD-20260518-024',
                'days_ago'       => 32,
                'status'         => 'delivered',
                'recipient'      => 'Siti Nurhaliza',
                'phone'          => '085711223344',
                'address'        => 'Jl. Diponegoro No. 88, Surabaya',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Onyx Blue', 'variant' => '100x100 Biru Premium', 'qty' => 1, 'price' => 3200000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=BlueOnyx'],
                ],
            ],
            [
                'customer_email' => 'siti.nurhaliza@example.com',
                'order_number'   => 'ORD-20260512-025',
                'days_ago'       => 38,
                'status'         => 'delivered',
                'recipient'      => 'Siti Nurhaliza',
                'phone'          => '085711223344',
                'address'        => 'Jl. Diponegoro No. 88, Surabaya',
                'payment_method' => 'bank_transfer',
                'notes'          => 'Sangat puas dengan kualitasnya',
                'items'          => [
                    ['name' => 'Marquina Black', 'variant' => '60x60 Polished', 'qty' => 15, 'price' => 480000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Marquina'],
                    ['name' => 'Keramik Spanol', 'variant' => '80x80 Coklat',    'qty' => 8, 'price' => 520000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Spanol'],
                ],
            ],

            // ============ Customer 4: Agus Pratama ============
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260619-026',
                'days_ago'       => 0,
                'status'         => 'processing',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Quartz Premium',  'variant' => '100x100 White', 'qty' => 4, 'price' => 1350000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Quartz'],
                    ['name' => 'Granito Carrara', 'variant' => '60x60 Grey',    'qty' => 9, 'price' => 410000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=CarraraG'],
                ],
            ],
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260616-027',
                'days_ago'       => 3,
                'status'         => 'shipped',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Marble Beige',     'variant' => '80x80 Beige Polished', 'qty' => 11, 'price' => 720000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Beige'],
                    ['name' => 'Mosaic Glass Mix', 'variant' => '30x30 Mix',           'qty' => 16, 'price' => 220000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Glass'],
                ],
            ],
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260611-028',
                'days_ago'       => 8,
                'status'         => 'delivered',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Keramik Rustic', 'variant' => '60x60 Coklat', 'qty' => 18, 'price' => 340000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Rustic'],
                ],
            ],
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260606-029',
                'days_ago'       => 13,
                'status'         => 'delivered',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Statuario Venato', 'variant' => '120x240 Premium', 'qty' => 1, 'price' => 3500000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Statuario'],
                    ['name' => 'Granito Black',     'variant' => '80x80 Black',     'qty' => 5, 'price' => 470000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Black'],
                ],
            ],
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260531-030',
                'days_ago'       => 19,
                'status'         => 'delivered',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Marble Calacatta', 'variant' => '100x100 Premium', 'qty' => 2, 'price' => 2400000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Calacatta'],
                    ['name' => 'Wood Look Oak',    'variant' => '20x120 Oak',      'qty' => 12, 'price' => 230000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Oak'],
                ],
            ],
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260526-031',
                'days_ago'       => 24,
                'status'         => 'delivered',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Sahara Beige',   'variant' => '60x60 Matte',  'qty' => 22, 'price' => 280000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Sahara'],
                    ['name' => 'Tropical Green', 'variant' => '60x60 Hijau',  'qty' => 8,  'price' => 380000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Tropical'],
                ],
            ],
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260520-032',
                'days_ago'       => 30,
                'status'         => 'delivered',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Onyx Gold', 'variant' => '120x240 Premium', 'qty' => 1, 'price' => 3800000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=GoldOnyx'],
                ],
            ],
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260514-033',
                'days_ago'       => 36,
                'status'         => 'delivered',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'bank_transfer',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Granito Premium', 'variant' => '80x80 Grey',     'qty' => 13, 'price' => 390000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Premium'],
                    ['name' => 'Keramik Mosaic',  'variant' => '30x30 Hijau',    'qty' => 20, 'price' => 180000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=HijauMosaic'],
                ],
            ],
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260508-034',
                'days_ago'       => 42,
                'status'         => 'delivered',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'credit_card',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Marble Carrara',  'variant' => '100x100 White', 'qty' => 3, 'price' => 1450000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Carrara'],
                    ['name' => 'Quartz Beige',    'variant' => '80x80 Beige',   'qty' => 6, 'price' => 520000,  'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=QuartzB'],
                ],
            ],
            [
                'customer_email' => 'agus.pratama@example.com',
                'order_number'   => 'ORD-20260502-035',
                'days_ago'       => 48,
                'status'         => 'delivered',
                'recipient'      => 'Agus Pratama',
                'phone'          => '081355577799',
                'address'        => 'Jl. Gatot Subroto No. 12, Medan',
                'payment_method' => 'e_wallet',
                'notes'          => null,
                'items'          => [
                    ['name' => 'Travertino Beige', 'variant' => '60x60 Beige', 'qty' => 14, 'price' => 360000, 'image' => 'https://placehold.co/80x80/1a1a1a/amber?text=Travert'],
                ],
            ],
        ];

        foreach ($orderDefs as $def) {
            $customer = $customers->firstWhere('email', $def['customer_email']);
            $subtotal = collect($def['items'])->reduce(fn($sum, $i) => $sum + ($i['qty'] * $i['price']), 0);
            $shipping = 15000;
            $grandTotal = $subtotal + $shipping;
            $createdAt = now()->subDays($def['days_ago'])->subHours(rand(1, 23));

            $order = Order::create([
                'order_number'     => $def['order_number'],
                'customer_id'      => $customer->id,
                'recipient_name'   => $def['recipient'],
                'phone'            => $def['phone'],
                'shipping_address' => $def['address'],
                'subtotal'         => $subtotal,
                'shipping_cost'    => $shipping,
                'grand_total'      => $grandTotal,
                'status'           => $def['status'],
                'notes'            => $def['notes'],
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            // Buat transaksi berstatus success untuk setiap order
            Transaction::create([
                'transaction_code' => 'TRX-' . strtoupper(Str::random(8)),
                'order_id'         => $order->id,
                'total_payment'    => $grandTotal,
                'status'           => 'success',
                'note'             => 'Pembayaran via ' . ($def['payment_method'] ?? 'bank_transfer'),
                'address_id'       => 1,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            foreach ($def['items'] as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => null,
                    'product_name' => $item['name'],
                    'variant_name' => $item['variant'],
                    'quantity'     => $item['qty'],
                    'price'        => $item['price'],
                    'image'        => $item['image'],
                    'created_at'   => $createdAt,
                    'updated_at'   => $createdAt,
                ]);
            }
        }

        $totalOrders = count($orderDefs);
        $this->command->info("OrderSeeder: {$totalOrders} orders berhasil dibuat untuk {$customers->count()} customers dengan transaksi success");
    }

    /**
     * Buat / ambil 4 customer dummy.
     */
    private function seedCustomers()
    {
        $defs = [
            ['email' => 'testcustomer@example.com',   'name' => 'Test',           'full_name' => 'Test Customer',    'phone' => '081234567890'],
            ['email' => 'budi.santoso@example.com',   'name' => 'Budi',           'full_name' => 'Budi Santoso',     'phone' => '081298765432'],
            ['email' => 'siti.nurhaliza@example.com', 'name' => 'Siti',           'full_name' => 'Siti Nurhaliza',   'phone' => '085711223344'],
            ['email' => 'agus.pratama@example.com',   'name' => 'Agus',           'full_name' => 'Agus Pratama',     'phone' => '081355577799'],
        ];

        $customers = collect();
        foreach ($defs as $d) {
            $customer = Customer::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name'         => $d['name'],
                    'full_name'    => $d['full_name'],
                    'phone_number' => $d['phone'],
                    'password'     => Hash::make('password'),
                    'status'       => 'active',
                ]
            );
            $customers->push($customer);
        }

        return $customers;
    }
}

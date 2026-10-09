<?php

namespace Database\Seeders;

/** Catalog transcribed from both HALOHA menus dated 01 July 2026.
 * Explicit SKUs are permanent identifiers: never renumber existing rows.
 */
final class HalohaMenuCatalog
{
    public static function categories(): array
    {
        return [
            [
                'name' => 'Haloha - Tea Series',
                'products' => [
                    ['sku' => 'HH-TEA-001', 'name' => 'Thai Tea Medium (16 oz)', 'selling_price' => 8000],
                    ['sku' => 'HH-TEA-002', 'name' => 'Thai Tea Large (22 oz)', 'selling_price' => 10000],
                    ['sku' => 'HH-TEA-003', 'name' => 'Thai Tea Medium Brown Sugar (16 oz)', 'selling_price' => 10000],
                    ['sku' => 'HH-TEA-004', 'name' => 'Thai Tea Large Brown Sugar (22 oz)', 'selling_price' => 12000],
                    ['sku' => 'HH-TEA-005', 'name' => 'Green Tea Medium (16 oz)', 'selling_price' => 10000],
                    ['sku' => 'HH-TEA-006', 'name' => 'Green Tea Large (22 oz)', 'selling_price' => 12000],
                    ['sku' => 'HH-TEA-007', 'name' => 'Green Tea Medium Brown Sugar (16 oz)', 'selling_price' => 12000],
                    ['sku' => 'HH-TEA-008', 'name' => 'Green Tea Large Brown Sugar (22 oz)', 'selling_price' => 14000],
                    ['sku' => 'HH-TEA-009', 'name' => 'Lemon Tea Medium (16 oz)', 'selling_price' => 8000],
                    ['sku' => 'HH-TEA-010', 'name' => 'Lemon Tea Large (22 oz)', 'selling_price' => 10000],
                    ['sku' => 'HH-TEA-011', 'name' => 'Original Tea Medium (16 oz)', 'selling_price' => 4000],
                    ['sku' => 'HH-TEA-012', 'name' => 'Original Tea Large (22 oz)', 'selling_price' => 7000],
                ],
            ],
            [
                'name' => 'Haloha - Choco & Coffee Series',
                'products' => [
                    ['sku' => 'HH-CHO-001', 'name' => 'Chocolate (22 oz)', 'selling_price' => 14000],
                    ['sku' => 'HH-CHO-002', 'name' => 'Dark Chocolate (22 oz)', 'selling_price' => 16000],
                    ['sku' => 'HH-CHO-003', 'name' => 'Cappucino (22 oz)', 'selling_price' => 15000],
                    ['sku' => 'HH-CHO-004', 'name' => 'Kopi Susu (16 oz)', 'selling_price' => 12000],
                ],
            ],
            [
                'name' => 'Haloha - Original Flavour Series',
                'products' => [
                    ['sku' => 'HH-FLV-001', 'name' => 'Vanila & Cookies (22 oz)', 'selling_price' => 15000],
                    ['sku' => 'HH-FLV-002', 'name' => 'Taro (22 oz)', 'selling_price' => 14000],
                    ['sku' => 'HH-FLV-003', 'name' => 'Banana (22 oz)', 'selling_price' => 14000],
                    ['sku' => 'HH-FLV-004', 'name' => 'Red Velvet (22 oz)', 'selling_price' => 14000],
                ],
            ],
            [
                'name' => 'Haloha - Brown Sugar Series',
                'products' => [
                    ['sku' => 'HH-BRN-001', 'name' => 'Fresh Milk Gula Aren (16 oz)', 'selling_price' => 14000],
                    ['sku' => 'HH-BRN-002', 'name' => 'Fresh Milk Regal (16 oz)', 'selling_price' => 16000],
                    ['sku' => 'HH-BRN-003', 'name' => 'Kopi Susu Gula Aren (16 oz)', 'selling_price' => 14000],
                    ['sku' => 'HH-BRN-004', 'name' => 'Kopi Susu Regal (16 oz)', 'selling_price' => 16000],
                ],
            ],
            [
                'name' => 'Haloha - Yakult Series',
                'products' => [
                    ['sku' => 'HH-YAK-001', 'name' => 'Yakult Lychee (16 oz)', 'selling_price' => 14000],
                    ['sku' => 'HH-YAK-002', 'name' => 'Yakult Mango (16 oz)', 'selling_price' => 14000],
                    ['sku' => 'HH-YAK-003', 'name' => 'Yakult Grape (16 oz)', 'selling_price' => 14000],
                ],
            ],
            [
                'name' => 'Haloha - Bottle Series',
                'products' => [
                    ['sku' => 'HH-BTL-001', 'name' => 'Bottle Thai Tea (300 ml)', 'selling_price' => 10000],
                    ['sku' => 'HH-BTL-002', 'name' => 'Bottle Green Tea (300 ml)', 'selling_price' => 11000],
                    ['sku' => 'HH-BTL-003', 'name' => 'Bottle Coffee Milk (300 ml)', 'selling_price' => 12000],
                ],
            ],
            [
                'name' => 'Haloha - HOT Series',
                'products' => [
                    ['sku' => 'HH-HOT-001', 'name' => 'Thai Tea Hot (8 oz)', 'selling_price' => 9000],
                    ['sku' => 'HH-HOT-002', 'name' => 'Green Tea Hot (8 oz)', 'selling_price' => 10000],
                    ['sku' => 'HH-HOT-003', 'name' => 'Chocolate Hot (8 oz)', 'selling_price' => 11000],
                    ['sku' => 'HH-HOT-004', 'name' => 'Dark Chocolate Hot (8 oz)', 'selling_price' => 12000],
                ],
            ],
            [
                'name' => 'Haloha - Toping',
                'products' => [
                    ['sku' => 'HH-TOP-001', 'name' => 'Cookies (1 Set)', 'selling_price' => 3000],
                    ['sku' => 'HH-TOP-002', 'name' => 'Rainbow Jelly (1 Set)', 'selling_price' => 3000],
                    ['sku' => 'HH-TOP-003', 'name' => 'Nata De Coco (1 Set)', 'selling_price' => 3000],
                    ['sku' => 'HH-TOP-004', 'name' => 'Regal (1 Set)', 'selling_price' => 3000],
                ],
            ],
            [
                'name' => 'Resto - Aneka Mie dan Pangsit',
                'products' => [
                    ['sku' => 'RST-MIE-001', 'name' => 'Mie Haloha A (Lvl 0-1, tanpa kuah kaldu)', 'selling_price' => 12000],
                    ['sku' => 'RST-MIE-002', 'name' => 'Mie Haloha B (Lvl 2-3, tanpa kuah kaldu)', 'selling_price' => 14000],
                    ['sku' => 'RST-MIE-003', 'name' => 'Mie Chili Oil Original (tanpa kuah kaldu)', 'selling_price' => 16000],
                    ['sku' => 'RST-MIE-004', 'name' => 'Mie Chili Oil Chicken Katsu (tanpa kuah kaldu)', 'selling_price' => 25000],
                    ['sku' => 'RST-MIE-005', 'name' => 'Mie Haloha Spesial (Bakso, Lvl 0-3, termasuk kuah kaldu)', 'selling_price' => 19000],
                    ['sku' => 'RST-MIE-006', 'name' => 'Mie Haloha Komplit (Bakso & Pangsit Rebus, Lvl 0-3, termasuk kuah kaldu)', 'selling_price' => 22000],
                    ['sku' => 'RST-MIE-007', 'name' => 'Mie Ayam (Ayam Kecap, Sawi, Pangsit Goreng, Kuah Kaldu)', 'selling_price' => 17000],
                    ['sku' => 'RST-MIE-008', 'name' => 'Mie Ayam Spesial (Bakso, termasuk kuah kaldu)', 'selling_price' => 24000],
                    ['sku' => 'RST-MIE-009', 'name' => 'Mie Ayam Komplit (Bakso & Pangsit Rebus, termasuk kuah kaldu)', 'selling_price' => 26000],
                    ['sku' => 'RST-MIE-010', 'name' => 'Bakso Kuah Polos (5 Pcs)', 'selling_price' => 22000],
                    ['sku' => 'RST-MIE-011', 'name' => 'Mie Goreng Spesial (Ayam, Udang, Bakso)', 'selling_price' => 24000],
                    ['sku' => 'RST-MIE-012', 'name' => 'Kwetiau Goreng Ayam', 'selling_price' => 22000],
                    ['sku' => 'RST-MIE-013', 'name' => 'Kwetiau Goreng Sapi', 'selling_price' => 25000],
                    ['sku' => 'RST-MIE-014', 'name' => 'Pangsit Chili Oil (4 Pcs)', 'selling_price' => 15000],
                    ['sku' => 'RST-MIE-015', 'name' => 'Pangsit Chili Oil (6 Pcs)', 'selling_price' => 20000],
                    ['sku' => 'RST-MIE-016', 'name' => 'Pangsit Rebus (4 Pcs)', 'selling_price' => 15000],
                    ['sku' => 'RST-MIE-017', 'name' => 'Pangsit Goreng (5 Pcs)', 'selling_price' => 15000],
                    ['sku' => 'RST-MIE-018', 'name' => 'Pangsit Goreng (10 Pcs)', 'selling_price' => 25000],
                ],
            ],
            [
                'name' => 'Resto - Hot Plate',
                'products' => [
                    ['sku' => 'RST-HOT-001', 'name' => 'Mie Hot Plate (Beef Black Pepper)', 'selling_price' => 25000],
                    ['sku' => 'RST-HOT-002', 'name' => 'Mie Hot Plate (Beef Teriyaki)', 'selling_price' => 25000],
                    ['sku' => 'RST-HOT-003', 'name' => 'Mie Hot Plate (Chicken Grill)', 'selling_price' => 23000],
                    ['sku' => 'RST-HOT-004', 'name' => 'Mie Hot Plate (Chicken Black Pepper)', 'selling_price' => 22000],
                    ['sku' => 'RST-HOT-005', 'name' => 'Mie Hot Plate (Chicken Teriyaki)', 'selling_price' => 22000],
                    ['sku' => 'RST-HOT-006', 'name' => 'Mie Hot Plate (Chicken Katsu)', 'selling_price' => 23000],
                    ['sku' => 'RST-HOT-007', 'name' => 'Tenderloin Steak (Sauce: BBQ / Black Pepper / Brown Sauce)', 'selling_price' => 33000],
                    ['sku' => 'RST-HOT-008', 'name' => 'Chicken Steak (Sauce: BBQ / Black Pepper / Brown)', 'selling_price' => 28000],
                ],
            ],
            [
                'name' => 'Resto - Rice Bowl',
                'products' => [
                    ['sku' => 'RST-RBL-001', 'name' => 'RB Chicken Katsu', 'selling_price' => 15000],
                    ['sku' => 'RST-RBL-002', 'name' => 'RB Egg Chicken Roll', 'selling_price' => 16000],
                    ['sku' => 'RST-RBL-003', 'name' => 'RB Shirmp Roll', 'selling_price' => 16000],
                    ['sku' => 'RST-RBL-004', 'name' => 'RB Chicken Wings', 'selling_price' => 17000],
                    ['sku' => 'RST-RBL-005', 'name' => 'RB Chicken Karage', 'selling_price' => 15000],
                    ['sku' => 'RST-RBL-006', 'name' => 'RB Mix Bento', 'selling_price' => 18000],
                    ['sku' => 'RST-RBL-007', 'name' => 'RB Chicken Gochujang', 'selling_price' => 18000],
                    ['sku' => 'RST-RBL-008', 'name' => 'RB Chicken Grill', 'selling_price' => 18000],
                    ['sku' => 'RST-RBL-009', 'name' => 'RB Beef Teriyaki', 'selling_price' => 22000],
                    ['sku' => 'RST-RBL-010', 'name' => 'RB Beef Black Pepper', 'selling_price' => 22000],
                    ['sku' => 'RST-RBL-011', 'name' => 'RB Cumi Cabe Hijau', 'selling_price' => 20000],
                ],
            ],
            [
                'name' => 'Resto - Extra Topping',
                'products' => [
                    ['sku' => 'RST-TOP-001', 'name' => 'Telur Mata Sapi', 'selling_price' => 4000],
                    ['sku' => 'RST-TOP-002', 'name' => 'Extra Sauce (Chili Oil / BBQ / BP / BS / Blgns)', 'selling_price' => 3000],
                ],
            ],
            [
                'name' => 'Resto - Chicken, Beef, And Fish',
                'products' => [
                    ['sku' => 'RST-CBF-001', 'name' => 'Ayam Negeri (Bakar, D/P)', 'selling_price' => 17000],
                    ['sku' => 'RST-CBF-002', 'name' => 'Ayam Negeri (Goreng Keremes, D/P)', 'selling_price' => 18000],
                    ['sku' => 'RST-CBF-003', 'name' => 'Ayam Pejantan (Bakar, D/P)', 'selling_price' => 20000],
                    ['sku' => 'RST-CBF-004', 'name' => 'Ayam Pejantan (Goreng Keremes, D/P)', 'selling_price' => 21000],
                    ['sku' => 'RST-CBF-005', 'name' => 'Chicken Katsu', 'selling_price' => 13000],
                    ['sku' => 'RST-CBF-006', 'name' => 'Ayam Cabe Garam (Porsi 1-2 Org)', 'selling_price' => 25000],
                    ['sku' => 'RST-CBF-007', 'name' => 'Chicken Karage (Porsi 1-2 Org)', 'selling_price' => 20000],
                    ['sku' => 'RST-CBF-008', 'name' => 'Chicken Black Pepper (Porsi 1-2 Org)', 'selling_price' => 25000],
                    ['sku' => 'RST-CBF-009', 'name' => 'Chicken Teriyaki (Porsi 1-2 Org)', 'selling_price' => 25000],
                    ['sku' => 'RST-CBF-010', 'name' => 'Beef Black Pepper (Porsi 1-2 Org)', 'selling_price' => 33000],
                    ['sku' => 'RST-CBF-011', 'name' => 'Beef Teriyaki (Porsi 1-2 Org)', 'selling_price' => 33000],
                    ['sku' => 'RST-CBF-012', 'name' => 'Iga Bakar', 'selling_price' => 33000],
                    ['sku' => 'RST-CBF-013', 'name' => 'Sop Iga', 'selling_price' => 35000],
                    ['sku' => 'RST-CBF-014', 'name' => 'Soto Betawi (Daging)', 'selling_price' => 30000],
                    ['sku' => 'RST-CBF-015', 'name' => 'Nila (Goreng)', 'selling_price' => 20000],
                    ['sku' => 'RST-CBF-016', 'name' => 'Nila (Bakar)', 'selling_price' => 20000],
                    ['sku' => 'RST-CBF-017', 'name' => 'Gurame (Goreng)', 'selling_price' => 60000],
                    ['sku' => 'RST-CBF-018', 'name' => 'Gurame (Bakar)', 'selling_price' => 60000],
                    ['sku' => 'RST-CBF-019', 'name' => 'Gurame (Asam Manis)', 'selling_price' => 40000],
                    ['sku' => 'RST-CBF-020', 'name' => 'Gurame (Saus Lemon)', 'selling_price' => 40000],
                    ['sku' => 'RST-CBF-021', 'name' => 'Cumi Cabe Hijau (Porsi 1-2 Org)', 'selling_price' => 30000],
                ],
            ],
            [
                'name' => 'Resto - Aneka Nasi',
                'products' => [
                    ['sku' => 'RST-NAS-001', 'name' => 'Nasi Putih', 'selling_price' => 5000],
                    ['sku' => 'RST-NAS-002', 'name' => 'Nasi Goreng', 'selling_price' => 20000],
                    ['sku' => 'RST-NAS-003', 'name' => 'Nasi Goreng Haloha (+Chicken Strips)', 'selling_price' => 25000],
                    ['sku' => 'RST-NAS-004', 'name' => 'Nasi Goreng Ikan Asin', 'selling_price' => 25000],
                    ['sku' => 'RST-NAS-005', 'name' => 'Nasi Goreng Mozarella', 'selling_price' => 25000],
                ],
            ],
            [
                'name' => 'Resto - Aneka Sayur',
                'products' => [
                    ['sku' => 'RST-SAY-001', 'name' => 'Cah Kangkung', 'selling_price' => 15000],
                    ['sku' => 'RST-SAY-002', 'name' => 'Cap Cay (Ayam, Udang, Bakso)', 'selling_price' => 20000],
                ],
            ],
            [
                'name' => 'Resto - Paket Nasi (Gratis Es Teh Manis)',
                'products' => [
                    ['sku' => 'RST-PKT-001', 'name' => 'Nasi + Ayam N. (Bakar / Grg Kremes, D/P)', 'selling_price' => 25000],
                    ['sku' => 'RST-PKT-002', 'name' => 'Nasi + Ayam P. (Bakar / Grg Kremes, D/P)', 'selling_price' => 28000],
                    ['sku' => 'RST-PKT-003', 'name' => 'Nasi + Ikan Nila (Bakar / Goreng)', 'selling_price' => 28000],
                    ['sku' => 'RST-PKT-004', 'name' => 'Nasi + Capcay (Ayam, Udang, Bakso)', 'selling_price' => 23000],
                    ['sku' => 'RST-PKT-005', 'name' => 'Nasi + Iga Bakar', 'selling_price' => 40000],
                    ['sku' => 'RST-PKT-006', 'name' => 'Nasi + Soto Betawi', 'selling_price' => 37000],
                ],
            ],
            [
                'name' => 'Resto - Aneka Snack',
                'products' => [
                    ['sku' => 'RST-SNK-001', 'name' => 'Cireng Bumbu Rujak', 'selling_price' => 15000],
                    ['sku' => 'RST-SNK-002', 'name' => 'Pempek (Lenjer, Telor, Adaan)', 'selling_price' => 22000],
                    ['sku' => 'RST-SNK-003', 'name' => 'Siomay Bandung (Siomay & Tahu)', 'selling_price' => 18000],
                    ['sku' => 'RST-SNK-004', 'name' => 'Dimsum Original (3 Pcs)', 'selling_price' => 14000],
                    ['sku' => 'RST-SNK-005', 'name' => 'Dimsum Original (4 Pcs)', 'selling_price' => 17000],
                    ['sku' => 'RST-SNK-006', 'name' => 'Dimsum Original (5 Pcs)', 'selling_price' => 20000],
                    ['sku' => 'RST-SNK-007', 'name' => 'Dimsum Original (6 Pcs)', 'selling_price' => 22000],
                    ['sku' => 'RST-SNK-008', 'name' => 'Dimsum Mentai (4 Pcs)', 'selling_price' => 24000],
                    ['sku' => 'RST-SNK-009', 'name' => 'Dimsum Mentai (6 Pcs)', 'selling_price' => 34000],
                    ['sku' => 'RST-SNK-010', 'name' => 'Dimsum Bolognese (4 Pcs)', 'selling_price' => 26000],
                    ['sku' => 'RST-SNK-011', 'name' => 'Dimsum Bolognese (6 Pcs)', 'selling_price' => 36000],
                    ['sku' => 'RST-SNK-012', 'name' => 'Burger', 'selling_price' => 18000],
                    ['sku' => 'RST-SNK-013', 'name' => 'Cheese Burger', 'selling_price' => 20000],
                    ['sku' => 'RST-SNK-014', 'name' => 'Spaghetti Bolognase', 'selling_price' => 22000],
                    ['sku' => 'RST-SNK-015', 'name' => 'Hot Dog', 'selling_price' => 18000],
                    ['sku' => 'RST-SNK-016', 'name' => 'Kentang Goreng (French Fries)', 'selling_price' => 20000],
                    ['sku' => 'RST-SNK-017', 'name' => 'Sosis Goreng (Sausage)', 'selling_price' => 22000],
                    ['sku' => 'RST-SNK-018', 'name' => 'Snack Platter A (Sosis dan Kentang)', 'selling_price' => 26000],
                    ['sku' => 'RST-SNK-019', 'name' => 'Snack Platter B (Sosis, Kentang, Spicy Wing)', 'selling_price' => 35000],
                    ['sku' => 'RST-SNK-020', 'name' => 'Lumpia Kulit Tahu (Chili Oil)', 'selling_price' => 18000],
                ],
            ],
            [
                'name' => 'Resto - Dessert',
                'products' => [
                    ['sku' => 'RST-DES-001', 'name' => 'Pisang Bakar (Coklat Keju)', 'selling_price' => 15000],
                    ['sku' => 'RST-DES-002', 'name' => 'Pisang Goreng (Brown Sugar)', 'selling_price' => 15000],
                    ['sku' => 'RST-DES-003', 'name' => 'Roti Bakar (Coklat Keju)', 'selling_price' => 14000],
                    ['sku' => 'RST-DES-004', 'name' => 'Ice Cream', 'selling_price' => 15000],
                    ['sku' => 'RST-DES-005', 'name' => 'Ice Pelangi (Kelapa, Cincau, Ketan Hitam, Nangka)', 'selling_price' => 15000],
                ],
            ],
            [
                'name' => 'Resto - Aneka Jus',
                'products' => [
                    ['sku' => 'RST-JUS-001', 'name' => 'Jus Apel', 'selling_price' => 16000],
                    ['sku' => 'RST-JUS-002', 'name' => 'Jus Alpukat', 'selling_price' => 16000],
                    ['sku' => 'RST-JUS-003', 'name' => 'Jus Jambu Merah', 'selling_price' => 15000],
                    ['sku' => 'RST-JUS-004', 'name' => 'Jus Sirsak', 'selling_price' => 15000],
                    ['sku' => 'RST-JUS-005', 'name' => 'Jus Melon', 'selling_price' => 15000],
                    ['sku' => 'RST-JUS-006', 'name' => 'Jus Mangga', 'selling_price' => 16000],
                    ['sku' => 'RST-JUS-007', 'name' => 'Jus Stroberi', 'selling_price' => 15000],
                    ['sku' => 'RST-JUS-008', 'name' => 'Mix Jus (Mix 2 Rasa Pilihan)', 'selling_price' => 18000],
                    ['sku' => 'RST-JUS-009', 'name' => 'Es Jeruk', 'selling_price' => 15000],
                ],
            ],
            [
                'name' => 'Resto - Milk Shake',
                'products' => [
                    ['sku' => 'RST-MLK-001', 'name' => 'Milk Shake Chocolate', 'selling_price' => 20000],
                    ['sku' => 'RST-MLK-002', 'name' => 'Milk Shake Vanila', 'selling_price' => 20000],
                    ['sku' => 'RST-MLK-003', 'name' => 'Milk Shake Strawberry', 'selling_price' => 20000],
                ],
            ],
            [
                'name' => 'Resto - Coffee and Chocolate',
                'products' => [
                    ['sku' => 'RST-COF-001', 'name' => 'Black Coffee (Hot, Tubruk)', 'selling_price' => 12000],
                    ['sku' => 'RST-COF-002', 'name' => 'Americano (Ice)', 'selling_price' => 15000],
                    ['sku' => 'RST-COF-003', 'name' => 'Kopi Susu Gula Aren (Ice)', 'selling_price' => 14000],
                    ['sku' => 'RST-COF-004', 'name' => 'Kopi Susu (Ice)', 'selling_price' => 12000],
                    ['sku' => 'RST-COF-005', 'name' => 'Chocolate (Hot / Ice)', 'selling_price' => 14000],
                    ['sku' => 'RST-COF-006', 'name' => 'Dark Chocolate (Hot / Ice)', 'selling_price' => 16000],
                ],
            ],
            [
                'name' => 'Resto - Tea',
                'products' => [
                    ['sku' => 'RST-TEA-001', 'name' => 'Teh Tawar (Hot / Ice)', 'selling_price' => 4000],
                    ['sku' => 'RST-TEA-002', 'name' => 'Teh Manis (Hot / Ice)', 'selling_price' => 5000],
                    ['sku' => 'RST-TEA-003', 'name' => 'Teh Manis Pitcher (Ice, 4-5 Orang)', 'selling_price' => 18000],
                    ['sku' => 'RST-TEA-004', 'name' => 'Lemon Tea (Ice)', 'selling_price' => 8000],
                    ['sku' => 'RST-TEA-005', 'name' => 'Lemon Grass (Ice)', 'selling_price' => 10000],
                ],
            ],
            [
                'name' => 'Resto - Air Mineral',
                'products' => [
                    ['sku' => 'RST-AIR-001', 'name' => 'Botol 330 ml', 'selling_price' => 4000],
                ],
            ],
            [
                'name' => 'Resto - Add On',
                'products' => [
                    ['sku' => 'RST-ADD-001', 'name' => 'Es Batu', 'selling_price' => 2000],
                ],
            ],
        ];
    }
}

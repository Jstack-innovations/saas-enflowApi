-- Backup of enflowSaas — 2026-07-16T11:47:02+00:00

SET FOREIGN_KEY_CHECKS=0;

-- Table: admin_sessions
DROP TABLE IF EXISTS `admin_sessions`;
CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_activity` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;


-- Table: admins
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_email` (`tenant_id`,`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO `admins` VALUES('1','1','Tristincassey@gmail.com','$2y$10$DaoSlI7Ee8AVKGv3WbAphOJfEpudvHkRWb49Il91BobXf4B3u9qSO','2026-07-13 20:21:41');

-- Table: banners
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `discount_title` varchar(255) DEFAULT NULL,
  `discount_subtitle` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO `banners` VALUES('1','1','351 Maison Street, NY','15% EXTRA DISCOUNT','Get your first order delivery free!');

-- Table: booked_tables
DROP TABLE IF EXISTS `booked_tables`;
CREATE TABLE `booked_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `booked` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_table` (`tenant_id`,`table_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4;

INSERT INTO `booked_tables` VALUES('1','1','6778','1');
INSERT INTO `booked_tables` VALUES('2','1','5667','1');
INSERT INTO `booked_tables` VALUES('4','1','3114','1');
INSERT INTO `booked_tables` VALUES('6','1','708','1');
INSERT INTO `booked_tables` VALUES('7','1','1','1');
INSERT INTO `booked_tables` VALUES('10','1','112','1');
INSERT INTO `booked_tables` VALUES('11','1','667','1');
INSERT INTO `booked_tables` VALUES('12','1','678','1');
INSERT INTO `booked_tables` VALUES('14','1','113','1');
INSERT INTO `booked_tables` VALUES('16','1','567','1');
INSERT INTO `booked_tables` VALUES('17','1','2','1');
INSERT INTO `booked_tables` VALUES('18','1','3','1');
INSERT INTO `booked_tables` VALUES('19','1','4','1');
INSERT INTO `booked_tables` VALUES('20','1','23','1');
INSERT INTO `booked_tables` VALUES('21','1','34','1');
INSERT INTO `booked_tables` VALUES('22','1','67','1');
INSERT INTO `booked_tables` VALUES('23','1','77','1');
INSERT INTO `booked_tables` VALUES('24','1','5','1');
INSERT INTO `booked_tables` VALUES('25','1','12','1');
INSERT INTO `booked_tables` VALUES('26','1','45','1');

-- Table: kitchen_production
DROP TABLE IF EXISTS `kitchen_production`;
CREATE TABLE `kitchen_production` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `menu_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Table: login_verifications
DROP TABLE IF EXISTS `login_verifications`;
CREATE TABLE `login_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(4) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Table: menu_items
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `badge` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;

INSERT INTO `menu_items` VALUES('1','1','appetizers','Authentic Nigerian Beef Suya','Charcoal-grilled beef marinated in traditional Nigerian suya spices, slow-roasted to smoky perfection and served with a bold, spicy finish.','1.00','https://jstack-sigma.vercel.app/artisangrill/suya.jpg','[\"Signature\", \"Spicy\"]','signature');
INSERT INTO `menu_items` VALUES('2','1','appetizers','Smoked Turkey Wings (Signature)','Tender smoked turkey wings, glazed with our house BBQ sauce, served with a zesty herb dip and crispy onion crunch.','24.00','https://jstack-sigma.vercel.app/artisangrill/wings.jpg','[\"Gluten-Free\", \"Chef\'s Pick\", \"Smoky\"]','');
INSERT INTO `menu_items` VALUES('3','1','appetizers','Moi Moi Deluxe (Nigerian Style)','Steamed Nigerian bean pudding made with fresh ground beans, peppers, and spices, served warm with a rich palm oil drizzle and a soft, silky texture.','32.00','https://jstack-sigma.vercel.app/artisangrill/moimoi.jpg','[\"Sharing\", \"Premium\"]','signature');
INSERT INTO `menu_items` VALUES('4','1','main','Assorted (Nigerian) Jollof Rice Supreme','Aromatic long-grain rice cooked in a rich tomato-pepper blend, served with tender chicken, caramelized onions, and a hint of smoky spice.','42.00','https://jstack-sigma.vercel.app/artisangrill/jollof.jpg','[\"Premium\", \"Best Seller\"]','signature');
INSERT INTO `menu_items` VALUES('5','1','main','Nigerian Pepper Soup (Chicken / Goat)','Hot and spicy broth infused with traditional Nigerian spices, served with tender meat and a refreshing kick of scent leaf.','26.00','https://jstack-sigma.vercel.app/artisangrill/peppersoup.jpg','[\"Vegetarian\", \"Seasonal\"]','');
INSERT INTO `menu_items` VALUES('6','1','main','Pounded Yam & Egusi','Smooth, stretchy pounded yam paired with a hearty egusi soup made from ground melon seeds, spinach, and tender beef, simmered in flavorful spices.','38.00','https://jstack-sigma.vercel.app/artisangrill/egusi.jpg','[\"Slow-Cooked\", \"Premium\", \"Traditional\", \"Hearty\", \"Rich\"]','signature');
INSERT INTO `menu_items` VALUES('7','1','regional','Jamaican Ackee & Saltfish','Jamaica\'s national dish—salted fish sautéed with ackee, peppers, and spices for a flavorful breakfast or main.','28.00','https://jstack-sigma.vercel.app/artisangrill/jamaica.jpg','[\"Spicy\", \"Traditional\"]','regional');
INSERT INTO `menu_items` VALUES('8','1','regional','Kenyan Ugali & Sukuma Wiki','Soft ugali served with sautéed kale, simmered in a rich tomato sauce and local spices for a comforting taste.','36.00','https://jstack-sigma.vercel.app/artisangrill/kenya.jpg','[\"Traditional\", \"Kenyan\", \"Healthy\"]','regional');
INSERT INTO `menu_items` VALUES('9','1','regional','Nigerian Bole & Fish','Roasted plantain paired with grilled fish and spicy pepper sauce, finished with crunchy groundnuts and smoky aroma.','32.00','https://jstack-sigma.vercel.app/artisangrill/nigeria.jpg','[\"Street Food\", \"Southern\", \"Signature\"]','regional');
INSERT INTO `menu_items` VALUES('10','1','continental','Spanish Patatas Bravas','Crispy golden potatoes served with spicy tomato sauce and creamy garlic aioli — the perfect shareable side.','48.00','https://jstack-sigma.vercel.app/artisangrill/spain.jpg','[\"Tapas\", \"Spicy\", \"Snack\"]','continental');
INSERT INTO `menu_items` VALUES('11','1','continental','Italian Truffle Mushroom Risotto','Creamy Arborio rice cooked with wild mushrooms, parmesan, and black truffle — luxurious and indulgent.','36.00','https://jstack-sigma.vercel.app/artisangrill/italy.jpg','[\"Italian\", \"Classic\"]','continental');
INSERT INTO `menu_items` VALUES('12','1','continental','Chinese Vegetable Chow Mein','Stir-fried noodles with crunchy veggies in a light savory sauce — simple, delicious, and satisfying.','34.00','https://jstack-sigma.vercel.app/artisangrill/china.jpg','[\"Traditional\", \"Vegetarian\", \"Noodles\"]','continental');
INSERT INTO `menu_items` VALUES('13','1','desserts','Chocolate Soufflé','Warm chocolate soufflé with vanilla bean ice cream and berry coulis','16.00','https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80','[\"Signature\", \"Made to Order\"]','signature');
INSERT INTO `menu_items` VALUES('14','1','desserts','Crème Brûlée Trio','Vanilla, lavender, and chocolate crème brûlée with shortbread cookies','18.00','https://images.unsplash.com/photo-1499636136210-6f4ee915583e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80','[\"Vegetarian\", \"Classic\"]','');
INSERT INTO `menu_items` VALUES('15','1','desserts','New York Cheesecake','Smooth and creamy cheesecake with a buttery biscuit base, finished with a light vanilla aroma.','22.00','https://jstack-sigma.vercel.app/artisangrill/yorkcake.jpg','[\"Classic\", \"Creamy\", \"Dessert\"]','');
INSERT INTO `menu_items` VALUES('16','1','breakfast','Yam & Egg Sauce','Boiled yam served with rich tomato and pepper egg sauce, cooked in palm or vegetable oil.','14.00','https://jstack-sigma.vercel.app/artisangrill/yamandegg.jpg','[\"Breakfast\", \"Classic\"]','breakfast');
INSERT INTO `menu_items` VALUES('17','1','breakfast','Fried Plantain & Scrambled Eggs','Sweet fried plantain paired with fluffy scrambled eggs, seasoned to perfection.','10.00','https://jstack-sigma.vercel.app/artisangrill/plantainandegg.jpg','[\"Vegan\", \"Healthy\", \"Savory\", \"Breakfast\"]','');
INSERT INTO `menu_items` VALUES('18','1','breakfast','Artisan Grilled Chicken Sandwich','Tender marinated chicken grilled to perfection, paired with fresh greens, ripe tomatoes, and a rich signature sauce on a warm brioche bun.','12.00','https://jstack-sigma.vercel.app/artisangrill/sandwich.jpg','[\"Sweet\", \"Kids\", \"Grilled\", \"Sandwich\"]','');
INSERT INTO `menu_items` VALUES('19','1','drinks','Bellare Wine','A smooth and elegant wine with delicate fruity notes and a clean, refreshing finish.','4.00','https://jstack-sigma.vercel.app/artisangrill/bellaire.jpg','[\"Signature\", \"Smooth\", \"Premium\", \"Wine\"]','');
INSERT INTO `menu_items` VALUES('20','1','drinks','Moët & Chandon Champagne','An iconic French champagne celebrated for its vibrant elegance and refined bubbles.','6.00','https://jstack-sigma.vercel.app/artisangrill/chandon.jpg','[\"Luxury\", \"Signature\", \"Sparkling\"]','');
INSERT INTO `menu_items` VALUES('21','1','drinks','Courvoisier','A distinguished French cognac known for its rich aroma and smooth character.','3.00','https://jstack-sigma.vercel.app/artisangrill/courvoisier.jpg','[\"Premium\", \"Smooth\", \"Luxury\"]','');
INSERT INTO `menu_items` VALUES('22','1','fastfood','Artisan Grilled Chicken Sandwich','Tender marinated chicken grilled to perfection with fresh greens and signature sauce.','12.00','https://jstack-sigma.vercel.app/artisangrill/sandwich.jpg','[\"Fast Food\", \"Popular\"]','');
INSERT INTO `menu_items` VALUES('23','1','fastfood','Chicken Wings (Nigerian Style)','Crispy, juicy chicken wings tossed in spicy pepper glaze with dip.','11.00','https://jstack-sigma.vercel.app/artisangrill/chickenwings.jpg','[\"Spicy\", \"Fastfood\"]','');
INSERT INTO `menu_items` VALUES('24','1','combos','Weekend Special','2 mains + 4 sides + 2 drinks — a perfect weekend feast.','55.00','https://jstack-sigma.vercel.app/artisangrill/weekend.jpg','[\"Combo\", \"Family\"]','');
INSERT INTO `menu_items` VALUES('25','1','combos','Couple Combo','2 appetizers + 2 mains + 2 drinks for a romantic dining experience.','42.00','https://jstack-sigma.vercel.app/artisangrill/couple.jpg','[\"Combo\", \"Couple\"]','');
INSERT INTO `menu_items` VALUES('26','1','vegan','(Nigerian) Jollof Rice Supreme','Aromatic rice cooked in tomato-pepper blend served with chicken and spices.','16.00','https://jstack-sigma.vercel.app/artisangrill/jollof.jpg','[\"Vegan\", \"Healthy\"]','');
INSERT INTO `menu_items` VALUES('27','1','vegan','Plantain & Beans (Ewa Agoyin Style)','Soft beans served with fried plantain and spicy pepper sauce.','14.00','https://jstack-sigma.vercel.app/artisangrill/beans.jpg','[\"Vegan\", \"Comfort\", \"Street Food\"]','');
INSERT INTO `menu_items` VALUES('28','1','kids','Kids Chicken Pie','Flaky crust filled with chicken, creamy veggies, and spices.','9.00','https://jstack-sigma.vercel.app/artisangrill/chickenpie.jpg','[\"Kids\", \"Snack\"]','');
INSERT INTO `menu_items` VALUES('29','1','kids','Rice & Stew','Steamed rice served with rich tomato stew and tender meat.','8.00','https://jstack-sigma.vercel.app/artisangrill/riceandstew.jpg','[\"Comfort\", \"Nigerian\", \"Signature\"]','Signature');

-- Table: menu_stock
DROP TABLE IF EXISTS `menu_stock`;
CREATE TABLE `menu_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_menu` (`tenant_id`,`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;

INSERT INTO `menu_stock` VALUES('1','1','1','0','0','2026-06-25 08:05:35');
INSERT INTO `menu_stock` VALUES('2','1','2','10','1','2026-07-09 20:18:16');
INSERT INTO `menu_stock` VALUES('3','1','3','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('4','1','4','15','1','2026-07-09 19:51:20');
INSERT INTO `menu_stock` VALUES('5','1','5','19','1','2026-06-25 08:21:22');
INSERT INTO `menu_stock` VALUES('6','1','6','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('7','1','7','19','1','2026-06-24 18:45:56');
INSERT INTO `menu_stock` VALUES('8','1','8','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('9','1','9','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('10','1','10','19','1','2026-06-24 18:46:00');
INSERT INTO `menu_stock` VALUES('11','1','11','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('12','1','12','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('13','1','13','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('14','1','14','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('15','1','15','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('16','1','16','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('17','1','17','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('18','1','18','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('19','1','19','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('20','1','20','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('21','1','21','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('22','1','22','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('23','1','23','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('24','1','24','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('25','1','25','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('26','1','26','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('27','1','27','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('28','1','28','20','1','2026-06-24 09:20:31');
INSERT INTO `menu_stock` VALUES('29','1','29','20','1','2026-06-24 09:20:31');

-- Table: offers
DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `main_text` varchar(255) DEFAULT NULL,
  `sub_text` varchar(255) DEFAULT NULL,
  `bg_color` varchar(20) DEFAULT NULL,
  `image` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

INSERT INTO `offers` VALUES('1','1','UP TO','50% OFF','For your first order','#F4E6C8','https://jstack-sigma.vercel.app/artisangrill/firstOrder.png');
INSERT INTO `offers` VALUES('2','1','ORDER 100 AED & GET','30 AED','CASHBACK','#FFB347','https://jstack-sigma.vercel.app/artisangrill/cashBack.png');
INSERT INTO `offers` VALUES('3','1','FREE DELIVERY','TODAY ONLY','On selected restaurants','#A0E7E5','https://jstack-sigma.vercel.app/artisangrill/freeDelivery.png');

-- Table: paid_order_items
DROP TABLE IF EXISTS `paid_order_items`;
CREATE TABLE `paid_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `paid_order_id` int(11) DEFAULT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `menu_name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4;

INSERT INTO `paid_order_items` VALUES('1','1','1','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('2','1','2','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('3','1','3','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('4','1','4','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('5','1','5','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('6','1','6','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('7','1','7','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('8','1','8','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('9','1','9','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('10','1','10','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('11','1','11','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('12','1','12','1','Test',NULL,'1');
INSERT INTO `paid_order_items` VALUES('13','1','13','1','Test',NULL,'1');
INSERT INTO `paid_order_items` VALUES('14','1','14','1','Test',NULL,'1');
INSERT INTO `paid_order_items` VALUES('15','1','15','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('16','1','16','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('17','1','17','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('18','1','17','2','Smoked Turkey Wings (Signature)','24.00','1');
INSERT INTO `paid_order_items` VALUES('19','1','17','4','Assorted (Nigerian) Jollof Rice Supreme','42.00','1');
INSERT INTO `paid_order_items` VALUES('20','1','18','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('21','1','18','2','Smoked Turkey Wings (Signature)','24.00','1');
INSERT INTO `paid_order_items` VALUES('22','1','18','4','Assorted (Nigerian) Jollof Rice Supreme','42.00','1');
INSERT INTO `paid_order_items` VALUES('23','1','18','7','Jamaican Ackee & Saltfish','28.00','1');
INSERT INTO `paid_order_items` VALUES('24','1','18','10','Spanish Patatas Bravas','48.00','1');
INSERT INTO `paid_order_items` VALUES('25','1','19','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('26','1','20','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('27','1','21','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('28','1','22','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('29','1','23','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('30','1','24','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('31','1','25','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('32','1','26','1','Authentic Nigerian Beef Suya','1.00','1');
INSERT INTO `paid_order_items` VALUES('33','1','27','2','Smoked Turkey Wings (Signature)','24.00','1');
INSERT INTO `paid_order_items` VALUES('34','1','28','2','Smoked Turkey Wings (Signature)','24.00','1');
INSERT INTO `paid_order_items` VALUES('35','1','29','2','Smoked Turkey Wings (Signature)','24.00','1');
INSERT INTO `paid_order_items` VALUES('36','1','29','5','Nigerian Pepper Soup (Chicken / Goat)','26.00','1');
INSERT INTO `paid_order_items` VALUES('37','1','30','4','Assorted (Nigerian) Jollof Rice Supreme','42.00','1');
INSERT INTO `paid_order_items` VALUES('38','1','31','4','Assorted (Nigerian) Jollof Rice Supreme','42.00','1');
INSERT INTO `paid_order_items` VALUES('39','1','32','2','Smoked Turkey Wings (Signature)','24.00','1');
INSERT INTO `paid_order_items` VALUES('40','1','33','2','Smoked Turkey Wings (Signature)','24.00','2');
INSERT INTO `paid_order_items` VALUES('41','1','33','4','Assorted (Nigerian) Jollof Rice Supreme','42.00','1');
INSERT INTO `paid_order_items` VALUES('42','1','34','2','Smoked Turkey Wings (Signature)','24.00','1');
INSERT INTO `paid_order_items` VALUES('43','1','35','2','Smoked Turkey Wings (Signature)','24.00','3');

-- Table: paid_orders
DROP TABLE IF EXISTS `paid_orders`;
CREATE TABLE `paid_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `table_no` varchar(10) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `plate_order_no` varchar(50) DEFAULT NULL,
  `order_type` varchar(20) DEFAULT 'table',
  `status` varchar(20) DEFAULT 'payment_pending',
  `full_address` varchar(255) DEFAULT NULL,
  `order_status` enum('Order placed','Cooking','Cooking done','Out for delivery','Delivered','Served','Picked up') DEFAULT 'Order placed',
  `user_id` int(11) DEFAULT NULL,
  `pickup_time` varchar(50) DEFAULT NULL,
  `session_code` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4;

INSERT INTO `paid_orders` VALUES('24','1','Samson','+2347089913116','23','4.13','10323906','2026-06-25 06:43:29','Artisan20260625GRILL49','table','paid',NULL,'Order placed',NULL,NULL,'TBL-23-CDD03');
INSERT INTO `paid_orders` VALUES('25','1','Samson Omoiyekewen','+2347089913116','34','4.13','10324046','2026-06-25 08:02:53','Artisan20260625GRILL97','table','paid','','Order placed',NULL,'',NULL);
INSERT INTO `paid_orders` VALUES('26','1','Hhj','+2347089913116','','4.13','10324050','2026-06-25 08:05:12','Artisan20260625GRILL84','delivery','paid','Dockyard Road, Port of Apapa, Apapa, Ilado, Apapa, Lagos State, 100242, Nigeria','Order placed',NULL,'',NULL);
INSERT INTO `paid_orders` VALUES('27','1','Tty','+2347089913116','','30.00','10324052','2026-06-25 08:07:11','Artisan20260625GRILL04','pickup','paid','','Order placed',NULL,'12:07',NULL);
INSERT INTO `paid_orders` VALUES('28','1','Llo','+2347089913116','67','30.00','10324118','2026-06-25 08:19:07','Artisan20260625GRILL26','table','paid','','Order placed',NULL,'',NULL);
INSERT INTO `paid_orders` VALUES('29','1','Samson','+2347089913116','77','56.00','10324131','2026-06-25 08:20:40','Artisan20260625GRILL56','table','paid',NULL,'Order placed',NULL,NULL,'TBL-77-241BF');
INSERT INTO `paid_orders` VALUES('30','1','Bbn','+2347089913116','','50.25','10324132','2026-06-25 08:22:52','Artisan20260625GRILL63','delivery','paid','New Marina Road, Lagos, Lagos Island, Lagos State, 100242, Nigeria','Order placed',NULL,'',NULL);
INSERT INTO `paid_orders` VALUES('31','1','Mmv','+2347089913116','','50.25','10324134','2026-06-25 08:24:36','Artisan20260625GRILL78','pickup','paid','','Order placed',NULL,'12:30',NULL);
INSERT INTO `paid_orders` VALUES('32','1','Tty','+2347089913116','13','30.00',NULL,'2026-07-09 19:47:42','Artisan20260709GRILL64','table','paid','','Order placed',NULL,'',NULL);
INSERT INTO `paid_orders` VALUES('33','1','Tttyyu','+2347089913116','12','96.00',NULL,'2026-07-09 19:50:37','Artisan20260709GRILL10','table','paid',NULL,'Order placed',NULL,NULL,'TBL-12-86AC1');
INSERT INTO `paid_orders` VALUES('34','1','Vvb','+2347089913116','67','30.00',NULL,'2026-07-09 20:09:42','DEARINAS2026070904','table','paid','','Order placed',NULL,'',NULL);
INSERT INTO `paid_orders` VALUES('35','1','Ioo','+2347089913116','45','78.00',NULL,'2026-07-09 20:17:04','DEARINAS2026070972','table','paid',NULL,'Order placed',NULL,NULL,'TBL-45-0BE64');

-- Table: reservations
DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `booking_date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `status` int(11) DEFAULT 1,
  `reservation_code` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Table: restaurant_tables
DROP TABLE IF EXISTS `restaurant_tables`;
CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `floor` varchar(10) DEFAULT NULL,
  `number` int(11) DEFAULT NULL,
  `seats` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `image` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4;

INSERT INTO `restaurant_tables` VALUES('1','1','1st','1','2','3000.00','https://jstack-sigma.vercel.app/artisangrill/1.jpg','Cozy table for 2 with soft lighting and comfortable seating.');
INSERT INTO `restaurant_tables` VALUES('2','1','1st','2','2','3000.00','https://jstack-sigma.vercel.app/artisangrill/2.jpg','Perfect for couples, with a private and intimate vibe.');
INSERT INTO `restaurant_tables` VALUES('3','1','1st','3','2','3000.00','https://jstack-sigma.vercel.app/artisangrill/3.jpg','Ideal for quick bites or light meals with premium comfort.');
INSERT INTO `restaurant_tables` VALUES('4','1','1st','4','2','3000.00','https://jstack-sigma.vercel.app/artisangrill/4.jpg','Quiet corner table for 2 with great ambiance.');
INSERT INTO `restaurant_tables` VALUES('5','1','1st','5','2','3000.00','https://jstack-sigma.vercel.app/artisangrill/5.jpg','Stylish table for 2 with warm lighting and comfy chairs.');
INSERT INTO `restaurant_tables` VALUES('6','1','1st','6','2','3000.00','https://jstack-sigma.vercel.app/artisangrill/6.jpg','Compact table for 2 with premium comfort and space.');
INSERT INTO `restaurant_tables` VALUES('7','1','1st','7','4','5000.00','https://jstack-sigma.vercel.app/artisangrill/7.jpg','Table for 4 with a spacious layout and modern design.');
INSERT INTO `restaurant_tables` VALUES('8','1','1st','8','4','5000.00','https://jstack-sigma.vercel.app/artisangrill/8.jpg','Great for small groups with cozy seating and privacy.');
INSERT INTO `restaurant_tables` VALUES('9','1','1st','9','4','5000.00','https://jstack-sigma.vercel.app/artisangrill/9.jpg','Perfect for family meals with comfortable seating.');
INSERT INTO `restaurant_tables` VALUES('10','1','1st','10','4','5000.00','https://jstack-sigma.vercel.app/artisangrill/10.jpg','Elegant table for 4 with premium ambiance.');
INSERT INTO `restaurant_tables` VALUES('11','1','2nd','11','4','5000.00','https://jstack-sigma.vercel.app/artisangrill/11.jpg','Modern table for 4 with extra legroom and comfort.');
INSERT INTO `restaurant_tables` VALUES('12','1','2nd','12','4','5000.00','https://jstack-sigma.vercel.app/artisangrill/12.jpg','Perfect for small parties and group conversations.');
INSERT INTO `restaurant_tables` VALUES('13','1','2nd','13','4','5000.00','https://jstack-sigma.vercel.app/artisangrill/13.jpg','Comfortable table for 4 with a relaxing vibe.');
INSERT INTO `restaurant_tables` VALUES('14','1','2nd','14','4','5000.00','https://jstack-sigma.vercel.app/artisangrill/14.jpg','Great for families and friends with cozy seating.');
INSERT INTO `restaurant_tables` VALUES('15','1','2nd','15','6','7500.00','https://jstack-sigma.vercel.app/artisangrill/15.jpg','Large table for 6 with premium comfort and space.');
INSERT INTO `restaurant_tables` VALUES('16','1','2nd','16','6','7500.00','https://jstack-sigma.vercel.app/artisangrill/16.jpg','Ideal for medium groups and family gatherings.');
INSERT INTO `restaurant_tables` VALUES('17','1','2nd','17','6','7500.00','https://jstack-sigma.vercel.app/artisangrill/17.jpg','Spacious table for 6 with a luxurious setting.');
INSERT INTO `restaurant_tables` VALUES('18','1','2nd','18','6','7500.00','https://jstack-sigma.vercel.app/artisangrill/18.jpg','Comfortable for groups with a premium dining feel.');
INSERT INTO `restaurant_tables` VALUES('19','1','2nd','19','6','7500.00','https://jstack-sigma.vercel.app/artisangrill/19.jpg','Perfect for group celebrations and events.');
INSERT INTO `restaurant_tables` VALUES('20','1','2nd','20','6','7500.00','https://jstack-sigma.vercel.app/artisangrill/20.jpg','Large table with a premium look and feel.');
INSERT INTO `restaurant_tables` VALUES('21','1','3rd','21','6','7500.00','https://jstack-sigma.vercel.app/artisangrill/table21.jpg','Spacious seating with a modern and elegant vibe.');
INSERT INTO `restaurant_tables` VALUES('22','1','3rd','22','6','7500.00','https://jstack-sigma.vercel.app/artisangrill/table22.jpg','Perfect for families, with a comfortable layout.');
INSERT INTO `restaurant_tables` VALUES('23','1','3rd','23','8','10000.00','https://jstack-sigma.vercel.app/artisangrill/table23.jpg','Large table for 8 with premium comfort and space.');
INSERT INTO `restaurant_tables` VALUES('24','1','3rd','24','8','10000.00','https://jstack-sigma.vercel.app/artisangrill/table24.jpg','Perfect for big groups and parties.');
INSERT INTO `restaurant_tables` VALUES('25','1','3rd','25','8','10000.00','https://jstack-sigma.vercel.app/artisangrill/table25.jpg','Spacious table with a luxury dining feel.');
INSERT INTO `restaurant_tables` VALUES('26','1','3rd','26','8','10000.00','https://jstack-sigma.vercel.app/artisangrill/table26.jpg','Ideal for large family gatherings and events.');
INSERT INTO `restaurant_tables` VALUES('27','1','3rd','27','8','10000.00','https://jstack-sigma.vercel.app/artisangrill/table27.jpg','Comfortable table with premium seating and space.');
INSERT INTO `restaurant_tables` VALUES('28','1','3rd','28','8','10000.00','https://jstack-sigma.vercel.app/artisangrill/table28.jpg','Perfect for big parties with a premium vibe.');
INSERT INTO `restaurant_tables` VALUES('29','1','3rd','29','8','10000.00','https://jstack-sigma.vercel.app/artisangrill/table29.jpg','Large table for 8 with a luxury setting.');
INSERT INTO `restaurant_tables` VALUES('30','1','3rd','30','8','10000.00','https://jstack-sigma.vercel.app/artisangrill/table30.jpg','Spacious table for 8 with premium comfort and elegance.');

-- Table: tax_settings
DROP TABLE IF EXISTS `tax_settings`;
CREATE TABLE `tax_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `tax` decimal(5,4) DEFAULT NULL,
  `delivery_fee` decimal(10,2) DEFAULT NULL,
  `service_fee` decimal(5,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_id` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO `tax_settings` VALUES('1','1','0.0750','3.00','0.0500');

-- Table: tenants
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `tagline` text DEFAULT NULL,
  `established` varchar(100) DEFAULT NULL,
  `hero_stat_dishes` varchar(20) DEFAULT NULL,
  `hero_stat_categories` varchar(20) DEFAULT NULL,
  `hero_stat_years` varchar(20) DEFAULT NULL,
  `footer_description` text DEFAULT NULL,
  `primary_color` varchar(20) DEFAULT '#d4a853',
  `social_instagram` varchar(255) DEFAULT NULL,
  `social_facebook` varchar(255) DEFAULT NULL,
  `social_twitter` varchar(255) DEFAULT NULL,
  `social_linkedin` varchar(255) DEFAULT NULL,
  `plan` enum('trial','active','suspended','cancelled','expired') DEFAULT 'trial',
  `trial_ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `flutterwave_public_key` varchar(255) DEFAULT NULL,
  `flutterwave_secret_key` varchar(255) DEFAULT NULL,
  `flutterwave_webhook_hash` varchar(255) DEFAULT NULL,
  `notification_email` varchar(255) DEFAULT NULL,
  `telegram_bot_token` varchar(255) DEFAULT NULL,
  `telegram_chat_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

INSERT INTO `tenants` VALUES('1','de-Arina\'sSpoT','de-arinas-pot ',NULL,'Explore premium regional and continental creations — crafted with refined technique and exceptional ingredients.','Est. 2008 — Lagos, Nigeria','60+','4','16','Redefining premium dining through innovation, sustainability, and exceptional hospitality since 2008.','#d4a853','#','#','#','#','active',NULL,'2026-06-23 17:26:56','FLWPUBK_TEST-3b1b4a951a2556f7c3c25d24d2a087de-X','FLWSECK_TEST-a763fa2c6e51571bd7acb803c6e0ec53-X','THEhybrid3002#','wsamson630@gmail.com','8929516825:AAGw1XNyT3U4H_RNHI21depIh4wrrYAQk00','1843218039');
INSERT INTO `tenants` VALUES('2','CC Jitters','ccjitters',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'#d4a853',NULL,NULL,NULL,NULL,'active',NULL,'2026-06-24 05:51:19',NULL,NULL,NULL,NULL,NULL,NULL);

-- Table: user_sessions
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','active') DEFAULT 'pending',
  `verification_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_email` (`tenant_id`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


SET FOREIGN_KEY_CHECKS=1;

-- Add five sample properties for seller/user ID 16.
-- Run once in phpMyAdmin with house_and_lot_db selected.
-- These listings are marked approved so they appear in buyer browsing.
-- Change user_id values below only if these should belong to another seller.

START TRANSACTION;

INSERT INTO properties
(user_id,title,description,category,location,region,province,city,barangay,lot_number,lot_area,floor_area,price,status,image_path,is_featured,is_approved,review_status,submission_type)
VALUES
(16,'Modern Minimalist House in Dasmarinas','A bright and practical family home with an open-plan living and dining area, comfortable bedrooms, and convenient access to schools, shopping, and transport.','House and Lot','Salawag, Dasmarinas City, Cavite','Region IV-A (CALABARZON)','Cavite','Dasmarinas City','Salawag','Block 4 Lot 8',120.00,98.00,4200000.00,'Available','assets/image/hero-house.jpg',0,1,'approved','listing');
SET @property_id = LAST_INSERT_ID();
INSERT INTO property_tags(property_id,tag_name) VALUES
(@property_id,'Furnished'),(@property_id,'Gated Community'),(@property_id,'Near School'),(@property_id,'With Garage'),(@property_id,'Ready for Occupancy');

INSERT INTO properties
(user_id,title,description,category,location,region,province,city,barangay,lot_number,lot_area,floor_area,price,status,image_path,is_featured,is_approved,review_status,submission_type)
VALUES
(16,'Cozy Townhouse near Imus City Center','A well-maintained townhouse with an efficient floor plan, natural light, and easy access to markets, public transportation, and everyday essentials.','Townhouse','Bayan Luma, Imus City, Cavite','Region IV-A (CALABARZON)','Cavite','Imus City','Bayan Luma','Block 7 Lot 16',85.00,72.00,2950000.00,'Available','assets/image/property-alabang.jpg',0,1,'approved','listing');
SET @property_id = LAST_INSERT_ID();
INSERT INTO property_tags(property_id,tag_name) VALUES
(@property_id,'Unfurnished'),(@property_id,'Near Mall'),(@property_id,'Near Public Transport'),(@property_id,'Pet-Friendly'),(@property_id,'Ready for Occupancy');

INSERT INTO properties
(user_id,title,description,category,location,region,province,city,barangay,lot_number,lot_area,floor_area,price,status,image_path,is_featured,is_approved,review_status,submission_type)
VALUES
(16,'Spacious Family Home in General Trias','A spacious home in a residential neighborhood with room for family activities, a private garden area, and convenient road connections to nearby commercial districts.','House and Lot','San Francisco, General Trias, Cavite','Region IV-A (CALABARZON)','Cavite','General Trias','San Francisco','Block 2 Lot 21',160.00,125.00,5650000.00,'Available','assets/image/why-house.jpg',0,1,'approved','listing');
SET @property_id = LAST_INSERT_ID();
INSERT INTO property_tags(property_id,tag_name) VALUES
(@property_id,'Gated Community'),(@property_id,'With Garden'),(@property_id,'With Garage'),(@property_id,'Corner Lot'),(@property_id,'Flood-Free');

INSERT INTO properties
(user_id,title,description,category,location,region,province,city,barangay,lot_number,lot_area,floor_area,price,status,image_path,is_featured,is_approved,review_status,submission_type)
VALUES
(16,'Contemporary Starter Home in Bacoor','A contemporary starter home with a functional kitchen, comfortable living spaces, and convenient access to schools, hospitals, malls, and public transport routes.','House and Lot','Molino III, Bacoor City, Cavite','Region IV-A (CALABARZON)','Cavite','Bacoor City','Molino III','Block 11 Lot 3',95.00,82.00,3650000.00,'Available','assets/image/hero-house.jpg',0,1,'approved','listing');
SET @property_id = LAST_INSERT_ID();
INSERT INTO property_tags(property_id,tag_name) VALUES
(@property_id,'Newly Renovated'),(@property_id,'Near Hospital'),(@property_id,'Near Mall'),(@property_id,'Near Public Transport'),(@property_id,'With Garage');

INSERT INTO properties
(user_id,title,description,category,location,region,province,city,barangay,lot_number,lot_area,floor_area,price,status,image_path,is_featured,is_approved,review_status,submission_type)
VALUES
(16,'Quiet Garden Home in Silang','A peaceful residential property with a garden-friendly lot, airy interiors, and a relaxed neighborhood setting while remaining within reach of town conveniences.','House and Lot','Puting Kahoy, Silang, Cavite','Region IV-A (CALABARZON)','Cavite','Silang','Puting Kahoy','Block 6 Lot 14',180.00,110.00,4850000.00,'Available','assets/image/property-alabang.jpg',0,1,'approved','listing');
SET @property_id = LAST_INSERT_ID();
INSERT INTO property_tags(property_id,tag_name) VALUES
(@property_id,'Unfurnished'),(@property_id,'With Garden'),(@property_id,'Pet-Friendly'),(@property_id,'Flood-Free'),(@property_id,'Near School');

COMMIT;

-- Verify the five new listings and their tags:
SELECT p.id,p.user_id,p.title,p.location,p.price,p.status,p.review_status,
       GROUP_CONCAT(pt.tag_name ORDER BY pt.tag_name SEPARATOR ', ') AS tags
FROM properties p
LEFT JOIN property_tags pt ON pt.property_id=p.id
WHERE p.user_id=16
  AND p.title IN (
    'Modern Minimalist House in Dasmarinas',
    'Cozy Townhouse near Imus City Center',
    'Spacious Family Home in General Trias',
    'Contemporary Starter Home in Bacoor',
    'Quiet Garden Home in Silang'
  )
GROUP BY p.id,p.user_id,p.title,p.location,p.price,p.status,p.review_status
ORDER BY p.id;

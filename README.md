colocation
==========

MySQL scheme
------------

users
  - user_id
  - user_name
  
categories
  - category_id
  - category_name
  
bills
  - bill_id
  - user_id
  - purcharse_date
  - shop_name
  - amount
  
bills_categories
  - bill_id
  - category_id
  - amount
  
users_categories_exclusions
  - user_id
  - category_id

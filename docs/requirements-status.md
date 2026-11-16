# Requirements status

Every requirement ID in the SRS, with its status during **milestone 4** (in progress).
Status is **done**, **not done** (with the milestone that delivers it) or **blocked** (with what it waits on).
Tests name the requirement ID in the test name; run `php artisan test` and `npm test`.
SRS version 1.1 (24 Oct 2026) took the wishlist out, so REQ-CHK-02 and REQ-DMO-35 are no longer listed.
SRS version 1.2 (25 Oct 2026) took out sorting and the staff account, so REQ-STR-06, REQ-DMO-09 to 11, REQ-ORD-03, 05, 07, 09, 11, 17, 19, 21, 23 and 25, and REQ-USR-02, 04 to 08 and 10 are no longer listed; it added REQ-STR-23 to 25, REQ-CHK-21 to 25, REQ-CMS-43 to 46, REQ-DB-37, REQ-SEC-43 and REQ-DMO-89 to 103.

**Totals:** 403 IDs. done 376, not done 24, blocked 3.


## REQ-STR

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-STR-01 | The storefront shall match the demo's design: layout, colors, fonts, icons, animations. | done | Demo layout, colours, fonts, icons and rack animation carried over; see review notes for screenshots |
| REQ-STR-02 | Every storefront page shall display without horizontal scrolling at screen widths from 360 px to 1920 px. | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px: no page scrolls sideways at any width (the checkout at 360 to 414 px was fixed) |
| REQ-STR-03 | The shop shall show only published products. | done | Test: req_str_03_the_shop_shows_only_published_products |
| REQ-STR-04 | The shop shall let customers filter products by category. | done | Test: req_str_04_category_pages_show_only_their_category |
| REQ-STR-05 | The shop shall let customers filter products by color. | done | Test: req_str_05_and_req_dmo_15_selecting_colours_shows_any_selected_colour |
| REQ-STR-07 | The shop shall show at most 6 products per page, as in the demo. | done | Test: req_str_07_and_req_dmo_07_a_page_shows_at_most_6_products |
| REQ-STR-08 | The system shall let customers search products by name, color or description. | done | Test: req_str_08_and_req_dmo_12_search_matches_name_ignoring_case |
| REQ-STR-09 | The product page shall show the photo gallery of the selected color. | done | Test: req_str_09_10_11_12_the_page_shows_gallery_price_colours_and_stock, req_str_09_the_gallery_shows_the_photos_of_the_selected_colour |
| REQ-STR-10 | The product page shall show the product's price. | done | Test: req_str_09_10_11_12_the_page_shows_gallery_price_colours_and_stock |
| REQ-STR-11 | The product page shall show the colors the product comes in. | done | Test: req_str_09_10_11_12_the_page_shows_gallery_price_colours_and_stock |
| REQ-STR-12 | The product page shall show whether the selected color is in stock. | done | Test: req_str_09_10_11_12_the_page_shows_gallery_price_colours_and_stock, req_str_12_req_dmo_100_an_out_of_stock_colour_can_be_chosen_but_not_added |
| REQ-STR-13 | The product page shall show up to 6 other products from the same category, ranked as in REQ-DMO-25. | done | Shows up to 6, per your decision that REQ-DMO-26 wins (RelatedProducts::LIMIT) |
| REQ-STR-14 | For products with sizes, the system shall require a size choice before the item is added to the bag. | done | Test: req_str_14_and_15_sized_products_require_a_size_and_show_the_size_guide |
| REQ-STR-15 | For products with sizes, the product page shall show the size guide. | done | Test: req_str_14_and_15_sized_products_require_a_size_and_show_the_size_guide |
| REQ-STR-16 | Prices shall show in JOD by default. | done | Prices render in JOD on the server (Money::jod) |
| REQ-STR-17 | Customers shall be able to show prices in USD, converted at the rate set by the owner. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic: 17 JD shows $23.97 at the seeded rate 1.41; rate comes from settings |
| REQ-STR-18 | The storefront shall be available in English. | done | Test: req_str_18_and_d_6_every_page_opens_in_english_with_its_tab_title |
| REQ-STR-19 | The storefront shall be available in Arabic. | done | Test: req_str_19_21_and_req_ux_51_every_page_opens_in_arabic_right_to_left, req_str_19_the_arabic_page_names_the_colour_after_the_product |
| REQ-STR-20 | Every storefront page shall have a language switch. | done | Test: req_str_20_and_req_dmo_84_the_language_button_switches_the_language_on_the_same_address, req_str_20_the_saved_language_stays_until_the_customer_switches_again, req_str_18_old_arabic_addresses_open_the_same_page_in_arabic |
| REQ-STR-21 | Arabic pages shall use a right-to-left layout. | done | Test: req_str_19_21_and_req_ux_51_every_page_opens_in_arabic_right_to_left |
| REQ-STR-22 | Arabic pages shall use the Arabic font the client approves in M1. | blocked | Waits on the client approving the Arabic font; IBM Plex Sans Arabic is the placeholder |
| REQ-STR-23 | The shop shall let customers filter products by size. | done | Test: req_str_23_and_req_dmo_93_selecting_sizes_shows_products_in_any_selected_size |
| REQ-STR-24 | The shop shall show each product once, as one card with a dot for each of its colors. | done | Test: req_str_24_and_req_dmo_96_each_product_is_one_card_with_a_dot_for_each_colour, req_str_24_arabic_cards_say_how_many_colours_in_arabic |
| REQ-STR-25 | The product page shall let customers choose one of the product's colors without reloading the page. | done | Test: req_str_25_the_product_page_offers_each_colour_in_the_owners_order |

## REQ-RCK

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-RCK-01 | The owner shall be able to add a rack item by uploading an image. | done | Test: req_rck_01_req_rck_02_add_a_garment_by_uploading_an_image_and_link_it |
| REQ-RCK-02 | The owner shall be able to link each rack item to a product. | done | Test: req_rck_01_req_rck_02_add_a_garment_by_uploading_an_image_and_link_it, req_rck_02_a_color_of_another_product_is_refused, req_rck_02_the_color_choice_shows_only_for_a_product_with_several_colors |
| REQ-RCK-03 | Tapping a rack item on the home page shall open its linked product. | done | Test: req_rck_03_tapping_a_garment_on_the_home_page_opens_its_product |
| REQ-RCK-04 | The owner shall be able to replace a rack item's image. | done | Test: req_rck_04_replace_a_garments_image |
| REQ-RCK-05 | The owner shall be able to rename a rack item. | done | Test: req_rck_05_req_rck_06_rename_a_garment_and_change_its_product |
| REQ-RCK-06 | The owner shall be able to change the product a rack item links to. | done | Test: req_rck_05_req_rck_06_rename_a_garment_and_change_its_product |
| REQ-RCK-07 | The owner shall be able to reorder rack items by drag and drop on a computer. | done | Test: req_rck_07_reorder_by_drag_and_drop |
| REQ-RCK-08 | The owner shall be able to reorder rack items with move-up/move-down buttons on a phone. | done | Test: req_rck_08_reorder_with_move_up_and_move_down_buttons |
| REQ-RCK-09 | The owner shall be able to hide a rack item without deleting it. | done | Test: req_rck_09_hide_a_garment_without_deleting_it |
| REQ-RCK-10 | The owner shall be able to delete a rack item. | done | Test: req_rck_10_req_cms_41_delete_a_garment_after_confirming |
| REQ-RCK-11 | The admin panel shall show a live preview of the rack that moves like the home page rack. | done | Test: req_rck_11_the_live_preview_shows_the_rack_and_follows_changes |
| REQ-RCK-12 | The system shall warn the owner when an uploaded rack image has no transparent background. | done | Test: req_rck_12_a_see_through_background_is_recognised, req_rck_12_the_owner_is_warned_when_the_image_has_no_transparent_background |
| REQ-RCK-13 | The system shall trim the empty transparent space around each uploaded rack image. | done | Test: req_rck_13_empty_transparent_space_is_trimmed |
| REQ-RCK-14 | The system shall scale each uploaded rack image to the same height as the other rack items. | done | Test: req_rck_14_every_garment_is_scaled_to_the_rack_height |
| REQ-RCK-15 | The system shall position each rack image so its hanger hook sits at the same point on the rail as the others. | done | Test: req_rck_15_the_hanger_hook_sits_at_the_centre_like_the_other_garments |
| REQ-RCK-16 | The owner shall be able to adjust a rack item's size by hand after the automatic fit. | done | Test: req_rck_16_adjust_a_garments_size_by_hand |

## REQ-CHK

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-CHK-01 | The system shall keep the bag in the customer's browser between visits. | done | Test: req_chk_01_the_bag_check_returns_current_product_data_only. resources/js/store/core.js keeps the bag in localStorage |
| REQ-CHK-03 | Adding an item to the bag shall keep the customer on the same page without opening the bag. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic: the drawer stays closed after Add |
| REQ-CHK-04 | Adding an item to the bag shall update the bag count. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-CHK-05 | Adding an item to the bag shall show a short "Added to bag" confirmation. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-CHK-06 | The bag shall open when the customer taps the bag icon or the confirmation. | done | Bag icon opens the drawer; tapping the "added to bag" toast opens it too |
| REQ-CHK-07 | Checkout shall require these fields: name, phone number with country code, city, area, street address. | done | Test: req_chk_07_req_chk_09_req_db_12_required_fields_are_checked_before_anything_is_saved |
| REQ-CHK-08 | Checkout shall accept an email address as an optional field. | done | Test: req_chk_08_email_is_optional. Email field marked (optional) |
| REQ-CHK-09 | Checkout shall block the order until every required field is filled in a valid format. | done | Test: req_chk_07_req_chk_09_req_db_12_required_fields_are_checked_before_anything_is_saved, req_chk_09_wrong_formats_are_refused_with_the_demo_messages |
| REQ-CHK-10 | Checkout shall add the chosen city's delivery fee to the order. | done | Test: req_chk_10_req_chk_12_req_chk_13_req_dmo_45_prices_and_fee_come_from_the_database_in_jod |
| REQ-CHK-11 | Checkout shall show the full total before the order is placed. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic: Delivery and Total update when a city is chosen; the server works the fee out again (CheckoutTest) |
| REQ-CHK-12 | On submit, the server shall recalculate every product price in the order. | done | Test: req_chk_10_req_chk_12_req_chk_13_req_dmo_45_prices_and_fee_come_from_the_database_in_jod, req_chk_12_req_chk_13_req_chk_14_req_chk_18_the_order_is_priced_saved_paid_and_announced |
| REQ-CHK-13 | On submit, the server shall recalculate the delivery fee. | done | Test: req_chk_10_req_chk_12_req_chk_13_req_dmo_45_prices_and_fee_come_from_the_database_in_jod, req_chk_12_req_chk_13_req_chk_14_req_chk_18_the_order_is_priced_saved_paid_and_announced, req_chk_13_a_city_outside_amman_gets_its_own_fee |
| REQ-CHK-14 | The system shall save every placed order. | done | Test: req_chk_12_req_chk_13_req_chk_14_req_chk_18_the_order_is_priced_saved_paid_and_announced, req_chk_14_req_db_06_the_order_and_all_its_items_are_saved |
| REQ-CHK-15 | After an order is placed, the system shall show its unique order number. | done | Test: req_chk_15_req_dmo_73_req_db_10_each_order_gets_a_unique_6_character_number_from_the_server, req_chk_15_req_dmo_75_req_sec_36_the_reply_is_only_the_confirmation_address |
| REQ-CHK-16 | Out-of-stock product colors shall not be orderable. | done | Test: req_chk_16_out_of_stock_products_are_not_orderable, req_chk_16_pieces_that_can_no_longer_be_ordered_are_refused_by_name_and_id, req_chk_16_req_cms_08_only_the_color_that_is_out_of_stock_cannot_be_bought, req_chk_16_req_cms_08_only_the_color_that_is_out_of_stock_is_refused, req_ux_27_req_chk_16_a_sol |
| REQ-CHK-17 | While the store is paused, checkout shall not accept orders. | done | Test: req_chk_17_a_paused_store_refuses_before_anything_is_saved, req_chk_17_a_paused_store_still_lets_shoppers_fill_their_bag_and_only_checkout_closes, req_chk_17_req_ux_27_a_paused_store_disables_place_order_and_says_why_below_it, req_chk_17_while_the_store_is_paused_checkout_takes_no_orders |
| REQ-CHK-18 | The system shall email the owner every new order. | done | Test: req_chk_12_req_chk_13_req_chk_14_req_chk_18_the_order_is_priced_saved_paid_and_announced, req_chk_18_req_chk_19_req_db_13_the_owner_and_the_customer_are_emailed_through_the_queue, req_chk_18_the_owner_gets_every_detail_and_a_link_to_the_order, req_chk_18_without_an_order_email_setting_the_owners_acc |
| REQ-CHK-19 | The system shall email the customer an order confirmation when the customer gave an email address. | done | Test: req_chk_18_req_chk_19_req_db_13_the_owner_and_the_customer_are_emailed_through_the_queue, req_chk_19_no_customer_email_without_an_address |
| REQ-CHK-20 | Customer emails shall be in the language the customer used on the site. | done | Test: req_chk_20_an_arabic_customer_gets_the_email_in_arabic, req_db_07_req_chk_20_items_keep_a_copy_of_each_piece_named_with_its_color_in_the_customers_language |
| REQ-CHK-21 | The phone number field shall accept digits only. | done | Test: req_chk_21_req_dmo_90_only_digits_count_and_arabic_indic_digits_are_0_to_9, tests/js/phone.test.mjs |
| REQ-CHK-22 | Checkout shall accept a phone number only when it has the number of digits used in the chosen country (Appendix D.8.3). | done | Test: req_chk_22_req_dmo_56_a_number_needs_exactly_the_countrys_digits_not_counting_a_0_at_the_start, req_chk_22_req_dmo_56_the_phone_needs_exactly_the_digits_of_the_chosen_country, req_chk_22_req_dmo_70_each_phone_country_carries_its_digits_and_its_message, tests/js/phone.test.mjs |
| REQ-CHK-23 | The order confirmation page shall offer a "Download receipt" button. | done | Test: req_chk_23_the_confirmation_page_offers_download_receipt_above_continue_shopping |
| REQ-CHK-24 | The receipt shall be a PDF showing the order number, date, delivery details, each item with its size, quantity and price, the subtotal, delivery fee, total and payment method. | done | Test: req_chk_24_the_receipt_shows_the_order_its_items_and_the_amounts |
| REQ-CHK-25 | The receipt shall be in the language the customer ordered in, laid out right to left for Arabic. | done | Test: req_chk_25_the_receipt_is_in_the_orders_language_right_to_left_for_arabic |

## REQ-ORD

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-ORD-01 | The admin panel shall list all orders, newest first. | done | Test: req_ord_01_all_orders_are_listed_newest_first, req_ord_01_an_arabic_order_reads_in_the_right_order_in_the_list |
| REQ-ORD-02 | The owner shall be able to search orders by order number, customer name or phone. | done | Test: req_ord_02_search_by_order_number_name_or_phone |
| REQ-ORD-04 | The owner shall be able to filter orders by status. | done | Test: req_ord_04_filter_by_status_with_a_count_on_each_chip |
| REQ-ORD-06 | The owner shall be able to filter orders by date. | done | Test: req_ord_06_filter_by_date |
| REQ-ORD-08 | The owner shall be able to filter orders by city. | done | Test: req_ord_08_filter_by_city |
| REQ-ORD-10 | The owner shall be able to open an order's detail view. | done | Test: req_ord_10_req_ord_12_to_15_the_order_view_shows_customer_items_fee_and_total |
| REQ-ORD-12 | The order detail view shall show the customer's details: name, phone, address, note. | done | Test: req_ord_10_req_ord_12_to_15_the_order_view_shows_customer_items_fee_and_total, req_ord_12_a_note_longer_than_500_characters_is_refused |
| REQ-ORD-13 | The order detail view shall show each item with its color, size, quantity, price. | done | Test: req_ord_10_req_ord_12_to_15_the_order_view_shows_customer_items_fee_and_total |
| REQ-ORD-14 | The order detail view shall show the delivery fee. | done | Test: req_ord_10_req_ord_12_to_15_the_order_view_shows_customer_items_fee_and_total |
| REQ-ORD-15 | The order detail view shall show the order total. | done | Test: req_ord_10_req_ord_12_to_15_the_order_view_shows_customer_items_fee_and_total |
| REQ-ORD-16 | The owner shall be able to set an order's status to one of: New, Confirmed, Out for delivery, Delivered, Cancelled. | done | Test: req_ord_16_req_ord_18_set_any_of_the_five_statuses_from_the_order_view |
| REQ-ORD-18 | The owner shall be able to change an order's status from the order detail view. | done | Test: req_ord_16_req_ord_18_set_any_of_the_five_statuses_from_the_order_view |
| REQ-ORD-20 | The owner shall be able to add notes to an order. | done | Test: req_ord_20_add_notes_to_an_order |
| REQ-ORD-22 | The owner shall be able to print a packing slip for an order. | done | Test: req_ord_22_print_a_packing_slip |
| REQ-ORD-24 | The owner shall be able to export orders to a spreadsheet (CSV). | done | Test: req_ord_24_req_db_29_export_orders_as_a_csv_file, req_ord_24_the_export_follows_the_chosen_status_chip |
| REQ-ORD-26 | The dashboard shall show the number of new orders waiting for confirmation. | done | Test: req_ord_26_new_orders_waiting_for_confirmation |
| REQ-ORD-27 | The dashboard shall show sales for the last 7 days. | done | Test: req_ord_27_sales_for_the_last_7_days |
| REQ-ORD-28 | The dashboard shall show sales for the current month. | done | Test: req_ord_28_sales_for_the_current_month |
| REQ-ORD-29 | The dashboard shall show the number of orders in each status. | done | Test: req_ord_29_the_number_of_orders_in_each_status |
| REQ-ORD-30 | The dashboard shall show the 5 most recent orders. | done | Test: req_ord_30_the_5_most_recent_orders |
| REQ-ORD-31 | The dashboard shall show the number of products on the site. | done | Test: req_ord_31_req_ord_32_products_on_the_site_and_out_of_stock |
| REQ-ORD-32 | The dashboard shall show the number of out-of-stock product colors. | done | Test: req_ord_31_req_ord_32_products_on_the_site_and_out_of_stock |
| REQ-ORD-33 | The dashboard shall show the number of items on the hero rack. | done | Test: req_ord_33_items_on_the_hero_rack |

## REQ-CMS

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-CMS-01 | The owner shall be able to search products by name or color. | done | Test: req_cms_01_search_products_by_color, req_cms_01_search_products_by_name |
| REQ-CMS-02 | The owner shall be able to filter products by category. | done | Test: req_cms_02_filter_products_by_category |
| REQ-CMS-03 | The owner shall be able to create a product. | done | Test: req_cms_03_req_cms_10_req_cms_11_req_cms_43_create_a_product_with_two_colors_each_with_photos_and_stock |
| REQ-CMS-04 | The owner shall be able to edit these product details: name, category, price, description, sizes, colors. | done | Test: req_cms_04_edit_name_category_price_description_and_sizes, req_cms_04_req_cms_44_add_a_color_and_remove_one_in_the_same_form |
| REQ-CMS-05 | The owner shall be able to duplicate a product. | done | Test: req_cms_05_duplicate_a_product_with_its_colors_and_photos |
| REQ-CMS-06 | The owner shall be able to hide a product from the website without deleting it. | done | Test: req_cms_06_hide_a_product_without_deleting_it |
| REQ-CMS-07 | The owner shall be able to delete a product. | done | Test: req_cms_07_req_cms_41_delete_a_product_after_confirming |
| REQ-CMS-08 | The owner shall be able to mark each color of a product as in stock or out of stock. | done | Test: req_chk_16_req_cms_08_only_the_color_that_is_out_of_stock_cannot_be_bought, req_chk_16_req_cms_08_only_the_color_that_is_out_of_stock_is_refused, req_cms_08_mark_one_color_out_of_stock |
| REQ-CMS-09 | The owner shall be able to feature a product on the home page. | done | Test: req_cms_09_feature_a_product_on_the_home_page_as_one_of_the_three_looks |
| REQ-CMS-10 | The owner shall be able to upload several photos for each product color. | done | Test: req_cms_03_req_cms_10_req_cms_11_req_cms_43_create_a_product_with_two_colors_each_with_photos_and_stock |
| REQ-CMS-11 | The owner shall be able to choose the main photo of each product color. | done | Test: req_cms_03_req_cms_10_req_cms_11_req_cms_43_create_a_product_with_two_colors_each_with_photos_and_stock, req_cms_11_req_cms_12_one_main_and_one_separate_hover_photo_for_each_color |
| REQ-CMS-12 | The owner shall be able to choose the hover photo of each product color. | done | Test: req_cms_11_req_cms_12_one_main_and_one_separate_hover_photo_for_each_color |
| REQ-CMS-13 | The owner shall be able to reorder the photos of each product color. | done | Test: req_cms_13_reorder_a_colors_photos |
| REQ-CMS-14 | The owner shall be able to manage categories. | done | Test: req_cms_14_manage_categories |
| REQ-CMS-15 | The owner shall be able to manage colors. | done | Test: req_cms_15_manage_colors |
| REQ-CMS-16 | The owner shall be able to manage collections. | done | Test: req_cms_16_manage_collections |
| REQ-CMS-17 | The owner shall be able to manage sizes. | done | Test: req_cms_17_manage_sizes |
| REQ-CMS-18 | The owner shall be able to edit the size guides. | done | Test: req_cms_18_edit_the_size_guides |
| REQ-CMS-19 | The owner shall be able to edit the home page hero texts: headline, body text, button label. | done | Test: req_cms_19_edit_the_home_hero_headline_text_and_button |
| REQ-CMS-20 | The owner shall be able to edit the about section. | done | Test: req_cms_20_edit_the_about_section |
| REQ-CMS-21 | The owner shall be able to edit the how-to-order steps. | done | Test: req_cms_21_edit_the_how_to_order_steps |
| REQ-CMS-22 | The owner shall be able to manage client reviews. | done | Test: req_cms_22_manage_client_reviews |
| REQ-CMS-23 | The owner shall be able to manage FAQs. | done | Test: req_cms_23_manage_faqs |
| REQ-CMS-24 | The owner shall be able to edit the policy pages. | done | Test: req_cms_24_edit_the_policy_pages |
| REQ-CMS-25 | The owner shall be able to manage delivery cities. | done | Test: req_cms_25_req_cms_26_req_cms_27_add_a_city_with_areas_and_a_fee |
| REQ-CMS-26 | The owner shall be able to manage the areas within each delivery city. | done | Test: req_cms_25_req_cms_26_req_cms_27_add_a_city_with_areas_and_a_fee |
| REQ-CMS-27 | The owner shall be able to set the delivery fee for each city. | done | Test: req_cms_25_req_cms_26_req_cms_27_add_a_city_with_areas_and_a_fee, req_cms_27_change_a_citys_delivery_fee |
| REQ-CMS-28 | The owner shall be able to switch delivery to each city on or off. | done | Test: req_cms_28_switch_delivery_to_a_city_off_and_on |
| REQ-CMS-29 | The owner shall be able to change the logo. | done | Test: req_cms_29_change_the_logo |
| REQ-CMS-30 | The owner shall be able to edit the contact phone number. | done | Test: req_cms_30_req_cms_31_req_cms_32_edit_phone_whatsapp_and_instagram |
| REQ-CMS-31 | The owner shall be able to edit the WhatsApp number. | done | Test: req_cms_30_req_cms_31_req_cms_32_edit_phone_whatsapp_and_instagram |
| REQ-CMS-32 | The owner shall be able to edit the Instagram link. | done | Test: req_cms_30_req_cms_31_req_cms_32_edit_phone_whatsapp_and_instagram |
| REQ-CMS-33 | The owner shall be able to set the USD exchange rate. | done | Test: req_cms_33_set_the_usd_exchange_rate |
| REQ-CMS-34 | The owner shall be able to edit the text each page shows in search engines. | done | Test: req_cms_34_edit_the_text_each_page_shows_in_search_engines |
| REQ-CMS-35 | The owner shall be able to set the email address that receives new orders. | done | Test: req_cms_35_set_the_email_that_receives_new_orders |
| REQ-CMS-36 | The owner shall be able to pause the store with one switch. | done | Test: req_cms_36_req_cms_37_pause_and_reopen_the_store_with_one_switch |
| REQ-CMS-37 | The owner shall be able to reopen a paused store with the same switch. | done | Test: req_cms_36_req_cms_37_pause_and_reopen_the_store_with_one_switch |
| REQ-CMS-38 | The owner shall be able to enter every customer-facing text in English. | done | Test: req_cms_38_req_cms_39_req_cms_40_every_text_in_english_and_arabic_side_by_side |
| REQ-CMS-39 | The owner shall be able to enter every customer-facing text in Arabic. | done | Test: req_cms_38_req_cms_39_req_cms_40_every_text_in_english_and_arabic_side_by_side |
| REQ-CMS-40 | The admin panel shall show each text's Arabic version next to its English version. | done | Test: req_cms_38_req_cms_39_req_cms_40_every_text_in_english_and_arabic_side_by_side |
| REQ-CMS-41 | The system shall ask for confirmation before anything is deleted. | done | Test: req_cms_07_req_cms_41_delete_a_product_after_confirming, req_cms_41_a_category_with_products_is_not_deleted, req_cms_41_removing_a_color_asks_first, req_rck_10_req_cms_41_delete_a_garment_after_confirming |
| REQ-CMS-42 | Past orders shall keep the details of products that are later deleted. | done | Test: req_cms_42_past_orders_keep_the_details_of_deleted_products |
| REQ-CMS-43 | The owner shall be able to add a product with one or more colors in one form, each color with its own photos and stock state. | done | Test: req_cms_03_req_cms_10_req_cms_11_req_cms_43_create_a_product_with_two_colors_each_with_photos_and_stock, req_cms_43_a_product_needs_at_least_one_color |
| REQ-CMS-44 | The owner shall be able to add a color to a product, or remove one, from the same form. | done | Test: req_cms_04_req_cms_44_add_a_color_and_remove_one_in_the_same_form, req_cst_09_req_cms_44_a_removed_color_takes_its_photo_files_with_it |
| REQ-CMS-45 | The owner shall be able to reorder a product's colors; the first color is the one shown first on the site. | done | Test: req_cms_45_reorder_colors_and_the_first_one_shows_first |
| REQ-CMS-46 | The owner shall be able to set the swatch shown for each color. | done | Test: req_cms_46_set_the_swatch_of_a_color |

## REQ-USR

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-USR-01 | The owner shall log in with an email address plus a password. | done | Test: req_usr_01_an_account_that_is_not_the_owner_cannot_sign_in, req_usr_01_req_usr_03_the_owner_logs_in_with_password_then_a_one_time_code |
| REQ-USR-03 | The owner's account shall use two-step verification. | done | Test: req_usr_01_req_usr_03_the_owner_logs_in_with_password_then_a_one_time_code, req_usr_03_the_owner_must_set_up_two_step_verification_before_using_the_admin |
| REQ-USR-09 | The owner shall be able to reset a forgotten password by email. | done | Test: req_usr_09_the_owner_can_reset_a_forgotten_password_by_email |
| REQ-USR-11 | The system shall record who made each admin change. | done | Test: req_usr_11_req_usr_12_each_admin_change_records_who_and_when, req_usr_11_the_owner_reads_the_activity_log |
| REQ-USR-12 | The system shall record when each admin change was made. | done | Test: req_usr_11_req_usr_12_each_admin_change_records_who_and_when |

## REQ-DB

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-DB-01 | All site data shall be stored in one MySQL 9.7 LTS database. | done | Test: req_db_01_the_site_uses_one_mysql_database_on_the_current_lts |
| REQ-DB-02 | The database shall accept connections only from the application on the same server. | not done | Set in deploy/mysql/jude.cnf; checked on the live server (HOSTING-CHECKLIST section 5). Waits on the live server, which needs approval (paid service) |
| REQ-DB-03 | The application shall connect to the database with an account that has only the permissions it needs. | not done | Accounts in deploy/mysql/grants.sql; checked on the live server (HOSTING-CHECKLIST section 5). Waits on the live server, which needs approval (paid service) |
| REQ-DB-04 | The database structure shall be created by version-controlled migration files, so it can be rebuilt exactly. | done | database/migrations |
| REQ-DB-05 | The database shall enforce the links between records (for example, an order item always belongs to an order). | done | Test: req_db_05_links_between_records_are_enforced |
| REQ-DB-06 | An order shall be saved together with all its items in one transaction: either everything is saved or nothing is. | done | Test: req_chk_14_req_db_06_the_order_and_all_its_items_are_saved |
| REQ-DB-07 | Each order item shall store a copy of the product's name, color, size, unit price at the time of ordering. | done | Test: req_db_07_each_item_keeps_a_copy_of_name_color_size_and_price, req_db_07_req_chk_20_items_keep_a_copy_of_each_piece_named_with_its_color_in_the_customers_language. order_items holds name, colour, size, unit price copies (migration 000400) |
| REQ-DB-08 | Prices shall be stored as exact decimal numbers, never as floating-point numbers. | done | Test: req_db_08_prices_are_exact_decimals |
| REQ-DB-09 | Every customer-facing text field shall have an English value plus an Arabic value. | done | Test: req_db_09_customer_facing_texts_have_english_and_arabic_columns |
| REQ-DB-10 | Each order shall get a unique order number. | done | Test: req_chk_15_req_dmo_73_req_db_10_each_order_gets_a_unique_6_character_number_from_the_server, req_db_10_a_number_that_is_already_taken_is_never_given_twice, req_db_10_and_32_order_numbers_and_link_tokens_are_unique |
| REQ-DB-11 | All dates shall be shown in Jordan time (Asia/Amman). | done | Test: req_db_11_dates_use_jordan_time |
| REQ-DB-12 | The backend shall reject any request whose data fails validation, before anything is saved. | done | Test: req_chk_07_req_chk_09_req_db_12_required_fields_are_checked_before_anything_is_saved |
| REQ-DB-13 | Order emails shall be sent by a background queue, so a slow email service never delays checkout. | done | Test: req_chk_18_req_chk_19_req_db_13_the_owner_and_the_customer_are_emailed_through_the_queue |
| REQ-DB-14 | A failed email shall be retried automatically at least 3 times. | done | Test: req_db_14_a_failed_email_is_tried_again_at_least_3_times |
| REQ-DB-15 | The backend shall generate resized WebP copies of every uploaded image. | done | Test: req_db_15_an_image_without_copies_still_works, req_db_15_bag_lines_use_the_smallest_copy, req_db_15_product_cards_list_their_resized_copies, req_db_15_rack_images_get_a_smaller_webp_copy, req_db_15_resized_webp_copies_are_made, req_db_15_seeded_demo_images_get_the_same_copies_as_uploads, req_db_15_t |
| REQ-DB-16 | Scheduled jobs (backups, cleanup) shall run automatically without anyone logged in. | done | Test: req_db_16_the_backups_and_checks_run_on_their_own |
| REQ-DB-17 | Every list query shall return one page of rows at a time (LIMIT), never a whole table. | done | ShopQuery::forPage, RelatedProducts::limit, HeroRack::limit |
| REQ-DB-18 | Each query shall select only the columns the page needs. | done | ShopQuery::CARD_COLUMNS and explicit selects in every query |
| REQ-DB-19 | Related records shall be loaded in one batch query per relation (eager loading), never one query per item. | done | Test: req_db_27_req_db_19_the_list_loads_25_rows_with_a_fixed_number_of_queries. Eager loading with constrained photos; lazy loading throws in development (REQ-DB-20 test) |
| REQ-DB-20 | The development setup shall turn on Laravel's lazy-loading prevention, so any one-query-per-item mistake fails during testing. | done | Test: req_db_20_lazy_loading_is_prevented_outside_production |
| REQ-DB-21 | Every column used to filter, sort or join shall have an index, as listed in the table above. | done | Test: req_db_21_and_22_filter_sort_and_search_columns_are_indexed |
| REQ-DB-22 | Product search shall use the full-text index, not a scan of every product. | done | Test: req_db_21_and_22_filter_sort_and_search_columns_are_indexed |
| REQ-DB-23 | No storefront page shall run more than 15 database queries. | done | Test: req_db_23_no_storefront_page_runs_more_than_15_queries |
| REQ-DB-24 | No single storefront query shall take more than 100 ms with 1,000 products in the database. | done | docs/m4-review-notes.md, Speed: measured on a copy set up like the live server with 1,000 products and 10,000 orders, again after the version 1.2 changes. Slowest of 2,755 store queries on a warmed copy: 20 ms (one size and colour filter took 80 ms on the first run after loading the data) |
| REQ-DB-25 | Categories, settings, delivery cities, the hero rack, site content shall be served from the server's memory cache. | done | Test: req_db_25_and_26_cached_content_is_cleared_when_it_changes |
| REQ-DB-26 | A cached item shall be cleared automatically as soon as the owner changes it. | done | Test: req_db_25_and_26_cached_content_is_cleared_when_it_changes, req_db_26_editing_a_category_clears_the_catalog_cache |
| REQ-DB-27 | Admin lists shall load 25 rows per page from the database. | done | Test: req_db_27_admin_lists_load_25_rows_per_page, req_db_27_req_db_19_the_list_loads_25_rows_with_a_fixed_number_of_queries |
| REQ-DB-28 | Dashboard figures shall be calculated inside the database with counting queries, never by loading all orders. | done | Test: req_db_28_figures_are_counted_in_the_database_never_by_loading_orders |
| REQ-DB-29 | CSV exports shall read orders in chunks of 500 rows. | done | Test: req_ord_24_req_db_29_export_orders_as_a_csv_file |
| REQ-DB-30 | MySQL shall log every query slower than 1 second. | not done | Set in deploy/mysql/jude.cnf; checked on the live server (HOSTING-CHECKLIST section 5). Waits on the live server, which needs approval (paid service) |
| REQ-DB-31 | Every storefront query shall include only published products, enforced in one place for the whole application. | done | Test: req_db_31_hidden_products_and_unknown_sizes_are_refused, req_db_31_published_products_only_is_enforced_in_one_place, req_sec_36_37_and_req_db_31_unpublished_products_are_left_out |
| REQ-DB-32 | Order confirmation links shall use a random token of at least 32 characters. | done | Test: req_db_10_and_32_order_numbers_and_link_tokens_are_unique, req_sec_41_req_db_32_the_confirmation_page_opens_only_with_its_random_token |
| REQ-DB-33 | The application's database account shall have no permission to delete tables or change the database structure. | not done | Accounts in deploy/mysql/grants.sql; checked on the live server (HOSTING-CHECKLIST section 5). Waits on the live server, which needs approval (paid service) |
| REQ-DB-34 | Database structure changes shall run only during deployment, with a separate account. | not done | deploy/deploy.sh migrates with the separate account; checked at the first deploy (HOSTING-CHECKLIST section 11). Waits on the live server, which needs approval (paid service) |
| REQ-DB-35 | Raw SQL that contains user input shall not be used anywhere in the code (see REQ-SEC-15). | done | M4 code audit (docs/m4-review-notes.md): no raw SQL holds typed-in text; the raw pieces are fixed text and related products binds its values |
| REQ-DB-36 | The cache shall run on the same server, with no paid cache service (see REQ-CST-08). | done | File/array cache on the same server; no paid cache |
| REQ-DB-37 | Each color of a product shall be one row in the product colors table, with its own stock state and photos; a product shall not have the same color twice. | done | Test: req_db_37_a_product_cannot_have_the_same_color_twice, req_db_37_two_saved_colors_trading_places_get_a_message_not_an_error_page |

## REQ-PRF

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-PRF-01 | These pages shall each score 85 or more for mobile performance in Google Lighthouse: home, shop, product. | done | docs/m4-review-notes.md, Speed: measured on a copy set up like the live server with 1,000 products and 10,000 orders, again after the version 1.2 changes. Lighthouse mobile 96 to 100 on home, shop and product (one colour and 4 colours), English and Arabic. Measured again on the live site after launch |
| REQ-PRF-02 | These pages shall each have a Largest Contentful Paint of 2.5 seconds or less in a Google Lighthouse mobile test: home, shop, product. | done | Test: req_dmo_82_and_req_ux_37_photos_below_the_first_screen_load_lazily_and_req_prf_02_the_first_row_at_once. docs/m4-review-notes.md, Speed: measured on a copy set up like the live server with 1,000 products and 10,000 orders, again after the version 1.2 changes. Largest Contentful Paint 1.5 to 2.4 s on home, shop and product (one colour and 4 colours), English and Arabic. Measured again on the live site after launch |
| REQ-PRF-03 | Admin pages shall respond within 2 seconds with 1,000 products in the store. | done | Test: req_prf_03_a_photo_in_the_form_previews_from_its_400_px_copy. docs/m4-review-notes.md, Speed: measured on a copy set up like the live server with 1,000 products and 10,000 orders, again after the version 1.2 changes. The server answers every admin page in 0.36 s or less and every page is ready within 0.84 s; lists, search and settings fully load within 1.5 s. A product form is ready to use within 1.5 s; its photo previews finish after about 2 s (6 to 8 photos) or 3 s (11 photos), see the review notes |
| REQ-PRF-04 | Admin pages shall respond within 2 seconds with 10,000 orders in the store. | done | docs/m4-review-notes.md, Speed: measured on a copy set up like the live server with 1,000 products and 10,000 orders, again after the version 1.2 changes. Orders list, search, an order and its packing slip fully loaded in 1.1 s or less |

## REQ-SAF

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-SAF-01 | The database shall be backed up daily to separate storage. | done | Test: req_saf_01_on_the_live_site_backups_go_to_another_server, req_saf_01_req_saf_02_the_database_and_the_photos_are_backed_up_every_night |
| REQ-SAF-02 | Uploaded images shall be backed up daily to separate storage. | done | Test: req_saf_01_req_saf_02_the_database_and_the_photos_are_backed_up_every_night |
| REQ-SAF-03 | Each backup shall be kept for 14 days. | done | Test: req_saf_03_each_backup_is_kept_for_14_days |
| REQ-SAF-04 | The site shall be restorable from a backup within 4 hours. | done | Test: req_saf_04_a_backup_restores_the_database_and_the_photos, req_saf_04_a_backup_storage_that_cannot_be_reached_is_reported, req_saf_04_a_backup_without_photos_leaves_the_photos_alone, req_saf_04_the_migration_password_can_be_typed_in_instead_of_set, req_saf_04_the_restore_asks_first_and_lists_the_back |

## REQ-SEC

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-SEC-01 | Admin login shall reject more than 5 attempts per minute from the same IP address. | done | Test: req_sec_01_login_allows_at_most_5_attempts_a_minute_from_one_ip |
| REQ-SEC-02 | An admin account shall be locked for 15 minutes after 5 wrong passwords in a row. | done | Test: req_sec_02_five_wrong_passwords_lock_the_account_for_15_minutes |
| REQ-SEC-03 | Passwords shall be stored hashed. | done | Test: req_sec_03_passwords_are_stored_hashed. Laravel hashed password cast |
| REQ-SEC-04 | Secret keys shall be kept out of the source code. | done | .env is not committed; deploy/env.production.example has no secrets |
| REQ-SEC-05 | All forms shall be protected against cross-site request forgery. | done | Test: req_sec_05_the_bag_check_sits_behind_csrf_protection |
| REQ-SEC-06 | Public forms shall reject submissions that fail an anti-spam check. | done | Test: req_sec_06_a_filled_anti_spam_field_is_refused |
| REQ-SEC-07 | Customer details shall be visible only to the logged-in owner. | done | M4 code audit (docs/m4-review-notes.md): orders are behind the admin login; a customer sees only their own order through its 64-character link |
| REQ-SEC-08 | The system shall send customer details to no outside service except the order email service. | done | M4 code audit (docs/m4-review-notes.md): customer details go only to the order email service; no other outside service is called |
| REQ-SEC-09 | Every page shall be served over HTTPS, with HTTP requests redirected to HTTPS. | not done | deploy/nginx/jude.conf redirects to HTTPS; checked on the live server (HOSTING-CHECKLIST section 6). Waits on the live server, which needs approval (paid service) |
| REQ-SEC-10 | All traffic shall pass through Cloudflare, which filters attacks and floods of traffic before they reach the server. | not done | Cloudflare proxy steps in HOSTING-CHECKLIST section 6. Waits on the domain and server, which need approval |
| REQ-SEC-11 | The server's firewall shall accept web traffic only from Cloudflare. | not done | Firewall steps in HOSTING-CHECKLIST section 2. Waits on the live server, which needs approval (paid service) |
| REQ-SEC-12 | The server's firewall shall close every port except web traffic (80, 443) plus SSH. | not done | Firewall steps in HOSTING-CHECKLIST section 2. Waits on the live server, which needs approval (paid service) |
| REQ-SEC-13 | Server login over SSH shall accept keys only, never passwords. | not done | SSH steps in HOSTING-CHECKLIST section 2. Waits on the live server, which needs approval (paid service) |
| REQ-SEC-14 | The server shall install operating-system security updates automatically. | not done | Automatic updates in HOSTING-CHECKLIST section 2. Waits on the live server, which needs approval (paid service) |
| REQ-SEC-15 | Every database query shall use parameter binding, so input can never run as a database command (SQL injection). | done | Query builder with bindings everywhere; search terms reduced to letters and numbers |
| REQ-SEC-16 | All text shown on pages shall be escaped, so input can never run as a script (cross-site scripting). | done | Test: req_sec_16_text_is_escaped |
| REQ-SEC-17 | Every page shall send a Content Security Policy that blocks scripts from unapproved sources. | done | Test: req_sec_17_a_new_nonce_for_every_response, req_sec_17_a_store_page_at_an_admin_like_address_keeps_the_store_policy, req_sec_17_storefront_pages_allow_only_the_stores_own_scripts_and_styles, req_sec_17_the_admin_panel_allows_scripts_only_from_the_store_itself, req_sec_17_the_home_rack_sizes_use_a_sty |
| REQ-SEC-18 | Every page shall send these security headers: HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy. | done | Test: req_sec_18_every_response_sends_the_security_headers, req_sec_18_nginx_adds_the_headers_to_the_files_it_serves_itself, req_sec_18_the_maintenance_page_sends_the_security_headers_too |
| REQ-SEC-19 | Uploads shall accept only JPG, PNG, WEBP images of 5 MB or less. | done | Test: req_sec_19_uploads_accept_only_jpg_png_webp_of_5_mb_or_less |
| REQ-SEC-20 | Every uploaded image shall be stored in a folder where files can never run as code. | done | Test: req_sec_20_images_go_only_into_the_image_folders_which_never_run_code |
| REQ-SEC-21 | Session cookies shall be marked Secure, HttpOnly, SameSite. | done | Test: req_sec_21_with_the_production_settings_the_session_cookie_is_secure_httponly_and_samesite |
| REQ-SEC-22 | An admin session shall end after 2 hours without activity. | done | Test: req_sec_22_an_admin_session_ends_after_2_idle_hours |
| REQ-SEC-23 | The system shall email the owner when the admin account logs in from a new device. | done | Test: req_sec_23_a_sign_in_by_an_account_that_is_not_the_owner_sends_no_email, req_sec_23_the_owner_gets_an_email_when_her_account_logs_in_from_a_new_device |
| REQ-SEC-24 | Checkout shall accept at most 5 orders per hour from the same IP address. | done | Test: req_sec_24_at_most_5_orders_an_hour_from_one_address, req_sec_24_refused_orders_do_not_count_towards_the_limit |
| REQ-SEC-25 | Every form shall accept only the fields it expects, ignoring any extra fields sent. | done | Test: req_sec_25_req_sec_35_fields_that_are_not_on_the_form_are_ignored |
| REQ-SEC-26 | Debug mode shall be off on the live site. | done | Test: req_sec_26_debug_is_off_in_the_production_settings |
| REQ-SEC-27 | Error pages shall show no technical details (code, file paths, database messages). | done | Test: req_sec_27_error_pages_show_no_technical_details |
| REQ-SEC-28 | Backups shall be encrypted. | done | Test: req_sec_28_every_file_in_a_backup_is_encrypted_with_aes_256, req_sec_28_without_a_password_no_backup_is_made |
| REQ-SEC-29 | At launch, the code's libraries shall have no known high or critical vulnerabilities (composer audit, npm audit). | done | composer audit and npm audit report nothing (Laravel 13.38, Filament 5.10, Vite 8) |
| REQ-SEC-30 | Before launch, an OWASP ZAP security scan of the live site shall report no high or medium risk findings. | not done | The local scan reports no high risk, and only the 2 medium findings on the admin panel that were accepted (docs/m4-review-notes.md). The scan of the live site waits on the server |
| REQ-SEC-31 | Every uploaded image shall be re-encoded before it is stored, removing any hidden code. | done | Test: req_sec_31_a_file_that_is_not_an_image_is_refused, req_sec_31_every_image_is_re_encoded_dropping_hidden_code |
| REQ-SEC-32 | Every uploaded image shall be given a new random file name. | done | Test: req_sec_32_every_image_gets_a_new_random_name |
| REQ-SEC-33 | The backend shall expose no public API beyond the endpoints the storefront itself uses (search, bag check, checkout). | done | Only the bag check (POST /bag/check) and placing an order (POST /checkout) exist besides pages |
| REQ-SEC-34 | Every public endpoint shall accept at most 60 requests per minute from the same IP address. | done | Test: req_sec_34_making_receipts_is_rate_limited_like_the_other_public_endpoints, req_sec_34_public_endpoints_allow_60_requests_a_minute |
| REQ-SEC-35 | Every endpoint shall validate its input against a fixed list of allowed fields, types, lengths. | done | Test: req_sec_25_req_sec_35_fields_that_are_not_on_the_form_are_ignored, req_sec_35_bytes_that_are_not_text_never_break_the_checkout, req_sec_35_input_is_validated, req_sec_35_odd_shop_addresses_show_the_shop_never_an_error |
| REQ-SEC-36 | Public endpoints shall return no customer data. | done | Test: req_chk_15_req_dmo_75_req_sec_36_the_reply_is_only_the_confirmation_address, req_sec_36_37_and_req_db_31_unpublished_products_are_left_out |
| REQ-SEC-37 | Public endpoints shall return no admin data. | done | Test: req_sec_36_37_and_req_db_31_unpublished_products_are_left_out |
| REQ-SEC-38 | Every admin request shall be refused unless it comes from a logged-in admin session. | done | Test: req_sec_38_admin_pages_refuse_visitors_who_are_not_logged_in, req_sec_38_the_packing_slip_needs_an_admin_session |
| REQ-SEC-39 | The server shall check on every admin request and action that the signed-in user is the owner. | done | Test: req_sec_39_an_account_that_is_not_the_owner_cannot_run_admin_actions_either, req_sec_39_an_account_that_is_not_the_owner_is_refused_every_admin_page |
| REQ-SEC-40 | Endpoints shall accept browser requests only from the store's own domain (CORS). | done | Test: req_sec_40_orders_from_another_site_are_refused |
| REQ-SEC-41 | The order confirmation page shall open only with a random link token, so changing the order number in the address cannot show another customer's order. | done | Test: req_sec_41_req_db_32_the_confirmation_page_opens_only_with_its_random_token |
| REQ-SEC-42 | Keys for outside services (such as the email service) shall stay on the server, never sent to the browser. | done | M4 code audit (docs/m4-review-notes.md): service keys live in the server .env only (deploy/env.production.example); nothing reaches the browser |
| REQ-SEC-43 | The receipt shall download only with the order's random link token, and shall be sent with Cache-Control: no-store, so no shared cache keeps a copy. | done | Test: req_dmo_102_req_sec_43_the_receipt_downloads_as_a_pdf_named_after_the_order_and_is_never_cached, req_sec_43_only_the_orders_random_link_token_opens_the_receipt |

## REQ-QLT

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-QLT-01 | The site shall work on the latest two versions of these browsers: Chrome, Safari, Firefox, Edge, Samsung Internet. | not done | Code checked against the last 2 versions of all 5 browsers (MDN data, caniuse): nothing missing; tested in Chromium 153 (Chrome, Edge, Samsung Internet). Safari and Firefox: 9-step hands-on check during the client review (docs/m4-review-notes.md) |
| REQ-QLT-02 | The storefront shall meet WCAG 2.1 AA. | done | Test: req_qlt_02_a_control_named_by_aria_label_includes_the_words_it_shows, req_qlt_02_aria_label_is_only_used_where_it_names_something. docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px; no violations left. Colour contrast behind the see-through header, hero and photo badges measured pixel by pixel |
| REQ-QLT-03 | The owner shall be able to complete every admin task in Section 3 without help after the training session. | not done | M4 |
| REQ-QLT-04 | The code shall follow the standard Laravel structure. | done | Standard Laravel 13 layout with app/Domain modules |
| REQ-QLT-05 | The source code shall be kept in a Git repository. | done | Git repository with one commit per milestone branch |
| REQ-QLT-06 | An uptime monitor shall email the owner within 5 minutes when the site is down. | not done | M4 |
| REQ-QLT-07 | Every customer-facing page shall work fully in English. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-QLT-08 | Every customer-facing page shall work fully in Arabic. | blocked | Works in Arabic; final sign-off waits on the owner approving the Arabic texts (REQ-DMO-87) |
| REQ-QLT-09 | Arabic pages shall display correctly right to left on phones. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-QLT-10 | Arabic pages shall display correctly right to left on computers. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-QLT-11 | Business logic shall follow the design rules in Appendix C.10 to C.14, checked by code review. | done | Domain modules per C.10 to C.14 |

## REQ-CST

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-CST-01 | The server shall be on a fixed monthly price plan. | not done | M4 (launch, needs approval for paid services) |
| REQ-CST-02 | No service shall use pay-per-use billing. | not done | M4 (launch, needs approval for paid services) |
| REQ-CST-03 | No service shall be set to upgrade itself to a paid tier automatically. | not done | M4 (launch, needs approval for paid services) |
| REQ-CST-04 | The server plan's included traffic shall be at least 10 times the store's expected monthly traffic. | not done | M4 (launch, needs approval for paid services) |
| REQ-CST-05 | Cloudflare shall cache the site's static files (images, styles, scripts). | not done | M4 (launch, needs approval for paid services) |
| REQ-CST-06 | The email service shall have a daily sending cap within its plan. | not done | M4 (launch, needs approval for paid services) |
| REQ-CST-07 | Billing alerts shall be turned on for every account that offers them, sent to the client's email. | not done | M4 (launch, needs approval for paid services) |
| REQ-CST-08 | The system shall use no paid third-party APIs. | done | M4 code audit (docs/m4-review-notes.md): no paid outside API is used; only SMTP email, Cloudflare free plan and SFTP backup storage |
| REQ-CST-09 | Old image copies shall be deleted automatically when an image is replaced, so disk use stays within the plan. | done | Test: req_cst_09_a_deleted_product_takes_its_photo_files_with_it, req_cst_09_a_replaced_product_photo_is_deleted_with_its_copies, req_cst_09_a_replaced_rack_image_is_deleted_with_its_copies, req_cst_09_req_cms_44_a_removed_color_takes_its_photo_files_with_it |
| REQ-CST-10 | The system shall email the owner when the server's disk is 80% full. | done | Test: req_cst_10_once_a_day_while_it_stays_full_and_again_after_it_was_freed, req_cst_10_the_owner_is_emailed_when_the_disk_is_80_percent_full, req_cst_10_the_real_disk_is_read |
| REQ-CST-11 | Before launch, RafaqTech shall give the client a list of every paid service with its exact price. | not done | M4 (launch, needs approval for paid services) |

## REQ-UX

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-UX-01 | Color, font size, spacing, radius, shadow, duration values shall appear only in tokens.css. | done | Test: req_ux_01_the_admin_stylesheet_takes_every_colour_and_size_from_tokens. Values live in tokens.css; components use var(--token) |
| REQ-UX-02 | The admin panel shall use the same color tokens as the storefront. | done | Test: req_ux_02_the_admin_uses_the_storefront_colour_tokens |
| REQ-UX-03 | Text sizes shall be only: 11, 12, 14, 16, 20, 24, 30, 36 px, plus these four display sizes: hero headline clamp(48px, 6.4vw, 96px); page headline clamp(42px, 6vw, 74px); contact title clamp(56px, 8vw, 112px); page title 30 px below 769 px screen width and 36 px from 769 px. | done | tokens.css text scale |
| REQ-UX-04 | No text shall be smaller than 11 px. | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px: no visible text under 11 px |
| REQ-UX-05 | Paragraph text (descriptions, FAQs, policies, about) shall be 16 px on phones. | done | Paragraph token 16 px |
| REQ-UX-06 | Form inputs shall use 16 px text. | done | Inputs use --text-input 16 px |
| REQ-UX-07 | Paragraph text shall use line height 1.5. | done | base.css |
| REQ-UX-08 | Headings shall use line height 1.2. | done | Test: req_ux_08_headings_use_line_height_1_2. docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px: every heading at 1.2 except the display titles Appendix D.3, D.5.12 and D.6 name |
| REQ-UX-09 | Paragraphs shall be at most 65ch wide. | done | base.css p max 65ch |
| REQ-UX-10 | Font weights shall be only 400, 500, 600, 700, plus 300 for Merriweather display text. | done | tokens.css weights |
| REQ-UX-11 | Margins, paddings, gaps shall use only: 4, 8, 12, 16, 24, 32, 48, 64, 96, 128 px (Appendix D.4.1 maps every demo value to one of these). A 1 px gap that draws a divider line is allowed. | done | D.4.1 spacing map applied to every padding, margin and gap |
| REQ-UX-12 | The page side margin shall be at least 16 px at every width. | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px: text keeps at least 16 px from the screen edge on phones; only rows that scroll sideways run past it |
| REQ-UX-13 | No page shall scroll sideways at any width from 360 px to 1920 px. | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px: no page scrolls sideways at any width (the checkout at 360 to 414 px was fixed) |
| REQ-UX-14 | Corner radii shall be only: 8 px for buttons, inputs, small controls; 12 px for cards, images, panels, dialogs; fully round for pills, badges, icon buttons, tabs, swatches; 2 px for flag images. | done | Radius map applied |
| REQ-UX-15 | Shadows shall be only these 3 levels: level 1 (cards, panels, images) 0 8px 24px #2a1c2012; level 2 (menus, toasts, floating buttons) 0 14px 36px #2a1c2029; level 3 (dialogs, drawer) 0 18px 50px #00000026, drawn as -18px 0 50px #00000026 on the side drawer. The header bar's glass shadow and the hero rack's decorative shadows are exempt (Appendix D.4.3). | done | Three shadow levels in tokens.css |
| REQ-UX-16 | Text smaller than 24 px shall have a contrast ratio of at least 4.5:1 against its background. | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px. Header glass made whiter (owner's choice, 24 Oct 2026): header links 5.2:1 or better over any photo |
| REQ-UX-17 | Text 24 px or larger shall have a contrast ratio of at least 3:1. | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px: large text 3:1 or better, hero headline 8.6:1 |
| REQ-UX-18 | Text on blush-200 (#f6dde4) shall use only ink (#2e1c22), ink-soft (#50393f) or ink-muted (#735e64). | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px: text on blush-200 uses only ink, ink-soft or ink-muted |
| REQ-UX-19 | Text on a filled button shall have a contrast ratio of at least 4.5:1. | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px: text on filled buttons passes 4.5:1 |
| REQ-UX-20 | Keyboard focus shall show a 2 px solid outline in rose-strong (#874058), 3 px away from the element (7.23:1 on white, 5.64:1 on blush-200). | done | tokens.css focus colour #874058, base.css outline |
| REQ-UX-21 | Every tap target shall be at least 44 × 44 px. | done | Tap targets raised to 44 px (footer links, options, chips) |
| REQ-UX-22 | Neighbouring tap targets shall be at least 8 px apart. | done | Header tools gap 8 px |
| REQ-UX-23 | Each screen shall show at most one filled (primary) button, not counting the Add to cart buttons on product cards. | done | Contact page has one primary button (test); other screens checked by eye |
| REQ-UX-24 | Every button shall have a hover state: background one step darker. | done | button.css hover states |
| REQ-UX-25 | Every button shall have a pressed state: scale 0.96. | done | button.css pressed scale 0.96 |
| REQ-UX-26 | Every disabled control shall show at 40% opacity with a not-allowed cursor. | done | button.css disabled 0.4 and not-allowed |
| REQ-UX-27 | A disabled control shall show a one-sentence reason directly below it (for example "Choose a city first"), unless the same reason is already shown as text in the same card, panel or form group (for example "Out of stock" on the product page); then no extra line shall be shown. | done | Test: req_chk_17_req_ux_27_a_paused_store_disables_place_order_and_says_why_below_it, req_ux_27_each_bag_line_has_a_place_for_the_reason_its_plus_stopped, req_ux_27_req_chk_16_a_sold_out_card_says_out_of_stock_on_its_button, req_ux_27_req_chk_16_a_sold_out_product_page_adds_no_line_because_out_of_stock_is |
| REQ-UX-28 | A button waiting for the server shall show a spinner in place of its label, keeping its width. | done | Checked in Chromium: Place order keeps 324 x 48 px with the spinner showing and the label hidden |
| REQ-UX-29 | Every tap or click shall change something on screen within 100 ms. | done | Checked in Chromium with the CPU slowed 4 times: 14 kinds of tap (menu, add to cart, bag drawer, filters, size, quantity, add to bag, gallery, bag line plus and remove, place order, another colour, Continue shopping in the drawer, FAQ) each changed the screen within 37 ms; a colour or size filter on a computer applies at once by opening the filtered page |
| REQ-UX-30 | Interface transitions shall last only 150, 200 or 300 ms. | done | Test: req_ux_30_31_transitions_last_150_200_or_300_ms_with_the_standard_easing. Durations limited to tokens 150/200/300 ms |
| REQ-UX-31 | Interface transitions shall use the easing cubic-bezier(.4, 0, .2, 1). | done | Test: req_ux_30_31_transitions_last_150_200_or_300_ms_with_the_standard_easing. Every transition uses --ease-standard |
| REQ-UX-32 | Only the hero rack (1.1 s entrance, 5.5 s sway) shall animate longer than 300 ms. | done | Test: req_ux_32_34_only_the_rack_animates_longer_and_animations_move_or_fade_only |
| REQ-UX-33 | With reduced motion turned on in the device settings, every animation shall stop except fades of 150 ms or less. | done | base.css reduced-motion rule |
| REQ-UX-34 | Animations shall change only transform or opacity. | done | Test: req_ux_32_34_only_the_rack_animates_longer_and_animations_move_or_fade_only |
| REQ-UX-35 | Every image shall have its width-to-height ratio set before it loads. | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px: every image has its width-to-height ratio set before it loads |
| REQ-UX-36 | Product images in grids shall use a 3:4 ratio. | done | Card photos 3:4 |
| REQ-UX-37 | Images below the first screen shall load only when scrolled near. | done | Test: req_dmo_82_and_req_ux_37_photos_below_the_first_screen_load_lazily_and_req_prf_02_the_first_row_at_once |
| REQ-UX-38 | Content that takes longer than 300 ms to load shall show a placeholder of the same size. | done | Test: req_ux_38_bag_views_have_grey_stand_ins_while_they_load |
| REQ-UX-39 | These views shall have an empty state with one sentence plus one button: bag, search with no results, shop filters with no results, every admin list. | done | Bag, search and filter empty states |
| REQ-UX-40 | Every error message shall name the problem in one sentence. | done | Review of every customer message (lang/en/store.php, PlaceOrderRequest): each names the problem; the checkout ones use the SRS wording (REQ-DMO-67 to 71) |
| REQ-UX-41 | Every error message shall tell the customer how to fix it in one sentence. | done | Review of every customer message: each says how to fix it in one sentence |
| REQ-UX-42 | Error text shall appear directly under its field. | done | Errors render under each field |
| REQ-UX-43 | A field's error shall appear when the customer leaves the field. | done | checkout.js shows a filled field's error when the customer leaves it |
| REQ-UX-44 | Every form field shall have a visible label above it. | done | Every checkout field has a label above it |
| REQ-UX-45 | The phone field shall use type="tel" with inputmode="numeric", so phones show the number keypad. | done | Test: req_ux_45_46_47_checkout_fields_use_the_right_types_and_autocomplete |
| REQ-UX-46 | The email field shall use type="email". | done | Test: req_ux_45_46_47_checkout_fields_use_the_right_types_and_autocomplete |
| REQ-UX-47 | Checkout fields shall carry autocomplete values: name, tel-national, email, address-level2, street-address. | done | Test: req_ux_45_46_47_checkout_fields_use_the_right_types_and_autocomplete |
| REQ-UX-48 | Icons shall come from one icon set, drawn at stroke width 1.7. | done | Demo icon set (resources/icons/icons.json) |
| REQ-UX-49 | Prices shall use tabular figures. | done | base.css tabular-nums on prices |
| REQ-UX-50 | Headings shall use text-wrap: balance. | done | base.css text-wrap: balance |
| REQ-UX-51 | Arabic pages shall set dir="rtl". | done | Test: req_str_19_21_and_req_ux_51_every_page_opens_in_arabic_right_to_left |
| REQ-UX-52 | Arabic pages shall mirror icons that point a direction (arrows, chevrons, back). | done | Test: req_ux_52_directional_icons_are_marked_for_mirroring |
| REQ-UX-53 | CSS shall use logical properties (margin-inline-start, not margin-left), so layouts mirror automatically. | done | Logical properties throughout the CSS |
| REQ-UX-54 | During stage 1, RafaqTech shall audit every storefront page against REQ-UX-01 to 53. | done | docs/m4-review-notes.md, Accessibility and design: axe-core 4.14 (WCAG 2.1 A and AA) plus measured checks on 32 page views (16 pages and states, English and Arabic) at 9 widths from 360 to 1920 px, plus the CSS review tests (StorefrontCssTest) |
| REQ-UX-55 | Before stage 2 starts, RafaqTech shall give the client the list of visual changes with before and after screenshots. | done | Ready for the client as the document "JUDE store: changes from the demo" (21 changes with before and after screenshots, including the version 1.2 changes) |
| REQ-UX-56 | Changes made to meet these rules shall not alter the page layouts, color palette, font families or logo. | done | Layouts, colour palette, fonts and logo unchanged; the one look change outside Appendix D.9 (whiter header glass) was approved by the owner |

## REQ-DMO

| ID | Requirement | Status | Evidence / note |
| --- | --- | --- | --- |
| REQ-DMO-01 | The storefront shall match every Build value in Appendix D. | done | Test: req_dmo_01_layers_blur_and_sizes_follow_d45, req_dmo_01_radii_shadows_and_motion_follow_d42_to_d44, req_dmo_01_spacing_has_only_the_ten_d41_steps, req_dmo_01_the_colours_are_the_d2_tokens, req_dmo_01_the_gradients_are_the_d2_gradients, req_dmo_01_the_parts_the_m4_check_found_use_the_appendix_d_value. tools/checks/appendix-d.cjs measured 426 Appendix D.5 and D.6 values in Chromium on 45 page views (English, 360 to 1920 px, with menus, drawer, filters and errors open): all match; tokens checked by AppendixDTokensTest. Found and fixed: trust strip text line height, hero top padding, badge position on phones, and for version 1.2 the Size filter section (33 px under the colours, now 24 px). Where the appendix disagrees with its own spacing map (6 spots), the written values are used |
| REQ-DMO-02 | Where a Build value differs from the Demo value, the Build value shall apply. | done | tools/checks/appendix-d.cjs measured 426 Appendix D.5 and D.6 values in Chromium on 45 page views (English, 360 to 1920 px, with menus, drawer, filters and errors open): all match; tokens checked by AppendixDTokensTest; every Build value that differs from the demo is the one built |
| REQ-DMO-03 | The demo files shall be stored unchanged in the project repository under /reference/demo. | done | reference/demo is a byte-for-byte copy (checked with diff) |
| REQ-DMO-04 | Anything visible in the demo that Appendix D and Sections 3 to 5 do not describe shall be built as the demo shows it. | done | Pages follow the demo markup and classes |
| REQ-DMO-05 | A change to any value in Appendix D shall need a change request signed by the client. | not done | Process rule; applies from sign-off |
| REQ-DMO-06 | Design values shall be stored as named tokens in one file, tokens.css, using the token names in this appendix. | done | resources/css/tokens.css holds every value |
| REQ-DMO-07 | A shop page shall show at most 6 products. | done | Test: req_str_07_and_req_dmo_07_a_page_shows_at_most_6_products |
| REQ-DMO-08 | The shop shall order products by display order, then by name A–Z; there is no sort menu. | done | Test: req_dmo_08_products_show_in_display_order_then_name_and_there_is_no_sort_menu, req_dmo_08_the_shop_has_no_sort_menu_and_hides_its_toolbar_only_when_it_is_empty |
| REQ-DMO-12 | Search shall match products whose name, description, category or the name of one of its colors contains the typed text, ignoring capital letters. | done | Test: req_dmo_12_search_matches_colour_and_category_names, req_str_08_and_req_dmo_12_search_matches_name_ignoring_case |
| REQ-DMO-13 | Search shall match Arabic names and descriptions on Arabic pages. | done | Test: req_dmo_13_search_matches_arabic_names_on_arabic_pages |
| REQ-DMO-14 | The color filter shall list only colors present in the current category. | done | Test: req_dmo_14_the_colour_filter_lists_only_colours_in_the_category |
| REQ-DMO-15 | Selecting colors shall show products that come in any selected color. | done | Test: req_str_05_and_req_dmo_15_selecting_colours_shows_any_selected_colour |
| REQ-DMO-16 | Checking "All colors" shall clear every color choice. | done | Test: tests/js/filters.test.mjs. resources/js/store/shop.js |
| REQ-DMO-17 | Unchecking the last color shall check "All colors". | done | Test: tests/js/filters.test.mjs. resources/js/store/shop.js |
| REQ-DMO-18 | From 1024 px, each filter change shall apply at once. | done | resources/js/store/shop.js (applies at once when the panel is a side column) |
| REQ-DMO-19 | Below 1024 px, filter changes shall apply when the customer taps "Apply filters". | done | resources/js/store/shop.js (sheet applies on "Apply filters") |
| REQ-DMO-20 | Escape shall close the filter panel. | done | resources/js/store/shop.js |
| REQ-DMO-21 | Switching category tabs shall keep the search text. | done | Test: req_dmo_21_and_22_category_tabs_keep_the_search_and_drop_colours_and_sizes |
| REQ-DMO-22 | Switching category tabs shall clear the color and size choices. | done | Test: req_dmo_21_and_22_category_tabs_keep_the_search_and_drop_colours_and_sizes |
| REQ-DMO-23 | Pagination shall show a window of 3 page numbers ending at the current page where possible, then "…" and the last page. With 7 pages: page 1 shows 1 2 3 … 7; page 4 shows 2 3 4 … 7; page 7 shows 5 6 7. | done | Test: req_dmo_23_pagination_shows_a_window_of_three_then_the_last_page |
| REQ-DMO-24 | Related products shall be products of the same category, excluding the product itself. | done | Test: req_dmo_24_and_26_related_products_are_up_to_6_from_the_same_category |
| REQ-DMO-25 | Related products shall be ranked by score: +4 when it comes in a color of the same family as the color shown, +2 if featured, +1 per shared collection; ties by display order, then name. | done | Test: req_dmo_25_related_products_are_ranked_by_score |
| REQ-DMO-26 | The product page shall show at most 6 related products. | done | Test: req_dmo_24_and_26_related_products_are_up_to_6_from_the_same_category |
| REQ-DMO-27 | The bag shall keep one line per product color and size. | done | Test: req_dmo_27_req_dmo_28_lines_of_the_same_piece_are_added_together_up_to_9. core.js bag.add keys lines by product colour and size |
| REQ-DMO-28 | A bag line quantity shall be between 1 and 9. | done | Test: req_dmo_27_req_dmo_28_lines_of_the_same_piece_are_added_together_up_to_9. core.js clamps 1 to 9; plus disabled at 9 |
| REQ-DMO-29 | Adding more of a product color and size already in the bag shall add to its line, up to 9. | done | core.js bag.add adds to the line up to 9 |
| REQ-DMO-30 | A sized product shall not enter the bag without a size. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic: message under sizes and toast |
| REQ-DMO-31 | Decreasing a bag line from 1 shall remove the line. | done | bag.js: minus at 1 removes the line |
| REQ-DMO-32 | The bag shall be kept in the customer's browser and survive closing the browser. | done | Test: req_dmo_32_the_bag_is_the_only_list_kept_in_the_browser. localStorage survives closing the browser |
| REQ-DMO-33 | Adding to the bag shall show the toast "{product} added to bag." | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-DMO-34 | Adding to the bag shall update the bag count without opening the drawer (REQ-CHK-03). Demo: the drawer opened. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-DMO-36 | Changing a quantity on the bag page shall show "Bag updated." | done | bag.js |
| REQ-DMO-37 | A toast shall stay 2800 ms. | done | config/store.php toast_ms 2800 |
| REQ-DMO-38 | A new toast shall replace the one on screen. | done | ui.js replaces the toast text and restarts its timer |
| REQ-DMO-39 | Prices shall show in JOD by default as "{amount} JD", for example "17 JD" or "12.50 JD". | done | Test: req_dmo_39_and_42_jod_prices_drop_decimals_only_when_whole |
| REQ-DMO-40 | Prices shall show in USD as the dollar sign followed by the amount, for example `$23.97`, converted with the rate set in the admin (seeded at 1.41). | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic: "$23.97" |
| REQ-DMO-41 | Converted prices shall round to 2 decimals. | done | core.js currency.format rounds to 2 decimals |
| REQ-DMO-42 | Whole amounts shall show without decimals. | done | Test: req_dmo_39_and_42_jod_prices_drop_decimals_only_when_whole |
| REQ-DMO-43 | A product with no price shall show "Price to be confirmed", and any subtotal that includes it shall show the same text. | done | Test: req_dmo_43_a_missing_price_shows_price_to_be_confirmed |
| REQ-DMO-44 | The chosen currency shall be remembered in the customer's browser. | done | core.js stores the currency in localStorage |
| REQ-DMO-45 | Orders shall be saved and charged in JOD whatever currency the customer views. | done | Test: req_chk_10_req_chk_12_req_chk_13_req_dmo_45_prices_and_fee_come_from_the_database_in_jod |
| REQ-DMO-46 | Escape shall close the currency menu. | done | ui.js dropdowns |
| REQ-DMO-47 | A click outside the currency menu shall close it. | done | ui.js dropdowns |
| REQ-DMO-48 | Opening a dialog (menu, drawer) shall lock page scrolling. | done | ui.js locks scroll and makes the page inert |
| REQ-DMO-49 | While a dialog is open, keyboard focus shall stay inside it. | done | ui.js focus trap |
| REQ-DMO-50 | Escape shall close an open dialog. | done | ui.js |
| REQ-DMO-51 | Closing a dialog shall return keyboard focus to the button that opened it. | done | ui.js returns focus to the opener |
| REQ-DMO-52 | The back-to-top button shall appear after the page is scrolled more than 600 px. | done | ui.js, offset 600 from config/store.php |
| REQ-DMO-53 | The back-to-top button shall scroll to the top smoothly, or instantly with reduced motion. | done | ui.js |
| REQ-DMO-54 | Tapping a product thumbnail shall show that photo in the main frame. | done | product.js gallery |
| REQ-DMO-55 | The product page quantity shall stay between 1 and 9. | done | Test: req_ux_27_req_dmo_55_the_quantity_plus_says_why_it_stops_at_9. product.js stepper clamps typed values to 1 to 9 |
| REQ-DMO-56 | The checkout phone number shall be valid only with the number of digits listed for the chosen country in D.8.3, not counting a 0 at the start. | done | Test: req_chk_22_req_dmo_56_a_number_needs_exactly_the_countrys_digits_not_counting_a_0_at_the_start, req_chk_22_req_dmo_56_the_phone_needs_exactly_the_digits_of_the_chosen_country, tests/js/phone.test.mjs |
| REQ-DMO-57 | If a typed or pasted number starts with the selected country's dial code (after +, 00 or nothing) and is longer than that country's numbers, the dial code shall be removed once. | done | Test: req_dmo_57_a_phone_typed_with_its_dial_code_is_saved_once_with_it, req_dmo_57_a_too_long_number_starting_with_the_dial_code_loses_it_once, tests/js/phone.test.mjs |
| REQ-DMO-58 | The phone country list shall offer the 16 countries in D.8.3, in that order, with Jordan selected by default. | done | Test: req_dmo_58_the_phone_list_offers_16_countries_with_jordan_first |
| REQ-DMO-59 | Choosing a city shall update the Delivery and Total rows at once. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-DMO-60 | The area picker shall stay disabled until a city is chosen. | done | checkout.js keeps the picker disabled until a city is chosen |
| REQ-DMO-61 | Typing in the area search shall filter the areas to names containing the text, ignoring capital letters. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic: "jab" in Amman |
| REQ-DMO-62 | Arrow Up and Arrow Down shall move through the area options. | done | checkout.js |
| REQ-DMO-63 | Enter shall choose the active area option. | done | checkout.js |
| REQ-DMO-64 | Escape shall close the area panel. | done | checkout.js |
| REQ-DMO-65 | Choosing "My area isn't listed" shall show the "Your area name" field, which is then required. | done | checkout.js |
| REQ-DMO-66 | Required checkout fields shall be: Name, Phone number, City, Area (or Your area name), Street and building. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic: empty submit flags the five required fields |
| REQ-DMO-67 | An empty Name, Phone number or Street and building field shall show "Please enter your {field name in lowercase}." | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-DMO-68 | An empty City shall show "Please choose your city." | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic |
| REQ-DMO-69 | An empty Area shall show "Please choose your area." | done | checkout.js |
| REQ-DMO-70 | A phone number with the wrong number of digits shall show "Please enter {digits} digits after +{dial code}.", for example "Please enter 9 digits after +962."; for Lebanon "7 or 8 digits" and for Germany "10 or 11 digits". | done | Test: req_chk_22_req_dmo_70_each_phone_country_carries_its_digits_and_its_message, req_dmo_70_req_dmo_90_the_arabic_checkout_takes_arabic_digits_and_answers_in_arabic, req_dmo_70_the_message_names_the_countrys_digits_and_dial_code. checkout.js + phone.js |
| REQ-DMO-71 | A filled-in email with an invalid format shall show "Please enter a valid email address." | done | checkout.js |
| REQ-DMO-72 | A failed submit shall keep everything typed, show the error summary and scroll to it. | done | checkout.js keeps values, shows the summary and scrolls to it |
| REQ-DMO-73 | The order number shall be 6 characters from ABCDEFGHJKLMNPQRSTUVWXYZ23456789, generated by the server, unique. Demo: made in the browser. | done | Test: req_chk_15_req_dmo_73_req_db_10_each_order_gets_a_unique_6_character_number_from_the_server |
| REQ-DMO-74 | After an order is saved, the bag shall be emptied. | done | Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic: the bag is empty after an order placed in English on a computer and in Arabic on a phone (checkout.js clears it when the server confirms) |
| REQ-DMO-75 | After an order is saved, the confirmation page shall be shown. | done | Test: req_chk_15_req_dmo_75_req_sec_36_the_reply_is_only_the_confirmation_address |
| REQ-DMO-76 | The confirmation email shall be sent by the server (section 4). Demo: sent from the browser through EmailJS. | done | Test: req_dmo_76_req_dmo_77_the_customer_email_is_worded_as_in_the_demo |
| REQ-DMO-77 | The confirmation email shall have the subject "Your JUDE order #{number}", start with "Hi {name}," and "Thank you for your order! Here are the details of order #{number}:", list the lines, Subtotal, Delivery, Total, Deliver to, Phone, Payment, and end with "With love," and "JUDE". | done | Test: req_dmo_76_req_dmo_77_the_customer_email_is_worded_as_in_the_demo |
| REQ-DMO-78 | The product page back link shall go back in browser history when the previous page is on the store, else to the category page. | done | product.js back link |
| REQ-DMO-79 | A garment on the rack shall link to its product. | done | Test: req_dmo_79_and_80_the_rack_shows_up_to_6_garments_in_order_linking_to_products |
| REQ-DMO-80 | The hero rack shall show up to 6 garments, in the order set in the admin. | done | Test: req_dmo_79_and_80_the_rack_shows_up_to_6_garments_in_order_linking_to_products |
| REQ-DMO-81 | The client reviews section shall show only when at least one review is published. | done | Test: req_dmo_81_reviews_show_only_when_one_is_published |
| REQ-DMO-82 | Photos below the first screen shall load only when scrolled near (loading="lazy"). | done | Test: req_dmo_82_and_req_ux_37_photos_below_the_first_screen_load_lazily_and_req_prf_02_the_first_row_at_once |
| REQ-DMO-83 | The first product photo on the product page shall load with high priority (fetchpriority="high"). | done | Test: req_dmo_83_the_first_photo_loads_with_high_priority |
| REQ-DMO-84 | The العربية button shall open the same page in Arabic. Demo: showed the notice "Arabic storefront copy is pending owner approval." | done | Test: req_dmo_84_the_switch_keeps_the_query_string, req_str_20_and_req_dmo_84_the_language_button_switches_the_language_on_the_same_address |
| REQ-DMO-89 | The phone field shall keep only digits: any other character typed or pasted is removed at once. | done | Test: tests/js/phone.test.mjs |
| REQ-DMO-90 | Arabic-Indic digits typed in the phone field shall become 0 to 9. | done | Test: req_chk_21_req_dmo_90_only_digits_count_and_arabic_indic_digits_are_0_to_9, req_dmo_70_req_dmo_90_the_arabic_checkout_takes_arabic_digits_and_answers_in_arabic, tests/js/phone.test.mjs |
| REQ-DMO-91 | The phone field shall stop at the chosen country's longest number length, plus 1 when the number starts with 0. | done | Test: tests/js/phone.test.mjs |
| REQ-DMO-92 | The size filter shall list only the sizes of products in the current category, in size order. | done | Test: req_dmo_92_the_size_filter_lists_only_the_sizes_in_the_category_in_size_order |
| REQ-DMO-93 | Selecting sizes shall show products that come in any selected size. | done | Test: req_str_23_and_req_dmo_93_selecting_sizes_shows_products_in_any_selected_size |
| REQ-DMO-94 | Checking "All sizes" shall clear every size choice, and unchecking the last size shall check "All sizes". | done | Test: tests/js/filters.test.mjs |
| REQ-DMO-95 | Color and size choices together shall show only products that match both. | done | Test: req_dmo_95_colour_and_size_together_show_only_products_that_match_both |
| REQ-DMO-96 | The shop shall show one card per product, however many colors it has. | done | Test: req_str_24_and_req_dmo_96_each_product_is_one_card_with_a_dot_for_each_colour |
| REQ-DMO-97 | A product card shall show its first color, or the first color that matches the active color filter or search. | done | Test: req_dmo_97_a_card_shows_its_first_colour_or_the_first_one_the_filter_or_search_matches |
| REQ-DMO-98 | The product page shall open on the color named in ?color=, or on the first color. | done | Test: req_dmo_98_the_page_opens_on_the_colour_in_the_address_or_on_the_first_one |
| REQ-DMO-99 | Choosing a color shall show its photos, name and stock without reloading the page, and change ?color= in the address. | done | Test: req_dmo_99_each_colour_carries_its_photos_name_and_stock_so_choosing_one_needs_no_reload. Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic (tools/checks/version-1-2.cjs): on a page opened on Navy, choosing Black shows its photos, name and stock without a reload, the address ends in ?color=black and the language link keeps the colour |
| REQ-DMO-100 | An out-of-stock color shall stay selectable; while it is selected, "Add to bag" is disabled and "Out of stock" shows. | done | Test: req_str_12_req_dmo_100_an_out_of_stock_colour_can_be_chosen_but_not_added |
| REQ-DMO-101 | "Continue shopping" in the bag drawer shall close the drawer and return focus to the button that opened it. | done | Test: req_dmo_101_the_bag_drawer_offers_checkout_and_continue_shopping_which_closes_it. Checked in a real browser (Chromium) at 1440 px and 390 px, English and Arabic (tools/checks/version-1-2.cjs): Continue shopping closes the drawer and the bag button has focus again |
| REQ-DMO-102 | "Download receipt" shall download the order's PDF receipt, named JUDE-order-{number}.pdf. | done | Test: req_dmo_102_req_sec_43_the_receipt_downloads_as_a_pdf_named_after_the_order_and_is_never_cached |
| REQ-DMO-103 | The address of a demo product that is now one color of a product shall redirect (301) to that product with ?color=. | done | Test: req_dmo_103_a_demo_product_that_is_now_one_colour_redirects_to_it |
| REQ-DMO-85 | The English storefront text shall be exactly the text in D.8.1 and D.6, word for word, including punctuation. | done | English text copied word for word into lang/en/store.php and the content seeder |
| REQ-DMO-86 | Every text in D.8.1 marked "editable" shall be editable in the admin panel (section 3.5). | done | Test: req_dmo_86_every_editable_site_text_has_a_field_in_the_admin |
| REQ-DMO-87 | Every English text shall have an Arabic version approved by the owner before launch. | blocked | Arabic drafts are in place; waits on the owner approving them |
| REQ-DMO-88 | The delivery cities, fees and areas in D.8.2 shall be loaded into the database at launch. | done | DeliverySeeder loads the 12 cities, fees and 215 areas |

## SRS 1.4 (5 Nov 2026)

SRS version 1.4 added 23 IDs. The ones built on the `srs-1.4` branch are listed here; the device checks wait for M5.

| ID | Requirement | Status | Evidence |
| --- | --- | --- | --- |
| REQ-STR-29 | The shop offers 7 categories, each with its own tab and page. | done | Test: req_str_29_req_cms_47_the_four_new_categories_exist_in_both_languages |
| REQ-STR-30 | The gallery shows at most 4 photos of the selected color, main photo first. | done | Gallery takes config store.max_photos_per_color; test: req_str_30_each_color_holds_up_to_4_photos |
| REQ-CHK-26 | Checkout offers Jordan and the 6 Gulf countries, Jordan by default. | done | Test: req_chk_26_every_jordan_city_costs_3_jd_and_every_gulf_country_25_jd |
| REQ-CHK-27 | For Jordan, city and area come from the owner's lists. | done | Existing checkout tests |
| REQ-CHK-28 | For a Gulf country, city and area are typed, 2 to 80 characters. | done | Test: req_chk_28_a_gulf_order_needs_its_city_and_area |
| REQ-CHK-29 | 3 JD to every city in Jordan. | done | Migration 2026_11_05_000100 and demo seed |
| REQ-CHK-30 | 25 JD per order to each Gulf country. | done | Test: req_chk_27_req_chk_31_a_gulf_order_takes_the_typed_city_and_the_gulf_fee |
| REQ-CHK-31 | Changing the country updates fee and total without reloading. | done | Browser check at 375, 740, 768 and 1440 px (Total 42 JD for one hijab to Saudi Arabia) |
| REQ-CHK-32 | Bag and checkout describe delivery with the admin fees. | done | Test: d_6_6_checkout_lead_reads_the_fees_from_the_delivery_list |
| REQ-CHK-33 | "Keep this number..." on confirmation, receipt and customer email. | done | Test: req_chk_33_the_confirmation_page_tells_the_customer_to_keep_the_number, OrderEmailsTest |
| REQ-ORD-34 | A full order number finds only that order. | done | Test: req_ord_34_a_full_order_number_finds_only_that_order |
| REQ-ORD-35 | Dashboard "Find order" box. | done | Test: req_ord_35_find_order_opens_the_order |
| REQ-CMS-47 | The admin starts with the 4 new categories in English and Arabic. | done | Same test as REQ-STR-29 |
| REQ-CMS-48 | The owner manages the delivery countries. | done | Delivery admin: Country field and filter |
| REQ-CMS-49 | The owner sets the fee for each Gulf country. | done | Delivery admin: fee per row |
| REQ-CMS-50 | The owner switches each country on or off. | done | Test: req_chk_29_a_switched_off_gulf_country_takes_no_orders |
| REQ-UX-57 | Mobile first: phone layout is the base, min-width media queries only. | done | No max-width layout queries left in resources/css (admin bottom menu rewritten) |
| REQ-UX-58 | Responsive from 360 to 1920 px on phones, tablets, computers. | not done (M5) | Device check list |
| REQ-UX-59 | Works in landscape. | not done (M5) | Checkout checked at 740 × 360; other pages in the M5 device check |
| REQ-UX-60 | Admin: 16 px fields, no sideways scroll, 44 px tap targets; wide lists scroll in their frame. | done | admin.css; browser check of every admin screen in M5 |
| REQ-UX-61 | Admin Save/Create stays visible on phones. | done | BasePage::stickyFormActions(), placed above the bottom menu |
| REQ-UX-62 | Usable at 200% text zoom. | not done (M5) | Device check list |
| REQ-UX-63 | Real-device test before launch, checklist to the client. | not done (M5) | Checklist |

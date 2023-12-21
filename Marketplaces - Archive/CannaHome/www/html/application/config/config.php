<?php

/**
 * Configuration
 *
 * For more info about constants please @see http://php.net/manual/en/function.define.php
 * If you want to know why we use "define" instead of "const" @see http://stackoverflow.com/q/2447791/1114320
 */

/**
 * Configuration for: Error reporting
 * Useful to show every little problem during development, but only show hard errors in production
 */
error_reporting(E_ERROR | E_WARNING | E_PARSE); // removed E_ALL
ini_set("display_errors", FALSE);

## DEBUG MODE
//ini_set("display_errors", true);
//ini_set("display_startup_errors", true);
//ini_set("error_reporting", 2147483647);

/**
 * Configuration for: Folders
 * Here you define where your folders are. Unless you have renamed them, there's no need to change this.
 */
define('LIBS_PATH', 'application/libs/');
define('CONTROLLER_PATH', 'application/controllers/');
define('MODELS_PATH', 'application/models/');
define('VIEWS_PATH', 'application/views/');
define('LIBRARY_PATH', 'library/');
define('UPLOADS_PATH', 'upload/');
define('COINBIN_PATH', 'application/js/coinbin/');

define('ADMINER_PATH', LIBRARY_PATH . 'adminer/adminer-4.2.5-mysql-en.php');

define('TEMPLATE_PATH', 'views/_templates/');
define('DEFAULT_LOGIN_DESTINATION', 'redirect/');
define('DEFAULT_LOGIN_DESTINATION_VENDOR', 'redirect/account/');
define('DEFAULT_LOGIN_DESTINATION_PENDING_DEPOSIT', 'account/find_pending_deposit/');
define('DEFAULT_LOGIN_DESTINATION_ACTIVE_TRANSACTIONS', 'redirect/account/');
define('DEFAULT_LOGGED_OUT_DESTINATION', 'login/');

define('DEFAULT_REGISTRATION_DESTINATION_INVITE_ONLY', 'redirect/');
define('DEFAULT_DESTINATION_EXPIRED_TRANSACTIONS', 'account/orders/expired/');
define('DEFAULT_LOGIN_DESTINATION_FORUM', '');

/**
 * Configuration for: Secure
 */
define('SECURE', false);
define('SITEWIDE_USERNAME_SALT', '☂♄ε η℮ẘ ṧ☂αᾔ∂αя∂ ☤ᾔ ⅾℯ℮℘ ẘεß ¢☺μღ℮я¢℮');
define('PROMO_CODE_SALT', 'ℐḟ ¥◎ü я℮ⓠυḯґε ƒ◎я¢ε т☺ ℘ґøღ◎тε ¥☺υґ ḯ∂εαʟ, ⊥♄ℯґℯ ☤﹩ ﹩☺мε⊥нḯηℊ ẘґ☺ηℊ ωḯ☂н ⑂øüґ їⅾεαł');
define('GUEST_ADMITTANCE_SALT', '911NGINX007');
define('SITE_RSA_PUBLIC_KEY', "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoGf1qVZEPk+0a3sIiAWj
D85FHPbdUVryXRQ9W5wt98Hrvi4OxB8dwKZA/oj99PGW/mRfliRXuAPIXip49QHA
3cWI598jOlXiQm7n+hfd7jZZFZcm4FohRpWQkIgNdu+HpTDpKdTUl8BqayluTfSG
Ctq4Cp+vbiaKdNVBUVg9muaTMAcHbg9gmwKpjqCWG+h4b7Hx0RUkYFfRtDPPWwhj
YgXEoczlgzscpUZ+UQ43KHfk+1WByJNWKU8+ERJhGvQLcEazDQqOCZSIehnbax3t
LMxqEWCuD5DhPLk0+qxususXg0jL6nukplRrf5bS5fdPfAvBKi0HcX++iQOT85DV
hQIDAQAB
-----END PUBLIC KEY-----");

define("SESSION_NAME", 'lemon');

/**
 * Configuration for: Cookies
 * Please note: The COOKIE_DOMAIN needs the domain where your app is,
 * in a format like this: .mydomain.com
 * Note the . in front of the domain. No www, no http, no slash here!
 * For local development .127.0.0.1 is fine, but when deploying you should
 * change this to your real domain, like '.mydomain.com' ! The leading dot makes the cookie available for
 * sub-domains too.
 * @see http://stackoverflow.com/q/9618217/1114320
 * @see php.net/manual/en/function.setcookie.php
 */
// 1209600 seconds = 2 weeks
define('COOKIE_RUNTIME', 1209600);
// the domain where the cookie is valid for, for local development ".127.0.0.1" and ".localhost" will work
// IMPORTANT: always put a dot in front of the domain, like ".mydomain.com" !
define('COOKIE_DOMAIN', '.localhost');

/**
 * Configuration for: Database
 * This is the place where you define your database credentials, type etc.
 *
 * database type
 * define('DB_TYPE', 'mysql');
 * database host, usually it's "127.0.0.1" or "localhost", some servers also need port info, like "127.0.0.1:8080"
 * define('DB_HOST', '127.0.0.1');
 * name of the database. please note: database and database table are not the same thing!
 * define('DB_NAME', 'login');
 * user for your database. the user needs to have rights for SELECT, UPDATE, DELETE and INSERT
 * By the way, it's bad style to use "root", but for development it will work
 * define('DB_USER', 'root');
 * The password of the above user
 * define('DB_PASS', 'xxx');
 */
require('db.php');

define("MYSQL_DATETIME_RANGE_LOWEST", '1000-01-01 00:00:00');
define("MYSQL_DATETIME_RANGE_HIGHEST", '9999-12-31 23:59:59');

define("MYSQL_INT_UNSIGNED_RANGE_LOWEST", 0);
define("MYSQL_INT_UNSIGNED_RANGE_HIGHEST", 4294967295);

// BITCOIN RELATED
define('USE_EXT', 'GMP');

define("LOWEST_PRICE_PROFITABLE_FOR_MARKET", 0.002); // ~ standardFee*3; 0.0006328*3

define('REQUIRED_TX_CONFIRMATIONS_ORDER', 1);
define('REQUIRED_TX_CONFIRMATIONS_ORDER_DIRECT', 1);

define('REQUIRED_TX_CONFIRMATIONS_BROADCAST', 1);
define('ADVISED_TX_CONFIRMATIONS_ACCEPT', 1);
define('TX_CONFIRMATIONS_ELECTRUM_CONFIRMED', 1);
define('TX_CONFIRMATIONS_ELECTRUM_UNCONFIRMED', 0);

define('MAXIMUM_BROADCAST_ATTEMPTS', 5);

define('SITE_BIP32_EXTENDED_PRIVATE_KEY', 'xprv9s21ZrQH143K2p9agueXu28Yy3AC2KnF3xZxGCHPKScdbrjGQqJRV69qiBh1wx43fj9QjJ1ksKHBZ83xLHaEpTm4Hc2aXu5hMGKe9et9LtF');
define('MARKETPLACE_EXTENDED_PUBLIC_KEY', '');
define('MARKETPLACE_EXTENDED_PUBLIC_KEY', '');
define('DONATIONS_BIP32_EXTENDED_PUBLIC_KEY', '');
define('DONATIONS_LTC_BIP32_EXTENDED_PUBLIC_KEY', '');

define('DONATIONS_BIP32_EXTENDED_PUBLIC_KEY_NEW', 'ypub6Xu94LTuAdhAKTz4Nib5798Ma9ohQ9mc3FfDYzcymFbh1MtKyJsEoR6xyVtUPUQtPJurLVyw2h39VFBCsrwek8N89jWPrZszkAUSmteVQyK');

define("REFERRAL_WALLET_EXTENDED_PRIVATE_KEY", 'yprvAJWPb7wEKtVrfnZAce38ozyitLan6totthS35xkC93wX9o9UYjBTTBcH1RiRDWm27xzQBn1j5EPiQzf4Vu5LhouXDeYUTagVP8vKJbgpToQ');
define("REFERRAL_COMMISION", 0.005);

define("SEGWIT_TRANSACTION_MINIMUM_FEE_PER_KILOBYTE", 2000);

define('LAST_MULTISIG_TX_TO_WITHDRAW_WITHOUT_FEES', 1702);

//define('JB_BIP32_EXTENDED_PUBLIC_KEY', 'xpub661MyMwAqRbcGFeqmd6qbfJft1wMrKgMf3oTsBuNUmyyBoXYo9KFPBDZpPkcjuo6X8cRUKxmLAECJuSHhKukEGL9F4TDw7z7Hfc1sdijpox');
define('INVOICE_PAYMENTS_EXTENDED_PUBLIC_KEY', 'xpub661MyMwAqRbcFTDNvBFaaHuihgqeSeEn1qVMi2CkHqBof7RyPzqV28AJXVknHsfGxv8VUWmk7vaYb8F6nKEtMdJLpz1CzdixAzSWy3eQJ1p');

define('SITE_RSA_PRIVATE_KEY', "-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEAoGf1qVZEPk+0a3sIiAWjD85FHPbdUVryXRQ9W5wt98Hrvi4O
xB8dwKZA/oj99PGW/mRfliRXuAPIXip49QHA3cWI598jOlXiQm7n+hfd7jZZFZcm
4FohRpWQkIgNdu+HpTDpKdTUl8BqayluTfSGCtq4Cp+vbiaKdNVBUVg9muaTMAcH
bg9gmwKpjqCWG+h4b7Hx0RUkYFfRtDPPWwhjYgXEoczlgzscpUZ+UQ43KHfk+1WB
yJNWKU8+ERJhGvQLcEazDQqOCZSIehnbax3tLMxqEWCuD5DhPLk0+qxususXg0jL
6nukplRrf5bS5fdPfAvBKi0HcX++iQOT85DVhQIDAQABAoIBAQCNjrExm7vl8Zkb
MRy6TZ81l6dOhF2UKlqw4ee0LQQ0HaLZ1vOZCIzNMuswtdzIiVvjbNkeOzxeXJg6
4eiU79Mw496KIlWIabqfPhjikKV+T+AWOapJW8D59Yv6wNaBG6ntklhyXiyvL1O6
9nktupmAgrzgQ0QiRUjzCi/2hZT4WRW9XCPwizjEC4CD4XUKBmLUvwfn8mImspZY
P/zq+lrIr0RnYvA5p7JwmUXzklr2h8/FhwcFZTM+NpQM1UX/iYhwJGE9KKwAVFGn
eEdC3YCPoaBQWz/vIeV3SIyEtd3Pu3iv0WL8RgpNhCdfuXXAogd5e7l+KE4In+D+
BgM7CVaBAoGBANEA0GZbN4kvz59JOGL8rZX5X1lQ+ObhS2E2ZGhXDuHbl+C/CfQj
UNv6QSgh1N8eV4mgbMNUZHCo21h369+BL012KgXRk/LdQudeDH+e2gFnOtz8KhSv
oA9zr72qRq5ebAwfhOwxZWg1eg7lbKMcgQiDcua9ufQs0M8ylVupP0iJAoGBAMR5
rFz+c04rxy1mqpujZvL8Kn2Ovg6Jht3TkGJhnu+Vyc+Z/vKX/OVTT3UKe/mmJn2J
uiQvrj9XOhRKIEd4hSmnH7vMABmQ/UpfYGth3wV5iFg5VXnLuNm/V+Io2eLvzQjB
OM35maaRZutuTiOz5eP7t3zg6BacR8btBJpENS4dAoGBAKpxpYKzxPil+wYFqmxf
cBisg6vNMw7mkJi4yO2mgcaDVLq+URm+a0TaM6TM4lMK5YyhPdGV00tlCWx+b+eX
7MGZKfAQ2DzpYJRayIqTO2qFiyWIp7CzAS9YwutEH9w3uJmyYq5UIkT3x5C6XPww
VLJisKOn+iw9GBTBRbi4r5kxAoGAe4tvAQLXip4onrniKf/z6nL6XP13MTj1X2N7
dQGhqVHtFufk0rMTyTg7zIMNNgxuQ55pN/vre9TjpoJ+DbMROQHoCHTc0zbCrxOO
U7e1P4IOZDuZLf8We4XAQ2wgpnzX1tt5VrvPDFh9+SjhZb3nnxZXaOUby6v1znaX
4FFnh1kCgYEAx4vD9chDw7zm3t28DOFODHpd/Hkib25i+bh4DlOR3M7+8F0qjFNo
p4fk6vSSHy6Ksk8pL+87Bn0lfDZB2gfpavPupyfg7sBDXxaaZvuEeAwcLYpih5mC
pGM1zGFijsq2S/ipxHt6b4gMvmgvS9X1AbLJYwvgrRjiqTgizaj9LLM=
-----END RSA PRIVATE KEY-----");


// ELECTRUM
define('ELECTRUM_PATH', '/usr/local/bin/electrum');
define('ELECTRUM_DAEMON_STATUS_INACTIVE', "Daemon not running");
define('ELECTRUM_DAEMON_RPC_ADDRESS', 'localhost');
define('ELECTRUM_DAEMON_RPC_PORT', 7780);
define('ELECTRUM_DAEMON_REQUEST_TIMEOUT', 10);

define('ELECTRUM_SERVER_ADDRESS', 'j3hshijk6gmlzozb.onion');
define('ELECTRUM_SERVER_PORT', 50007);

define('ELECTRUM_SERVER_REQUEST_TIMEOUT', 7);
define('ELECTRUM_SERVER_REQUEST_RESPONSE_LENGTH', 528);
define('ELECTRUM_SERVER_REQUEST_MAXIMUM_SERVER_ATTEMPTS', 5);

// ELECTRUM
define('ELECTRUM_LTC_PATH', '/usr/local/bin/electrum-ltc');
define('ELECTRUM_LTC_DAEMON_STATUS_INACTIVE', "Daemon not running");
define('ELECTRUM_LTC_DAEMON_RPC_ADDRESS', 'localhost');
define('ELECTRUM_LTC_DAEMON_RPC_PORT', 7781);

define('ELECTRUM_LTC_SERVER_ADDRESS', 'glb6ymybt4yhavfb.onion');
define('ELECTRUM_LTC_SERVER_PORT', 50007);

/*** IDs ***/

// LISTING ATTRIBUTES
define("LISTING_ATTRIBUTE_FROM_CONTINENT", 1);
define("LISTING_ATTRIBUTE_FROM_COUNTRY", 2);
define("LISTING_ATTRIBUTE_DEPOSIT", 3);
define("LISTING_ATTRIBUTE_QUANTITY", 4);
define("LISTING_ATTRIBUTE_QUANTITY_LEFT", 5);
define("LISTING_ATTRIBUTE_MAX_ORDER", 6);
define("LISTING_ATTRIBUTE_CRITICAL_QUANTITY", 7);

define("QUANTITY_LEFT_DEFAULT_CRITICAL_VALUE", 0);


// MAX STRLENs
define("MAX_LENGTH_LISTING_NAME", 100);
define("MAX_LENGTH_ATTRIBUTE_VALUE", 30);
define("MAX_LENGTH_LISTING_SHIPPING_NAME", 50);
define("MAX_LENGTH_LISTING_SHIPPING_DESCRIPTION", 100);
define("MAX_LENGTH_DISCUSSION_TITLE", 100);
define("MAX_LENGTH_MESSAGE_CONTENT", 7000);
define("MAX_LENGTH_MESSAGE_SUBJECT", 100);

define("LATEST_UPDATE_EXCERPT_MAX_LENGTH", 200);


// ITEMS PER PAGE
define("CONVERSATIONS_PER_PAGE", 11);
define("MESSAGES_PER_PAGE", 10);
define("LISTINGS_PER_PAGE", 21); 
define("REVIEWS_PER_PAGE", 15); 
define("FEATURED_VENDOR_COMMENTS_PER_PAGE", 8);
define("FEATURED_LISTINGS_PER_SEARCH_PAGE", 6);
define("FEATURED_LISTINGS_PER_PAGE", 8);
define("TRANSACTIONS_PER_PAGE", 100);
define("VENDORS_LISTINGS_PER_PAGE", 50);
define("DISPUTE_MESSAGES_PER_PAGE", 10);
define("RELATED_LISTINGS_PER_PAGE", 8);
define("FAVORITE_LISTINGS_PER_PAGE", 9);

define("BLOG_POSTS_PER_PAGE", 10);
define("BLOG_COMMENTS_PER_PAGE", 50);

define("LISTING_FEATURED_COMMENT_COUNT", 2);


/**
* Modified Bayesian estimate to sort listings by 'rating' while factoring in number of ratings.
*
* 	B = (r*n+k*C)/(n+k) + a*log_10(n)
*		r = average rating for particular rating
*		n = total number of ratings
*		C = average rating of all listings
*		k = weigthing factor
*		a = correction coefficient; a higher number favors listings with more ratings
*
*/
define("LISTING_SORT_RATING_BAYESIAN_ESTIMATE_WEIGHTING_FACTOR", 1);
define("LISTING_SORT_RATING_BAYESIAN_ESTIMATE_CORRECTION_COEFFICIENT", 0.020);

// Listing Grouping
define("LISTINGS_GRID_OPTIONS_MAX_QUANTITY", 5);
define("LISTINGS_TABULAR_OPTIONS_MAX_QUANTITY", 10);
define("LISTINGS_TABULAR_OPTIONS_MAX_QUANTITY_SINGLE_ROW", 8);
define("LISTINGS_TABULAR_OPTIONS_MAX_QUANTITY_NON_ABBREVIATED_UNITS", 5);

// DEFAULT SORT BY
define("SORT_BY_BLOG_POSTS", 'id_desc'); // [id_desc | id_asc]
define("SORT_BY_BLOG_POSTS_COMMENTS", 'id_desc'); // [id_desc | id_asc], for blog page
define("SORT_BY_BLOG_POST_COMMENTS", 'id_asc'); // [id_desc | id_asc], for blog post page

define("BLOG_POSTS_COMMENTS_COUNT", 2);

define("LATEST_UPDATES_COUNT", 8);
define("LATEST_UPDATES_ON_MARKET_FRONTPAGE_COUNT", 8);
define("DISCUSSIONS_PER_PAGE", 50);
define("DISCUSSION_COMMENTS_PER_PAGE", 20);

define("FRONTPAGE_LISTINGS_COUNT", 7);

// ADMIN / MOD ALIASES and IDs
define('SYSTEM_MESSAGER_ID', 6);
define("ALIAS_SUPPORT", 'Lorem');
define('SUPPORT_USER_ID', 6);


// REPUTATION LEVELS
define("REPUTATION_USER_DESCRIPTION", 0);
define("REPUTATION_USER_IMAGE", 0); // was 1
define("REPUTATION_LISTING_IMAGE", 0); // was 1
define("REPUTATION_AFFECT_REPUTATION", 5);
define("REPUTATION_DISCUSSION_VOTE", 5);
define("REPUTATION_FORUM_SIGNATURE", 0);
define("REPUTATION_VENDOR_PREAPPROVED_LISTING", 0); // was 10
define("REPUTATION_PTS_YELLOW", 0);
define("REPUTATION_LISTING_TAGS", 0);
define("REPUTATION_PTS_GREEN", 0);
define("REPUTATION_VERIFIED_VENDOR", 0);
define("REPUTATION_DISABLE_ESCROW", 0);
define("REPUTATION_ATTRIBUTE_" . LISTING_ATTRIBUTE_DEPOSIT, 999);



// REPUTATION GAIN
define("REPUTATION_GAIN_DEPOSIT", 1);
define("REPUTATION_GAIN_ORDER_REJECTED", -1);
define("REPUTATION_GAIN_REFUND_ORDER", -1);
define("REPUTATION_GAIN_ORDER_REFUNDED", -1);
define("REPUTATION_GAIN_FAILED_TO_REJECT", -2);
define("REPUTATION_GAIN_FINALIZE", 1);
define("REPUTATION_GAIN_ACCEPT_ORDER", 1);


define("MAX_FILE_SIZE", 350000);
define("MAX_UNAPPROVED_LISTINGS_PER_USER", 20);
define("MAX_VENDOR_SECTIONS", 6);

// IMAGE DIMENSIONS
define("LISTING_IMAGES_MAX", 4);
define("LISTING_IMAGE_WIDTH", 375); // does not affect full size image
define("LISTING_IMAGE_HEIGHT", 287); // does not affect full size image
define("LISTING_IMAGE_THUMBNAIL_WIDTH", 79);
define("LISTING_IMAGE_THUMBNAIL_HEIGHT", 60);

define("LISTING_IMAGE_WIDTH_DISPLAYED", 1500);
define("LISTING_IMAGE_HEIGHT_DISPLAYED", 1148);

define("AVATAR_IMAGE_WIDTH", 150);
define("AVATAR_IMAGE_HEIGHT", 150);

define("AVATAR_IMAGE_SMALL_WIDTH", 100);
define("AVATAR_IMAGE_SMALL_HEIGHT", 100);

define("AVATAR_IMAGE_THUMBNAIL_WIDTH", 50);
define("AVATAR_IMAGE_THUMBNAIL_HEIGHT", 50);

define("IMAGE_MEDIUM_SUFFIX", '_medium');
define("IMAGE_SMALL_SUFFIX", '_small');
define("IMAGE_THUMBNAIL_SUFFIX", '_thumbnail');

define("VENDOR_LOGO_WIDTH", 392);
define("VENDOR_LOGO_HEIGHT", 100);

// FEES
define("MARKETPLACE_FEE", 0.04);

// TRANSACTION TIMEOUT
define("PENDING_CONFIRMATION_TIMEOUT_DAYS", 2);
define("PENDING_DEPOSIT_TIMEOUT_DAYS", 3);
define("PENDING_ACCEPT_TIMEOUT_DAYS", 3);
define("IN_TRANSIT_TIMEOUT_DAYS", 5);
define("REJECTED_TIMEOUT_DAYS", 5);
define("IN_DISPUTE_TIMEOUT_DAYS", 2);
define("PENDING_FEEDBACK_DAYS", 30);
define("CALL_MEDIATOR_DAYS", 3);
define("AUTO_FINALIZE_VENDOR_DAYS", 30);
define("AUTO_FINALIZE_BUYER_DAYS", 35);

define("PENDING_CONFIRMATION_TIMEOUT_MINUTES", 60);
define("PENDING_DEPOSIT_TIMEOUT_MINUTES", 90);
define("PENDING_DEPOSIT_CONFIRMATION_TIMEOUT_DAYS", 2);

define("PENDING_DEPOSIT_TIMEOUT_MINUTES_RENEWAL", 60);

define("REFRESH_PAYMENT_PAGE_SECONDS_AFTER_WINDOW_EXPIRY", 10);

define("PENDING_DEPOSIT_TIMEOUT_TOLERANCE_MINUTES", 20);
define("PENDING_DEPOSIT_TIMEOUT_REMAINING_MINUTES_DOUBLE_CHECK_BALANCE", 10);
define("FAILED_DEPOSIT_ASCERTAINMENT_WINDOW_MINUTES", 1440*2); // two days
define("FAILED_DEPOSIT_ASCERTAINMENT_WINDOW_MINUTES_EXTENDED", 1440*7); // two days
define("PENDING_CONFIRMATION_TIMEOUT_REMAINING_MINUTES_DOUBLE_CHECK_BALANCE", 1440);

define("EXPIRED_TRANSACTION_TIMEOUT_DAYS", 2);
define("EXPIRED_TRANSACTION_EXTENSION_DAYS", 3);

define("ALLOWED_TIME_TO_UNMARK_SHIPPED", 3600);

define("ALLOW_ORDER_PAYMENT_WINDOW_RENEWAL_MINUTES", 12*60); // 6 hours

define("DAYS_UNTIL_RATINGS_NO_LONGER_COUNT_IN_SCORE", 183);
define("VENDOR_AVERAGE_RATING_MINIMUM_RATINGS", 540);

define("UNWITHDRAWN_REFUND_TIMEOUT_TOLERANCE_DAYS", 30);

define("TRANSACTION_EVENTS_FLAG_PAID", 'paid');
define("TRANSACTION_EVENTS_FLAG_ACCEPTED", 'accepted');
define("TRANSACTION_EVENTS_FLAG_REJECTED", 'rejected');
define("TRANSACTION_EVENTS_FLAG_REFUNDED", 'refunded');
define("TRANSACTION_EVENTS_FLAG_FINALIZED", 'finalized');

define("TRANSACTION_IDENTIFIER_LENGTH", 10);

// STANDARD MESSAGES & NOTIFICATIONS
define("JAVASCRIPT_WARNING", "It appears your browser has javascript enabled. You are advised to <strong>disable javascript</strong> for security reasons. Click to learn how.");


/**
 * Configuration for: Error messages and notices
 *
 * In this project, the error messages, notices etc are all-together called "feedback".
 */
define("FEEDBACK_UNKNOWN_ERROR", "Unknown error occurred!");
define("FEEDBACK_PASSWORD_WRONG_3_TIMES", "You have typed in a wrong password 3 or more times already. Please wait 30 seconds to try again.");
define("FEEDBACK_PASSWORD_WRONG", "Password was wrong.");
define("FEEDBACK_USER_DOES_NOT_EXIST", "This user does not exist.");
// The "login failed"-message is a security improved feedback that doesn't show a potential attacker if the user exists or not
define("FEEDBACK_LOGIN_FAILED", "Incorrect username or password");
define("FEEDBACK_USERNAME_FIELD_EMPTY", "Invalid username. A valid username contains no spaces and consists entirely of alpha-numeric characters.");
define("FEEDBACK_PASSWORD_FIELD_EMPTY", "Password field was empty.");
define("FEEDBACK_USERNAME_SAME_AS_OLD_ONE", "That username is the same as your current one. Please choose another one.");
define("FEEDBACK_USERNAME_ALREADY_TAKEN", "That username is already taken. Please choose another one.");
define("FEEDBACK_USERNAME_CHANGE_SUCCESSFUL", "Your username has been changed successfully.");
define("FEEDBACK_USERNAME_AND_PASSWORD_FIELD_EMPTY", "Username and password fields were empty.");
define("FEEDBACK_USERNAME_WRONG", 'The username was incorrect');
define("FEEDBACK_CAPTCHA_WRONG", "Try that again.");
define("FEEDBACK_PASSWORD_REPEAT_WRONG", "Password and password repeat are not the same.");
define("FEEDBACK_PASSWORD_TOO_SHORT", "Password has a minimum length of 6 characters.");
define("FEEDBACK_USERNAME_TOO_SHORT_OR_TOO_LONG", "Username cannot be shorter than 2 or longer than 64 characters.");
define("FEEDBACK_ACCOUNT_CREATION_FAILED", "Your registration failed. Please go back and try again.");
define("FEEDBACK_PASSWORD_CHANGE_SUCCESSFUL", "Password successfully changed.");
define("FEEDBACK_PASSWORD_CHANGE_FAILED", "Your password changing failed.");
define("FEEDBACK_ACCOUNT_LOCKED_BRUTE", 'This account has been locked because of too many failed login attempts. Please wait before trying again.');
define("FEEDBACK_INVALID_HASH", 'Hash was not valid.');
define("FEEDBACK_ACCOUNT_DELETION_SUCCESS", 'Your account along with all traces of it\'s existence have been wiped from our servers permanently.');
define("FEEDBACK_ACCOUNT_DELETION_FAILED", 'Your account could not be deleted.');
define("FEEDBACK_INVALID_AUTHENTICATION_CODE", "Authentication code was incorrect. Re-decrypt and try again.");
define("FEEDBACK_UNSUPPORTED_PGP_PUBLIC_KEY", "This account has been locked due to suspicious login activity.");
define("FEEDBACK_FAILED_2FA_ATTEMPT", "Two-Factor Authentication Failed.");
define("FEEDBACK_ACCOUNT_BANNED", 'Your account has been banned');


define("VENDOR_APPLICATION_APPROVED_MESSAGE_UNFORMATTED", '[b]Welcome aboard![/b]' . PHP_EOL . PHP_EOL . 'It is with great pleasure that I inform you that your application for becoming a vendor has been accepted.' . PHP_EOL . PHP_EOL . 'You may now submit your listings to the marketplace and start selling.');
define("VENDOR_APPLICATION_UNSUCCESSFUL_UNFORMATTED", '[b]Unsuccessful vendor application[/b]' . PHP_EOL . PHP_EOL . 'We appreciate that you took the time to apply to become a vendor.' . PHP_EOL . PHP_EOL . 'After reviewing your submitted application materials, we have decided that we will not offer you a vendor account at this time.' . PHP_EOL . PHP_EOL . 'Please do apply again in the future should you feel that you have become more qualified.' . PHP_EOL . PHP_EOL . 'Again, thank you for applying. We wish you all the best.');

define("VENDOR_INACTIVITY_AUTO_VACATION_DAYS", 3);

define("LOCKDOWN_TEMPLATE", 'narrow');
define("FORUM_TEMPLATE", 'forum');

// AMOUNT TRANSACTED <--- all in EUR
define("AMOUNT_TRANSACTED_TRUSTED_VENDOR", 1000000); ## can ban a buyer
define("AMOUNT_TRANSACTED_SKIP_CAPTCHA", 0);

// FLOOD CONTROL <---- mostly in seconds
define("SEND_MESSAGE_MINIMUM_WAIT", 2);
define("ASK_QUESTION_MINIMUM_WAIT", 5);
define("MINIMUM_INTERVAL_BETWEEN_COMMENTS_MINUTES", 0);
define("CREATE_ORDER_MINIMUM_WAIT", 2);
define("EDIT_ORDER_MINIMUM_WAIT", 2);

define("UNENCRYPTED_MESSAGE_CACHE_EXPIRATION", 3600);
define("ELECTRUM_BALANCE_CACHE_EXPIRATION", 60);
define("ELECTRUM_UNSPENT_OUTPUTS_CACHE_EXPIRATION", 60);
define("CACHE_EXPIRATION_2FA_AUTHENTICATION_CODE", 300); // 5 minutes

define("WAIT_UNTIL_CAN_SEND_MESSAGE", 5); // 30 minutes

// FORUM STUFF
define("MAXIMUM_COMMENT_COUNT_USER_DELETABLE", 1);
define("USER_BLOG_POST_VISIBLE_ON_PROFILE_DAYS_SINCE_UPDATED", 14);
define("FORUM_ACCESS_PREFIX", 'forum');

// TIMEZONES
define("DEFAULT_TIMEZONE", 'Etc/UTC');
define("TIMEZONE_MESSAGES", DEFAULT_TIMEZONE);
define("TIMEZONE_CHAT_MESSAGES", DEFAULT_TIMEZONE);

// REGULAR EXPRESSIONS
define("REGEX_USERNAME", "^[0-9a-zA-Z]+_?[0-9a-zA-Z]+$");

define("REGEX_PGP_ENCRYPTED_MESSAGE", '/-----BEGIN PGP MESSAGE-----/');

define("REGEX_PGP_MESSAGE_ONLY", '#^\s*-----BEGIN\sPGP\s(?:(?:SIGNED\s)?MESSAGE|PUBLIC KEY BLOCK)-----.+-----END\sPGP\s(?:SIGNATURE|MESSAGE|PUBLIC KEY BLOCK)-----\s*$#s');

define("REGEX_PGP_MESSAGE_WITH_BOLD_SUBJECT", "#^\s*\[b\]((?:(?!\[\/b).)*)\[\/b]\s*-----BEGIN\sPGP(?:\sSIGNED)?\sMESSAGE-----.+-----END\sPGP\s(?:SIGNATURE|MESSAGE)-----\s*$#s");
define("REGEX_PGP_MESSAGE_WITH_BOLD_SUBJECT_SEARCH", "#^\[b\]((?:(?!\[\/b).)*)\[\/b]\s*(.+)#s");
define("REGEX_PGP_MESSAGE_WITH_BOLD_SUBJECT_REPLACE", "[b]$1[/b][pgp]$2[/pgp]");

define("REGEX_PGP_CONTENT", '#^(-----BEGIN PGP (?:(?:SIGNED )?MESSAGE|PUBLIC KEY BLOCK)-----(?:(?!-----END PGP (?:MESSAGE|SIGNATURE|PUBLIC KEY BLOCK)-----).)*-----END PGP (?:MESSAGE|SIGNATURE|PUBLIC KEY BLOCK)-----)$#smi');

//define("REGEX_MESSAGE_SUBJECT_PATTERN", "^(?:\[a=[&#39;&quot;]?[\w\/]+[&#39;&quot;]?\])?.{1," . MAX_LENGTH_MESSAGE_SUBJECT . "}(?:\[\/a\])?$");

define("REGEX_BLOG_POST_TITLE", "^[\w][^\n]{1," . (MAX_LENGTH_DISCUSSION_TITLE - 1) . "}");

define("REGEX_BITCOIN_ADDRESS", "\w{26,63}");

define("REGEX_MIXED_CASE_STRING", "/^(?=.*?[a-z])(?=.*?[A-Z]).+$/");
define("REGEX_TITLE_CASE_WORDS_CAPTURE_WORDS", '/([A-Z][a-z]+|(?:(?![A-Z][a-z]).)+)/');

define("REGEX_LISTING_QUANTITY_EXTRACT_NUMBER_UNIT", '/^(\d+\.\d+)\s*(\w+)$/');

define("REGEX_URL_SAFE", '/^[\w\-\/#]+$/');

define("REGEX_CRYPTOCURRENCY_EXTENDED_PUBLIC", '^[xyz]pub.+');
define("REGEX_CRYPTOCURRENCY_ADDRESS", "^\w{26,43}$");
define("REGEX_CRYPTOCURRENCY_PUBLIC_KEY", "^0(?:[23][0-9a-z]{64}|4[0-9a-z]{128})$");
define("REGEX_CRYPTOCURRENCY_TRANSACTION_HASH", '/^[0-9a-f]{64}$/');
define("REGEX_CRYPTOCURRENCY_RAW_TRANSACTION", '/^[0-9a-f]{2,}$/');

define("REGEX_HYPERLINK", '/(https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b[-a-zA-Z0-9@:%_\+.~#?&\/\/=;]*)/');
define("REGEX_HYPERLINK_REPLACE", '[a=$1]$1[/a]');

// GRANULARITIES
define("GRANULARITY_VENDOR_LAST_SEEN_HOURS", 24); // Rounded up to nearest multiple of x


// INVITES
define("INVITE_REQUEST_SITE_URL", 'http://aaa.onion/');
define("INVITES_VENDORS_TOP_UP_QUANTITY", 100); // Invites generated until vendor has this number of unissued invites
define("INVITES_PER_PAGE", 100);


// SUPPORT / CHAT
define("CHAT_ROLE_SUPPORT", 'support');
define("CHAT_ROLE_COLOR_SUPPORT", 'green');
define("CHAT_ROLE_SUBJECT", 'subject');

define("CHAT_EVENT_TYPE_ID_USER_JOINED", 1);
define("CHAT_EVENT_TYPE_ID_STATUS_CHANGED", 2);

define("CHAT_EVENT_TYPE_PARAMETER_FLAG_PREFIX", 'CHAT_EVENT_TYPE_PARAMETER_');
define("CHAT_EVENT_TYPE_PARAMETER_SUBJECT_USER_ALIAS", 'SubjectUserAlias');
define("CHAT_EVENT_TYPE_PARAMETER_STATUS_TITLE", 'StatusTitle');

define("CHAT_STATUS_FLAG_PREFIX", 'CHAT_STATUS_ID_');
define("CHAT_STATUS_ID_OPEN", 1);
define("CHAT_STATUS_ID_ONGOING", 2);
define("CHAT_STATUS_ID_IMPORTANT", 3);
//define("CHAT_STATUS_ID_URGENT", 4);
define("CHAT_STATUS_ID_CLOSED", 4);
define("CHAT_STATUS_ID_INITIAL_USER", CHAT_STATUS_ID_OPEN);
define("CHAT_STATUS_ID_INITIAL_MOD", CHAT_STATUS_ID_ONGOING);

define("CHAT_MESSAGES_COLOR_UNREAD", 'yellow');

define("CHAT_MESSAGES_SORT_MODE_ID_DESC", 'id_desc');
define("CHAT_MESSAGES_SORT_MODE_DEFAULT", CHAT_MESSAGES_SORT_MODE_ID_DESC);

define("CHAT_MESSAGES_ENTRIES_PER_PAGE_DEFAULT", 15);
define("CHAT_MESSAGES_DATE_FORMAT", 'Y-n-j');
define("CHAT_MESSAGES_TIME_FORMAT", 'g:ia T');

define("CHAT_MESSAGE_ENTRY_TYPE_MESSAGE", 'message');
define("CHAT_MESSAGE_ENTRY_TYPE_EVENT", 'event');
define("CHAT_MESSAGE_ENTRY_TYPE_NOTE", 'note');

define("SUPPORT_OVERVIEW_DEFAULT_FILTER_MODE", 'open');
define("SUPPORT_OVERVIEW_FILTER_MODE_ALL", 'all');
define("SUPPORT_OVERVIEW_DEFAULT_SORT_MODE", 'priority_desc');
define("SUPPORT_OVERVIEW_DEFAULT_CHAT_MESSAGE_QUANTITY", 5);
define("SUPPORT_OVERVIEW_DEFAULT_CHAT_MESSAGE_SORT_MODE", CHAT_MESSAGES_SORT_MODE_DEFAULT);
define("SUPPORT_OVERVIEW_CHATS_PER_PAGE", 30);

define("SUPPORT_INFO_TITLE", 'Get Support');
define("SUPPORT_INFO_TITLE_TRANSACTIONS", 'Need help with this order?');
define("SUPPORT_INFO_BODY", "<p>Before contacting support, please check if your question has been answered in the <a href='/faq/'>FAQ</a>.</p>");
define("SUPPORT_INFO_BODY_BUYER", "<p>Before contacting support, please check these resources for an answer to your question:</p><ul><li><a href='/faq/'>FAQs</a></li><li><a href='/p/buyerwiki/'>Wiki</a></li><li><a target='_blank' href='/go/forum/'>Forum Stickies</a></li></ul>");
define("SUPPORT_INFO_BODY_VENDOR", "<p>Before contacting support, please check these resources for an answer to your question:</p><ul><li><a href='/faq/'>FAQs</a></li><li><a href='/p/vendorwiki/'>Wiki</a></li><li><a target='_blank' href='/go/forum/'>Forum Stickies</a></li></ul>");

define("SUPPORT_INFO_STATUS_CHANGED_ONGOING", '<p class="color-green"><strong>Thank you for your support request.</strong></p><p>A member of our support team will get back to you within 8-10 hours or sooner. In the meantime, feel free to continue browsing our site.</p><p>This support pane will fly out automatically when you receive a new support message.</p>');

define("CHAT_BUTTON_COLOR_UNREAD", 'red');
define("CHAT_BUTTON_COLOR_UNANSWERED", 'green');
define("CHAT_BUTTON_COLOR_NOTHING", 'grey');
define("CHAT_BUTTON_COLOR_NON_MODS", 'red');
define("CHAT_BUTTON_COLOR_IMPORTANT", 'orange');
define("CHAT_BUTTON_COLOR_DISPUTES", 'blue');

define("SUPPORT_TRANSACTION_PANEL_TRANSACTION_MAX_AGE_MONTHS", 6);
define("SUPPORT_TRANSACTION_PANEL_BLOCK_EXPLORER_URL_PREFIX_ADDRESS", "http://explorernuoc63nb.onion/address/");
define("SUPPORT_TRANSACTION_PANEL_BLOCK_EXPLORER_SUFFIX", '');
define("SUPPORT_TRANSACTION_PANEL_LTC_BLOCK_EXPLORER_URL_PREFIX_ADDRESS", "https://insight.litecore.io/address/");		

## CRON & MEMCACHED
define("CRON_SECRET_PARAMETER_KEY", 'cron');
define("CRON_SECRET_PARAMETER_VALUE", "9AKX7Fsbc3646C4HJxu7HQFu");

define("CRON_SCHEDULE_EVERY_MINUTE", "oneMinute");
define("CRON_SCHEDULE_EVERY_2_MINUTES", 'twoMinutes');
define("CRON_SCHEDULE_EVERY_10_MINUTES", 'tenMinutes');
define("CRON_SCHEDULE_EVERY_15_MINUTES", 'fifteenMinutes');
define("CRON_SCHEDULE_EVERY_30_MINUTES", 'halfHour');
define("CRON_SCHEDULE_EVERY_HOUR", 'oneHour');
define("CRON_SCHEDULE_EVERY_DAY", 'oneDay');

define("CRON_UNRESPONSIVE_CRON_RUNNER_MAX_INTERVAL_MINUTES", 15);

//define("CRON_METHOD_GET_USER_INFO", 'getUserInfo');

define("CRON_AUTO_DELETE_ALL_MESSAGES_OLDER_THAN_DAYS", 60);

define("CRON_UPDATE_USER_INFO_CACHE_INACTIVITY_LIMIT_SECONDS", 3600*24); //1 day
define("CRON_UPDATE_USER_INFO_CACHE_MAX_ITERATION_COUNT", 720); // 1 day

define("CRON_UPDATE_EXCHANGE_RATES_API_URL_EUROPEAN_CENTRAL_BANK", 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');

define("CRON_UPDATE_EXCHANGE_RATES_API_URL_BLOCKCHAIN", 'https://blockchainbdgpzk.onion/ticker');
define("CRON_UPDATE_EXCHANGE_RATES_API_URL_PROPRIETARY_USD_PER_EUR", 'http://c5fe2odojmiuercn.onion/eurusd');
define("CRON_UPDATE_EXCHANGE_RATES_API_URL_PROPRIETARY_USD_PER_BTC", 'http://c5fe2odojmiuercn.onion/api');

define("CRON_UPDATE_EXCHANGE_RATES_API_URL_BTC_COINMARKETCAP", 'https://api.coinmarketcap.com/v1/ticker/bitcoin/?convert=EUR');
define("CRON_UPDATE_EXCHANGE_RATES_API_URL_BTC_CRYPTOCOMPARE", 'https://min-api.cryptocompare.com/data/price?fsym=BTC&tsyms=USD,EUR');
define("CRON_UPDATE_EXCHANGE_RATES_API_URL_BTC_SOCHAIN", 'https://chain.so/api/v2/get_price/BTC/EUR');
define("CRON_UPDATE_EXCHANGE_RATES_API_URL_LTC_COINMARKETCAP", 'https://api.coinmarketcap.com/v1/ticker/litecoin/?convert=EUR');
define("CRON_UPDATE_EXCHANGE_RATES_API_URL_LTC_CRYPTOCOMPARE", 'https://min-api.cryptocompare.com/data/price?fsym=LTC&tsyms=BTC,USD,EUR');             
define("CRON_UPDATE_EXCHANGE_RATES_API_URL_LTC_SOCHAIN", 'https://chain.so/api/v2/get_price/LTC/EUR');
define("CRON_UPDATE_EXCHANGE_RATES_API_URL_LTC_CRYPTONATOR", 'https://api.cryptonator.com/api/ticker/ltc-eur');             
define("CRON_UPDATE_EXCHANGE_RATES_MAXIMUM_RELATIVE_DIFFERENCE", 0.2);

define("CRON_RETRIEVE_BITCOIN_FEE_ESTIMATES_API_URL", 'https://bitcoinfees.earn.com/api/v1/fees/list');

define("MEMCACHED_KEY_PREFIX_USER_INFO", 'userInfo');
define("MEMCACHED_KEY_SUFFIX_ITERATION_COUNT", '-iterationCount');
define("MEMCACHED_CACHE_EXPIRATION_SECONDS_USER_INFO", 180); // 3 minutes
define("MEMCACHED_CACHE_EXPIRATION_SECONDS_USER_INFO_CRON", 3720); // 62 minutes

define("MEMCACHED_FRONTPAGE_LISTINGS_EXPIRATION", 1800); // 30 mins

# MESSAGES
define("MESSAGES_TIME_FORMAT", 'j F Y — g:ia T');
define("MESSAGES_TIME_FORMAT_ADMIN", 'j F Y');

# API
define("API_QUERY_CHAT_MESSAGES_QUANTITY", 10);

# VENDOR LOGOS
define("VENDOR_LOGO_BANNER_NEW_LISTINGS_TAG", "New Listings");
define("VENDOR_LOGO_BANNER_NEW_LISTINGS_INTERVAL_DAYS", 2);

# VARIOUS DEFAULTS
define("DEFAULT_DECIMALS", 2);
define("DEFAULT_DECIMAL_SEPARATOR", '.');
define("DEFAULT_THOUSANDS_SEPARATOR", '');

# LISTING GROUPS
define("LISTING_GROUP_SETTING_SYNCHRONIZE_IMAGES_DB_COLUMN", 'SynchronizeImages');
define("LISTING_GROUP_SETTING_SYNCHRONIZE_DESCRIPTIONS_DB_COLUMN", 'SynchronizeDescriptions');
define("LISTING_GROUP_SETTING_SYNCHRONIZE_SHIPPING_DB_COLUMN", 'SynchronizeShipping');
define("LISTING_GROUP_SETTING_SYNCHRONIZE_STOCK_DB_COLUMN", 'SynchronizeStock');

define("LISTING_GROUP_LABEL_MAX_LENGTH", 25);

define("LISTING_CAN_CHANGE_TITLE_INTERVAL_DAYS", 7);

# HTML ENTITIES
define("ENTITY_BITCOIN_SYMBOL", '&#579;');
define("ENTITY_LITECOIN_SYMBOL", '&#321;');
define("ENTITY_TILDE", '&#126;');

# DEFAULT PAGES
define("PAGE_MULTISIG_SETUP", 'multisig');
define("PAGE_TRANSACTION_SIGNING_TUTORIAL", 'transactions');
define("PAGE_BITCOIN_MINING_FEES", 'txfees');
define("PAGE_BITCOIN_WALLETS", 'electrum');

# DIMENSIONS AND UNITS
define("DIMENSION_ID_MASS", 1);
define("DIMENSION_ID_AMOUNT", 2);
define("DIMENSION_ID_VOLUME", 3);

define("BASE_DIMENSION_MASS", 'g');
define("BASE_DIMENSION_VOLUME", 'ml');

define("UNIT_ID_KILOGRAM", 1);
define("UNIT_ID_GRAM", 2);
define("UNIT_ID_OUNCE", 3);
define("UNIT_ID_POUND", 4);
define("UNIT_ID_PIECE", 5);

define("ORDER_QUANTITY_DROPDOWN_OPTIONS_QUANTITY", 20);

# PROMOTIONAL CODES
define("LISTING_PROMOTIONAL_CODE_LENGTH_MAX", 10);
define("LISTING_PROMOTIONAL_CODE_PLACEHOLDER", 'abcde12345');

define("LISTING_PROMOTIONAL_CODE_DISCOUNT_MIN", 1);
define("LISTING_PROMOTIONAL_CODE_DISCOUNT_MAX", 50000);
define("LISTING_PROMOTIONAL_CODE_DISCOUNT_STEP", 1);

define("LISTING_PROMOTIONAL_CODE_QUANTITY_MIN", 0);
define("LISTING_PROMOTIONAL_CODE_QUANTITY_MAX", 65535); // SMALLINT UNSIGNED

define("TRANSACTION_APPLY_PROMOTIONAL_CODE_FEEDBACK_CODE_INVALID", 'That code was invalid or has already been used.');

# ICONS
define("ICONS_INDEX_CLASS", 0);
define("ICONS_INDEX_ENTITY", 1);
define("ICONS_PREFIX", "icon-");
define("SPRITES_PREFIX", "sprite-");

# ADMIN
define("ADMIN_ANALYTICS_AUTO_REFRESH_SECONDS", 60);
define("ADMIN_ANALYTICS_GRAPH_ROLLING_REVENUES_INTERVAL_DAYS", 30);

# LOCALES AND SHIPPING FILTERS
define("LOCALE_DEFAULT_ID", 1); // UNITED STATES

define("SHIPPING_FILTER_DELIMITER", "-");
define("SHIPPING_FILTER_PREFIX_LOCALE", "locale");
define("SHIPPING_FILTER_PREFIX_COUNTRY", "country");

# NOTIFICATIONS
define("USER_NOTIFICATION_TYPEID_UNREAD_MESSAGES", 1);
define("USER_NOTIFICATION_TYPEID_PENDING_TRANSACTIONS", 2);
define("USER_NOTIFICATION_TYPEID_TRANSACTION_IN_DISPUTE", 3);
define("USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_FEEDBACK", 4); // only for buyers
define("USER_NOTIFICATION_TYPEID_TRANSACTION_FINALIZED_PENDING_WITHDRAWAL", 5);
define("USER_NOTIFICATION_TYPEID_UNREAD_FORUM_SUBSCRIPTIONS", 6);
define("USER_NOTIFICATION_TYPEID_TRANSACTION_PENDING_ACCEPT", 7);
define("USER_NOTIFICATION_TYPEID_TRANSACTION_REFUNDED_PENDING_WITHDRAWAL", 8);
define("USER_NOTIFICATION_TYPEID_TRANSACTION_ACCEPTED", 9); // only for buyers, only decremented when dismissed
define("USER_NOTIFICATION_TYPEID_LISTING_NEW_QUESTION", 10);
define("USER_NOTIFICATION_TYPEID_TRANSACTION_BROADCAST_UNSUCCESSFUL", 11);
define("USER_NOTIFICATION_TYPEID_TRANSACTION_STATUS_CHANGED", 12);
define("USER_NOTIFICATION_TYPEID_LISTING_OUT_OF_STOCK", 13);


define("USER_NOTIFICATION_RECALLIBRATION_CHUNK_SIZE", 3);


# TRANSACTIONS
define("TRANSACTIONS_DEFAULT_SORTING_MODE", 'id_desc');
define("TRANSACTIONS_OVERVIEW_VIEW_ALL_MAXIMUM_AGE_MONTHS", 3);
define("TRANSACTIONS_OVERVIEW_VIEW_ALL_MAXIMUM_AGE_UNPAID_HOURS", 24);
define("TRANSACTIONS_BUYER_VIEW_PAST_TIMEOUT_DAYS", 2);

define("TRANSACTIONS_COLOR_CODE_STATE_IN_DISPUTE", 'purple');
define("TRANSACTIONS_COLOR_CODE_STATE_PENDING_REFUND", 'red');
define("TRANSACTIONS_COLOR_CODE_STATE_PENDING_WITHDRAW", 'orange');
define("TRANSACTIONS_COLOR_CODE_STATE_PENDING_ACCEPT", 'green');

# PRIVATE DOMAIN SCHEME
define("PRIVATE_DOMAINS_BUYER_CRITERION_MINIMUM_TRANSACTED_EUR", 90);

define("PRIVATE_DOMAINS_MAXIMUM_PER_USER", 3);
define("PRIVATE_DOMAINS_STATE_UNGRANTED", 0);
define("PRIVATE_DOMAINS_STATE_NORMAL_GRANTED", 1);
define("PRIVATE_DOMAINS_STATE_RECENTLY_GRANTED", 2);
define("PRIVATE_DOMAINS_STATE_DOMAINS_CHANGED", 3);

define("ZERO_PRICE_TEXTUAL_REPLACEMENT", 'FREE');

// CURRENCIES & CRYPTOCURRENCIES
define("CURRENCY_ID_BTC", 1);
define("CURRENCY_ID_USD", 3);
define("CURRENCY_ID_LTC", 2);

define('BITCOIN_SMALLEST_INCREMENT_DISCERNED', 0.00001);
define('BITCOIN_DECIMAL_PLACES', 5);
define('BITCOIN_FLOAT_ROUNDING_COEFFICIENT', 1e5);

define('MINER_FEE', 0.001);

define('BITCOIN_FEE_LEVEL_FASTEST', 1);
define('BITCOIN_FEE_LEVEL_LOWEST', 25);
define('BITCOIN_FEE_LEVEL_DEFAULT', BITCOIN_FEE_LEVEL_LOWEST);
define('BITCOIN_FEE_LEVEL_CPFP_TARGET', 2);
define('BITCOIN_TRANSACTION_AVERAGE_SIZE_KB', 0.32);
define('BITCOIN_TRANSACTION_MEDIAN_P2PKH_SIZE_KB', 0.226);

define('BITCOIN_FEE_LOW_LOWEST_FEE_LEVEL_COEFFICIENT', 0.5);

/**
* Empirical function to estimate the signed transaction size
* given unsigned tx size, number of inputs and number of signers
*
* 	signedTxSize = ceil(unsignedTxSize + a*(numInputs*numSigs)^b)
*		a = empirical coefficient
*		b = empirical exponent
*
*/

define('BITCOIN_TRANSACTION_SIZE_ESTIMATION_EMPIRICAL_COEFFICIENT', 76);
define('BITCOIN_TRANSACTION_SIZE_ESTIMATION_EMPIRICAL_EXPONENT', 0.965);

define('CRYPTOCURRENCIES_PREFIX_PUBLIC_BITCOIN', '00');
define('CRYPTOCURRENCIES_PREFIX_PUBLIC_LITECOIN', '30');

define('CRYPTOCURRENCIES_PREFIX_SCRIPT_HASH_LITECOIN', '32');

define(
	"CRYPTOCURRENCIES_NAMES",
	[
		'BTC' => 'Bitcoin',
		'LTC' => 'Litecoin'
	]
);
define(
	"CRYPTOCURRENCIES_IDS",
	[
		'BTC' => CURRENCY_ID_BTC,
		'LTC' => CURRENCY_ID_LTC
	]
);
define(
	"CRYPTOCURRENCIES_HRPS",
	[
		'BTC' => 'bc',
		'LTC' => 'ltc'
	]
);

define("CRYPTOCURRENCIES_CRYPTOCURRENCY_ID_DEFAULT", CURRENCY_ID_BTC);
define("CRYPTOCURRENCIES_PREFIX_PUBLIC_DEFAULT", CRYPTOCURRENCIES_PREFIX_PUBLIC_BITCOIN);

define("CRYPTOCURRENCIES_FEE_LEVEL_FASTEST", 1);
define("CRYPTOCURRENCIES_FEE_LEVEL_LOWEST", 25);
define("CRYPTOCURRENCIES_FEE_LEVEL_DEFAULT", CRYPTOCURRENCIES_FEE_LEVEL_LOWEST);

define("CRYPTOCURRENCIES_FEE_ESTIMATION_CONNECTION_ATTEMPTS", 3);

define("ORDER_VIEW_ADVANCED_DEFAULT_ENABLED", false);
define(
	"ORDER_VIEW_ADVANCED_DEFAULT_ITEMS_PER_PAGE_OPTIONS",
	[
		5,
		10,
		20,
		50
	]
);
define("ORDER_VIEW_ADVANCED_DEFAULT_ITEMS_PER_PAGE", 10);

define(
	"NEW_MEMBER_WELCOME_MESSAGE",
	"[size=120][b]Welcome to CannaHome![/b][/size]" . PHP_EOL . PHP_EOL .
	"Before you make your first order on our site, you will need to learn some things about how our ordering system works." . PHP_EOL . PHP_EOL .
	"Trust us, it will save you time and help you to avoid confusion and frustration later." . PHP_EOL . PHP_EOL .
	"[hr]" . PHP_EOL . PHP_EOL .
	"[b]Select which of the following best describes your level of knowledge and experience with darknet markets : [/b]" . PHP_EOL . PHP_EOL .
	"- [a=/p/new-to-darknet/]I have very little or no experience with darknet markets. I need to learn the basics.[/a]" . PHP_EOL .
	"- [a=/p/new-to-canna-home/]I am an experienced darknet buyer at other markets. I need a quick summary of what's different here.[/a]" . PHP_EOL . PHP_EOL .
	"[size=90]Click on the option that best describes your situation to view the corresponding article in our \"Buyer's Wiki\".[/size]"
);

define(
	"STAR_MEMBERS_WELCOME_MESSAGE",
	"[b]Congratulations! You are now a [u]Star member[/u] at Home![/b]" . PHP_EOL . PHP_EOL .
	"We award Star status to buyers with perfect ratings who have been active members of Home for at least 90 days." . PHP_EOL . PHP_EOL .
	"There is a special forum section for star-members only. It is only visible to star-members and admin staff — not vendors. So feel free to have open discussions with other star-members in the forum." . PHP_EOL . PHP_EOL .
	"Star-members also play an important role in helping keep Home safe. New vendors must be nominated by a star-member. We rely on you to help keep the vendor quality at Home at the highest levels. You can read more about how the nominating process works in the [a=/go/forum/discussions/stars/]star-only forum section[/a]." . PHP_EOL . PHP_EOL .
	"Thanks for being a loyal and trusted Home member." . PHP_EOL . PHP_EOL .
	"Cheers," . PHP_EOL .
	"[b][i]Finn[/i][/b]" . PHP_EOL . PHP_EOL .
	"[size=80]This is an automated message. Replies will not be read.[/size]"
);

# Iterative Redirect
define("ITERATIVE_REDIRECT_WAIT_SECONDS_DEFAULT", 7);
define("ITERATIVE_REDIRECT_SESSION_KEY", 'iterativeRedirect');
define("ITERATIVE_REDIRECT_SESSION_KEY_PARAMETERS", 0);
define("ITERATIVE_REDIRECT_SESSION_KEY_REQUEST_BODY", 1);


define("ACCOUNT_HOMEPAGE_MAX_FORUM_ENTRIES", 5);

define("ACCOUNT_STATISTICS_DEFAULT_QUERY_IDENTIFIER", 'overview');

# USER CLASSES
define("USER_CLASS_ID_STAR_BUYERS", 3);
define("USER_CLASS_TEXT_MAX_LENGTH", 20);

define("USER_CLASS_PRIVILEGES_UPLOADS_INTERVAL", 60*60*24);
define("USER_CLASS_PRIVILEGES_AVATAR_WIDTH_STAR_BUYERS", 50);
define("USER_CLASS_PRIVILEGES_AVATAR_HEIGHT_STAR_BUYERS", 50);

define("FORUM_REVIEW_LABEL", 'Verified Review');
define("FORUM_REVIEWS_CATEGORY_ID", 2);
define("FORUM_DISCUSSION_TYPEID_DISCUSSION", 1);
define("FORUM_DISCUSSION_TYPEID_LISTING", 2);

define("FORUM_VENDOR_NOMINATION_TYPE_ID", 3);
define("FORUM_VENDOR_NOMINATION_LABEL", 'Nomination');
define("FORUM_VENDOR_NOMINATION_COLOR", 'blue');
define("FORUM_VENDOR_NOMINATION_FOOTER", '<pre><strong>Buyer feedback &amp; comments requested.</strong><br>Any Home member can comment on this nomination. The comment period is open for seven days. This is your chance to be heard. If you have experience with this vendor, we want to hear from you.</pre>');

define("FORUM_REPORTED_DISCUSSION_COLOR", 'red');

define("FORUM_MAX_IMAGES_PER_DISCUSSION_COMMENT", 3);
define("FORUM_MAX_UPLOAD_PER_DAY", 10);

define(
	"FORUM_MAX_UPLOAD_PER_DAY_BY_RANK",
	[
		1 => 10,
		2 => 20,
		3 => 20
	]
);

define("FORUM_STAR_MEMBER_PRIVILEGES_EDIT_OWN_FLAIR_RANK", 2);
define("FORUM_STAR_MEMBER_PRIVILEGES_ADD_NEW_FLAIR_RANK", 3);

define("USER_ONLINE_LAST_SEEN_MINUTES", 15);


define("MAX_VISIBLE_RATINGS_DEFAULT", 5000);
define("MAX_VISIBLE_INDIVIDUAL_RATINGS", 1000);
define("MAX_AGE_VISIBLE_TRANSACTION_COMMENTS_MONTHS", 6);

define("INCOGNITO_ACCESS_PREFIX", 'i');
define("INCOGNITO_SITE_TITLE", 'Premium Ecommerce Wordpress Template DEMO');


define("USER_LAST_SEEN_REFRESH_FREQUENCY_SECONDS", 300);

define("DATABASE_MEMCACHED_DEFAULT_EXPIRY", 1); //60*10); // 10 minutes

define("DATABASE_MEMCACHED_KEY_CURRENCIES", 'db_currencies');

define("SIGNING_ACCOUNT_INDEX", 2);


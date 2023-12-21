<?php

/**
 * All routes related to user profile
 * Grouped under the prefix "profile" and under auth middleware
 */
Route::prefix('profile')->group(function(){

    Route::get('index','ProfileController@index')->name('profile.index');
    Route::post('changepassword', 'ProfileController@changePassword')-> name('profile.password.change'); // change password route
    Route::get('2fa/{turn}', 'ProfileController@change2fa') -> name('profile.2fa.change'); // change 2fa

    // add or remove to whishlist
    Route::get('add/wishlist/{product}', 'ProfileController@addRemoveWishlist') -> name('profile.wishlist.add');
    Route::get('wishlist', 'ProfileController@wishlist') -> name('profile.wishlist');

    // PGP routes
    Route::get('pgp', 'ProfileController@pgp') -> name('profile.pgp');
    Route::post('pgp', 'ProfileController@pgpPost') -> name('profile.pgp.post');
    Route::get('pgp/confirm', 'ProfileController@pgpConfirm') -> name('profile.pgp.confirm');
    Route::post('pgp/confirm', 'ProfileController@storePGP') -> name('profile.pgp.store');
    Route::get('pgp/old', 'ProfileController@oldpgp') -> name('profile.pgp.old');

    Route::get('become/vendor', 'ProfileController@becomeVendor') -> name('profile.vendor.become');
    Route::get('become', 'ProfileController@become') -> name('profile.become');

    Route::post('vendor/address', 'ProfileController@changeAddress') -> name('profile.vendor.address'); // add address to account
    Route::get('vendor/address/remove/{id}', 'ProfileController@removeAddress') -> name('profile.vendor.address.remove'); // add address to account

    // Vendor routes
    Route::get('vendor', 'VendorController@vendor') -> name('profile.vendor');
    Route::post('vendor/update/profile','VendorController@updateVendorProfilePost')-> name('profile.vendor.update.post');
    
    // Digital options
    Route::get('vendor/product/digital/add', 'VendorController@addDigitalShow') -> name('profile.vendor.product.digital');
    Route::post('vendor/product/digital/add/{product?}', 'VendorController@addDigital') -> name('profile.vendor.product.digital.post');

    // Product add basic info
    Route::get('vendor/product/add/{type?}', 'VendorController@addBasicShow') -> name('profile.vendor.product.add');
    Route::post('vendor/product/adding/{product?}', 'VendorController@addShow') -> name('profile.vendor.product.add.post');
    
    // Add remove offers
    Route::get('vendor/product/offers/add', 'VendorController@addOffersShow') -> name('profile.vendor.product.offers');
    Route::post('vendor/product/offers/new/{product?}', 'VendorController@addOffer') -> name('profile.vendor.product.offers.add'); // add offer
    Route::get('vendor/product/offers/remove/{quantity}/{product?}', 'VendorController@removeOffer') -> name('profile.vendor.product.offers.remove'); // add offer
    
    // Delivery
    Route::get('vendor/product/delivery/add', 'VendorController@addDeliveryShow') -> name('profile.vendor.product.delivery');
    Route::post('vendor/product/delivery/add/{product?}', 'VendorController@newShipping') -> name('profile.vendor.product.delivery.new');
    Route::post('vendor/product/delivery/options/{product?}', 'VendorController@newShippingOption') -> name('profile.vendor.product.delivery.options');
    Route::get('vendor/product/delivery/remove/{index}/{product?}', 'VendorController@removeShipping') -> name('profile.vendor.product.delivery.remove');

    // Images section
    Route::get('vendor/product/images/add', 'VendorController@addImagesShow') -> name('profile.vendor.product.images');
    Route::get('vendor/product/images/remove/{id}/{product?}', 'VendorController@removeImage') -> name('profile.vendor.product.images.remove');
    Route::get('vendor/product/images/default/{id}/{product?}', 'VendorController@defaultImage') -> name('profile.vendor.product.images.default');
    Route::post('vendor/product/images/add/{product?}', 'VendorController@addImage') -> name('profile.vendor.product.images.post'); // new image

    // New product
    Route::post('vendor/product/post', 'VendorController@newProduct') -> name('profile.vendor.product.post');
    
    // Delete product
    Route::get('vendor/product/{id}/delete/confirmation', 'VendorController@confirmProductRemove') -> name('profile.vendor.product.remove.confirm');
    Route::get('vendor/product/{id}/delete', 'VendorController@removeProduct') -> name('profile.vendor.product.remove');

    // Edit Product
    Route::get('vendor/product/edit/{id}/section/{section?}', 'VendorController@editProduct') -> name('profile.vendor.product.edit');

    // Sales routes
    Route::get('sales/{state?}', 'VendorController@sales') -> name('profile.sales');
    Route::get('sale/{sale}', 'VendorController@sale') -> name('profile.sales.single');
    Route::get('sales/{sale}/sent/confirm', 'VendorController@confirmSent') -> name('profile.sales.sent.confirm');
    Route::get('sale/{sale}/sent', 'VendorController@markAsSent') -> name('profile.sales.sent');

    // Cart routes
    Route::get('cart', 'ProfileController@cart') -> name('profile.cart');
    Route::post('cart/{product}/add', 'ProfileController@addToCart') -> name('profile.cart.add');
    Route::get('cart/clear', 'ProfileController@clearCart') -> name('profile.cart.clear');
    Route::get('cart/remove/{product}', 'ProfileController@removeProduct') -> name('profile.cart.remove');
    Route::get('checkout', 'ProfileController@checkout') -> name('profile.cart.checkout');
    Route::get('make/purchase', 'ProfileController@makePurchases') -> name('profile.cart.make.purchases');

    // Purchases routes
    Route::get('purchases/{state?}', 'ProfileController@purchases') -> name('profile.purchases');
    Route::get('purchases/{purchase}/message', 'ProfileController@purchaseMessage') -> name('profile.purchases.message');
    Route::get('purchase/{purchase}', 'ProfileController@purchase') -> name('profile.purchases.single');
    Route::get('purchase/{purchase}/delivered/confirm', 'ProfileController@deliveredConfirm') -> name('profile.purchases.delivered.confirm');
    Route::get('purchase/{purchase}/delivered', 'ProfileController@markAsDelivered') -> name('profile.purchases.delivered');

    // canceled for both Vendor and Buyer
    Route::get('purchase/{purchase}/canceled/confirm', 'ProfileController@confirmCanceled') -> name('profile.purchases.canceled.confirm');
    Route::get('purchase/{purchase}/canceled', 'ProfileController@markAsCanceled') -> name('profile.purchases.canceled');

    // Purchase - Disputes
    Route::post('purchase/{purchase}/dispute', 'ProfileController@makeDispute') -> name('profile.purchases.dispute');
    Route::post('purchase/dispute/{dispute}/new/message', 'ProfileController@newDisputeMessage') -> name('profile.purchases.disputes.message');
    Route::post('purchase/{purchase}/dispute/resolve', 'Admin\AdminController@resolveDispute') -> name('profile.purchases.disputes.resolve');

    // Purchase - Feedbacks
    Route::post('purchase/{purchase}/feedback/new', 'ProfileController@leaveFeedback') -> name('profile.purchases.feedback.new');

    /**
     * Messages
     */
    Route::middleware(['can_read_messages'])->group(function () {
        Route::get('messages/{conversation?}', 'MessageController@messages') -> name('profile.messages');
        Route::post('messages/conversation/new', 'MessageController@startConversation') -> name('profile.messages.conversation.new');
        Route::get('messages/conversations/list', 'MessageController@listConversations') -> name('profile.messages.conversations.list');
        Route::post('messages/{conversation}/message/new', 'MessageController@newMessage') -> name('profile.messages.message.new');
        Route::get('messages/{conversation}/sendmessage', 'MessageController@newMessage') -> name('profile.messages.send.message'); // get request for redirecting from new conversation
    });
    Route::get('messages/decrypt/key','MessageController@decryptKeyShow')->name('profile.messages.decrypt.show');
    Route::post('messages/decrypt/key','MessageController@decryptKeyPost')->name('profile.messages.decrypt.post');

    /**
     * Notifications
     */

    Route::get('notifications','NotificationController@viewNotifications')->name('profile.notifications');
    Route::post('notifications/delete','NotificationController@deleteNotifications')->name('profile.notifications.delete');

    /**
     * Bitmessage
     */
    Route::get('bitmessage','BitmessageController@show')->name('profile.bitmessage');
    Route::post('bitmessage/send/code','BitmessageController@sendConfirmation')->name('profile.bitmessage.sendcode');
    Route::post('bitmessage/confirm/code','BitmessageController@confirmAddress')->name('profile.bitmessage.confirmcode');


    /**
     * Tickets
     */
    Route::get('tickets/{ticket?}', 'ProfileController@tickets') -> name('profile.tickets');
    Route::post('tickets/new', 'ProfileController@newTicket') -> name('profile.tickets.new');
    Route::post('tickets/{ticket}/newmessage', 'ProfileController@newTicketMessage') -> name('profile.tickets.message.new');


    /**
     * Product clone
     */
    Route::get('product/clone/{product}','ProductController@cloneProductShow')->name('profile.vendor.product.clone.show');
    Route::post('product/clone/{product}','ProductController@cloneProductPost')->name('profile.vendor.product.clone.post');

});